<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UBO_Inventory {

    public static function fetch( $site_key, $params = [] ) {
        // Only cache full 100-product fetches (no filters) — filtered/search calls skip cache
        $is_cacheable = empty( $params['search'] ) && empty( $params['sku'] ) && empty( $params['stock_status'] )
                        && ( $params['per_page'] ?? 0 ) >= 100;
        $cache_key    = 'ubo_inventory_' . $site_key;

        if ( $is_cacheable ) {
            $cached = get_transient( $cache_key );
            if ( $cached !== false ) return $cached;
        }

        $sites = UBO_Orders::get_sites();
        $site  = $sites[ $site_key ];

        if ( empty( $site['url'] ) || empty( $site['ck'] ) || empty( $site['cs'] ) ) {
            return [ 'error' => 'API credentials missing for ' . $site_key ];
        }

        $client   = new UBO_API_Client( $site['url'], $site['ck'], $site['cs'] );
        $products = $client->get( 'products', $params );

        if ( isset( $products['error'] ) ) return $products;

        // Fetch variations for variable products
        foreach ( $products as &$product ) {
            if ( $product['type'] === 'variable' ) {
                $product['variations_data'] = $client->get(
                    'products/' . (int) $product['id'] . '/variations',
                    [ 'per_page' => 100 ]
                );
            }
        }
        unset( $product );

        if ( $is_cacheable ) {
            set_transient( $cache_key, $products, 5 * MINUTE_IN_SECONDS );
        }

        return $products;
    }

    public static function bust_cache( $site_key ) {
        delete_transient( 'ubo_inventory_' . $site_key );
    }
}
