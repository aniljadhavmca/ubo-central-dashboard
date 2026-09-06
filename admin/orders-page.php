<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$site_filter   = sanitize_text_field( $_GET['site'] ?? 'US' );
$status_filter = sanitize_text_field( $_GET['status'] ?? '' );
$date_after    = sanitize_text_field( $_GET['date_after'] ?? '' );

$params = [];
if ( $status_filter ) $params['status']     = $status_filter;
if ( $date_after )    $params['after']       = $date_after . 'T00:00:00';

$orders = UBO_Orders::fetch( $site_filter, $params );
?>
<div class="wrap">
    <h1>Orders — <?php echo esc_html( $site_filter ); ?> Site</h1>

    <form method="get" style="margin-bottom:16px;">
        <input type="hidden" name="page" value="ubo-orders" />
        <select name="site">
            <option value="US"    <?php selected( $site_filter, 'US' ); ?>>US Site</option>
            <option value="India" <?php selected( $site_filter, 'India' ); ?>>India Site</option>
        </select>
        <select name="status">
            <option value="">All Statuses</option>
            <?php foreach ( ['pending','processing','on-hold','completed','cancelled','refunded','failed'] as $s ) : ?>
                <option value="<?php echo $s; ?>" <?php selected( $status_filter, $s ); ?>><?php echo ucfirst($s); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_after" value="<?php echo esc_attr( $date_after ); ?>" placeholder="Orders after date" />
        <button class="button">Filter</button>
    </form>

    <?php if ( isset( $orders['error'] ) ) : ?>
        <div class="notice notice-error"><p><?php echo esc_html( $orders['error'] ); ?></p></div>
    <?php elseif ( empty( $orders ) ) : ?>
        <p>No orders found.</p>
    <?php else : ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Order #</th><th>Date</th><th>Status</th><th>Customer</th>
                    <th>Email</th><th>Items</th><th>Total</th><th>Payment</th><th>Shipping</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $orders as $order ) :
                $billing  = $order['billing'] ?? [];
                $shipping = $order['shipping'] ?? [];
                $items    = array_map( fn($i) => $i['name'] . ' x' . $i['quantity'], $order['line_items'] ?? [] );
                $ship_str = implode( ', ', array_filter( [ $shipping['address_1'] ?? '', $shipping['city'] ?? '', $shipping['country'] ?? '' ] ) );
            ?>
                <tr>
                    <td>#<?php echo esc_html( $order['number'] ); ?></td>
                    <td><?php echo esc_html( date( 'Y-m-d', strtotime( $order['date_created'] ) ) ); ?></td>
                    <td><span style="text-transform:capitalize"><?php echo esc_html( $order['status'] ); ?></span></td>
                    <td><?php echo esc_html( $billing['first_name'] . ' ' . $billing['last_name'] ); ?></td>
                    <td><?php echo esc_html( $billing['email'] ?? '' ); ?></td>
                    <td><?php echo esc_html( implode( ', ', $items ) ); ?></td>
                    <td><?php echo esc_html( $order['currency'] . ' ' . $order['total'] ); ?></td>
                    <td><?php echo esc_html( $order['payment_method_title'] ?? '' ); ?></td>
                    <td><?php echo esc_html( $ship_str ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
