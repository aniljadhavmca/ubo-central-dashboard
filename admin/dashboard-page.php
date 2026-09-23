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
    <div class="ubo-v2-card ubo-v2-chart-card">
        <div class="ubo-v2-chart-header">
            <div class="ubo-v2-chart-title-row">
                <span class="ubo-v2-chart-title">Sales Order Summary</span>
                <div class="ubo-v2-chart-period-wrap">
                    <select id="ubo-chart-period" class="ubo-v2-chart-period-sel">
                        <option value="this_week">This Week</option>
                        <option value="prev_week">Previous Week</option>
                        <option value="this_month" selected>This Month</option>
                        <option value="last_quarter">Last Quarter</option>
                        <option value="this_year">This Year</option>
                        <option value="prev_year">Previous Year</option>
                    </select>
                </div>
            </div>
            <div class="ubo-v2-chart-controls">
                <div class="ubo-v2-chart-store-tabs">
                    <button class="ubo-v2-chart-tab active" data-site="US">🇺🇸 US</button>
                    <button class="ubo-v2-chart-tab" data-site="India">🇮🇳 India</button>
                </div>
                <div class="ubo-v2-metric-pills">
                    <button class="ubo-v2-metric-pill active" data-metric="quantity">By Quantity</button>
                    <button class="ubo-v2-metric-pill" data-metric="value">By Value</button>
                </div>
            </div>
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
    var curSite   = 'US';
    var curMetric = 'quantity';
    var curData   = { labels: [], data: [] };

    function loadChart() {
        var period = $('#ubo-chart-period').val();
        $('#ubo-graf-loading').show();
        $('#ubo-graf-empty, #ubo-graf-chart').hide();

        $.post(uboAdmin.ajaxUrl, {
            action: 'ubo_sales_chart', nonce: uboAdmin.nonce,
            site: curSite, period: period, metric: curMetric
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
        var isVal  = metric === 'value';
        var sym    = curSite === 'India' ? '₹' : '$';
        var accent = '#4da6ff';
        var ns     = 'http://www.w3.org/2000/svg';

        var $svg   = $('#ubo-graf-svg');
        var $yAxis = $('#ubo-graf-y');
        var $xAxis = $('#ubo-graf-x');
        var W      = $svg.parent().width();
        var H      = 220;
        var padL   = 0; var padR = 8; var padT = 16; var padB = 0;
        var plotW  = W - padL - padR;
        var plotH  = H - padT - padB;
        var n      = data.length;

        $svg.attr({ viewBox: '0 0 ' + W + ' ' + H, width: W, height: H });
        $svg.empty(); $yAxis.empty(); $xAxis.empty();

        // Nice rounded max so y-axis ticks are clean integers
        var rawMax   = Math.max.apply(null, data) || 1;
        var yTicks   = 4;
        var tickStep = Math.ceil(rawMax / yTicks) || 1;
        var max      = tickStep * yTicks;

        // Y-axis grid lines + labels
        for (var t = 0; t <= yTicks; t++) {
            var val = tickStep * (yTicks - t);
            var gy  = padT + (plotH / yTicks) * t;
            var gl  = document.createElementNS(ns, 'line');
            gl.setAttribute('x1', padL); gl.setAttribute('x2', W - padR);
            gl.setAttribute('y1', gy);   gl.setAttribute('y2', gy);
            gl.setAttribute('stroke', t === yTicks ? '#c8d0da' : '#edf0f4');
            gl.setAttribute('stroke-width', '1');
            $svg[0].appendChild(gl);
            var lbl = isVal ? sym + (val >= 1000 ? (val/1000).toFixed(1)+'k' : val) : val;
            $yAxis.append('<div style="position:absolute;right:4px;top:' + (gy - 8) + 'px;font-size:10px;color:#8792a2;white-space:nowrap;">' + lbl + '</div>');
        }

        if (n === 0) return;

        // Build pixel points — evenly spaced across full width
        var pts = [];
        for (var i = 0; i < n; i++) {
            var px = padL + (n === 1 ? plotW / 2 : (plotW / (n - 1)) * i);
            var py = padT + plotH - (data[i] / max) * plotH;
            pts.push([px, py]);
        }

        // Catmull-Rom → cubic bezier, tension 0.35 for smooth bell shape
        function catmullPath(p) {
            if (p.length < 2) return 'M' + p[0][0] + ',' + p[0][1];
            var alpha = 0.35;
            var d = 'M' + p[0][0] + ',' + p[0][1];
            for (var i = 0; i < p.length - 1; i++) {
                var p0 = p[Math.max(i-1, 0)];
                var p1 = p[i];
                var p2 = p[i+1];
                var p3 = p[Math.min(i+2, p.length-1)];
                var cp1x = p1[0] + (p2[0] - p0[0]) * alpha;
                var cp1y = p1[1] + (p2[1] - p0[1]) * alpha;
                var cp2x = p2[0] - (p3[0] - p1[0]) * alpha;
                var cp2y = p2[1] - (p3[1] - p1[1]) * alpha;
                d += ' C' + cp1x.toFixed(2) + ',' + cp1y.toFixed(2) + ' ' + cp2x.toFixed(2) + ',' + cp2y.toFixed(2) + ' ' + p2[0] + ',' + p2[1];
            }
            return d;
        }

        var linePath = catmullPath(pts);
        var bottom   = padT + plotH;

        // Gradient fill — stronger at top, fades to transparent
        var defs = document.createElementNS(ns, 'defs');
        var grad = document.createElementNS(ns, 'linearGradient');
        grad.setAttribute('id', 'ubo-grad'); grad.setAttribute('x1','0'); grad.setAttribute('y1','0'); grad.setAttribute('x2','0'); grad.setAttribute('y2','1');
        var s1 = document.createElementNS(ns,'stop'); s1.setAttribute('offset','0%');   s1.setAttribute('stop-color', accent); s1.setAttribute('stop-opacity','0.30');
        var s2 = document.createElementNS(ns,'stop'); s2.setAttribute('offset','80%');  s2.setAttribute('stop-color', accent); s2.setAttribute('stop-opacity','0.06');
        var s3 = document.createElementNS(ns,'stop'); s3.setAttribute('offset','100%'); s3.setAttribute('stop-color', accent); s3.setAttribute('stop-opacity','0');
        grad.appendChild(s1); grad.appendChild(s2); grad.appendChild(s3);
        defs.appendChild(grad);
        $svg[0].appendChild(defs);

        // Crosshair vertical line (hidden until hover)
        var xhair = document.createElementNS(ns, 'line');
        xhair.setAttribute('y1', padT); xhair.setAttribute('y2', bottom);
        xhair.setAttribute('stroke', '#b0b8c4'); xhair.setAttribute('stroke-width', '1');
        xhair.setAttribute('stroke-dasharray', '3,3');
        xhair.style.display = 'none';
        $svg[0].appendChild(xhair);

        // Gradient fill area
        var fillEl = document.createElementNS(ns, 'path');
        fillEl.setAttribute('d', linePath + ' L' + pts[n-1][0] + ',' + bottom + ' L' + pts[0][0] + ',' + bottom + ' Z');
        fillEl.setAttribute('fill', 'url(#ubo-grad)');
        $svg[0].appendChild(fillEl);

        // Stroke line
        var lineEl = document.createElementNS(ns, 'path');
        lineEl.setAttribute('d', linePath);
        lineEl.setAttribute('fill', 'none');
        lineEl.setAttribute('stroke', accent);
        lineEl.setAttribute('stroke-width', '2');
        lineEl.setAttribute('stroke-linejoin', 'round');
        lineEl.setAttribute('stroke-linecap', 'round');
        $svg[0].appendChild(lineEl);

        // Invisible hover slots — one rect per data point, full chart height
        var slotW = n > 1 ? plotW / (n - 1) : plotW;
        for (var i = 0; i < n; i++) {
            (function(idx, px, py) {
                var hit = document.createElementNS(ns, 'rect');
                hit.setAttribute('x', Math.max(0, px - slotW / 2));
                hit.setAttribute('y', padT);
                hit.setAttribute('width', slotW);
                hit.setAttribute('height', plotH);
                hit.setAttribute('fill', 'transparent');
                hit.setAttribute('style', 'cursor:crosshair;');
                hit.addEventListener('mouseenter', function() {
                    xhair.setAttribute('x1', px); xhair.setAttribute('x2', px);
                    xhair.style.display = '';
                    var dot = document.getElementById('ubo-hover-dot');
                    if (!dot) {
                        dot = document.createElementNS(ns, 'circle');
                        dot.setAttribute('id', 'ubo-hover-dot');
                        dot.setAttribute('r', '4');
                        dot.setAttribute('fill', '#fff');
                        dot.setAttribute('stroke', accent);
                        dot.setAttribute('stroke-width', '2');
                        $svg[0].appendChild(dot);
                    }
                    dot.setAttribute('cx', px); dot.setAttribute('cy', py);
                    dot.style.display = '';
                    var parts    = labels[idx].split('\n');
                    var dispLbl  = parts.length > 1 ? parts[0] + ' ' + parts[1] : parts[0];
                    var valStr   = isVal ? sym + data[idx].toLocaleString() : data[idx] + ' orders';
                    var $tt      = $('#ubo-graf-tooltip');
                    $tt.html('<strong>' + dispLbl + '</strong><br>' + valStr).show();
                    var svgRect  = $svg[0].getBoundingClientRect();
                    var wrapRect = $svg.closest('.ubo-graf-plot-area')[0].getBoundingClientRect();
                    $tt.css({ left: Math.max(0, px + svgRect.left - wrapRect.left - 44) + 'px', top: Math.max(0, py + svgRect.top - wrapRect.top - 54) + 'px' });
                });
                hit.addEventListener('mouseleave', function() {
                    xhair.style.display = 'none';
                    var dot = document.getElementById('ubo-hover-dot');
                    if (dot) dot.style.display = 'none';
                    $('#ubo-graf-tooltip').hide();
                });
                $svg[0].appendChild(hit);
            })(i, pts[i][0], pts[i][1]);
        }

        // X-axis labels — two lines (day + month), max ~10 evenly spaced
        var step = Math.max(1, Math.ceil(n / 10));
        for (var i = 0; i < n; i += step) {
            var xPct  = n === 1 ? 50 : (pts[i][0] - padL) / plotW * 100;
            var parts = labels[i].split('\n');
            $xAxis.append(
                '<span style="position:absolute;left:' + xPct + '%;transform:translateX(-50%);text-align:center;line-height:1.3;">'
                + '<span style="display:block;font-size:10px;color:#3c4257;font-weight:500;">' + (parts[0]||'') + '</span>'
                + (parts[1] ? '<span style="display:block;font-size:9px;color:#8792a2;">' + parts[1] + '</span>' : '')
                + '</span>'
            );
        }
    }
    // Store tab
    $(document).on('click', '.ubo-v2-chart-tab', function() {
        $('.ubo-v2-chart-tab').removeClass('active');
        $(this).addClass('active');
        curSite = $(this).data('site');
        loadChart();
    });
    // Metric pill toggle
    $(document).on('click', '.ubo-v2-metric-pill', function() {
        $('.ubo-v2-metric-pill').removeClass('active');
        $(this).addClass('active');
        curMetric = $(this).data('metric');
        loadChart();
    });
    $('#ubo-chart-period').on('change', loadChart);

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
