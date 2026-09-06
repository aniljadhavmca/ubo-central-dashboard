<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UBO_API_Client {

    private $base_url;
    private $ck;
    private $cs;
    private $last_total_count = 0;

    public function __construct( $base_url, $ck, $cs ) {
        $this->base_url = trailingslashit( $base_url ) . 'wp-json/wc/v3/';
        $this->ck       = $ck;
        $this->cs       = $cs;
    }

    public function get( $endpoint, $params = [] ) {
        $params['per_page'] = $params['per_page'] ?? 100;
        $url = $this->base_url . $endpoint . '?' . http_build_query( $params );

        $response = wp_remote_get( $url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode( $this->ck . ':' . $this->cs ),
            ],
            'timeout' => 30,
        ]);

        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $this->last_total_count = (int) wp_remote_retrieve_header( $response, 'x-wp-total' );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return $body ?? [];
    }

    public function get_total_count() {
        return $this->last_total_count;
    }
}
