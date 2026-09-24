<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

// ── Helpers defined FIRST so they're available everywhere below ──
if ( ! function_exists( 'ubo_color_hex' ) ) {
    function ubo_color_hex( $color ) {
        $map = [
            'black'=>'#1a1a1a','white'=>'#f5f5f5','red'=>'#ef4444','blue'=>'#3b82f6',
            'green'=>'#22c55e','yellow'=>'#eab308','orange'=>'#f97316','pink'=>'#ec4899',
            'purple'=>'#a855f7','grey'=>'#9ca3af','gray'=>'#9ca3af','brown'=>'#92400e',
            'navy'=>'#1e3a5f','maroon'=>'#7f1d1d','beige'=>'#d4b896','cream'=>'#fef9c3',
            'teal'=>'#14b8a6','indigo'=>'#6366f1','violet'=>'#8b5cf6','gold'=>'#d97706',
        ];
        return $map[ strtolower( $color ) ] ?? '#635bff';
    }
}
if ( ! function_exists( 'ubo_country_flag' ) ) {
    function ubo_country_flag( $code ) {
        $code = strtoupper( trim( $code ) );
        if ( strlen( $code ) !== 2 ) return '🌐';
        $flag = '';
        foreach ( str_split( $code ) as $c ) {
            $ord = ord( $c ) - ord( 'A' );
            if ( $ord < 0 || $ord > 25 ) return '🌐';
            $flag .= mb_chr( 0x1F1E6 + $ord );
        }
        return $flag;
    }
}
if ( ! function_exists( 'ubo_alert_low_stock' ) ) {
    function ubo_alert_low_stock( $products, $threshold ) {
        $low = [];
        if ( ! is_array( $products ) || isset( $products['error'] ) ) return $low;
        foreach ( $products as $p ) {
            if ( ( $p['type'] ?? '' ) === 'variable' && ! empty( $p['variations_data'] ) ) {
                foreach ( $p['variations_data'] as $v ) {
                    $qty = (int)( $v['stock_quantity'] ?? 0 );
                    if ( $qty > 0 && $qty <= $threshold ) {
                        $attrs = implode( ' / ', array_map( fn($a) => $a['option'], $v['attributes'] ?? [] ) );
                        $low[] = [ 'name' => $p['name'] . ( $attrs ? ' — ' . $attrs : '' ), 'sku' => $v['sku'] ?? '', 'qty' => $qty, 'image' => $p['images'][0]['src'] ?? '' ];
                    }
                }
            } else {
                $qty = (int)( $p['stock_quantity'] ?? 0 );
                if ( $qty > 0 && $qty <= $threshold )
                    $low[] = [ 'name' => $p['name'], 'sku' => $p['sku'] ?? '', 'qty' => $qty, 'image' => $p['images'][0]['src'] ?? '' ];
            }
        }
        usort( $low, fn( $a, $b ) => $a['qty'] - $b['qty'] );
        return $low;
    }
}

// ── Data ──
$threshold    = (int) get_option( 'ubo_low_stock_threshold', UBO_LOW_STOCK_THRESHOLD );
$us_all       = UBO_Inventory::fetch( 'US',    [ 'per_page' => 100 ] );
$in_all       = UBO_Inventory::fetch( 'India', [ 'per_page' => 100 ] );
$us_insights  = UBO_Orders::get_insights( 'US',    8 );
$in_insights  = UBO_Orders::get_insights( 'India', 8 );

$us_low       = ubo_alert_low_stock( $us_all, $threshold );
$in_low       = ubo_alert_low_stock( $in_all, $threshold );

// Build out-of-stock lists counting individual variations, not just parent products
function ubo_alert_out_of_stock( $products ) {
    $out = [];
    if ( ! is_array( $products ) || isset( $products['error'] ) ) return $out;
    foreach ( $products as $p ) {
        if ( ( $p['type'] ?? '' ) === 'variable' && ! empty( $p['variations_data'] ) ) {
            foreach ( $p['variations_data'] as $v ) {
                $qty = (int)( $v['stock_quantity'] ?? 0 );
                $st  = $v['stock_status'] ?? 'instock';
                if ( $st === 'outofstock' || $qty === 0 ) {
                    $attrs = implode( ' / ', array_map( fn($a) => $a['option'], $v['attributes'] ?? [] ) );
                    $out[] = [
                        'name'  => $p['name'] . ( $attrs ? ' — ' . $attrs : '' ),
                        'sku'   => $v['sku'] ?? '',
                        'image' => ! empty( $v['image']['src'] ) ? $v['image']['src'] : ( $p['images'][0]['src'] ?? '' ),
                    ];
                }
            }
        } else {
            $st  = $p['stock_status'] ?? 'instock';
            $qty = (int)( $p['stock_quantity'] ?? 0 );
            if ( $st === 'outofstock' || $qty === 0 ) {
                $out[] = [
                    'name'  => $p['name'],
                    'sku'   => $p['sku'] ?? '',
                    'image' => $p['images'][0]['src'] ?? '',
                ];
            }
        }
    }
    return $out;
}

