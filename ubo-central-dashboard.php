<?php
/**
 * Plugin Name: UBO Central Dashboard
 * Description: Pulls WooCommerce orders and inventory from US and India sites.
 * Version: 1.0.0
 * Author: UnitedByOm
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'UBO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UBO_LOW_STOCK_THRESHOLD', 10 );

require_once UBO_PLUGIN_DIR . 'includes/class-api-client.php';
require_once UBO_PLUGIN_DIR . 'includes/class-orders.php';
require_once UBO_PLUGIN_DIR . 'includes/class-inventory.php';
require_once UBO_PLUGIN_DIR . 'includes/class-adjustments.php';
require_once UBO_PLUGIN_DIR . 'includes/class-reserved.php';
require_once UBO_PLUGIN_DIR . 'includes/class-webhook.php';

UBO_Webhook::register();

// Enqueue admin assets
add_action( 'admin_enqueue_scripts', 'ubo_enqueue_assets' );
function ubo_enqueue_assets( $hook ) {
    if ( strpos( $hook, 'ubo' ) === false ) return;
    wp_enqueue_style( 'ubo-admin', plugin_dir_url( __FILE__ ) . 'assets/ubo-admin.css', [], '1.4.0' );
    wp_enqueue_script( 'ubo-admin', plugin_dir_url( __FILE__ ) . 'assets/ubo-admin.js', [ 'jquery' ], '1.4.0', true );
    wp_localize_script( 'ubo-admin', 'uboAdmin', [
        'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
        'nonce'       => wp_create_nonce( 'ubo_ajax' ),
        'currentPage' => sanitize_text_field( $_GET['page'] ?? '' ),
    ] );
}

// AJAX: live search SKUs
add_action( 'wp_ajax_ubo_search_skus', 'ubo_ajax_search_skus' );
function ubo_ajax_search_skus() {
    check_ajax_referer( 'ubo_ajax', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

    $query       = sanitize_text_field( wp_unslash( $_POST['query'] ?? '' ) );
    $site_filter = sanitize_text_field( wp_unslash( $_POST['site'] ?? '' ) );
    if ( strlen( $query ) < 1 ) wp_send_json_error();

    $sites_to_search = ( $site_filter && in_array( $site_filter, [ 'US', 'India' ], true ) )
        ? [ $site_filter ]
        : [ 'US', 'India' ];

    $results = [];
    foreach ( $sites_to_search as $site_key ) {
        // Search by exact SKU param AND by product name — merge both
        $by_sku  = UBO_Inventory::fetch( $site_key, [ 'sku' => $query, 'per_page' => 20 ] );
        $by_name = UBO_Inventory::fetch( $site_key, [ 'search' => $query, 'per_page' => 20 ] );
        $merged  = [];
        foreach ( [ $by_sku, $by_name ] as $batch ) {
            if ( is_array( $batch ) && ! isset( $batch['error'] ) ) {
                foreach ( $batch as $p ) $merged[ $p['id'] ] = $p;
            }
        }
        foreach ( $merged as $p ) {
            if ( $p['type'] === 'variable' && ! empty( $p['variations_data'] ) ) {
                foreach ( $p['variations_data'] as $v ) {
                    $sku = $v['sku'] ?: $p['sku'] . '-' . $v['id'];
                    if ( ! $sku ) continue;
                    $attrs = implode( ' / ', array_map( fn($a) => $a['option'], $v['attributes'] ?? [] ) );
                    $results[ $sku ] = [ 'sku' => $sku, 'name' => $p['name'] . ( $attrs ? ' — ' . $attrs : '' ), 'site' => $site_key ];
                }
            } else {
                $sku = $p['sku'] ?: 'ID-' . $p['id'];
                $results[ $sku ] = [ 'sku' => $sku, 'name' => $p['name'], 'site' => $site_key ];
            }
        }
    }
    wp_send_json_success( array_values( array_slice( $results, 0, 15 ) ) );
}

// AJAX: validate that a SKU exists on a specific site
add_action( 'wp_ajax_ubo_validate_sku', 'ubo_ajax_validate_sku' );
function ubo_ajax_validate_sku() {
    check_ajax_referer( 'ubo_ajax', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

    $sku  = sanitize_text_field( wp_unslash( $_POST['sku'] ?? '' ) );
    $site = sanitize_text_field( wp_unslash( $_POST['site'] ?? '' ) );
    if ( ! $sku || ! in_array( $site, [ 'US', 'India' ], true ) ) {
        wp_send_json_error( [ 'message' => 'Invalid input.' ] );
    }

    $sites  = UBO_Orders::get_sites();
    $s      = $sites[ $site ] ?? null;
    if ( ! $s || empty( $s['url'] ) ) wp_send_json_error( [ 'message' => 'Store not configured.' ] );

    $client   = new UBO_API_Client( $s['url'], $s['ck'], $s['cs'] );
    $products = $client->get( 'products', [ 'sku' => $sku, 'per_page' => 5 ] );

    if ( ! is_array( $products ) || isset( $products['error'] ) || empty( $products ) ) {
        wp_send_json_error( [ 'message' => 'SKU "' . esc_html( $sku ) . '" not found on ' . $site . ' store.' ] );
    }

    // WC sku param can return partial matches — verify exact match
    $found = false;
    foreach ( $products as $p ) {
        if ( $p['sku'] === $sku ) { $found = true; break; }
        if ( $p['type'] === 'variable' ) {
            $vars = $client->get( 'products/' . (int) $p['id'] . '/variations', [ 'per_page' => 100 ] );
            if ( is_array( $vars ) ) {
                foreach ( $vars as $v ) {
                    if ( $v['sku'] === $sku ) { $found = true; break 2; }
                }
            }
        }
    }

    $found
        ? wp_send_json_success( [ 'message' => 'SKU found on ' . $site . ' store.' ] )
        : wp_send_json_error( [ 'message' => 'SKU "' . esc_html( $sku ) . '" not found on ' . $site . ' store.' ] );
}

// AJAX: save reserved stock
add_action( 'wp_ajax_ubo_save_reserved', 'ubo_ajax_save_reserved' );
function ubo_ajax_save_reserved() {
    check_ajax_referer( 'ubo_ajax', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
    $result = UBO_Reserved::handle_form_ajax();
    $result ? wp_send_json_success() : wp_send_json_error();
}

register_activation_hook( __FILE__, 'ubo_create_tables' );
function ubo_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ubo_stock_adjustments (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        sku varchar(100) NOT NULL,
        site varchar(10) NOT NULL,
        adjustment int(11) NOT NULL,
        reason varchar(50) NOT NULL,
        note text,
        user_id bigint(20) NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) {$charset};" );

    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ubo_reserved_stock (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        sku varchar(100) NOT NULL,
        site varchar(10) NOT NULL,
        reserved int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY sku_site (sku, site)
    ) {$charset};" );
}

add_action( 'admin_menu', 'ubo_register_menus' );
function ubo_register_menus() {
    add_menu_page( 'UBO Central Dashboard', 'UBO Dashboard', 'manage_options', 'ubo-dashboard', 'ubo_dashboard_page', 'dashicons-store', 56 );
    add_submenu_page( 'ubo-dashboard', 'Overview',     'Overview',     'manage_options', 'ubo-dashboard',    'ubo_dashboard_page' );
    add_submenu_page( 'ubo-dashboard', 'Unified Orders','Unified Orders','manage_options', 'ubo-orders',       'ubo_orders_page' );
    add_submenu_page( 'ubo-dashboard', 'Inventory',    'Inventory',    'manage_options', 'ubo-inventory',    'ubo_inventory_page' );
    add_submenu_page( 'ubo-dashboard', 'SKU Central',  'SKU Central',  'manage_options', 'ubo-sku',          'ubo_sku_page' );
    add_submenu_page( 'ubo-dashboard', 'Adjustments',  'Adjustments',  'manage_options', 'ubo-adjustments',  'ubo_adjustments_page' );
    add_submenu_page( 'ubo-dashboard', 'Alerts',       'Alerts',       'manage_options', 'ubo-alerts',       'ubo_alerts_page' );
    add_submenu_page( 'ubo-dashboard', 'Settings',     'Settings',     'manage_options', 'ubo-settings',     'ubo_settings_page' );
}

function ubo_dashboard_page()    { require_once UBO_PLUGIN_DIR . 'admin/dashboard-page.php'; }
function ubo_orders_page()       { require_once UBO_PLUGIN_DIR . 'admin/orders-page.php'; }
function ubo_inventory_page()    { require_once UBO_PLUGIN_DIR . 'admin/inventory-page.php'; }
function ubo_sku_page()          { require_once UBO_PLUGIN_DIR . 'admin/sku-central-page.php'; }
function ubo_adjustments_page()  { require_once UBO_PLUGIN_DIR . 'admin/adjustments-page.php'; }
function ubo_alerts_page()       { require_once UBO_PLUGIN_DIR . 'admin/alerts-page.php'; }
function ubo_settings_page()     { require_once UBO_PLUGIN_DIR . 'admin/settings-page.php'; }

add_action( 'admin_init', 'ubo_register_settings' );
function ubo_register_settings() {
    $fields = [ 'ubo_us_url', 'ubo_us_ck', 'ubo_us_cs', 'ubo_us_webhook_secret', 'ubo_in_url', 'ubo_in_ck', 'ubo_in_cs', 'ubo_in_webhook_secret', 'ubo_low_stock_threshold' ];
    foreach ( $fields as $field ) {
        register_setting( 'ubo_settings_group', $field, [ 'sanitize_callback' => 'sanitize_text_field' ] );
    }
}
