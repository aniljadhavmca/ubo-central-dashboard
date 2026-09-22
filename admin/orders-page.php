<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$status_filter = sanitize_text_field( $_GET['status'] ?? '' );
$date_after    = sanitize_text_field( $_GET['date_after'] ?? '' );
$site_filter   = sanitize_text_field( $_GET['site'] ?? 'all' );

$params = [ 'per_page' => 50, 'orderby' => 'date', 'order' => 'desc' ];
if ( $status_filter ) $params['status'] = $status_filter;
if ( $date_after )    $params['after']  = $date_after . 'T00:00:00';

$all_orders = [];

if ( $site_filter === 'all' || $site_filter === 'US' ) {
    $us_orders = UBO_Orders::fetch( 'US', $params );
    if ( is_array( $us_orders ) && ! isset( $us_orders['error'] ) ) {
        foreach ( $us_orders as &$o ) { $o['_site'] = 'US'; $o['_flag'] = '🇺🇸'; $o['_currency'] = '$'; }
        $all_orders = array_merge( $all_orders, $us_orders );
    }
}

if ( $site_filter === 'all' || $site_filter === 'India' ) {
    $in_orders = UBO_Orders::fetch( 'India', $params );
    if ( is_array( $in_orders ) && ! isset( $in_orders['error'] ) ) {
        foreach ( $in_orders as &$o ) { $o['_site'] = 'India'; $o['_flag'] = '🇮🇳'; $o['_currency'] = '₹'; }
        $all_orders = array_merge( $all_orders, $in_orders );
    }
}

// Sort merged feed by date descending
usort( $all_orders, fn( $a, $b ) => strtotime( $b['date_created'] ) - strtotime( $a['date_created'] ) );

$status_colors = [
    'completed'  => '#22c55e', 'processing' => '#f59e0b', 'on-hold'   => '#6366f1',
    'pending'    => '#94a3b8', 'cancelled'  => '#ef4444', 'refunded'  => '#f97316', 'failed' => '#dc2626',
];
?>
<style>
.ubo-wrap { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 24px 20px; max-width: 1400px; }
.ubo-wrap h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 20px; }
.ubo-filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
.ubo-filters select, .ubo-filters input { padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; }
.ubo-filters .button { padding: 6px 16px; }
.ubo-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; color: #fff; text-transform: capitalize; }
.ubo-table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
.ubo-table th { background: #1e293b; color: #fff; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; }
.ubo-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: top; color: #334155; }
.ubo-table tr:last-child td { border-bottom: none; }
.ubo-table tr:hover td { background: #f8fafc; }
.ubo-site-tag { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 4px; }
.ubo-site-us { background: #dbeafe; color: #1d4ed8; }
.ubo-site-in { background: #ffedd5; color: #c2410c; }
.ubo-count { font-size: 12px; color: #94a3b8; margin-bottom: 12px; }
</style>

<div class="ubo-wrap">
    <h1>🗂️ Unified Order Feed</h1>

    <form method="get">
        <input type="hidden" name="page" value="ubo-orders" />
        <div class="ubo-filters">
            <select name="site">
                <option value="all"  <?php selected( $site_filter, 'all' ); ?>>All Stores</option>
                <option value="US"   <?php selected( $site_filter, 'US' ); ?>>🇺🇸 US Only</option>
                <option value="India"<?php selected( $site_filter, 'India' ); ?>>🇮🇳 India Only</option>
            </select>
            <select name="status">
                <option value="">All Statuses</option>
                <?php foreach ( ['pending','processing','on-hold','completed','cancelled','refunded','failed'] as $s ) : ?>
                    <option value="<?php echo $s; ?>" <?php selected( $status_filter, $s ); ?>><?php echo ucfirst( $s ); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="date_after" value="<?php echo esc_attr( $date_after ); ?>" />
            <button class="button button-primary">Filter</button>
        </div>
    </form>

    <p class="ubo-count"><?php echo count( $all_orders ); ?> orders found</p>

    <?php if ( empty( $all_orders ) ) : ?>
        <p style="color:#94a3b8;">No orders found. Check API credentials in Settings.</p>
    <?php else : ?>
        <table class="ubo-table">
            <thead>
                <tr>
                    <th>Store</th><th>Order #</th><th>Date</th><th>Customer</th>
                    <th>Items</th><th>Status</th><th>Total</th><th>Shipping</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $all_orders as $order ) :
                $billing  = $order['billing'] ?? [];
                $shipping = $order['shipping'] ?? [];
                $name     = trim( ( $billing['first_name'] ?? '' ) . ' ' . ( $billing['last_name'] ?? '' ) ) ?: 'Guest';
                $status   = $order['status'] ?? 'unknown';
                $color    = $status_colors[ $status ] ?? '#94a3b8';
                $date     = date( 'M j, Y g:i A', strtotime( $order['date_created'] ) );
                $ship_str = implode( ', ', array_filter( [ $shipping['city'] ?? '', $shipping['country'] ?? '' ] ) );
                $items    = [];
                foreach ( $order['line_items'] ?? [] as $item ) {
                    $meta = [];
                    foreach ( $item['meta_data'] ?? [] as $m ) {
                        if ( ! str_starts_with( $m['key'], '_' ) ) $meta[] = $m['value'];
                    }
                    $items[] = $item['name'] . ( $meta ? ' (' . implode( ', ', $meta ) . ')' : '' ) . ' ×' . $item['quantity'];
                }
                $site_class = $order['_site'] === 'US' ? 'ubo-site-us' : 'ubo-site-in';
            ?>
                <tr>
                    <td><span class="ubo-site-tag <?php echo $site_class; ?>"><?php echo $order['_flag'] . ' ' . esc_html( $order['_site'] ); ?></span></td>
                    <td><strong>#<?php echo esc_html( $order['number'] ); ?></strong></td>
                    <td style="white-space:nowrap;font-size:12px;"><?php echo esc_html( $date ); ?></td>
                    <td><?php echo esc_html( $name ); ?><br><span style="font-size:11px;color:#94a3b8;"><?php echo esc_html( $billing['email'] ?? '' ); ?></span></td>
                    <td style="font-size:12px;"><?php echo esc_html( implode( ' | ', $items ) ); ?></td>
                    <td><span class="ubo-badge" style="background:<?php echo $color; ?>"><?php echo esc_html( $status ); ?></span></td>
                    <td><?php echo esc_html( $order['_currency'] . $order['total'] ); ?></td>
                    <td style="font-size:12px;"><?php echo esc_html( $ship_str ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
