<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$search = sanitize_text_field( $_GET['search'] ?? '' );

$params = [ 'per_page' => 100 ];
if ( $search ) $params['search'] = $search;

$us_products = UBO_Inventory::fetch( 'US', $params );
$in_products = UBO_Inventory::fetch( 'India', $params );

// Index by SKU
function ubo_index_by_sku( $products ) {
    $index = [];
    if ( ! is_array( $products ) || isset( $products['error'] ) ) return $index;
    foreach ( $products as $p ) {
        if ( $p['type'] === 'variable' && ! empty( $p['variations_data'] ) ) {
            foreach ( $p['variations_data'] as $v ) {
                $sku = $v['sku'] ?: ( $p['sku'] . '-' . $v['id'] );
                $attrs = implode( ' / ', array_map( fn($a) => $a['option'], $v['attributes'] ?? [] ) );
                $index[ $sku ] = [
                    'name'   => $p['name'] . ' — ' . $attrs,
                    'sku'    => $sku,
                    'qty'    => (int) ( $v['stock_quantity'] ?? 0 ),
                    'status' => $v['stock_status'],
                ];
            }
        } else {
            $sku = $p['sku'] ?: 'ID-' . $p['id'];
            $index[ $sku ] = [
                'name'   => $p['name'],
                'sku'    => $sku,
                'qty'    => (int) ( $p['stock_quantity'] ?? 0 ),
                'status' => $p['stock_status'],
            ];
        }
    }
    return $index;
}

$us_index = ubo_index_by_sku( $us_products );
$in_index = ubo_index_by_sku( $in_products );
$all_skus = array_unique( array_merge( array_keys( $us_index ), array_keys( $in_index ) ) );
sort( $all_skus );

$threshold = (int) ( get_option( 'ubo_low_stock_threshold', UBO_LOW_STOCK_THRESHOLD ) );
?>
<style>
.ubo-wrap { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 24px 20px; max-width: 1400px; }
.ubo-wrap h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 20px; }
.ubo-filters { display: flex; gap: 10px; margin-bottom: 20px; }
.ubo-filters input { padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; width: 260px; }
.ubo-table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
.ubo-table th { background: #1e293b; color: #fff; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; }
.ubo-table td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.ubo-table tr:last-child td { border-bottom: none; }
.ubo-table tr:hover td { background: #f8fafc; }
.qty-ok   { color: #16a34a; font-weight: 700; }
.qty-low  { color: #d97706; font-weight: 700; }
.qty-zero { color: #dc2626; font-weight: 700; }
.qty-na   { color: #cbd5e1; }
.avail-calc { font-size: 12px; color: #6366f1; font-weight: 600; }
</style>

<div class="ubo-wrap">
    <h1>📦 SKU Central — US &amp; India Stock</h1>

    <form method="get">
        <input type="hidden" name="page" value="ubo-sku" />
        <div class="ubo-filters">
            <input type="text" name="search" value="<?php echo esc_attr( $search ); ?>" placeholder="Search by product name or SKU…" />
            <button class="button button-primary">Search</button>
            <?php if ( $search ) : ?><a href="<?php echo admin_url('admin.php?page=ubo-sku'); ?>" class="button">Clear</a><?php endif; ?>
        </div>
    </form>

    <?php if ( empty( $all_skus ) ) : ?>
        <p style="color:#94a3b8;">No products found. Check API credentials in Settings.</p>
    <?php else : ?>
        <table class="ubo-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product / Variation</th>
                    <th>🇺🇸 US Stock</th>
                    <th>🇮🇳 India Stock</th>
                    <th>Total Available</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $all_skus as $sku ) :
                $us = $us_index[ $sku ] ?? null;
                $in = $in_index[ $sku ] ?? null;
                $name = $us['name'] ?? $in['name'] ?? $sku;

                $us_qty = $us ? $us['qty'] : null;
                $in_qty = $in ? $in['qty'] : null;
                $total  = ( $us_qty ?? 0 ) + ( $in_qty ?? 0 );

                $us_class = is_null( $us_qty ) ? 'qty-na' : ( $us_qty === 0 ? 'qty-zero' : ( $us_qty <= $threshold ? 'qty-low' : 'qty-ok' ) );
                $in_class = is_null( $in_qty ) ? 'qty-na' : ( $in_qty === 0 ? 'qty-zero' : ( $in_qty <= $threshold ? 'qty-low' : 'qty-ok' ) );

                $status = 'OK';
                $status_color = '#22c55e';
                if ( $total === 0 ) { $status = 'Out of Stock'; $status_color = '#ef4444'; }
                elseif ( $total <= $threshold ) { $status = 'Low Stock'; $status_color = '#f59e0b'; }
            ?>
                <tr>
                    <td><code style="font-size:11px;"><?php echo esc_html( $sku ); ?></code></td>
                    <td><?php echo esc_html( $name ); ?></td>
                    <td class="<?php echo $us_class; ?>"><?php echo is_null( $us_qty ) ? '—' : $us_qty; ?></td>
                    <td class="<?php echo $in_class; ?>"><?php echo is_null( $in_qty ) ? '—' : $in_qty; ?></td>
                    <td class="avail-calc"><?php echo $total; ?></td>
                    <td><span style="font-size:11px;font-weight:700;color:<?php echo $status_color; ?>"><?php echo $status; ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
