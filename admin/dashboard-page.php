<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$us    = UBO_Orders::get_totals( 'US' );
$india = UBO_Orders::get_totals( 'India' );

$us_sellers    = UBO_Orders::get_top_sellers( 'US',    5 );
$india_sellers = UBO_Orders::get_top_sellers( 'India', 5 );
$us_stocked    = UBO_Orders::get_top_stocked( 'US',    5 );
$india_stocked = UBO_Orders::get_top_stocked( 'India', 5 );
$us_status     = UBO_Orders::get_status_summary( 'US' );
$india_status  = UBO_Orders::get_status_summary( 'India' );

$time = current_time( 'D, M j Y · g:i A' );

$status_meta = [
    'pending'    => [ 'label' => 'Pending',    'color' => '#f59e0b', 'bg' => '#fffbeb' ],
    'processing' => [ 'label' => 'Processing', 'color' => '#3b82f6', 'bg' => '#eff6ff' ],
    'on-hold'    => [ 'label' => 'On Hold',    'color' => '#8b5cf6', 'bg' => '#f5f3ff' ],
    'completed'  => [ 'label' => 'Completed',  'color' => '#10b981', 'bg' => '#ecfdf5' ],
    'cancelled'  => [ 'label' => 'Cancelled',  'color' => '#ef4444', 'bg' => '#fef2f2' ],
    'refunded'   => [ 'label' => 'Refunded',   'color' => '#f97316', 'bg' => '#fff7ed' ],
];
?>
<div class="ubo-v2-wrap">

    <!-- ── Header ── -->
    <div class="ubo-v2-header">
        <div class="ubo-v2-header-left">
            <div class="ubo-v2-logo">🕉️</div>
            <div>
                <div class="ubo-v2-title">UBO Central Dashboard</div>
                <div class="ubo-v2-subtitle">Unified inventory &amp; orders across US &amp; India</div>
            </div>
        </div>
        <div class="ubo-v2-header-right">
            <span class="ubo-v2-time">🕐 <?php echo esc_html( $time ); ?></span>
            <a href="<?php echo esc_url( admin_url('admin.php?page=ubo-sku') ); ?>" class="ubo-v2-btn">📦 SKU Central</a>
            <a href="<?php echo esc_url( admin_url('admin.php?page=ubo-alerts') ); ?>" class="ubo-v2-btn ubo-v2-btn-alert">🔔 Alerts</a>
        </div>
    </div>

    <!-- ── KPI Row ── -->
    <div class="ubo-v2-kpi-grid">

        <div class="ubo-v2-kpi">
            <div class="ubo-v2-kpi-icon" style="background:#eff6ff;color:#3b82f6;">🛒</div>
            <div class="ubo-v2-kpi-body">
                <div class="ubo-v2-kpi-label">Total Orders</div>
                <div class="ubo-v2-kpi-value"><?php echo esc_html( (int)$us['order_count'] + (int)$india['order_count'] ); ?></div>
                <div class="ubo-v2-kpi-split">
                    <span class="ubo-v2-flag-val">🇺🇸 <?php echo esc_html( $us['order_count'] ); ?></span>
                    <span class="ubo-v2-flag-val">🇮🇳 <?php echo esc_html( $india['order_count'] ); ?></span>
                </div>
            </div>
        </div>

        <div class="ubo-v2-kpi">
            <div class="ubo-v2-kpi-icon" style="background:#ecfdf5;color:#10b981;">💰</div>
            <div class="ubo-v2-kpi-body">
                <div class="ubo-v2-kpi-label">Revenue (This Year)</div>
                <div class="ubo-v2-kpi-value" style="font-size:20px;">$<?php echo esc_html( number_format( (float)$us['revenue'], 0 ) ); ?></div>
                <div class="ubo-v2-kpi-split">
                    <span class="ubo-v2-flag-val">🇺🇸 $<?php echo esc_html( number_format( (float)$us['revenue'], 0 ) ); ?></span>
                    <span class="ubo-v2-flag-val">🇮🇳 ₹<?php echo esc_html( number_format( (float)$india['revenue'], 0 ) ); ?></span>
                </div>
            </div>
        </div>

        <div class="ubo-v2-kpi">
            <div class="ubo-v2-kpi-icon" style="background:#f5f3ff;color:#8b5cf6;">📦</div>
            <div class="ubo-v2-kpi-body">
                <div class="ubo-v2-kpi-label">Total Products</div>
                <div class="ubo-v2-kpi-value"><?php echo esc_html( (int)$us['product_count'] + (int)$india['product_count'] ); ?></div>
                <div class="ubo-v2-kpi-split">
                    <span class="ubo-v2-flag-val">🇺🇸 <?php echo esc_html( $us['product_count'] ); ?></span>
                    <span class="ubo-v2-flag-val">🇮🇳 <?php echo esc_html( $india['product_count'] ); ?></span>
                </div>
            </div>
        </div>

        <div class="ubo-v2-kpi ubo-v2-kpi-alert">
            <div class="ubo-v2-kpi-icon" style="background:#fef2f2;color:#ef4444;">⚠️</div>
            <div class="ubo-v2-kpi-body">
                <div class="ubo-v2-kpi-label">Out of Stock</div>
                <div class="ubo-v2-kpi-value" style="color:#ef4444;"><?php echo esc_html( (int)$us['low_stock'] + (int)$india['low_stock'] ); ?></div>
                <div class="ubo-v2-kpi-split">
                    <span class="ubo-v2-flag-val">🇺🇸 <?php echo esc_html( $us['low_stock'] ); ?></span>
                    <span class="ubo-v2-flag-val">🇮🇳 <?php echo esc_html( $india['low_stock'] ); ?></span>
                </div>
            </div>
        </div>

    </div>

    <!-- ── Order Status Summary ── -->
    <div class="ubo-v2-section-title">📊 Order Status Summary</div>
    <div class="ubo-v2-status-grid">
        <?php foreach ( [ 'US' => [ $us_status, '🇺🇸', 'US Store' ], 'India' => [ $india_status, '🇮🇳', 'India Store' ] ] as $key => [ $summary, $flag, $label ] ) : ?>
        <div class="ubo-v2-card">
            <div class="ubo-v2-card-header">
                <span><?php echo $flag; ?> <?php echo esc_html( $label ); ?></span>
                <a href="<?php echo esc_url( admin_url('admin.php?page=ubo-orders&site=' . $key) ); ?>" class="ubo-v2-view-all">View orders →</a>
            </div>
            <div class="ubo-v2-status-list">
                <?php if ( empty( $summary ) ) : ?>
                    <div class="ubo-v2-empty">No order data available</div>
                <?php else : ?>
                    <?php foreach ( $summary as $status => $count ) :
                        $meta  = $status_meta[ $status ] ?? [ 'label' => ucfirst( $status ), 'color' => '#64748b', 'bg' => '#f8fafc' ];
                        $total = array_sum( $summary );
                        $pct   = $total > 0 ? round( ( $count / $total ) * 100 ) : 0;
                    ?>
                    <div class="ubo-v2-status-row">
                        <div class="ubo-v2-status-left">
                            <span class="ubo-v2-status-dot" style="background:<?php echo esc_attr( $meta['color'] ); ?>;"></span>
                            <span class="ubo-v2-status-name"><?php echo esc_html( $meta['label'] ); ?></span>
                        </div>
                        <div class="ubo-v2-status-right">
                            <div class="ubo-v2-status-bar-wrap">
                                <div class="ubo-v2-status-bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo esc_attr( $meta['color'] ); ?>;"></div>
                            </div>
                            <span class="ubo-v2-status-count" style="color:<?php echo esc_attr( $meta['color'] ); ?>;"><?php echo esc_html( $count ); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Top Sellers + Top Stocked ── -->
    <div class="ubo-v2-two-col">

        <!-- Top Selling This Month -->
        <div class="ubo-v2-card">
            <div class="ubo-v2-card-header">
                <span>🔥 Top Selling This Month</span>
            </div>
            <div class="ubo-v2-rank-tabs">
                <button class="ubo-v2-tab active" data-target="ubo-ts-us">🇺🇸 US</button>
                <button class="ubo-v2-tab" data-target="ubo-ts-india">🇮🇳 India</button>
            </div>
            <div id="ubo-ts-us" class="ubo-v2-tab-panel active">
                <?php if ( empty( $us_sellers ) ) : ?>
                    <div class="ubo-v2-empty">No sales data this month</div>
                <?php else : ?>
                    <?php foreach ( $us_sellers as $i => $item ) : ?>
                    <div class="ubo-v2-rank-row">
                        <span class="ubo-v2-rank-num"><?php echo $i + 1; ?></span>
                        <div class="ubo-v2-rank-info">
                            <div class="ubo-v2-rank-name"><?php echo esc_html( $item['name'] ?? 'Product #' . $item['product_id'] ); ?></div>
                            <div class="ubo-v2-rank-meta">ID: <?php echo esc_html( $item['product_id'] ); ?></div>
                        </div>
                        <span class="ubo-v2-rank-badge"><?php echo esc_html( $item['quantity'] ); ?> sold</span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div id="ubo-ts-india" class="ubo-v2-tab-panel">
                <?php if ( empty( $india_sellers ) ) : ?>
                    <div class="ubo-v2-empty">No sales data this month</div>
                <?php else : ?>
                    <?php foreach ( $india_sellers as $i => $item ) : ?>
                    <div class="ubo-v2-rank-row">
                        <span class="ubo-v2-rank-num"><?php echo $i + 1; ?></span>
                        <div class="ubo-v2-rank-info">
                            <div class="ubo-v2-rank-name"><?php echo esc_html( $item['name'] ?? 'Product #' . $item['product_id'] ); ?></div>
                            <div class="ubo-v2-rank-meta">ID: <?php echo esc_html( $item['product_id'] ); ?></div>
                        </div>
                        <span class="ubo-v2-rank-badge"><?php echo esc_html( $item['quantity'] ); ?> sold</span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Stocked by Store -->
        <div class="ubo-v2-card">
            <div class="ubo-v2-card-header">
                <span>📦 Top Stocked Items</span>
            </div>
            <div class="ubo-v2-rank-tabs">
                <button class="ubo-v2-tab active" data-target="ubo-stk-us">🇺🇸 US</button>
                <button class="ubo-v2-tab" data-target="ubo-stk-india">🇮🇳 India</button>
            </div>
            <div id="ubo-stk-us" class="ubo-v2-tab-panel active">
                <?php if ( empty( $us_stocked ) ) : ?>
                    <div class="ubo-v2-empty">No stock data available</div>
                <?php else :
                    $max_us = (int)( $us_stocked[0]['stock_quantity'] ?? 1 );
                    foreach ( $us_stocked as $i => $p ) :
                        $qty = (int)( $p['stock_quantity'] ?? 0 );
                        $pct = $max_us > 0 ? round( ( $qty / $max_us ) * 100 ) : 0;
                ?>
                    <div class="ubo-v2-rank-row">
                        <span class="ubo-v2-rank-num"><?php echo $i + 1; ?></span>
                        <div class="ubo-v2-rank-info" style="flex:1;">
                            <div class="ubo-v2-rank-name"><?php echo esc_html( $p['name'] ); ?></div>
                            <div class="ubo-v2-stock-bar-wrap">
                                <div class="ubo-v2-stock-bar-fill" style="width:<?php echo $pct; ?>%;"></div>
                            </div>
                        </div>
                        <span class="ubo-v2-rank-badge ubo-v2-badge-green"><?php echo esc_html( $qty ); ?> units</span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <div id="ubo-stk-india" class="ubo-v2-tab-panel">
                <?php if ( empty( $india_stocked ) ) : ?>
                    <div class="ubo-v2-empty">No stock data available</div>
                <?php else :
                    $max_in = (int)( $india_stocked[0]['stock_quantity'] ?? 1 );
                    foreach ( $india_stocked as $i => $p ) :
                        $qty = (int)( $p['stock_quantity'] ?? 0 );
                        $pct = $max_in > 0 ? round( ( $qty / $max_in ) * 100 ) : 0;
                ?>
                    <div class="ubo-v2-rank-row">
                        <span class="ubo-v2-rank-num"><?php echo $i + 1; ?></span>
                        <div class="ubo-v2-rank-info" style="flex:1;">
                            <div class="ubo-v2-rank-name"><?php echo esc_html( $p['name'] ); ?></div>
                            <div class="ubo-v2-stock-bar-wrap">
                                <div class="ubo-v2-stock-bar-fill" style="width:<?php echo $pct; ?>%;"></div>
                            </div>
                        </div>
                        <span class="ubo-v2-rank-badge ubo-v2-badge-green"><?php echo esc_html( $qty ); ?> units</span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

    </div>

    <!-- ── Recent Orders ── -->
    <div class="ubo-v2-section-title">🧾 Recent Orders</div>
    <div class="ubo-v2-two-col">
        <?php foreach ( [ 'US' => [ $us, '🇺🇸', '$' ], 'India' => [ $india, '🇮🇳', '₹' ] ] as $key => [ $data, $flag, $cur ] ) : ?>
        <div class="ubo-v2-card">
            <div class="ubo-v2-card-header">
                <span><?php echo $flag; ?> <?php echo esc_html( $key ); ?> Store</span>
                <a href="<?php echo esc_url( admin_url('admin.php?page=ubo-orders&site=' . $key) ); ?>" class="ubo-v2-view-all">View all →</a>
            </div>
            <?php if ( empty( $data['recent_orders'] ) ) : ?>
                <div class="ubo-v2-empty">No recent orders or API not configured.</div>
            <?php else : ?>
            <table class="ubo-v2-table">
                <thead>
                    <tr><th>Order</th><th>Customer</th><th>Status</th><th>Total</th><th>Date</th></tr>
                </thead>
                <tbody>
                <?php foreach ( $data['recent_orders'] as $order ) :
                    $billing = $order['billing'] ?? [];
                    $name    = trim( ( $billing['first_name'] ?? '' ) . ' ' . ( $billing['last_name'] ?? '' ) ) ?: 'Guest';
                    $status  = $order['status'] ?? 'unknown';
                    $meta    = $status_meta[ $status ] ?? [ 'label' => ucfirst( $status ), 'color' => '#64748b', 'bg' => '#f8fafc' ];
                    $date    = date( 'M j', strtotime( $order['date_created'] ) );
                ?>
                    <tr>
                        <td><span class="ubo-v2-order-num">#<?php echo esc_html( $order['number'] ); ?></span></td>
                        <td class="ubo-v2-customer"><?php echo esc_html( $name ); ?></td>
                        <td>
                            <span class="ubo-v2-status-pill" style="color:<?php echo esc_attr( $meta['color'] ); ?>;background:<?php echo esc_attr( $meta['bg'] ); ?>;">
                                <?php echo esc_html( $meta['label'] ); ?>
                            </span>
                        </td>
                        <td class="ubo-v2-total"><?php echo esc_html( $cur . $order['total'] ); ?></td>
                        <td class="ubo-v2-date"><?php echo esc_html( $date ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
(function() {
    // Tab switcher for Top Sellers / Top Stocked panels
    document.querySelectorAll('.ubo-v2-rank-tabs').forEach(function(tabs) {
        tabs.querySelectorAll('.ubo-v2-tab').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var panel = btn.closest('.ubo-v2-card');
                panel.querySelectorAll('.ubo-v2-tab').forEach(function(b) { b.classList.remove('active'); });
                panel.querySelectorAll('.ubo-v2-tab-panel').forEach(function(p) { p.classList.remove('active'); });
                btn.classList.add('active');
                document.getElementById(btn.dataset.target).classList.add('active');
            });
        });
    });
})();
</script>
