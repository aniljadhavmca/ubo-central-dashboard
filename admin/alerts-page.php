<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$threshold = (int) get_option( 'ubo_low_stock_threshold', UBO_LOW_STOCK_THRESHOLD );
$us_out    = UBO_Inventory::fetch( 'US',    [ 'stock_status' => 'outofstock', 'per_page' => 100 ] );
$in_out    = UBO_Inventory::fetch( 'India', [ 'stock_status' => 'outofstock', 'per_page' => 100 ] );
$us_all    = UBO_Inventory::fetch( 'US',    [ 'per_page' => 100 ] );
$in_all    = UBO_Inventory::fetch( 'India', [ 'per_page' => 100 ] );

function ubo_get_low_stock( $products, $threshold ) {
    $low = [];
    if ( ! is_array( $products ) || isset( $products['error'] ) ) return $low;
    foreach ( $products as $p ) {
        if ( $p['type'] === 'variable' && ! empty( $p['variations_data'] ) ) {
            foreach ( $p['variations_data'] as $v ) {
                $qty = (int)( $v['stock_quantity'] ?? 0 );
                if ( $qty > 0 && $qty <= $threshold ) {
                    $attrs = implode( ' / ', array_map( fn($a) => $a['option'], $v['attributes'] ?? [] ) );
                    $low[] = [ 'name' => $p['name'] . ' — ' . $attrs, 'sku' => $v['sku'], 'qty' => $qty ];
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

$us_low = ubo_get_low_stock( $us_all, $threshold );
$in_low = ubo_get_low_stock( $in_all, $threshold );
?>
<div class="ubo-wrap">

    <div class="ubo-page-header">
        <div>
            <h1>🚨 Stock Alerts</h1>
            <div class="ubo-subtitle">
                Low stock threshold: <strong><?php echo (int) $threshold; ?> units</strong> —
                <a href="<?php echo esc_url( admin_url('admin.php?page=ubo-settings') ); ?>" style="color:#7c3aed;">change in Settings</a>
            </div>
        </div>
    </div>

    <!-- Out of Stock -->
    <div style="font-size:13px;font-weight:700;color:#991b1b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">⛔ Out of Stock</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:28px;">
        <?php foreach ( [ 'US' => [ '🇺🇸', $us_out ], 'India' => [ '🇮🇳', $in_out ] ] as $label => [ $flag, $products ] ) :
            $items = is_array( $products ) && ! isset( $products['error'] ) ? $products : [];
        ?>
        <div class="ubo-panel">
            <div class="ubo-panel-header">
                <h3><?php echo $flag; ?> <?php echo esc_html( $label ); ?> — Out of Stock
                    <span class="ubo-badge ubo-badge-cancelled" style="margin-left:6px;"><?php echo count( $items ); ?></span>
                </h3>
            </div>
            <div class="ubo-panel-body">
                <?php if ( empty( $items ) ) : ?>
                    <div class="ubo-empty-state" style="padding:24px;"><div class="ubo-empty-icon">✅</div><p>All products in stock.</p></div>
                <?php else : ?>
                    <table class="ubo-table">
                        <thead><tr><th>Product</th><th>SKU</th></tr></thead>
                        <tbody>
                        <?php foreach ( $items as $p ) : ?>
                            <tr>
                                <td><?php echo esc_html( $p['name'] ); ?></td>
                                <td><span class="ubo-sku-code"><?php echo esc_html( $p['sku'] ); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Low Stock -->
    <div style="font-size:13px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">⚠️ Low Stock</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <?php foreach ( [ 'US' => [ '🇺🇸', $us_low ], 'India' => [ '🇮🇳', $in_low ] ] as $label => [ $flag, $items ] ) : ?>
        <div class="ubo-panel">
            <div class="ubo-panel-header">
                <h3><?php echo $flag; ?> <?php echo esc_html( $label ); ?> — Low Stock
                    <span class="ubo-badge ubo-badge-processing" style="margin-left:6px;"><?php echo count( $items ); ?></span>
                </h3>
            </div>
            <div class="ubo-panel-body">
                <?php if ( empty( $items ) ) : ?>
                    <div class="ubo-empty-state" style="padding:24px;"><div class="ubo-empty-icon">✅</div><p>No low-stock products.</p></div>
                <?php else : ?>
                    <table class="ubo-table">
                        <thead><tr><th>Product</th><th>SKU</th><th>Qty Left</th></tr></thead>
                        <tbody>
                        <?php foreach ( $items as $item ) : ?>
                            <tr>
                                <td><?php echo esc_html( $item['name'] ); ?></td>
                                <td><span class="ubo-sku-code"><?php echo esc_html( $item['sku'] ); ?></span></td>
                                <td class="qty-low"><?php echo (int) $item['qty']; ?></td>
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
