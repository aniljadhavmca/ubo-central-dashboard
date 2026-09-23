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
            <span class="ubo-v2-chart-sep"></span>
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
        <div class="ubo-graf-wrap">
            <div id="ubo-graf-loading" class="ubo-graf-state">Loading…</div>
            <div id="ubo-graf-empty"  class="ubo-graf-state" style="display:none;">No data for this period.</div>
            <div id="ubo-graf-chart"  class="ubo-graf-chart" style="display:none;">
                <div class="ubo-graf-y-axis" id="ubo-graf-y"></div>
                <div class="ubo-graf-plot-area">
                    <svg id="ubo-graf-svg" class="ubo-graf-svg" preserveAspectRatio="none"></svg>
                    <div class="ubo-graf-x-axis" id="ubo-graf-x"></div>
                    <div id="ubo-graf-tooltip" class="ubo-graf-tooltip" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
(function($) {
    // ── Rank tab switcher ──
    $(document).on('click', '.ubo-v2-rank-tabs .ubo-v2-tab', function() {
        var $card = $(this).closest('.ubo-v2-card');
        $card.find('.ubo-v2-tab').removeClass('active');
        $card.find('.ubo-v2-tab-panel').removeClass('active');
        $(this).addClass('active');
        $('#' + $(this).data('target')).addClass('active');
    });

    // ── Grafana-style SVG chart ──
    var curSite = 'US';
    var curData = { labels: [], data: [] };

    function loadChart() {
        var period = $('#ubo-chart-period').val();
        var metric = $('#ubo-chart-metric').val();
        $('#ubo-graf-loading').show();
        $('#ubo-graf-empty, #ubo-graf-chart').hide();

        $.post(uboAdmin.ajaxUrl, {
            action: 'ubo_sales_chart', nonce: uboAdmin.nonce,
            site: curSite, period: period, metric: metric
        }, function(res) {
            $('#ubo-graf-loading').hide();
            if (!res.success || !res.data.labels.length) {
                $('#ubo-graf-empty').show(); return;
            }
            curData = res.data;
            $('#ubo-graf-chart').show();
            drawChart(res.data.labels, res.data.data, res.data.metric);
        }, 'json').fail(function() {
            $('#ubo-graf-loading').hide();
            $('#ubo-graf-empty').show();
        });
    }

    function drawChart(labels, data, metric) {
        var isVal   = metric === 'value';
        var sym     = curSite === 'India' ? '₹' : '$';
        var accent  = curSite === 'US' ? '#635bff' : '#f59e0b';
        var aFill   = curSite === 'US' ? 'rgba(99,91,255,.08)' : 'rgba(245,158,11,.08)';

        var $svg    = $('#ubo-graf-svg');
        var $yAxis  = $('#ubo-graf-y');
        var $xAxis  = $('#ubo-graf-x');
        var W       = $svg.parent().width();
        var H       = 220;
        var padL    = 0; var padR = 8; var padT = 12; var padB = 0;
        var plotW   = W - padL - padR;
        var plotH   = H - padT - padB;

        $svg.attr({ viewBox: '0 0 ' + W + ' ' + H, width: W, height: H });
        $svg.empty(); $yAxis.empty(); $xAxis.empty();

        var max = Math.max.apply(null, data) || 1;
        var n   = data.length;

        // Y-axis ticks (4 lines)
        var yTicks = 4;
        $yAxis.empty();
        for (var t = 0; t <= yTicks; t++) {
            var val = Math.round((max / yTicks) * (yTicks - t));
            var y   = padT + (plotH / yTicks) * t;
            // grid line in SVG
            var line = document.createElementNS('http://www.w3.org/2000/svg','line');
            line.setAttribute('x1', padL); line.setAttribute('x2', W - padR);
            line.setAttribute('y1', y);    line.setAttribute('y2', y);
            line.setAttribute('stroke', '#e8ecf0'); line.setAttribute('stroke-width', '1');
            $svg[0].appendChild(line);
            // label
            var lbl = isVal ? sym + (val >= 1000 ? (val/1000).toFixed(1)+'k' : val) : val;
            $yAxis.append('<div style="position:absolute;right:4px;top:' + (y - 8) + 'px;font-size:10px;color:#8792a2;white-space:nowrap;">' + lbl + '</div>');
        }

        if (n === 0) return;

        // Build points
        var pts = [];
        for (var i = 0; i < n; i++) {
            var x = padL + (n === 1 ? plotW / 2 : (plotW / (n - 1)) * i);
            var y = padT + plotH - (data[i] / max) * plotH;
            pts.push([x, y]);
        }

        // Smooth path using cubic bezier
        function smooth(points) {
            if (points.length < 2) return 'M' + points[0][0] + ',' + points[0][1];
            var d = 'M' + points[0][0] + ',' + points[0][1];
            for (var i = 0; i < points.length - 1; i++) {
                var cp1x = points[i][0] + (points[i+1][0] - (i > 0 ? points[i-1][0] : points[i][0])) * 0.2;
                var cp1y = points[i][1] + (points[i+1][1] - (i > 0 ? points[i-1][1] : points[i][1])) * 0.2;
                var cp2x = points[i+1][0] - (i < points.length - 2 ? points[i+2][0] - points[i][0] : points[i+1][0] - points[i][0]) * 0.2;
                var cp2y = points[i+1][1] - (i < points.length - 2 ? points[i+2][1] - points[i][1] : points[i+1][1] - points[i][1]) * 0.2;
                d += ' C' + cp1x + ',' + cp1y + ' ' + cp2x + ',' + cp2y + ' ' + points[i+1][0] + ',' + points[i+1][1];
            }
            return d;
        }

        var ns = 'http://www.w3.org/2000/svg';
        var linePath = smooth(pts);
        var bottom   = padT + plotH;

        // Gradient fill
        var defs = document.createElementNS(ns, 'defs');
        var grad = document.createElementNS(ns, 'linearGradient');
        grad.setAttribute('id', 'ubo-grad'); grad.setAttribute('x1','0'); grad.setAttribute('y1','0'); grad.setAttribute('x2','0'); grad.setAttribute('y2','1');
        var s1 = document.createElementNS(ns,'stop'); s1.setAttribute('offset','0%');   s1.setAttribute('stop-color', accent); s1.setAttribute('stop-opacity','0.18');
        var s2 = document.createElementNS(ns,'stop'); s2.setAttribute('offset','100%'); s2.setAttribute('stop-color', accent); s2.setAttribute('stop-opacity','0');
        grad.appendChild(s1); grad.appendChild(s2); defs.appendChild(grad); $svg[0].appendChild(defs);

        // Fill area
        var fillPath = document.createElementNS(ns, 'path');
        fillPath.setAttribute('d', linePath + ' L' + pts[pts.length-1][0] + ',' + bottom + ' L' + pts[0][0] + ',' + bottom + ' Z');
        fillPath.setAttribute('fill', 'url(#ubo-grad)');
        $svg[0].appendChild(fillPath);

        // Line
        var lineEl = document.createElementNS(ns, 'path');
        lineEl.setAttribute('d', linePath);
        lineEl.setAttribute('fill', 'none');
        lineEl.setAttribute('stroke', accent);
        lineEl.setAttribute('stroke-width', '2');
        lineEl.setAttribute('stroke-linejoin', 'round');
        lineEl.setAttribute('stroke-linecap', 'round');
        $svg[0].appendChild(lineEl);

        // Dots + invisible hit areas
        for (var i = 0; i < pts.length; i++) {
            (function(idx, px, py) {
                var dot = document.createElementNS(ns, 'circle');
                dot.setAttribute('cx', px); dot.setAttribute('cy', py);
                dot.setAttribute('r', n <= 14 ? '4' : '3');
                dot.setAttribute('fill', '#fff');
                dot.setAttribute('stroke', accent);
                dot.setAttribute('stroke-width', '2');
                $svg[0].appendChild(dot);

                // Hit area
                var hit = document.createElementNS(ns, 'circle');
                hit.setAttribute('cx', px); hit.setAttribute('cy', py);
                hit.setAttribute('r', '14');
                hit.setAttribute('fill', 'transparent');
                hit.setAttribute('style', 'cursor:pointer;');
                hit.addEventListener('mouseenter', function(e) {
                    var val = isVal ? sym + data[idx].toLocaleString() : data[idx] + ' units';
                    var $tt = $('#ubo-graf-tooltip');
                    $tt.html('<strong>' + labels[idx] + '</strong><br>' + val).show();
                    var rect = $svg[0].getBoundingClientRect();
                    var wrap = $svg.closest('.ubo-graf-plot-area')[0].getBoundingClientRect();
                    var tx = px - wrap.left + rect.left - 40;
                    var ty = py - wrap.top  + rect.top  - 52;
                    $tt.css({ left: Math.max(0, tx) + 'px', top: ty + 'px' });
                });
                hit.addEventListener('mouseleave', function() { $('#ubo-graf-tooltip').hide(); });
                $svg[0].appendChild(hit);
            })(i, pts[i][0], pts[i][1]);
        }

        // X-axis labels — show max 10 evenly spaced
        var step = Math.ceil(n / 10);
        for (var i = 0; i < n; i += step) {
            var xPct = n === 1 ? 50 : (pts[i][0] - padL) / plotW * 100;
            $xAxis.append('<span style="position:absolute;left:' + xPct + '%;transform:translateX(-50%);font-size:10px;color:#8792a2;white-space:nowrap;">' + labels[i] + '</span>');
        }
    }

    // Store tab
    $(document).on('click', '.ubo-v2-chart-tab', function() {
        $('.ubo-v2-chart-tab').removeClass('active');
        $(this).addClass('active');
        curSite = $(this).data('site');
        loadChart();
    });
    $('#ubo-chart-period, #ubo-chart-metric').on('change', loadChart);

    // Redraw on resize
    var resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (curData.labels.length) drawChart(curData.labels, curData.data, curData.metric);
        }, 150);
    });

    $(window).on('load', loadChart);

})(jQuery);
</script>
