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
    wp_enqueue_style( 'ubo-admin', plugin_dir_url( __FILE__ ) . 'assets/ubo-admin.css', [], '1.2.0' );
    wp_enqueue_script( 'ubo-admin', plugin_dir_url( __FILE__ ) . 'assets/ubo-admin.js', [ 'jquery' ], '1.2.0', true );
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

    $query = sanitize_text_field( wp_unslash( $_POST['query'] ?? '' ) );
    $page  = sanitize_text_field( wp_unslash( $_POST['page'] ?? 'ubo-sku' ) );
    if ( strlen( $query ) < 2 ) wp_send_json_error();

    // Search both stores
    $results = [];
    foreach ( [ 'US', 'India' ] as $site_key ) {
        $products = UBO_Inventory::fetch( $site_key, [ 'search' => $query, 'per_page' => 20 ] );
        if ( ! is_array( $products ) || isset( $products['error'] ) ) continue;
        foreach ( $products as $p ) {
            if ( $p['type'] === 'variable' && ! empty( $p['variations_data'] ) ) {
                foreach ( $p['variations_data'] as $v ) {
                    $sku = $v['sku'] ?: $p['sku'] . '-' . $v['id'];
                    $attrs = implode( ' / ', array_map( fn($a) => $a['option'], $v['attributes'] ?? [] ) );
                    $results[ $sku ] = [ 'sku' => $sku, 'name' => $p['name'] . ( $attrs ? ' — ' . $attrs : '' ) ];
                }
            } else {
                $sku = $p['sku'] ?: 'ID-' . $p['id'];
                $results[ $sku ] = [ 'sku' => $sku, 'name' => $p['name'] ];
            }
        }
    }
    wp_send_json_success( array_values( array_slice( $results, 0, 15 ) ) );
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
