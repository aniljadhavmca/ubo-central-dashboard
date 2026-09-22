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

        if ( empty( $secret ) ) return true; // secret not configured — allow (dev mode)

        $body    = $request->get_body();
        $hash    = base64_encode( hash_hmac( 'sha256', $body, $secret, true ) );
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

        // Only reduce stock for active orders
        if ( ! in_array( $status, [ 'processing', 'completed', 'on-hold' ], true ) ) return;

        $line_items = $order['line_items'] ?? [];
        if ( empty( $line_items ) ) return;

        $sites  = UBO_Orders::get_sites();
        $s      = $sites[ $site ] ?? null;
        if ( ! $s || empty( $s['url'] ) ) return;

        $client = new UBO_API_Client( $s['url'], $s['ck'], $s['cs'] );

        foreach ( $line_items as $item ) {
            $product_id   = $item['variation_id'] ?: $item['product_id'];
            $qty_ordered  = (int) $item['quantity'];
            $sku          = $item['sku'] ?? '';

            if ( ! $product_id || ! $qty_ordered ) continue;

            // Fetch current stock
            $endpoint = $item['variation_id']
                ? 'products/' . $item['product_id'] . '/variations/' . $item['variation_id']
                : 'products/' . $item['product_id'];

            $product     = $client->get( $endpoint );
            $current_qty = (int) ( $product['stock_quantity'] ?? 0 );
            $new_qty     = max( 0, $current_qty - $qty_ordered );

            $client->put( $endpoint, [
                'stock_quantity' => $new_qty,
                'manage_stock'   => true,
            ] );

            // Log the auto-reduction
            UBO_Adjustments::log(
                $sku ?: 'product-' . $product_id,
                $site,
                -$qty_ordered,
                'order',
                'Auto-reduced — Order #' . ( $order['number'] ?? $order['id'] ) . ' (' . $status . ')'
            );
        }
    }
}