$us_out_items = ubo_alert_out_of_stock( $us_all );
$in_out_items = ubo_alert_out_of_stock( $in_all );
$us_total     = is_array( $us_all ) && ! isset( $us_all['error'] ) ? count( $us_all ) : 0;
$in_total     = is_array( $in_all ) && ! isset( $in_all['error'] ) ? count( $in_all ) : 0;
$total_out    = count( $us_out_items ) + count( $in_out_items );
$total_low    = count( $us_low ) + count( $in_low );
$total_healthy = max( 0, $us_total + $in_total - $total_out - $total_low );

// Active store tab from URL, default US
$active_store = in_array( sanitize_text_field( wp_unslash( $_GET['store'] ?? '' ) ), [ 'US', 'India' ] )
    ? sanitize_text_field( wp_unslash( $_GET['store'] ) )
    : 'US';
?>
<div class="ubo-v2-wrap ubo-alerts-wrap">

    <!-- ── Header ── -->
    <div class="ubo-v2-header">
        <div class="ubo-v2-header-left">
            <div class="ubo-v2-logo">🔔</div>
            <div>
                <div class="ubo-v2-title">Stock Alerts &amp; Insights</div>
                <div class="ubo-v2-subtitle">
                    Low stock threshold: <strong><?php echo $threshold; ?> units</strong> —
                    <a href="<?php echo esc_url( admin_url('admin.php?page=ubo-settings') ); ?>" style="color:#635bff;">change in Settings</a>
                </div>
            </div>
        </div>
        <div class="ubo-v2-header-right">
            <a href="<?php echo esc_url( admin_url('admin.php?page=ubo-dashboard') ); ?>" class="ubo-v2-btn">← Dashboard</a>
            <button id="ubo-refresh-btn" class="ubo-v2-btn ubo-v2-btn-refresh" title="Clear cache &amp; reload fresh data">↻ Refresh</button>
        </div>
    </div>

    <!-- ── KPI strip ── -->
    <div class="ubo-alert-kpi-row">
        <div class="ubo-alert-kpi ubo-alert-kpi-red">
            <div class="ubo-alert-kpi-icon">⛔</div>
            <div>
                <div class="ubo-alert-kpi-val"><?php echo $total_out; ?></div>
                <div class="ubo-alert-kpi-label">Out of Stock</div>
            </div>
            <div class="ubo-alert-kpi-split">
                <span>🇺🇸 <?php echo count( $us_out_items ); ?></span>
                <span>🇮🇳 <?php echo count( $in_out_items ); ?></span>
            </div>
        </div>
        <div class="ubo-alert-kpi ubo-alert-kpi-amber">
            <div class="ubo-alert-kpi-icon">⚠️</div>
            <div>
                <div class="ubo-alert-kpi-val"><?php echo $total_low; ?></div>
                <div class="ubo-alert-kpi-label">Low Stock</div>
            </div>
            <div class="ubo-alert-kpi-split">
                <span>🇺🇸 <?php echo count( $us_low ); ?></span>
                <span>🇮🇳 <?php echo count( $in_low ); ?></span>
            </div>
        </div>
        <div class="ubo-alert-kpi ubo-alert-kpi-green">
            <div class="ubo-alert-kpi-icon">✅</div>
            <div>
                <div class="ubo-alert-kpi-val"><?php echo $total_healthy; ?></div>
                <div class="ubo-alert-kpi-label">Healthy Stock</div>
            </div>
        </div>
        <div class="ubo-alert-kpi ubo-alert-kpi-blue">
            <div class="ubo-alert-kpi-icon">📦</div>
            <div>
                <div class="ubo-alert-kpi-val"><?php echo $us_total + $in_total; ?></div>
                <div class="ubo-alert-kpi-label">Total Products</div>
            </div>
            <div class="ubo-alert-kpi-split">
                <span>🇺🇸 <?php echo $us_total; ?></span>
                <span>🇮🇳 <?php echo $in_total; ?></span>
            </div>
        </div>
    </div>

    <!-- ── Store switcher tabs ── -->
    <div class="ubo-store-switcher">
        <button class="ubo-store-tab <?php echo $active_store === 'US' ? 'active' : ''; ?>" data-store="US">
            🇺🇸 US Store
            <?php $us_issues = count( $us_out_items ) + count( $us_low ); if ( $us_issues ) : ?>
            <span class="ubo-store-tab-badge"><?php echo $us_issues; ?></span>
            <?php endif; ?>
        </button>
        <button class="ubo-store-tab <?php echo $active_store === 'India' ? 'active' : ''; ?>" data-store="India">
            🇮🇳 India Store
            <?php $in_issues = count( $in_out_items ) + count( $in_low ); if ( $in_issues ) : ?>
            <span class="ubo-store-tab-badge"><?php echo $in_issues; ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- ── US Store panel ── -->
    <div class="ubo-store-panel <?php echo $active_store === 'US' ? 'active' : ''; ?>" data-panel="US">
        <div class="ubo-alerts-grid">

            <div class="ubo-alert-card-wrap">
                <div class="ubo-v2-section-title">⛔ Out of Stock</div>
                <div class="ubo-v2-card ubo-alert-card ubo-alert-card-red">
                    <div class="ubo-v2-card-header">
                        <span>Out of Stock Products</span>
                        <span class="ubo-alert-badge ubo-alert-badge-red"><?php echo count( $us_out_items ); ?> items</span>
                    </div>
                    <?php if ( empty( $us_out_items ) ) : ?>
                        <div class="ubo-v2-empty"><span>✅</span>All in stock</div>
                    <?php else : ?>
                        <div class="ubo-alert-list">
                            <?php foreach ( $us_out_items as $p ) : ?>
                            <div class="ubo-alert-row">
                                <div class="ubo-alert-severity ubo-alert-severity-red"></div>
                                <?php echo ubo_thumb( $p['image'] ?? '', 28, $p['name'] ); ?>
                                <div class="ubo-alert-row-info">
                                    <div class="ubo-alert-row-name"><?php echo esc_html( $p['name'] ); ?></div>
                                    <?php if ( ! empty( $p['sku'] ) ) : ?><div class="ubo-alert-row-sku"><?php echo esc_html( $p['sku'] ); ?></div><?php endif; ?>
                                </div>
                                <span class="ubo-alert-badge ubo-alert-badge-red">0</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ubo-alert-card-wrap">
                <div class="ubo-v2-section-title">⚠️ Low Stock <span style="font-size:11px;font-weight:400;color:#8792a2;">≤ <?php echo $threshold; ?></span></div>
                <div class="ubo-v2-card ubo-alert-card ubo-alert-card-amber">
                    <div class="ubo-v2-card-header">
                        <span>Low Stock Products</span>
                        <span class="ubo-alert-badge ubo-alert-badge-amber"><?php echo count( $us_low ); ?> items</span>
                    </div>
                    <?php if ( empty( $us_low ) ) : ?>
                        <div class="ubo-v2-empty"><span>✅</span>None low</div>
                    <?php else : ?>
                        <div class="ubo-alert-list">
                            <?php foreach ( $us_low as $item ) :
                                $pct = $threshold > 0 ? round( ( $item['qty'] / $threshold ) * 100 ) : 0;
                                $bc  = $item['qty'] <= 2 ? '#ef4444' : ( $item['qty'] <= 5 ? '#f97316' : '#f59e0b' );
                            ?>
                            <div class="ubo-alert-row">
                                <div class="ubo-alert-severity ubo-alert-severity-amber"></div>
                                <?php echo ubo_thumb( $item['image'] ?? '', 28, $item['name'] ); ?>
                                <div class="ubo-alert-row-info">
                                    <div class="ubo-alert-row-name"><?php echo esc_html( $item['name'] ); ?></div>
                                    <?php if ( ! empty( $item['sku'] ) ) : ?><div class="ubo-alert-row-sku"><?php echo esc_html( $item['sku'] ); ?></div><?php endif; ?>
                                    <div class="ubo-alert-stock-bar-wrap"><div class="ubo-alert-stock-bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $bc; ?>;"></div></div>
                                </div>
                                <span class="ubo-alert-badge" style="background:#fff7ed;color:#c2410c;"><?php echo (int)$item['qty']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="ubo-v2-section-title" style="margin-top:8px;">📊 Sales Insights — 🇺🇸 US Store <span style="font-size:11px;font-weight:400;color:#8792a2;">(This Year)</span></div>
        <?php ubo_render_insights( $us_insights, 'us' ); ?>
    </div>

    <!-- ── India Store panel ── -->
    <div class="ubo-store-panel <?php echo $active_store === 'India' ? 'active' : ''; ?>" data-panel="India">
        <div class="ubo-alerts-grid">

            <div class="ubo-alert-card-wrap">
                <div class="ubo-v2-section-title">⛔ Out of Stock</div>
                <div class="ubo-v2-card ubo-alert-card ubo-alert-card-red">
                    <div class="ubo-v2-card-header">
                        <span>Out of Stock Products</span>
                        <span class="ubo-alert-badge ubo-alert-badge-red"><?php echo count( $in_out_items ); ?> items</span>
                    </div>
                    <?php if ( empty( $in_out_items ) ) : ?>
                        <div class="ubo-v2-empty"><span>✅</span>All in stock</div>
                    <?php else : ?>
                        <div class="ubo-alert-list">
                            <?php foreach ( $in_out_items as $p ) : ?>
                            <div class="ubo-alert-row">
                                <div class="ubo-alert-severity ubo-alert-severity-red"></div>
                                <?php echo ubo_thumb( $p['image'] ?? '', 28, $p['name'] ); ?>
                                <div class="ubo-alert-row-info">
                                    <div class="ubo-alert-row-name"><?php echo esc_html( $p['name'] ); ?></div>
                                    <?php if ( ! empty( $p['sku'] ) ) : ?><div class="ubo-alert-row-sku"><?php echo esc_html( $p['sku'] ); ?></div><?php endif; ?>
                                </div>
                                <span class="ubo-alert-badge ubo-alert-badge-red">0</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ubo-alert-card-wrap">
                <div class="ubo-v2-section-title">⚠️ Low Stock <span style="font-size:11px;font-weight:400;color:#8792a2;">≤ <?php echo $threshold; ?></span></div>
                <div class="ubo-v2-card ubo-alert-card ubo-alert-card-amber">
                    <div class="ubo-v2-card-header">
                        <span>Low Stock Products</span>
                        <span class="ubo-alert-badge ubo-alert-badge-amber"><?php echo count( $in_low ); ?> items</span>
                    </div>
                    <?php if ( empty( $in_low ) ) : ?>
                        <div class="ubo-v2-empty"><span>✅</span>None low</div>
                    <?php else : ?>
                        <div class="ubo-alert-list">
                            <?php foreach ( $in_low as $item ) :
                                $pct = $threshold > 0 ? round( ( $item['qty'] / $threshold ) * 100 ) : 0;
                                $bc  = $item['qty'] <= 2 ? '#ef4444' : ( $item['qty'] <= 5 ? '#f97316' : '#f59e0b' );
                            ?>
                            <div class="ubo-alert-row">
                                <div class="ubo-alert-severity ubo-alert-severity-amber"></div>
                                <?php echo ubo_thumb( $item['image'] ?? '', 28, $item['name'] ); ?>
                                <div class="ubo-alert-row-info">
                                    <div class="ubo-alert-row-name"><?php echo esc_html( $item['name'] ); ?></div>
                                    <?php if ( ! empty( $item['sku'] ) ) : ?><div class="ubo-alert-row-sku"><?php echo esc_html( $item['sku'] ); ?></div><?php endif; ?>
                                    <div class="ubo-alert-stock-bar-wrap"><div class="ubo-alert-stock-bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $bc; ?>;"></div></div>
                                </div>
                                <span class="ubo-alert-badge" style="background:#fff7ed;color:#c2410c;"><?php echo (int)$item['qty']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="ubo-v2-section-title" style="margin-top:8px;">📊 Sales Insights — 🇮🇳 India Store <span style="font-size:11px;font-weight:400;color:#8792a2;">(This Year)</span></div>
        <?php ubo_render_insights( $in_insights, 'in' ); ?>
    </div>

