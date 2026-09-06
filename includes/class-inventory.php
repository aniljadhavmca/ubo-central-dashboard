<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UBO_Inventory {

    public static function fetch( $site_key, $params = [] ) {
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
                $product['variations_data'] = $client->get( 'products/' . $product['id'] . '/variations' );
            }
        }

        return $products;
    }
}
