<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$us    = UBO_Orders::get_totals( 'US' );
$india = UBO_Orders::get_totals( 'India' );
$time  = current_time( 'h:i A' ) . ' · ' . current_time( 'M j, Y' );

$status_classes = [
    'completed'  => 'ubo-badge-completed',
    'processing' => 'ubo-badge-processing',
    'on-hold'    => 'ubo-badge-on-hold',
    'pending'    => 'ubo-badge-pending',
    'cancelled'  => 'ubo-badge-cancelled',
    'refunded'   => 'ubo-badge-refunded',
    'failed'     => 'ubo-badge-failed',
];
?>
<div class="ubo-wrap">

    <div class="ubo-page-header">
        <div>
            <h1>🕉️ UBO Central Dashboard</h1>
            <div class="ubo-subtitle">Unified view across US &amp; India stores</div>
        </div>
        <div class="ubo-sync-badge">🕐 <?php echo esc_html( $time ); ?></div>
    </div>

    <!-- Row 1: Orders + Products -->
    <div class="ubo-stat-grid">
        <a class="ubo-stat-card ubo-stat-blue" href="<?php echo esc_url( admin_url('admin.php?page=ubo-orders&site=US') ); ?>">
            <span class="ubo-stat-icon">🇺🇸</span>
            <div class="ubo-stat-label">US Orders</div>
            <div class="ubo-stat-value"><?php echo esc_html( $us['order_count'] ); ?></div>
            <div class="ubo-stat-sub">Total orders this year</div>
            <div class="ubo-stat-bar"></div>
        </a>
        <a class="ubo-stat-card ubo-stat-orange" href="<?php echo esc_url( admin_url('admin.php?page=ubo-orders&site=India') ); ?>">
            <span class="ubo-stat-icon">🇮🇳</span>
            <div class="ubo-stat-label">India Orders</div>
            <div class="ubo-stat-value"><?php echo esc_html( $india['order_count'] ); ?></div>
            <div class="ubo-stat-sub">Total orders this year</div>
            <div class="ubo-stat-bar"></div>
        </a>
        <a class="ubo-stat-card ubo-stat-green" href="<?php echo esc_url( admin_url('admin.php?page=ubo-inventory&site=US') ); ?>">
            <span class="ubo-stat-icon">📦</span>
            <div class="ubo-stat-label">US Products</div>
            <div class="ubo-stat-value"><?php echo esc_html( $us['product_count'] ); ?></div>
            <div class="ubo-stat-sub">Active products</div>
            <div class="ubo-stat-bar"></div>
        </a>
        <a class="ubo-stat-card ubo-stat-green" href="<?php echo esc_url( admin_url('admin.php?page=ubo-inventory&site=India') ); ?>">
            <span class="ubo-stat-icon">📦</span>
            <div class="ubo-stat-label">India Products</div>
            <div class="ubo-stat-value"><?php echo esc_html( $india['product_count'] ); ?></div>
            <div class="ubo-stat-sub">Active products</div>
            <div class="ubo-stat-bar"></div>
        </a>
    </div>

    <!-- Row 2: Revenue + Alerts -->
    <div class="ubo-stat-grid">
        <div class="ubo-stat-card ubo-stat-green">
            <span class="ubo-stat-icon">💵</span>
            <div class="ubo-stat-label">US Revenue</div>
            <div class="ubo-stat-value" style="font-size:22px;">$<?php echo esc_html( number_format( (float) $us['revenue'], 2 ) ); ?></div>
            <div class="ubo-stat-sub">This year</div>
            <div class="ubo-stat-bar"></div>
        </div>
        <div class="ubo-stat-card ubo-stat-green">
            <span class="ubo-stat-icon">💵</span>
            <div class="ubo-stat-label">India Revenue</div>
            <div class="ubo-stat-value" style="font-size:22px;">₹<?php echo esc_html( number_format( (float) $india['revenue'], 2 ) ); ?></div>
            <div class="ubo-stat-sub">This year</div>
            <div class="ubo-stat-bar"></div>
        </div>
        <a class="ubo-stat-card ubo-stat-red" href="<?php echo esc_url( admin_url('admin.php?page=ubo-alerts') ); ?>">
            <span class="ubo-stat-icon">🔴</span>
            <div class="ubo-stat-label">US Out of Stock</div>
            <div class="ubo-stat-value"><?php echo esc_html( $us['low_stock'] ); ?></div>
            <div class="ubo-stat-sub">Needs restocking</div>
            <div class="ubo-stat-bar"></div>
        </a>
        <a class="ubo-stat-card ubo-stat-red" href="<?php echo esc_url( admin_url('admin.php?page=ubo-alerts') ); ?>">
            <span class="ubo-stat-icon">🔴</span>
            <div class="ubo-stat-label">India Out of Stock</div>
            <div class="ubo-stat-value"><?php echo esc_html( $india['low_stock'] ); ?></div>
            <div class="ubo-stat-sub">Needs restocking</div>
            <div class="ubo-stat-bar"></div>
        </a>
    </div>

    <!-- Recent Orders -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <?php foreach ( [ 'US' => [ $us, '🇺🇸', '$' ], 'India' => [ $india, '🇮🇳', '₹' ] ] as $label => [ $data, $flag, $currency ] ) :
            $page_url = admin_url( 'admin.php?page=ubo-orders&site=' . $label );
        ?>
        <div class="ubo-panel">
            <div class="ubo-panel-header">
                <h3><?php echo $flag; ?> Recent Orders — <?php echo esc_html( $label ); ?></h3>
                <a href="<?php echo esc_url( $page_url ); ?>">View all →</a>
            </div>
            <div class="ubo-panel-body">
                <?php if ( empty( $data['recent_orders'] ) ) : ?>
                    <div class="ubo-empty-state"><div class="ubo-empty-icon">📭</div><p>No orders found or API not configured.</p></div>
                <?php else : ?>
                    <table class="ubo-table">
                        <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Total</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ( $data['recent_orders'] as $order ) :
                            $billing = $order['billing'] ?? [];
                            $name    = trim( ( $billing['first_name'] ?? '' ) . ' ' . ( $billing['last_name'] ?? '' ) ) ?: 'Guest';
                            $status  = $order['status'] ?? 'unknown';
                            $cls     = $status_classes[ $status ] ?? 'ubo-badge-default';
                            $date    = date( 'M j', strtotime( $order['date_created'] ) );
                        ?>
                            <tr>
                                <td><strong>#<?php echo esc_html( $order['number'] ); ?></strong></td>
                                <td><?php echo esc_html( $name ); ?></td>
                                <td><span class="ubo-badge <?php echo esc_attr( $cls ); ?>"><?php echo esc_html( $status ); ?></span></td>
                                <td><?php echo esc_html( $currency . $order['total'] ); ?></td>
                                <td style="white-space:nowrap;"><?php echo esc_html( $date ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>




