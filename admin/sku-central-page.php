<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

// Handle reserved stock form
$saved = UBO_Reserved::handle_form();

$search       = sanitize_text_field( $_GET['search'] ?? '' );
$filter_color = sanitize_text_field( $_GET['color'] ?? '' );
$filter_size  = sanitize_text_field( $_GET['size'] ?? '' );
$filter_type  = sanitize_text_field( $_GET['garment_type'] ?? '' );

$params = [ 'per_page' => 100 ];
if ( $search ) $params['search'] = $search;

$us_products = UBO_Inventory::fetch( 'US', $params );
$in_products = UBO_Inventory::fetch( 'India', $params );
$reserved_map = UBO_Reserved::get_all();

function ubo_build_sku_index( $products ) {
    $index = [];
    if ( ! is_array( $products ) || isset( $products['error'] ) ) return $index;

    foreach ( $products as $p ) {
        $garment_type = '';
        foreach ( $p['categories'] ?? [] as $cat ) {
            $garment_type = $cat['name'];
            break;
        }

        if ( $p['type'] === 'variable' && ! empty( $p['variations_data'] ) ) {
            foreach ( $p['variations_data'] as $v ) {
                $sku   = $v['sku'] ?: ( $p['sku'] . '-' . $v['id'] );
                $color = '';
                $size  = '';
                foreach ( $v['attributes'] ?? [] as $attr ) {
                    $key = strtolower( $attr['name'] );
                    if ( str_contains( $key, 'color' ) || str_contains( $key, 'colour' ) ) $color = $attr['option'];
                    if ( str_contains( $key, 'size' ) ) $size = $attr['option'];
                }
                $attrs = implode( ' / ', array_filter( [ $color, $size ] ) );
                $index[ $sku ] = [
                    'name'         => $p['name'] . ( $attrs ? ' — ' . $attrs : '' ),
                    'sku'          => $sku,
                    'qty'          => (int) ( $v['stock_quantity'] ?? 0 ),
                    'status'       => $v['stock_status'],
                    'color'        => $color,
                    'size'         => $size,
                    'garment_type' => $garment_type,
                ];
            }
        } else {
            $sku = $p['sku'] ?: 'ID-' . $p['id'];
            $index[ $sku ] = [
                'name'         => $p['name'],
                'sku'          => $sku,
                'qty'          => (int) ( $p['stock_quantity'] ?? 0 ),
                'status'       => $p['stock_status'],
                'color'        => '',
                'size'         => '',
                'garment_type' => $garment_type,
            ];
        }
    }
    return $index;
}

$us_index = ubo_build_sku_index( $us_products );
$in_index = ubo_build_sku_index( $in_products );
$all_skus = array_unique( array_merge( array_keys( $us_index ), array_keys( $in_index ) ) );
sort( $all_skus );

// Collect filter options
$all_colors = $all_sizes = $all_types = [];
foreach ( $all_skus as $sku ) {
    $row = $us_index[ $sku ] ?? $in_index[ $sku ] ?? [];
    if ( ! empty( $row['color'] ) ) $all_colors[] = $row['color'];
    if ( ! empty( $row['size'] ) )  $all_sizes[]  = $row['size'];
    if ( ! empty( $row['garment_type'] ) ) $all_types[] = $row['garment_type'];
}
$all_colors = array_unique( $all_colors );
$all_sizes  = array_unique( $all_sizes );
$all_types  = array_unique( $all_types );
sort( $all_colors ); sort( $all_sizes ); sort( $all_types );

// Apply attribute filters
if ( $filter_color || $filter_size || $filter_type ) {
    $all_skus = array_filter( $all_skus, function( $sku ) use ( $us_index, $in_index, $filter_color, $filter_size, $filter_type ) {
        $row = $us_index[ $sku ] ?? $in_index[ $sku ] ?? [];
        if ( $filter_color && ( $row['color'] ?? '' ) !== $filter_color ) return false;
        if ( $filter_size  && ( $row['size']  ?? '' ) !== $filter_size  ) return false;
        if ( $filter_type  && ( $row['garment_type'] ?? '' ) !== $filter_type ) return false;
        return true;
    } );
}

