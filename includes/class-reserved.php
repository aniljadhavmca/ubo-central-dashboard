<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UBO_Reserved {

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'ubo_reserved_stock';
    }

    public static function get( $sku, $site ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            'SELECT reserved FROM ' . self::table() . ' WHERE sku = %s AND site = %s',
            $sku, $site
        ) );
        return $row ? (int) $row->reserved : 0;
    }

    public static function set( $sku, $site, $qty ) {
        global $wpdb;
        $table    = self::table();
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE sku = %s AND site = %s", $sku, $site
        ) );
        if ( $existing ) {
            $wpdb->update( $table, [ 'reserved' => (int) $qty ], [ 'sku' => $sku, 'site' => $site ] );
        } else {
            $wpdb->insert( $table, [ 'sku' => $sku, 'site' => $site, 'reserved' => (int) $qty ] );
        }
    }

    public static function get_all() {
        global $wpdb;
        $rows = $wpdb->get_results( 'SELECT sku, site, reserved FROM ' . self::table() );
        $map  = [];
        foreach ( $rows as $r ) {
            $map[ $r->sku ][ $r->site ] = (int) $r->reserved;
        }
        return $map;
    }

    public static function handle_form() {
        if ( ! isset( $_POST['ubo_reserved_nonce'] ) ) return null;
        if ( ! wp_verify_nonce( $_POST['ubo_reserved_nonce'], 'ubo_reserved' ) ) return null;
        if ( ! current_user_can( 'manage_options' ) ) return null;

        $sku  = sanitize_text_field( $_POST['reserved_sku'] ?? '' );
        $site = sanitize_text_field( $_POST['reserved_site'] ?? '' );
        $qty  = (int) ( $_POST['reserved_qty'] ?? 0 );

        if ( ! $sku || ! $site ) return null;

        self::set( $sku, $site, $qty );
        return true;
    }
}
