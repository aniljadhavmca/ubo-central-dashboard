<?php
/**
 * Plugin Name: UBO Central Dashboard
 * Description: Pulls WooCommerce orders and inventory from US and India sites.
 * Version: 1.0.0
 * Author: UnitedByOm
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'UBO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once UBO_PLUGIN_DIR . 'includes/class-api-client.php';
require_once UBO_PLUGIN_DIR . 'includes/class-orders.php';
require_once UBO_PLUGIN_DIR . 'includes/class-inventory.php';

add_action( 'admin_menu', 'ubo_register_menus' );
function ubo_register_menus() {
    add_menu_page( 'UBO Central Dashboard', 'UBO Dashboard', 'manage_options', 'ubo-dashboard', 'ubo_dashboard_page', 'dashicons-store', 56 );
    add_submenu_page( 'ubo-dashboard', 'Overview', 'Overview', 'manage_options', 'ubo-dashboard', 'ubo_dashboard_page' );
    add_submenu_page( 'ubo-dashboard', 'Orders', 'Orders', 'manage_options', 'ubo-orders', 'ubo_orders_page' );
    add_submenu_page( 'ubo-dashboard', 'Inventory', 'Inventory', 'manage_options', 'ubo-inventory', 'ubo_inventory_page' );
    add_submenu_page( 'ubo-dashboard', 'Settings', 'Settings', 'manage_options', 'ubo-settings', 'ubo_settings_page' );
}

function ubo_dashboard_page() {
    require_once UBO_PLUGIN_DIR . 'admin/dashboard-page.php';
}

function ubo_orders_page() {
    require_once UBO_PLUGIN_DIR . 'admin/orders-page.php';
}

function ubo_inventory_page() {
    require_once UBO_PLUGIN_DIR . 'admin/inventory-page.php';
}

function ubo_settings_page() {
    require_once UBO_PLUGIN_DIR . 'admin/settings-page.php';
}

add_action( 'admin_init', 'ubo_register_settings' );
function ubo_register_settings() {
    $fields = [ 'ubo_us_url', 'ubo_us_ck', 'ubo_us_cs', 'ubo_in_url', 'ubo_in_ck', 'ubo_in_cs' ];
    foreach ( $fields as $field ) {
        register_setting( 'ubo_settings_group', $field, [ 'sanitize_callback' => 'sanitize_text_field' ] );
    }
}
