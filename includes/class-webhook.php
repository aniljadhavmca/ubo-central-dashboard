<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class UBO_Webhook {

    public static function register() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes() {
        register_rest_route( 'ubo/v1', '/webhook/(?P<site>US|India)', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle' ],
            'permission_callback' => [ __CLASS__, 'verify' ],
        ] );
    }

    // Verify WooCommerce webhook signature
    public static function verify( WP_REST_Request $request ) {
        $site      = $request->get_param( 'site' );
        $secret    = get_option( $site === 'US' ? 'ubo_us_webhook_secret' : 'ubo_in_webhook_secret' );
        $signature = $request->get_header( 'x-wc-webhook-signature' );

        if ( empty( $secret ) || empty( $signature ) ) {
            return new WP_Error( 'ubo_webhook_auth', 'Webhook secret not configured or signature missing.', [ 'status' => 401 ] );
        }

        $body = $request->get_body();
        $hash = base64_encode( hash_hmac( 'sha256', $body, $secret, true ) );
        return hash_equals( $hash, (string) $signature );
    }

    public static function handle( WP_REST_Request $request ) {
        $site    = $request->get_param( 'site' );
        $payload = $request->get_json_params();
        $topic   = $request->get_header( 'x-wc-webhook-topic' );

        if ( ! $payload || ! $topic ) {
            return new WP_REST_Response( [ 'status' => 'ignored' ], 200 );
        }

        // Handle order created / updated
        if ( in_array( $topic, [ 'order.created', 'order.updated' ], true ) ) {
            self::process_order( $site, $payload );
        }

        return new WP_REST_Response( [ 'status' => 'ok' ], 200 );
    }

    private static function process_order( $site, $order ) {
        $status = $order['status'] ?? '';
        if ( ! in_array( $status, [ 'processing', 'completed', 'on-hold' ], true ) ) return;

        $line_items = $order['line_items'] ?? [];
        if ( empty( $line_items ) ) return;

        // WooCommerce already reduces stock_quantity automatically on order.
        // We only log the event here for the adjustment history.
        foreach ( $line_items as $item ) {
            $qty_ordered = (int) ( $item['quantity'] ?? 0 );
            $sku         = sanitize_text_field( $item['sku'] ?? '' );
            $product_id  = (int) ( $item['variation_id'] ?: $item['product_id'] ?? 0 );
            if ( ! $qty_ordered ) continue;

            UBO_Adjustments::log(
                $sku ?: 'product-' . $product_id,
                $site,
                -$qty_ordered,
                'order',
                'Auto — Order #' . sanitize_text_field( (string)( $order['number'] ?? $order['id'] ?? '' ) ) . ' (' . $status . ')'
            );
        }

        // Bust dashboard transients so next page load shows fresh data
        delete_transient( 'ubo_totals_' . $site );
        delete_transient( 'ubo_top_sellers_' . $site );
    }
}
