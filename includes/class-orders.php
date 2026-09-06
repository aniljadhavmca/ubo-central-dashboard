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
}
