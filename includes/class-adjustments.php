<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UBO_Adjustments {

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'ubo_stock_adjustments';
    }

    public static function log( $sku, $site, $adjustment, $reason, $note = '' ) {
        global $wpdb;
        $wpdb->insert( self::table(), [
            'sku'        => sanitize_text_field( $sku ),
            'site'       => sanitize_text_field( $site ),
            'adjustment' => (int) $adjustment,
            'reason'     => sanitize_text_field( $reason ),
            'note'       => sanitize_textarea_field( $note ),
            'user_id'    => get_current_user_id(),
            'created_at' => current_time( 'mysql' ),
        ] );
    }

    public static function get_log( $limit = 100 ) {
        global $wpdb;
        $table = self::table();
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT a.*, u.user_login FROM {$table} a LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID ORDER BY a.created_at DESC LIMIT %d", $limit )
        );
    }

    public static function handle_form() {
        if ( ! isset( $_POST['ubo_adj_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['ubo_adj_nonce'], 'ubo_adjustment' ) ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        $sku        = sanitize_text_field( $_POST['sku'] ?? '' );
        $site       = sanitize_text_field( $_POST['site'] ?? '' );
        $adjustment = (int) ( $_POST['adjustment'] ?? 0 );
        $reason     = sanitize_text_field( $_POST['reason'] ?? '' );
        $note       = sanitize_textarea_field( $_POST['note'] ?? '' );

        if ( ! $sku || ! $site || ! $adjustment || ! $reason ) return;

        // Push stock update to WooCommerce via API
        $sites   = UBO_Orders::get_sites();
        $s       = $sites[ $site ] ?? null;
        if ( ! $s ) return;

        $client   = new UBO_API_Client( $s['url'], $s['ck'], $s['cs'] );
        $products = $client->get( 'products', [ 'sku' => $sku ] );

        if ( ! empty( $products ) && ! isset( $products['error'] ) ) {
            $product    = $products[0];
            $current_qty = (int) ( $product['stock_quantity'] ?? 0 );
            $new_qty     = $current_qty + $adjustment;

            $client->put( 'products/' . $product['id'], [
                'stock_quantity'  => $new_qty,
                'manage_stock'    => true,
            ] );
        }

        self::log( $sku, $site, $adjustment, $reason, $note );
    }
}