$threshold = (int) get_option( 'ubo_low_stock_threshold', UBO_LOW_STOCK_THRESHOLD );
?>
<style>
.ubo-wrap { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 24px 20px; max-width: 1500px; }
.ubo-wrap h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 20px; }
.ubo-filters { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
.ubo-filters input, .ubo-filters select { padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; }
.ubo-filters input { width: 220px; }
.ubo-table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
.ubo-table th { background: #1e293b; color: #fff; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; }
.ubo-table td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
.ubo-table tr:last-child td { border-bottom: none; }
.ubo-table tr:hover td { background: #f8fafc; }
.qty-ok   { color: #16a34a; font-weight: 700; }
.qty-low  { color: #d97706; font-weight: 700; }
.qty-zero { color: #dc2626; font-weight: 700; }
.qty-na   { color: #cbd5e1; }
.qty-avail { color: #6366f1; font-weight: 700; }
.qty-reserved { color: #f59e0b; font-weight: 600; }
.ubo-reserved-input { width: 60px; padding: 3px 6px; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 12px; text-align: center; }
.ubo-save-btn { font-size: 11px; padding: 3px 8px; cursor: pointer; }
.notice-saved { background: #f0fdf4; border-left: 4px solid #22c55e; padding: 10px 14px; margin-bottom: 16px; font-size: 13px; color: #166534; border-radius: 4px; }
</style>

<div class="ubo-wrap">
    <h1>📦 SKU Central — US &amp; India Stock</h1>

    <?php if ( $saved ) : ?>
        <div class="notice-saved">✅ Reserved stock updated.</div>
    <?php endif; ?>

    <form method="get">
        <input type="hidden" name="page" value="ubo-sku" />
        <div class="ubo-filters">
            <input type="text" name="search" value="<?php echo esc_attr( $search ); ?>" placeholder="Search name or SKU…" />

            <select name="garment_type">
                <option value="">All Garment Types</option>
                <?php foreach ( $all_types as $t ) : ?>
                    <option value="<?php echo esc_attr( $t ); ?>" <?php selected( $filter_type, $t ); ?>><?php echo esc_html( $t ); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="color">
                <option value="">All Colors</option>
                <?php foreach ( $all_colors as $c ) : ?>
                    <option value="<?php echo esc_attr( $c ); ?>" <?php selected( $filter_color, $c ); ?>><?php echo esc_html( $c ); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="size">
                <option value="">All Sizes</option>
                <?php foreach ( $all_sizes as $sz ) : ?>
                    <option value="<?php echo esc_attr( $sz ); ?>" <?php selected( $filter_size, $sz ); ?>><?php echo esc_html( $sz ); ?></option>
                <?php endforeach; ?>
            </select>

            <button class="button button-primary">Filter</button>
            <?php if ( $search || $filter_color || $filter_size || $filter_type ) : ?>
                <a href="<?php echo admin_url('admin.php?page=ubo-sku'); ?>" class="button">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <p style="font-size:12px;color:#94a3b8;margin-bottom:12px;"><?php echo count( $all_skus ); ?> SKUs shown &nbsp;·&nbsp; Reserved qty is editable inline — click Save to update.</p>

    <?php if ( empty( $all_skus ) ) : ?>
        <p style="color:#94a3b8;">No products found. Check API credentials in Settings.</p>
    <?php else : ?>
        <table class="ubo-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product / Variation</th>
                    <th>Garment</th>
                    <th>Color</th>
                    <th>Size</th>
                    <th>🇺🇸 US Stock</th>
                    <th>🇮🇳 India Stock</th>
                    <th>🔒 US Reserved</th>
                    <th>🔒 India Reserved</th>
                    <th>✅ Available</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $all_skus as $sku ) :
                $us_row = $us_index[ $sku ] ?? null;
                $in_row = $in_index[ $sku ] ?? null;
                $meta   = $us_row ?? $in_row;

                $us_qty      = $us_row ? $us_row['qty'] : null;
                $in_qty      = $in_row ? $in_row['qty'] : null;
                $us_reserved = $reserved_map[ $sku ]['US']    ?? 0;
                $in_reserved = $reserved_map[ $sku ]['India'] ?? 0;
                $us_avail    = max( 0, ( $us_qty ?? 0 ) - $us_reserved );
                $in_avail    = max( 0, ( $in_qty ?? 0 ) - $in_reserved );
                $total_avail = $us_avail + $in_avail;

                $us_class = is_null( $us_qty ) ? 'qty-na' : ( $us_qty === 0 ? 'qty-zero' : ( $us_qty <= $threshold ? 'qty-low' : 'qty-ok' ) );
                $in_class = is_null( $in_qty ) ? 'qty-na' : ( $in_qty === 0 ? 'qty-zero' : ( $in_qty <= $threshold ? 'qty-low' : 'qty-ok' ) );

                $status = 'OK'; $status_color = '#22c55e';
                if ( $total_avail === 0 ) { $status = 'Out of Stock'; $status_color = '#ef4444'; }
                elseif ( $total_avail <= $threshold ) { $status = 'Low Stock'; $status_color = '#f59e0b'; }
            ?>
                <tr>
                    <td><code style="font-size:11px;"><?php echo esc_html( $sku ); ?></code></td>
                    <td><?php echo esc_html( $meta['name'] ?? $sku ); ?></td>
                    <td style="font-size:12px;color:#64748b;"><?php echo esc_html( $meta['garment_type'] ?? '' ); ?></td>
                    <td style="font-size:12px;"><?php echo esc_html( $meta['color'] ?? '' ); ?></td>
                    <td style="font-size:12px;"><?php echo esc_html( $meta['size'] ?? '' ); ?></td>
                    <td class="<?php echo $us_class; ?>"><?php echo is_null( $us_qty ) ? '—' : $us_qty; ?></td>
                    <td class="<?php echo $in_class; ?>"><?php echo is_null( $in_qty ) ? '—' : $in_qty; ?></td>

                    <!-- US Reserved inline edit -->
                    <td>
                        <form method="post" style="display:flex;align-items:center;gap:4px;">
                            <?php wp_nonce_field( 'ubo_reserved', 'ubo_reserved_nonce' ); ?>
                            <input type="hidden" name="reserved_sku" value="<?php echo esc_attr( $sku ); ?>" />
                            <input type="hidden" name="reserved_site" value="US" />
                            <input type="number" name="reserved_qty" value="<?php echo $us_reserved; ?>" min="0" class="ubo-reserved-input" />
                            <button type="submit" class="button ubo-save-btn">Save</button>
                        </form>
                    </td>

                    <!-- India Reserved inline edit -->
                    <td>
                        <form method="post" style="display:flex;align-items:center;gap:4px;">
                            <?php wp_nonce_field( 'ubo_reserved', 'ubo_reserved_nonce' ); ?>
                            <input type="hidden" name="reserved_sku" value="<?php echo esc_attr( $sku ); ?>" />
                            <input type="hidden" name="reserved_site" value="India" />
                            <input type="number" name="reserved_qty" value="<?php echo $in_reserved; ?>" min="0" class="ubo-reserved-input" />
                            <button type="submit" class="button ubo-save-btn">Save</button>
                        </form>
                    </td>

                    <td class="qty-avail"><?php echo $total_avail; ?></td>
                    <td><span style="font-size:11px;font-weight:700;color:<?php echo $status_color; ?>"><?php echo $status; ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
