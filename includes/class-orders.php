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
        $cache_key = 'ubo_totals_' . $site_key;
        $cached    = get_transient( $cache_key );
        if ( $cached !== false ) return $cached;

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
        $client->get( 'products', [ 'per_page' => 1, 'stock_status' => 'outofstock' ] );
        $low_stock = $client->get_total_count();

        $revenue = isset( $report[0]['total_sales'] ) ? $report[0]['total_sales'] : '0';

        $result = [
            'order_count'   => $order_count ?: '0',
            'product_count' => $product_count ?: '0',
            'revenue'       => $revenue,
            'low_stock'     => $low_stock ?: '0',
            'recent_orders' => is_array( $orders ) && ! isset( $orders['error'] ) ? $orders : [],
        ];
        set_transient( $cache_key, $result, 5 * MINUTE_IN_SECONDS );
        return $result;
    }

    // Top selling SKUs by quantity sold (uses WC reports/top_sellers)
    public static function get_top_sellers( $site_key, $limit = 5 ) {
        $cache_key = 'ubo_top_sellers_' . $site_key;
        $cached    = get_transient( $cache_key );
        if ( $cached !== false ) return $cached;

        $sites = self::get_sites();
        $site  = $sites[ $site_key ];
        if ( empty( $site['url'] ) || empty( $site['ck'] ) || empty( $site['cs'] ) ) return [];
        $client  = new UBO_API_Client( $site['url'], $site['ck'], $site['cs'] );
        $results = $client->get( 'reports/top_sellers', [ 'period' => 'month', 'per_page' => $limit ] );
        if ( ! is_array( $results ) || isset( $results['error'] ) ) {
            set_transient( $cache_key, [], 5 * MINUTE_IN_SECONDS );
            return [];
        }
        $data = array_slice( $results, 0, $limit );
        // Fetch image for each product
        foreach ( $data as &$item ) {
            if ( ! empty( $item['product_id'] ) ) {
                $p = $client->get( 'products/' . (int) $item['product_id'] );
                $item['image'] = ( is_array( $p ) && ! empty( $p['images'][0]['src'] ) ) ? $p['images'][0]['src'] : '';
            }
        }
        unset( $item );
        set_transient( $cache_key, $data, 5 * MINUTE_IN_SECONDS );
        return $data;
    }

    // Top stocked products (highest stock_quantity)
    public static function get_top_stocked( $site_key, $limit = 5 ) {
        $cache_key = 'ubo_top_stocked_' . $site_key;
        $cached    = get_transient( $cache_key );
        if ( $cached !== false ) return $cached;

        $sites = self::get_sites();
        $site  = $sites[ $site_key ];
        if ( empty( $site['url'] ) || empty( $site['ck'] ) || empty( $site['cs'] ) ) return [];
        $client   = new UBO_API_Client( $site['url'], $site['ck'], $site['cs'] );
        $products = $client->get( 'products', [ 'per_page' => 50, 'orderby' => 'date', 'order' => 'desc', 'stock_status' => 'instock' ] );
        if ( ! is_array( $products ) || isset( $products['error'] ) ) return [];
        usort( $products, fn( $a, $b ) => (int)( $b['stock_quantity'] ?? 0 ) - (int)( $a['stock_quantity'] ?? 0 ) );
        $data = array_slice( $products, 0, $limit );
        set_transient( $cache_key, $data, 5 * MINUTE_IN_SECONDS );
        return $data;
    }

    // Insights: best products, colors, sizes, countries from recent orders
    public static function get_insights( $site_key, $limit = 8 ) {
        $cache_key = 'ubo_insights_' . $site_key;
        $cached    = get_transient( $cache_key );
        if ( $cached !== false ) return $cached;

        $sites = self::get_sites();
        $site  = $sites[ $site_key ];
        if ( empty( $site['url'] ) || empty( $site['ck'] ) || empty( $site['cs'] ) ) return [];

        $client = new UBO_API_Client( $site['url'], $site['ck'], $site['cs'] );
        $all_orders = [];
        foreach ( [ 'completed', 'processing' ] as $status ) {
            $batch = $client->get( 'orders', [
                'per_page' => 50,
                'status'   => $status,
                'after'    => date( 'Y-01-01' ) . 'T00:00:00',
            ] );
            if ( is_array( $batch ) && ! isset( $batch['error'] ) ) {
                $all_orders = array_merge( $all_orders, $batch );
            }
        }
        if ( empty( $all_orders ) ) return [];

        $products  = [];
        $colors    = [];
        $sizes     = [];
        $countries = [];

        foreach ( $all_orders as $order ) {
            $country = $order['billing']['country'] ?? $order['shipping']['country'] ?? '';
            if ( $country ) $countries[ $country ] = ( $countries[ $country ] ?? 0 ) + 1;

            foreach ( $order['line_items'] ?? [] as $li ) {
                $qty  = (int) ( $li['quantity'] ?? 1 );
                $name = $li['name'] ?? '';
                $products[ $name ] = ( $products[ $name ] ?? 0 ) + $qty;
                foreach ( $li['meta_data'] ?? [] as $meta ) {
                    $key = strtolower( $meta['key'] ?? '' );
                    $val = ucfirst( strtolower( $meta['value'] ?? '' ) );
                    if ( ! $val ) continue;
                    if ( in_array( $key, [ 'color', 'colour', 'pa_color', 'pa_colour' ] ) )
                        $colors[ $val ] = ( $colors[ $val ] ?? 0 ) + $qty;
                    if ( in_array( $key, [ 'size', 'pa_size', 'pa_sizes' ] ) )
                        $sizes[ $val ] = ( $sizes[ $val ] ?? 0 ) + $qty;
                }
            }
        }

        arsort( $products ); arsort( $colors ); arsort( $sizes ); arsort( $countries );

        $result = [
            'products'  => array_slice( $products,  0, $limit, true ),
            'colors'    => array_slice( $colors,    0, $limit, true ),
            'sizes'     => array_slice( $sizes,     0, $limit, true ),
            'countries' => array_slice( $countries, 0, $limit, true ),
        ];
        set_transient( $cache_key, $result, 10 * MINUTE_IN_SECONDS );
        return $result;
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
