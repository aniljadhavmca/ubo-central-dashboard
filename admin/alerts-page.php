<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$threshold = (int) get_option( 'ubo_low_stock_threshold', UBO_LOW_STOCK_THRESHOLD );
$us_out    = UBO_Inventory::fetch( 'US',    [ 'stock_status' => 'outofstock', 'per_page' => 100 ] );
$in_out    = UBO_Inventory::fetch( 'India', [ 'stock_status' => 'outofstock', 'per_page' => 100 ] );
$us_all    = UBO_Inventory::fetch( 'US',    [ 'per_page' => 100 ] );
$in_all    = UBO_Inventory::fetch( 'India', [ 'per_page' => 100 ] );

$us_insights = UBO_Orders::get_insights( 'US',    8 );
$in_insights = UBO_Orders::get_insights( 'India', 8 );

if ( ! function_exists( 'ubo_alert_low_stock' ) ) {
    function ubo_alert_low_stock( $products, $threshold ) {
        $low = [];
        if ( ! is_array( $products ) || isset( $products['error'] ) ) return $low;
        foreach ( $products as $p ) {
            if ( $p['type'] === 'variable' && ! empty( $p['variations_data'] ) ) {
                foreach ( $p['variations_data'] as $v ) {
                    $qty = (int)( $v['stock_quantity'] ?? 0 );
                    if ( $qty > 0 && $qty <= $threshold ) {
                        $attrs = implode( ' / ', array_map( fn($a) => $a['option'], $v['attributes'] ?? [] ) );
                        $low[] = [ 'name' => $p['name'] . ( $attrs ? ' — ' . $attrs : '' ), 'sku' => $v['sku'], 'qty' => $qty ];
                    }
                }
            } else {
                $qty = (int)( $p['stock_quantity'] ?? 0 );
                if ( $qty > 0 && $qty <= $threshold )
                    $low[] = [ 'name' => $p['name'], 'sku' => $p['sku'], 'qty' => $qty ];
            }
        }
        usort( $low, fn( $a, $b ) => $a['qty'] - $b['qty'] );
        return $low;
    }
}

$us_low = ubo_alert_low_stock( $us_all, $threshold );
$in_low = ubo_alert_low_stock( $in_all, $threshold );

$us_out_items = is_array( $us_out ) && ! isset( $us_out['error'] ) ? $us_out : [];
$in_out_items = is_array( $in_out ) && ! isset( $in_out['error'] ) ? $in_out : [];

