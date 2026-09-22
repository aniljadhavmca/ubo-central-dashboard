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
            sanitize_text_field( $sku ),
            sanitize_text_field( $site )
        ) );
        return $row ? (int) $row->reserved : 0;
    }

    public static function set( $sku, $site, $qty ) {
        global $wpdb;
        $table    = self::table();
        $sku      = sanitize_text_field( $sku );
        $site     = sanitize_text_field( $site );
        $qty      = (int) $qty;

        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE sku = %s AND site = %s", $sku, $site
        ) );

        if ( $existing ) {
            $wpdb->update( $table, [ 'reserved' => $qty ], [ 'sku' => $sku, 'site' => $site ], [ '%d' ], [ '%s', '%s' ] );
        } else {
            $wpdb->insert( $table, [ 'sku' => $sku, 'site' => $site, 'reserved' => $qty ], [ '%s', '%s', '%d' ] );
        }
    }

    public static function get_all() {
        global $wpdb;
        // Table name is internally generated — safe, no user input
        $rows = $wpdb->get_results( 'SELECT sku, site, reserved FROM ' . self::table() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $map  = [];
        foreach ( $rows as $r ) {
            $map[ $r->sku ][ $r->site ] = (int) $r->reserved;
        }
        return $map;
    }

    public static function handle_form() {
        if ( ! isset( $_POST['ubo_reserved_nonce'] ) ) return null;
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubo_reserved_nonce'] ) ), 'ubo_reserved' ) ) return null;
        if ( ! current_user_can( 'manage_options' ) ) return null;

        $sku  = sanitize_text_field( wp_unslash( $_POST['reserved_sku'] ?? '' ) );
        $site = sanitize_text_field( wp_unslash( $_POST['reserved_site'] ?? '' ) );
        $qty  = (int) ( $_POST['reserved_qty'] ?? 0 );

        if ( ! $sku || ! in_array( $site, [ 'US', 'India' ], true ) ) return null;

        self::set( $sku, $site, $qty );
        return true;
    }
}
