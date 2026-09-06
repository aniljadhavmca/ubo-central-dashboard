<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$us    = UBO_Orders::get_totals( 'US' );
$india = UBO_Orders::get_totals( 'India' );
$time  = current_time( 'h:i A' ) . ' · ' . current_time( 'M j, Y' );

$status_colors = [
    'completed'  => '#22c55e',
    'processing' => '#f59e0b',
    'on-hold'    => '#6366f1',
    'pending'    => '#94a3b8',
    'cancelled'  => '#ef4444',
    'refunded'   => '#f97316',
    'failed'     => '#dc2626',
];
?>
<style>
.ubo-wrap { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 24px 20px; max-width: 1400px; }
.ubo-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
.ubo-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; }
.ubo-header .ubo-sync { font-size: 12px; color: #94a3b8; background: #f1f5f9; padding: 6px 12px; border-radius: 20px; }

/* Stat Cards */
.ubo-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.ubo-card { background: #fff; border-radius: 14px; padding: 22px 24px; box-shadow: 0 1px 4px rgba(0,0,0,.07); border: 1px solid #f1f5f9; position: relative; overflow: hidden; text-decoration: none; display: block; transition: box-shadow .2s; }
.ubo-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); }
.ubo-card .ubo-card-icon { font-size: 28px; margin-bottom: 10px; display: block; }
.ubo-card .ubo-card-label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin-bottom: 6px; }
.ubo-card .ubo-card-value { font-size: 36px; font-weight: 700; color: #1e293b; line-height: 1; }
.ubo-card .ubo-card-sub { font-size: 12px; color: #94a3b8; margin-top: 6px; }
.ubo-card-accent { position: absolute; top: 0; right: 0; width: 80px; height: 80px; border-radius: 0 14px 0 80px; opacity: .08; }
.ubo-card.us   .ubo-card-accent { background: #3b82f6; }
.ubo-card.in   .ubo-card-accent { background: #f97316; }
.ubo-card.rev  .ubo-card-accent { background: #22c55e; }
.ubo-card.low  .ubo-card-accent { background: #ef4444; }

/* Revenue + Low Stock row */
.ubo-cards-2 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }

/* Recent Orders */
.ubo-recent { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.ubo-panel { background: #fff; border-radius: 14px; padding: 20px 24px; box-shadow: 0 1px 4px rgba(0,0,0,.07); border: 1px solid #f1f5f9; }
.ubo-panel h3 { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0 0 16px; display: flex; align-items: center; gap: 8px; }
.ubo-panel h3 a { margin-left: auto; font-size: 12px; font-weight: 500; color: #6366f1; text-decoration: none; }
.ubo-panel h3 a:hover { text-decoration: underline; }
.ubo-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.ubo-table th { text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; padding: 0 8px 10px; border-bottom: 1px solid #f1f5f9; }
.ubo-table td { padding: 10px 8px; border-bottom: 1px solid #f8fafc; color: #334155; vertical-align: middle; }
.ubo-table tr:last-child td { border-bottom: none; }
.ubo-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; color: #fff; text-transform: capitalize; }
.ubo-empty { color: #94a3b8; font-size: 13px; text-align: center; padding: 20px 0; }
</style>

<div class="ubo-wrap">

    <div class="ubo-header">
        <h1>🕉️ UBO Central Dashboard</h1>
        <span class="ubo-sync">🕐 Last loaded: <?php echo esc_html( $time ); ?></span>
    </div>

    <!-- Row 1: Orders + Products -->
    <div class="ubo-cards">
        <a class="ubo-card us" href="<?php echo admin_url('admin.php?page=ubo-orders&site=US'); ?>">
            <div class="ubo-card-accent"></div>
            <span class="ubo-card-icon">🇺🇸</span>
            <div class="ubo-card-label">US Orders</div>
            <div class="ubo-card-value"><?php echo esc_html( $us['order_count'] ); ?></div>
            <div class="ubo-card-sub">Total orders this year</div>
        </a>
        <a class="ubo-card in" href="<?php echo admin_url('admin.php?page=ubo-orders&site=India'); ?>">
            <div class="ubo-card-accent"></div>
            <span class="ubo-card-icon">🇮🇳</span>
            <div class="ubo-card-label">India Orders</div>
            <div class="ubo-card-value"><?php echo esc_html( $india['order_count'] ); ?></div>
            <div class="ubo-card-sub">Total orders this year</div>
        </a>
        <a class="ubo-card us" href="<?php echo admin_url('admin.php?page=ubo-inventory&site=US'); ?>">
            <div class="ubo-card-accent"></div>
            <span class="ubo-card-icon">📦</span>
            <div class="ubo-card-label">US Products</div>
            <div class="ubo-card-value"><?php echo esc_html( $us['product_count'] ); ?></div>
            <div class="ubo-card-sub">Total active products</div>
        </a>
        <a class="ubo-card in" href="<?php echo admin_url('admin.php?page=ubo-inventory&site=India'); ?>">
            <div class="ubo-card-accent"></div>
            <span class="ubo-card-icon">📦</span>
            <div class="ubo-card-label">India Products</div>
            <div class="ubo-card-value"><?php echo esc_html( $india['product_count'] ); ?></div>
            <div class="ubo-card-sub">Total active products</div>
        </a>
    </div>

    <!-- Row 2: Revenue + Low Stock -->
    <div class="ubo-cards-2">
        <div class="ubo-card rev">
            <div class="ubo-card-accent"></div>
            <span class="ubo-card-icon">💰</span>
            <div class="ubo-card-label">US Revenue</div>
            <div class="ubo-card-value" style="font-size:28px;">$<?php echo esc_html( number_format( (float) $us['revenue'], 2 ) ); ?></div>
            <div class="ubo-card-sub">This year</div>
        </div>
        <div class="ubo-card rev">
            <div class="ubo-card-accent"></div>
            <span class="ubo-card-icon">💰</span>
            <div class="ubo-card-label">India Revenue</div>
            <div class="ubo-card-value" style="font-size:28px;">₹<?php echo esc_html( number_format( (float) $india['revenue'], 2 ) ); ?></div>
            <div class="ubo-card-sub">This year</div>
        </div>
        <a class="ubo-card low" href="<?php echo admin_url('admin.php?page=ubo-inventory&site=US&stock_status=outofstock'); ?>">
            <div class="ubo-card-accent"></div>
            <span class="ubo-card-icon">🔴</span>
            <div class="ubo-card-label">US Out of Stock</div>
            <div class="ubo-card-value"><?php echo esc_html( $us['low_stock'] ); ?></div>
            <div class="ubo-card-sub">Products need restocking</div>
        </a>
        <a class="ubo-card low" href="<?php echo admin_url('admin.php?page=ubo-inventory&site=India&stock_status=outofstock'); ?>">
            <div class="ubo-card-accent"></div>
            <span class="ubo-card-icon">🔴</span>
            <div class="ubo-card-label">India Out of Stock</div>
            <div class="ubo-card-value"><?php echo esc_html( $india['low_stock'] ); ?></div>
            <div class="ubo-card-sub">Products need restocking</div>
        </a>
    </div>

    <!-- Recent Orders -->
    <div class="ubo-recent">
        <?php foreach ( [ 'US' => $us, 'India' => $india ] as $label => $data ) :
            $flag     = $label === 'US' ? '🇺🇸' : '🇮🇳';
            $currency = $label === 'US' ? '$' : '₹';
            $page_url = admin_url( 'admin.php?page=ubo-orders&site=' . $label );
        ?>
        <div class="ubo-panel">
            <h3>
                <?php echo $flag; ?> Recent Orders — <?php echo esc_html( $label ); ?>
                <a href="<?php echo esc_url( $page_url ); ?>">View all →</a>
            </h3>
            <?php if ( empty( $data['recent_orders'] ) ) : ?>
                <p class="ubo-empty">No orders found or API not configured.</p>
            <?php else : ?>
                <table class="ubo-table">
                    <thead>
                        <tr>
                            <th>Order</th><th>Customer</th><th>Status</th><th>Total</th><th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $data['recent_orders'] as $order ) :
                        $billing = $order['billing'] ?? [];
                        $name    = trim( ( $billing['first_name'] ?? '' ) . ' ' . ( $billing['last_name'] ?? '' ) ) ?: 'Guest';
                        $status  = $order['status'] ?? 'unknown';
                        $color   = $status_colors[ $status ] ?? '#94a3b8';
                        $date    = date( 'M j', strtotime( $order['date_created'] ) );
                    ?>
                        <tr>
                            <td><strong>#<?php echo esc_html( $order['number'] ); ?></strong></td>
                            <td><?php echo esc_html( $name ); ?></td>
                            <td><span class="ubo-badge" style="background:<?php echo $color; ?>"><?php echo esc_html( $status ); ?></span></td>
                            <td><?php echo esc_html( $currency . $order['total'] ); ?></td>
                            <td><?php echo esc_html( $date ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

</div>