</div>

<?php
function ubo_render_insights( $ins, $prefix ) {
    $products = $ins['products'] ?? [];
    $colors   = $ins['colors']   ?? [];
    $sizes    = $ins['sizes']    ?? [];
    $max_p    = $products ? max( array_values( $products ) ) : 1;
    $max_c    = $colors   ? max( array_values( $colors ) )   : 1;
    $total_sz = array_sum( $sizes ) ?: 1;
    ?>
    <div class="ubo-ins-three-col">

        <!-- Best Products -->
        <div class="ubo-v2-card">
            <div class="ubo-v2-card-header"><span>🏆 Best-Selling Products</span></div>
            <div class="ubo-ins-list">
                <?php if ( empty( $products ) ) : ?>
                    <div class="ubo-v2-empty">No sales data available</div>
                <?php else : $i = 0; foreach ( $products as $name => $qty ) : $i++;
                    $pct = round( ( $qty / $max_p ) * 100 ); ?>
                <div class="ubo-ins-row">
                    <span class="ubo-v2-rank-num"><?php echo $i; ?></span>
                    <div class="ubo-ins-info">
                        <div class="ubo-ins-name"><?php echo esc_html( $name ); ?></div>
                        <div class="ubo-ins-bar-wrap"><div class="ubo-ins-bar-fill ubo-ins-bar-purple" style="width:<?php echo $pct; ?>%;"></div></div>
                    </div>
                    <span class="ubo-v2-rank-badge"><?php echo $qty; ?> sold</span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Best Colors -->
        <div class="ubo-v2-card">
            <div class="ubo-v2-card-header"><span>🎨 Best-Selling Colors</span></div>
            <div class="ubo-ins-list">
                <?php if ( empty( $colors ) ) : ?>
                    <div class="ubo-v2-empty" style="font-size:11px;">No color data — check variation attribute names include "color"</div>
                <?php else : foreach ( $colors as $color => $qty ) :
                    $pct = round( ( $qty / $max_c ) * 100 ); ?>
                <div class="ubo-ins-row">
                    <span class="ubo-ins-color-dot" style="background:<?php echo esc_attr( ubo_color_hex( $color ) ); ?>;"></span>
                    <div class="ubo-ins-info">
                        <div class="ubo-ins-name"><?php echo esc_html( $color ); ?></div>
                        <div class="ubo-ins-bar-wrap"><div class="ubo-ins-bar-fill ubo-ins-bar-pink" style="width:<?php echo $pct; ?>%;"></div></div>
                    </div>
                    <span class="ubo-v2-rank-badge"><?php echo $qty; ?> sold</span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Best Sizes -->
        <div class="ubo-v2-card">
            <div class="ubo-v2-card-header"><span>📐 Best-Selling Sizes</span></div>
            <div class="ubo-ins-list">
                <?php if ( empty( $sizes ) ) : ?>
                    <div class="ubo-v2-empty" style="font-size:11px;">No size data — check variation attribute names include "size"</div>
                <?php else : foreach ( $sizes as $size => $qty ) :
                    $pct = round( ( $qty / $total_sz ) * 100 ); ?>
                <div class="ubo-ins-size-row">
                    <span class="ubo-ins-size-tag"><?php echo esc_html( strtoupper( $size ) ); ?></span>
                    <div class="ubo-ins-bar-wrap" style="flex:1;">
                        <div class="ubo-ins-bar-fill ubo-ins-bar-teal" style="width:<?php echo $pct; ?>%;"></div>
                    </div>
                    <span class="ubo-ins-size-pct"><?php echo $pct; ?>%</span>
                    <span class="ubo-v2-rank-badge" style="background:#f0fdf4;color:#166534;"><?php echo $qty; ?></span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

    </div>
    <?php
}
?>

<script>
(function($) {
    // Store tab switcher
    $(document).on('click', '.ubo-store-tab', function() {
        var store = $(this).data('store');
        $('.ubo-store-tab').removeClass('active');
        $(this).addClass('active');
        $('.ubo-store-panel').removeClass('active');
        $('[data-panel="' + store + '"]').addClass('active');
    });

    // Refresh button
    $(document).on('click', '#ubo-refresh-btn', function() {
        var $btn = $(this);
        $btn.text('↻ Refreshing…').prop('disabled', true);
        $.post(uboAdmin.ajaxUrl, { action: 'ubo_refresh_cache', nonce: uboAdmin.nonce }, function() {
            $btn.text('✓ Done!');
            setTimeout(function() { location.reload(); }, 600);
        }, 'json').fail(function() {
            $btn.text('↻ Refresh').prop('disabled', false);
        });
    });
})(jQuery);
</script>
