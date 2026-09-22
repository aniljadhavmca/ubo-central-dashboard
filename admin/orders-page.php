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
        foreach ( $us_orders as &$o ) { $o['_site'] = 'US'; $o['_currency'] = '$'; }
        $all_orders = array_merge( $all_orders, $us_orders );
    }
}
if ( $site_filter === 'all' || $site_filter === 'India' ) {
    $in_orders = UBO_Orders::fetch( 'India', $params );
    if ( is_array( $in_orders ) && ! isset( $in_orders['error'] ) ) {
        foreach ( $in_orders as &$o ) { $o['_site'] = 'India'; $o['_currency'] = '₹'; }
        $all_orders = array_merge( $all_orders, $in_orders );
    }
}
usort( $all_orders, fn( $a, $b ) => strtotime( $b['date_created'] ) - strtotime( $a['date_created'] ) );

$status_classes = [
    'completed'  => 'ubo-badge-completed',  'processing' => 'ubo-badge-processing',
    'on-hold'    => 'ubo-badge-on-hold',     'pending'    => 'ubo-badge-pending',
    'cancelled'  => 'ubo-badge-cancelled',   'refunded'   => 'ubo-badge-refunded',
    'failed'     => 'ubo-badge-failed',
];
$statuses = [ 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' ];
?>
<div class="ubo-wrap">

    <div class="ubo-page-header">
        <div>
            <h1>🗂️ Unified Order Feed</h1>
            <div class="ubo-subtitle">All orders from US &amp; India stores in one view</div>
        </div>
    </div>

    <form method="get" id="ubo-orders-form">
        <input type="hidden" name="page" value="ubo-orders" />
        <div class="ubo-filter-bar">
            <label>Store</label>
            <select name="site" class="ubo-auto-filter">
                <option value="all"   <?php selected( $site_filter, 'all' ); ?>>All Stores</option>
                <option value="US"    <?php selected( $site_filter, 'US' ); ?>>🇺🇸 United States</option>
                <option value="India" <?php selected( $site_filter, 'India' ); ?>>🇮🇳 India</option>
            </select>

            <label>Status</label>
            <select name="status" class="ubo-auto-filter">
                <option value="">All Statuses</option>
                <?php foreach ( $statuses as $s ) : ?>
                    <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status_filter, $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
                <?php endforeach; ?>
            </select>

            <label>From date</label>
            <input type="date" name="date_after" value="<?php echo esc_attr( $date_after ); ?>" class="ubo-auto-filter" />

            <?php if ( $status_filter || $date_after || $site_filter !== 'all' ) : ?>
                <a href="<?php echo esc_url( admin_url('admin.php?page=ubo-orders') ); ?>" class="ubo-btn ubo-btn-secondary">✕ Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="ubo-result-bar">
        <span><strong><?php echo count( $all_orders ); ?></strong> orders found</span>
    </div>

    <?php if ( empty( $all_orders ) ) : ?>
        <div class="ubo-panel"><div class="ubo-empty-state"><div class="ubo-empty-icon">📭</div><p>No orders found. Try adjusting your filters or check API credentials in Settings.</p></div></div>
    <?php else : ?>
        <div class="ubo-table-wrap">
            <table class="ubo-table">
                <thead>
                    <tr>
                        <th>Store</th><th>Order #</th><th>Date &amp; Time</th><th>Customer</th>
                        <th>Items</th><th>Status</th><th>Total</th><th>Ship To</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $all_orders as $order ) :
                    $billing  = $order['billing'] ?? [];
                    $shipping = $order['shipping'] ?? [];
                    $name     = trim( ( $billing['first_name'] ?? '' ) . ' ' . ( $billing['last_name'] ?? '' ) ) ?: 'Guest';
                    $status   = $order['status'] ?? 'unknown';
                    $cls      = $status_classes[ $status ] ?? 'ubo-badge-default';
                    $date     = date( 'M j, Y · g:i A', strtotime( $order['date_created'] ) );
                    $ship_str = implode( ', ', array_filter( [ $shipping['city'] ?? '', $shipping['country'] ?? '' ] ) );
                    $items    = [];
                    foreach ( $order['line_items'] ?? [] as $item ) {
                        $meta = [];
                        foreach ( $item['meta_data'] ?? [] as $m ) {
                            if ( ! str_starts_with( $m['key'], '_' ) ) $meta[] = $m['value'];
                        }
                        $items[] = $item['name'] . ( $meta ? ' (' . implode( ', ', $meta ) . ')' : '' ) . ' ×' . $item['quantity'];
                    }
                    $store_cls = $order['_site'] === 'US' ? 'ubo-store-us' : 'ubo-store-india';
                    $flag      = $order['_site'] === 'US' ? '🇺🇸' : '🇮🇳';
                ?>
                    <tr>
                        <td><span class="ubo-store-tag <?php echo esc_attr( $store_cls ); ?>"><?php echo $flag . ' ' . esc_html( $order['_site'] ); ?></span></td>
                        <td><strong>#<?php echo esc_html( $order['number'] ); ?></strong></td>
                        <td style="white-space:nowrap;font-size:12px;color:#64748b;"><?php echo esc_html( $date ); ?></td>
                        <td>
                            <?php echo esc_html( $name ); ?>
                            <?php if ( ! empty( $billing['email'] ) ) : ?>
                                <br><span style="font-size:11px;color:#94a3b8;"><?php echo esc_html( $billing['email'] ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;max-width:220px;"><?php echo esc_html( implode( ' · ', $items ) ); ?></td>
                        <td><span class="ubo-badge <?php echo esc_attr( $cls ); ?>"><?php echo esc_html( $status ); ?></span></td>
                        <td style="font-weight:600;"><?php echo esc_html( $order['_currency'] . $order['total'] ); ?></td>
                        <td style="font-size:12px;color:#64748b;"><?php echo esc_html( $ship_str ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