$total_out = count( $us_out_items ) + count( $in_out_items );
$total_low = count( $us_low ) + count( $in_low );
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
        </div>
    </div>

    <!-- ── Alert KPI strip ── -->
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
                <div class="ubo-alert-kpi-val"><?php
                    $us_in  = is_array( $us_all ) && ! isset( $us_all['error'] ) ? count( $us_all ) : 0;
                    $in_in  = is_array( $in_all ) && ! isset( $in_all['error'] ) ? count( $in_all ) : 0;
                    echo max( 0, $us_in + $in_in - $total_out - $total_low );
                ?></div>
                <div class="ubo-alert-kpi-label">Healthy Stock</div>
            </div>
        </div>
        <div class="ubo-alert-kpi ubo-alert-kpi-blue">
            <div class="ubo-alert-kpi-icon">📦</div>
            <div>
                <div class="ubo-alert-kpi-val"><?php echo $us_in + $in_in; ?></div>
                <div class="ubo-alert-kpi-label">Total Products</div>
            </div>
            <div class="ubo-alert-kpi-split">
                <span>🇺🇸 <?php echo $us_in; ?></span>
                <span>🇮🇳 <?php echo $in_in; ?></span>
            </div>
        </div>
    </div>

    <!-- ── Out of Stock ── -->
    <div class="ubo-v2-section-title">⛔ Out of Stock</div>
    <div class="ubo-v2-two-col">
        <?php foreach ( [ 'US' => [ '🇺🇸', $us_out_items ], 'India' => [ '🇮🇳', $in_out_items ] ] as $label => [ $flag, $items ] ) : ?>
        <div class="ubo-v2-card ubo-alert-card ubo-alert-card-red">
            <div class="ubo-v2-card-header">
                <span><?php echo $flag; ?> <?php echo esc_html( $label ); ?> Store</span>
                <span class="ubo-alert-badge ubo-alert-badge-red"><?php echo count( $items ); ?> items</span>
            </div>
            <?php if ( empty( $items ) ) : ?>
                <div class="ubo-v2-empty">
                    <span style="font-size:24px;">✅</span><br>All products in stock
                </div>
            <?php else : ?>
                <div class="ubo-alert-list">
                    <?php foreach ( $items as $p ) : ?>
                    <div class="ubo-alert-row">
                        <div class="ubo-alert-severity ubo-alert-severity-red"></div>
                        <div class="ubo-alert-row-info">
                            <div class="ubo-alert-row-name"><?php echo esc_html( $p['name'] ); ?></div>
                            <?php if ( $p['sku'] ) : ?>
                            <div class="ubo-alert-row-sku"><?php echo esc_html( $p['sku'] ); ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="ubo-alert-badge ubo-alert-badge-red">0 units</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Low Stock ── -->
    <div class="ubo-v2-section-title">⚠️ Low Stock <span style="font-size:11px;font-weight:400;color:#8792a2;">(≤ <?php echo $threshold; ?> units)</span></div>
    <div class="ubo-v2-two-col">
        <?php foreach ( [ 'US' => [ '🇺🇸', $us_low ], 'India' => [ '🇮🇳', $in_low ] ] as $label => [ $flag, $items ] ) : ?>
        <div class="ubo-v2-card ubo-alert-card ubo-alert-card-amber">
            <div class="ubo-v2-card-header">
                <span><?php echo $flag; ?> <?php echo esc_html( $label ); ?> Store</span>
                <span class="ubo-alert-badge ubo-alert-badge-amber"><?php echo count( $items ); ?> items</span>
            </div>
            <?php if ( empty( $items ) ) : ?>
                <div class="ubo-v2-empty">
                    <span style="font-size:24px;">✅</span><br>No low-stock products
                </div>
            <?php else : ?>
                <div class="ubo-alert-list">
                    <?php foreach ( $items as $item ) :
                        $pct = $threshold > 0 ? round( ( $item['qty'] / $threshold ) * 100 ) : 0;
                        $bar_color = $item['qty'] <= 2 ? '#ef4444' : ( $item['qty'] <= 5 ? '#f97316' : '#f59e0b' );
                    ?>
                    <div class="ubo-alert-row">
                        <div class="ubo-alert-severity ubo-alert-severity-amber"></div>
                        <div class="ubo-alert-row-info">
                            <div class="ubo-alert-row-name"><?php echo esc_html( $item['name'] ); ?></div>
                            <?php if ( $item['sku'] ) : ?>
                            <div class="ubo-alert-row-sku"><?php echo esc_html( $item['sku'] ); ?></div>
                            <?php endif; ?>
                            <div class="ubo-alert-stock-bar-wrap">
                                <div class="ubo-alert-stock-bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $bar_color; ?>;"></div>
                            </div>
                        </div>
                        <span class="ubo-alert-badge" style="background:#fff7ed;color:#c2410c;"><?php echo (int) $item['qty']; ?> left</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Insights ── -->
    <div class="ubo-v2-section-title" style="margin-top:32px;">📊 Sales Insights <span style="font-size:11px;font-weight:400;color:#8792a2;">(This Year · Completed &amp; Processing orders)</span></div>

    <!-- Best Products + Best Colors -->
    <div class="ubo-v2-two-col">

        <!-- Best-Selling Products -->
        <div class="ubo-v2-card">
            <div class="ubo-v2-card-header"><span>🏆 Best-Selling Products</span></div>
            <div class="ubo-v2-rank-tabs">
                <button class="ubo-v2-tab active" data-target="ubo-ins-prod-us">🇺🇸 US</button>
                <button class="ubo-v2-tab" data-target="ubo-ins-prod-in">🇮🇳 India</button>
            </div>
            <div class="ubo-v2-tab-panels-wrap">
                <?php foreach ( [ 'us' => $us_insights, 'in' => $in_insights ] as $key => $ins ) :
                    $products = $ins['products'] ?? [];
                    $max = $products ? max( array_values( $products ) ) : 1;
                ?>
                <div id="ubo-ins-prod-<?php echo $key; ?>" class="ubo-v2-tab-panel <?php echo $key === 'us' ? 'active' : ''; ?>">
                    <?php if ( empty( $products ) ) : ?>
                        <div class="ubo-v2-empty">No sales data available</div>
                    <?php else : ?>
                        <?php $i = 0; foreach ( $products as $name => $qty ) : $i++;
                            $pct = $max > 0 ? round( ( $qty / $max ) * 100 ) : 0; ?>
                        <div class="ubo-ins-row">
                            <span class="ubo-v2-rank-num"><?php echo $i; ?></span>
                            <div class="ubo-ins-info">
                                <div class="ubo-ins-name"><?php echo esc_html( $name ); ?></div>
                                <div class="ubo-ins-bar-wrap"><div class="ubo-ins-bar-fill ubo-ins-bar-purple" style="width:<?php echo $pct; ?>%;"></div></div>
                            </div>
                            <span class="ubo-v2-rank-badge"><?php echo $qty; ?> sold</span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Best-Selling Colors -->
        <div class="ubo-v2-card">
            <div class="ubo-v2-card-header"><span>🎨 Best-Selling Colors</span></div>
            <div class="ubo-v2-rank-tabs">
                <button class="ubo-v2-tab active" data-target="ubo-ins-col-us">🇺🇸 US</button>
                <button class="ubo-v2-tab" data-target="ubo-ins-col-in">🇮🇳 India</button>
            </div>
            <div class="ubo-v2-tab-panels-wrap">
                <?php foreach ( [ 'us' => $us_insights, 'in' => $in_insights ] as $key => $ins ) :
                    $colors = $ins['colors'] ?? [];
                    $max = $colors ? max( array_values( $colors ) ) : 1;
                ?>
                <div id="ubo-ins-col-<?php echo $key; ?>" class="ubo-v2-tab-panel <?php echo $key === 'us' ? 'active' : ''; ?>">
                    <?php if ( empty( $colors ) ) : ?>
                        <div class="ubo-v2-empty">No color data available</div>
                    <?php else : ?>
                        <?php $i = 0; foreach ( $colors as $color => $qty ) : $i++;
                            $pct = $max > 0 ? round( ( $qty / $max ) * 100 ) : 0; ?>
                        <div class="ubo-ins-row">
                            <span class="ubo-ins-color-dot" style="background:<?php echo esc_attr( ubo_color_hex( $color ) ); ?>;"></span>
                            <div class="ubo-ins-info">
                                <div class="ubo-ins-name"><?php echo esc_html( $color ); ?></div>
                                <div class="ubo-ins-bar-wrap"><div class="ubo-ins-bar-fill ubo-ins-bar-pink" style="width:<?php echo $pct; ?>%;"></div></div>
                            </div>
                            <span class="ubo-v2-rank-badge"><?php echo $qty; ?> sold</span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- Best Sizes + Orders by Country -->
    <div class="ubo-v2-two-col">

        <!-- Best-Selling Sizes -->
        <div class="ubo-v2-card">
            <div class="ubo-v2-card-header"><span>📐 Best-Selling Sizes</span></div>
            <div class="ubo-v2-rank-tabs">
                <button class="ubo-v2-tab active" data-target="ubo-ins-sz-us">🇺🇸 US</button>
                <button class="ubo-v2-tab" data-target="ubo-ins-sz-in">🇮🇳 India</button>
            </div>
            <div class="ubo-v2-tab-panels-wrap">
                <?php foreach ( [ 'us' => $us_insights, 'in' => $in_insights ] as $key => $ins ) :
                    $sizes = $ins['sizes'] ?? [];
                    $total_sizes = array_sum( $sizes ) ?: 1;
                ?>
                <div id="ubo-ins-sz-<?php echo $key; ?>" class="ubo-v2-tab-panel <?php echo $key === 'us' ? 'active' : ''; ?>">
                    <?php if ( empty( $sizes ) ) : ?>
                        <div class="ubo-v2-empty">No size data available</div>
                    <?php else : ?>
                        <?php foreach ( $sizes as $size => $qty ) :
                            $pct = round( ( $qty / $total_sizes ) * 100 ); ?>
                        <div class="ubo-ins-size-row">
                            <span class="ubo-ins-size-tag"><?php echo esc_html( strtoupper( $size ) ); ?></span>
                            <div class="ubo-ins-bar-wrap" style="flex:1;">
                                <div class="ubo-ins-bar-fill ubo-ins-bar-teal" style="width:<?php echo $pct; ?>%;"></div>
                            </div>
                            <span class="ubo-ins-size-pct"><?php echo $pct; ?>%</span>
                            <span class="ubo-v2-rank-badge" style="background:#f0fdf4;color:#166534;"><?php echo $qty; ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Orders by Country -->
        <div class="ubo-v2-card">
            <div class="ubo-v2-card-header"><span>🌍 Orders by Country</span></div>
            <div class="ubo-v2-rank-tabs">
                <button class="ubo-v2-tab active" data-target="ubo-ins-cty-us">🇺🇸 US</button>
                <button class="ubo-v2-tab" data-target="ubo-ins-cty-in">🇮🇳 India</button>
            </div>
            <div class="ubo-v2-tab-panels-wrap">
                <?php foreach ( [ 'us' => $us_insights, 'in' => $in_insights ] as $key => $ins ) :
                    $countries = $ins['countries'] ?? [];
                    $total_cty = array_sum( $countries ) ?: 1;
                ?>
                <div id="ubo-ins-cty-<?php echo $key; ?>" class="ubo-v2-tab-panel <?php echo $key === 'us' ? 'active' : ''; ?>">
                    <?php if ( empty( $countries ) ) : ?>
                        <div class="ubo-v2-empty">No country data available</div>
                    <?php else : ?>
                        <?php foreach ( $countries as $code => $count ) :
                            $pct  = round( ( $count / $total_cty ) * 100 );
                            $flag = ubo_country_flag( $code );
                        ?>
                        <div class="ubo-ins-row">
                            <span class="ubo-ins-flag"><?php echo $flag; ?></span>
                            <div class="ubo-ins-info">
                                <div class="ubo-ins-name"><?php echo esc_html( $code ); ?> <span style="color:#8792a2;font-size:11px;"><?php echo $pct; ?>%</span></div>
                                <div class="ubo-ins-bar-wrap"><div class="ubo-ins-bar-fill ubo-ins-bar-blue" style="width:<?php echo $pct; ?>%;"></div></div>
                            </div>
                            <span class="ubo-v2-rank-badge" style="background:#eff6ff;color:#1d4ed8;"><?php echo $count; ?> orders</span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

</div>

<?php
// Helper: map common color names to hex for the dot
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

// Helper: country code to flag emoji
function ubo_country_flag( $code ) {
    $code = strtoupper( $code );
    if ( strlen( $code ) !== 2 ) return '🌐';
    $flag = '';
    foreach ( str_split( $code ) as $c ) {
        $flag .= mb_chr( 0x1F1E6 + ord( $c ) - ord( 'A' ) );
    }
    return $flag;
}
?>

<script>
(function($) {
    $(document).on('click', '.ubo-v2-rank-tabs .ubo-v2-tab', function() {
        var $card = $(this).closest('.ubo-v2-card');
        $card.find('.ubo-v2-tab').removeClass('active');
        $card.find('.ubo-v2-tab-panel').removeClass('active');
        $(this).addClass('active');
        $('#' + $(this).data('target')).addClass('active');
    });
})(jQuery);
</script>
