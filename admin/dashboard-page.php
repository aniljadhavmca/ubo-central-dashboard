<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$us    = UBO_Orders::get_totals( 'US' );
$india = UBO_Orders::get_totals( 'India' );

$us_sellers    = UBO_Orders::get_top_sellers( 'US',    5 );
$india_sellers = UBO_Orders::get_top_sellers( 'India', 5 );
$us_stocked    = UBO_Orders::get_top_stocked( 'US',    5 );
$india_stocked = UBO_Orders::get_top_stocked( 'India', 5 );

$time = current_time( 'D, M j Y · g:i A' );
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
            <div class="ubo-v2-tab-panels-wrap">
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
            <div class="ubo-v2-tab-panels-wrap">
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

    </div>

    <!-- ── Sales Order Summary Chart ── -->
    <div class="ubo-v2-section-title">📈 Sales Order Summary</div>
    <div class="ubo-v2-card ubo-v2-chart-card">
        <div class="ubo-v2-chart-toolbar">
            <button class="ubo-v2-chart-tab active" data-site="US">🇺🇸 US</button>
            <button class="ubo-v2-chart-tab" data-site="India">🇮🇳 India</button>
            <div class="ubo-v2-chart-sep"></div>
            <select id="ubo-chart-metric" class="ubo-v2-chart-sel">
                <option value="quantity">By Quantity</option>
                <option value="value">By Value</option>
            </select>
            <select id="ubo-chart-period" class="ubo-v2-chart-sel">
                <option value="this_week">This Week</option>
                <option value="prev_week">Previous Week</option>
                <option value="this_month" selected>This Month</option>
                <option value="last_quarter">Last Quarter</option>
                <option value="this_year">This Year</option>
                <option value="prev_year">Previous Year</option>
            </select>
        </div>
        <div class="ubo-v2-chart-wrap">
            <canvas id="ubo-sales-chart"></canvas>
            <div id="ubo-chart-loading" class="ubo-v2-chart-overlay">⏳ Loading chart…</div>
            <div id="ubo-chart-empty" class="ubo-v2-chart-overlay" style="display:none;">No order data for this period.</div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function($) {
    // ── Rank tab switcher ──
    $('.ubo-v2-rank-tabs').on('click', '.ubo-v2-tab', function() {
        var $card = $(this).closest('.ubo-v2-card');
        $card.find('.ubo-v2-tab').removeClass('active');
        $card.find('.ubo-v2-tab-panel').removeClass('active');
        $(this).addClass('active');
        $('#' + $(this).data('target')).addClass('active');
    });

    // ── Sales Chart ──
    var chartInst = null;
    var curSite   = 'US';

    function loadChart() {
        var period  = $('#ubo-chart-period').val();
        var metric  = $('#ubo-chart-metric').val();
        var $canvas = $('#ubo-sales-chart');

        $('#ubo-chart-loading').show();
        $('#ubo-chart-empty').hide();
        if ( chartInst ) { chartInst.destroy(); chartInst = null; }

        $.post(uboAdmin.ajaxUrl, {
            action : 'ubo_sales_chart',
            nonce  : uboAdmin.nonce,
            site   : curSite,
            period : period,
            metric : metric
        }, function(res) {
            $('#ubo-chart-loading').hide();
            if ( ! res.success || ! res.data.labels.length ) {
                $('#ubo-chart-empty').show();
                return;
            }
            var labels   = res.data.labels;
            var data     = res.data.data;
            var isVal    = res.data.metric === 'value';
            var accent   = curSite === 'US' ? '#635bff' : '#f59e0b';
            var accentBg = curSite === 'US' ? 'rgba(99,91,255,.15)' : 'rgba(245,158,11,.15)';
            var cur      = curSite === 'India' ? '₹' : '$';

            chartInst = new Chart($canvas[0].getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: isVal ? 'Revenue' : 'Units Sold',
                        data: data,
                        backgroundColor: accentBg,
                        borderColor: accent,
                        borderWidth: 2,
                        borderRadius: 5,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(c) {
                                    return isVal ? cur + c.parsed.y.toLocaleString() : c.parsed.y + ' units';
                                }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#8792a2' } },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f0f3f6' },
                            ticks: {
                                font: { size: 11 }, color: '#8792a2',
                                callback: function(v) { return isVal ? cur + v.toLocaleString() : v; }
                            }
                        }
                    }
                }
            });
        }, 'json').fail(function() {
            $('#ubo-chart-loading').hide();
            $('#ubo-chart-empty').show();
        });
    }

    $(document).on('click', '.ubo-v2-chart-tab', function() {
        $('.ubo-v2-chart-tab').removeClass('active');
        $(this).addClass('active');
        curSite = $(this).data('site');
        loadChart();
    });

    $('#ubo-chart-period, #ubo-chart-metric').on('change', loadChart);

    loadChart();
})(jQuery);
</script>
