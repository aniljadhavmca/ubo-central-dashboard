<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UBO_Orders {

    public static function get_sites() {
        return [
            'US'    => [
                'url' => get_option( 'ubo_us_url' ),
                'ck'  => get_option( 'ubo_us_ck' ),
                'cs'  => get_option( 'ubo_us_cs' ),
            ],
            'India' => [
                'url' => get_option( 'ubo_in_url' ),
                'ck'  => get_option( 'ubo_in_ck' ),
                'cs'  => get_option( 'ubo_in_cs' ),
            ],
        ];
    }

    public static function fetch( $site_key, $params = [] ) {
        $sites = self::get_sites();
        $site  = $sites[ $site_key ];

        if ( empty( $site['url'] ) || empty( $site['ck'] ) || empty( $site['cs'] ) ) {
            return [ 'error' => 'API credentials missing for ' . $site_key ];
        }

        $client = new UBO_API_Client( $site['url'], $site['ck'], $site['cs'] );
        return $client->get( 'orders', $params );
    }

    public static function get_totals( $site_key ) {
        $sites = self::get_sites();
        $site  = $sites[ $site_key ];

        if ( empty( $site['url'] ) || empty( $site['ck'] ) || empty( $site['cs'] ) ) {
            return [ 'order_count' => '—', 'product_count' => '—', 'revenue' => '—', 'low_stock' => '—', 'recent_orders' => [] ];
        }

        $client  = new UBO_API_Client( $site['url'], $site['ck'], $site['cs'] );
        $report  = $client->get( 'reports/sales', [ 'period' => 'year' ] );
        $orders  = $client->get( 'orders', [ 'per_page' => 5, 'orderby' => 'date', 'order' => 'desc' ] );
        $client->get( 'products', [ 'per_page' => 1 ] );
        $product_count = $client->get_total_count();
        $client->get( 'orders', [ 'per_page' => 1 ] );
        $order_count = $client->get_total_count();
        $low     = $client->get( 'products', [ 'per_page' => 1, 'stock_status' => 'outofstock' ] );
        $low_stock = $client->get_total_count();

        $revenue = isset( $report[0]['total_sales'] ) ? $report[0]['total_sales'] : '0';

        return [
            'order_count'   => $order_count ?: '0',
            'product_count' => $product_count ?: '0',
            'revenue'       => $revenue,
            'low_stock'     => $low_stock ?: '0',
            'recent_orders' => is_array( $orders ) && ! isset( $orders['error'] ) ? $orders : [],
        ];
    }

    // Top selling SKUs by quantity sold (uses WC reports/top_sellers)
    public static function get_top_sellers( $site_key, $limit = 5 ) {
        $sites = self::get_sites();
        $site  = $sites[ $site_key ];
        if ( empty( $site['url'] ) || empty( $site['ck'] ) || empty( $site['cs'] ) ) return [];
        $client  = new UBO_API_Client( $site['url'], $site['ck'], $site['cs'] );
        $results = $client->get( 'reports/top_sellers', [ 'period' => 'month', 'per_page' => $limit ] );
        return is_array( $results ) && ! isset( $results['error'] ) ? array_slice( $results, 0, $limit ) : [];
    }

    // Top stocked products (highest stock_quantity)
    public static function get_top_stocked( $site_key, $limit = 5 ) {
        $sites = self::get_sites();
        $site  = $sites[ $site_key ];
        if ( empty( $site['url'] ) || empty( $site['ck'] ) || empty( $site['cs'] ) ) return [];
        $client   = new UBO_API_Client( $site['url'], $site['ck'], $site['cs'] );
        $products = $client->get( 'products', [ 'per_page' => 50, 'orderby' => 'date', 'order' => 'desc', 'stock_status' => 'instock' ] );
        if ( ! is_array( $products ) || isset( $products['error'] ) ) return [];
        usort( $products, fn( $a, $b ) => (int)( $b['stock_quantity'] ?? 0 ) - (int)( $a['stock_quantity'] ?? 0 ) );
        return array_slice( $products, 0, $limit );
    }

    // Order status breakdown counts
    public static function get_status_summary( $site_key ) {
        $sites = self::get_sites();
        $site  = $sites[ $site_key ];
        if ( empty( $site['url'] ) || empty( $site['ck'] ) || empty( $site['cs'] ) ) return [];
        $client   = new UBO_API_Client( $site['url'], $site['ck'], $site['cs'] );
        $statuses = [ 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded' ];
        $summary  = [];
        foreach ( $statuses as $s ) {
            $client->get( 'orders', [ 'per_page' => 1, 'status' => $s ] );
            $count = $client->get_total_count();
            if ( $count > 0 ) $summary[ $s ] = $count;
        }
        return $summary;
    }
}
