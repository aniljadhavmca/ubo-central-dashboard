<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$threshold = (int) get_option( 'ubo_low_stock_threshold', UBO_LOW_STOCK_THRESHOLD );

$us_out  = UBO_Inventory::fetch( 'US',    [ 'stock_status' => 'outofstock', 'per_page' => 100 ] );
$in_out  = UBO_Inventory::fetch( 'India', [ 'stock_status' => 'outofstock', 'per_page' => 100 ] );
$us_all  = UBO_Inventory::fetch( 'US',    [ 'per_page' => 100 ] );
$in_all  = UBO_Inventory::fetch( 'India', [ 'per_page' => 100 ] );

function ubo_get_low_stock( $products, $threshold ) {
    $low = [];
    if ( ! is_array( $products ) || isset( $products['error'] ) ) return $low;
    foreach ( $products as $p ) {
        if ( $p['type'] === 'variable' && ! empty( $p['variations_data'] ) ) {
            foreach ( $p['variations_data'] as $v ) {
                $qty = (int) ( $v['stock_quantity'] ?? 0 );
                if ( $qty > 0 && $qty <= $threshold ) {
                    $attrs = implode( ' / ', array_map( fn($a) => $a['option'], $v['attributes'] ?? [] ) );
                    $low[] = [ 'name' => $p['name'] . ' — ' . $attrs, 'sku' => $v['sku'], 'qty' => $qty ];
                }
            }
        } else {
            $qty = (int) ( $p['stock_quantity'] ?? 0 );
            if ( $qty > 0 && $qty <= $threshold ) {
                $low[] = [ 'name' => $p['name'], 'sku' => $p['sku'], 'qty' => $qty ];
            }
        }
    }
    usort( $low, fn( $a, $b ) => $a['qty'] - $b['qty'] );
    return $low;
}

$us_low = ubo_get_low_stock( $us_all, $threshold );
$in_low = ubo_get_low_stock( $in_all, $threshold );
?>
<style>
.ubo-wrap { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 24px 20px; max-width: 1200px; }
.ubo-wrap h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 6px; }
.ubo-threshold { font-size: 13px; color: #64748b; margin-bottom: 24px; }
.ubo-threshold a { color: #6366f1; }
.ubo-alert-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 32px; }
.ubo-panel { background: #fff; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
.ubo-panel h2 { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.ubo-panel h2 .count { font-size: 12px; background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 20px; }
.ubo-panel h2 .count-warn { background: #fef3c7; color: #d97706; }
.ubo-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.ubo-table th { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; padding: 0 8px 8px; border-bottom: 1px solid #f1f5f9; text-align: left; }
.ubo-table td { padding: 8px; border-bottom: 1px solid #f8fafc; color: #334155; }
.ubo-table tr:last-child td { border-bottom: none; }
.qty-zero { color: #dc2626; font-weight: 700; }
.qty-low  { color: #d97706; font-weight: 700; }
.ubo-empty { color: #94a3b8; font-size: 13px; padding: 12px 0; }
</style>

<div class="ubo-wrap">
    <h1>🚨 Stock Alerts</h1>
    <p class="ubo-threshold">Low stock threshold: <strong><?php echo $threshold; ?> units</strong> — <a href="<?php echo admin_url('admin.php?page=ubo-settings'); ?>">change in Settings</a></p>

    <!-- Out of Stock -->
    <div class="ubo-alert-grid">
        <?php foreach ( [ 'US' => [ '🇺🇸', $us_out ], 'India' => [ '🇮🇳', $in_out ] ] as $label => [ $flag, $products ] ) :
            $items = is_array( $products ) && ! isset( $products['error'] ) ? $products : [];
        ?>
        <div class="ubo-panel">
            <h2><?php echo $flag; ?> <?php echo $label; ?> — Out of Stock <span class="count"><?php echo count( $items ); ?></span></h2>
            <?php if ( empty( $items ) ) : ?>
                <p class="ubo-empty">✅ No out-of-stock products.</p>
            <?php else : ?>
                <table class="ubo-table">
                    <thead><tr><th>Product</th><th>SKU</th></tr></thead>
                    <tbody>
                    <?php foreach ( $items as $p ) : ?>
                        <tr>
                            <td><?php echo esc_html( $p['name'] ); ?></td>
                            <td><code style="font-size:11px;"><?php echo esc_html( $p['sku'] ); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Low Stock -->
    <div class="ubo-alert-grid">
        <?php foreach ( [ 'US' => [ '🇺🇸', $us_low ], 'India' => [ '🇮🇳', $in_low ] ] as $label => [ $flag, $items ] ) : ?>
        <div class="ubo-panel">
            <h2><?php echo $flag; ?> <?php echo $label; ?> — Low Stock <span class="count count-warn"><?php echo count( $items ); ?></span></h2>
            <?php if ( empty( $items ) ) : ?>
                <p class="ubo-empty">✅ No low-stock products.</p>
            <?php else : ?>
                <table class="ubo-table">
                    <thead><tr><th>Product</th><th>SKU</th><th>Qty</th></tr></thead>
                    <tbody>
                    <?php foreach ( $items as $item ) : ?>
                        <tr>
                            <td><?php echo esc_html( $item['name'] ); ?></td>
                            <td><code style="font-size:11px;"><?php echo esc_html( $item['sku'] ); ?></code></td>
                            <td class="qty-low"><?php echo esc_html( $item['qty'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
