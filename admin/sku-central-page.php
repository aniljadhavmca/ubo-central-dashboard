<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$saved = UBO_Reserved::handle_form();

$search       = sanitize_text_field( $_GET['search']       ?? '' );
$sku_exact    = sanitize_text_field( $_GET['sku']          ?? '' );
$filter_color = sanitize_text_field( $_GET['color']        ?? '' );
$filter_size  = sanitize_text_field( $_GET['size']         ?? '' );
$filter_type  = sanitize_text_field( $_GET['garment_type'] ?? '' );

// Fetch by name search AND by exact SKU param, merge to catch both cases
if ( ! function_exists( 'ubo_fetch_merged' ) ) {
    function ubo_fetch_merged( $site_key, $search, $sku_exact ) {
        if ( ! $search && ! $sku_exact ) {
            return UBO_Inventory::fetch( $site_key, [ 'per_page' => 100 ] );
        }
        $by_name = $search    ? UBO_Inventory::fetch( $site_key, [ 'search' => $search,    'per_page' => 100 ] ) : [];
        $by_sku  = $sku_exact ? UBO_Inventory::fetch( $site_key, [ 'sku'    => $sku_exact, 'per_page' => 100 ] ) : [];
        $merged  = [];
        foreach ( [ $by_name, $by_sku ] as $batch ) {
            if ( is_array( $batch ) && ! isset( $batch['error'] ) ) {
                foreach ( $batch as $p ) $merged[ $p['id'] ] = $p;
            }
        }
        return array_values( $merged );
    }
}

$us_products  = ubo_fetch_merged( 'US',    $search, $sku_exact );
$in_products  = ubo_fetch_merged( 'India', $search, $sku_exact );
$reserved_map = UBO_Reserved::get_all();

if ( ! function_exists( 'ubo_build_sku_index' ) ) {
    function ubo_build_sku_index( $products ) {
        $index = [];
        if ( ! is_array( $products ) || isset( $products['error'] ) ) return $index;
        foreach ( $products as $p ) {
            $garment_type = $p['categories'][0]['name'] ?? '';
            if ( $p['type'] === 'variable' && ! empty( $p['variations_data'] ) ) {
                foreach ( $p['variations_data'] as $v ) {
                    $sku = $v['sku'] ?: ( $p['sku'] . '-' . $v['id'] );
                    $color = $size = '';
                    foreach ( $v['attributes'] ?? [] as $attr ) {
                        $key = strtolower( $attr['name'] );
                        if ( str_contains( $key, 'color' ) || str_contains( $key, 'colour' ) ) $color = $attr['option'];
                        if ( str_contains( $key, 'size' ) ) $size = $attr['option'];
                    }
                    $index[ $sku ] = [
                        'name' => $p['name'] . ( ( $color || $size ) ? ' — ' . implode( ' / ', array_filter( [ $color, $size ] ) ) : '' ),
                        'sku'  => $sku, 'qty' => (int)( $v['stock_quantity'] ?? 0 ),
                        'color' => $color, 'size' => $size, 'garment_type' => $garment_type,
                    ];
                }
            } else {
                $sku = $p['sku'] ?: 'ID-' . $p['id'];
                $index[ $sku ] = [
                    'name' => $p['name'], 'sku' => $sku, 'qty' => (int)( $p['stock_quantity'] ?? 0 ),
                    'color' => '', 'size' => '', 'garment_type' => $garment_type,
                ];
            }
        }
        return $index;
    }
}

$us_index = ubo_build_sku_index( $us_products );
$in_index = ubo_build_sku_index( $in_products );
$all_skus = array_unique( array_merge( array_keys( $us_index ), array_keys( $in_index ) ) );
sort( $all_skus );

$all_colors = $all_sizes = $all_types = [];
foreach ( $all_skus as $sku ) {
    $row = $us_index[ $sku ] ?? $in_index[ $sku ] ?? [];
    if ( ! empty( $row['color'] ) ) $all_colors[] = $row['color'];
    if ( ! empty( $row['size'] ) )  $all_sizes[]  = $row['size'];
    if ( ! empty( $row['garment_type'] ) ) $all_types[] = $row['garment_type'];
}
$all_colors = array_unique( $all_colors ); sort( $all_colors );
$all_sizes  = array_unique( $all_sizes );  sort( $all_sizes );
$all_types  = array_unique( $all_types );  sort( $all_types );

if ( $filter_color || $filter_size || $filter_type ) {
    $all_skus = array_values( array_filter( $all_skus, function( $sku ) use ( $us_index, $in_index, $filter_color, $filter_size, $filter_type ) {
        $row = $us_index[ $sku ] ?? $in_index[ $sku ] ?? [];
        if ( $filter_color && ( $row['color'] ?? '' ) !== $filter_color ) return false;
        if ( $filter_size  && ( $row['size']  ?? '' ) !== $filter_size  ) return false;
        if ( $filter_type  && ( $row['garment_type'] ?? '' ) !== $filter_type ) return false;
        return true;
    } ) );
}

$threshold = (int) get_option( 'ubo_low_stock_threshold', UBO_LOW_STOCK_THRESHOLD );
$has_filters = $search || $sku_exact || $filter_color || $filter_size || $filter_type;
?>
<div class="ubo-wrap">

    <div class="ubo-page-header">
        <div>
            <h1>📦 SKU Central</h1>
            <div class="ubo-subtitle">Live stock across US &amp; India — with reserved &amp; available calculations</div>
        </div>
    </div>

    <?php if ( $saved ) : ?>
        <div class="ubo-notice ubo-notice-success">✅ Reserved stock updated successfully.</div>
    <?php endif; ?>

    <form method="get" id="ubo-sku-form">
        <input type="hidden" name="page" value="ubo-sku" />
        <input type="hidden" id="ubo-sku-exact" name="sku" value="<?php echo esc_attr( $sku_exact ); ?>" />
        <div class="ubo-filter-bar">

            <div class="ubo-search-wrap">
                <span class="ubo-search-icon">🔍</span>
                <input type="search" id="ubo-live-search" name="search"
                    value="<?php echo esc_attr( $search ?: $sku_exact ); ?>"
                    placeholder="Search by name or SKU…"
                    autocomplete="off" />
                <div id="ubo-search-suggestions" class="ubo-search-suggestions"></div>
            </div>

            <label>Garment</label>
            <select name="garment_type" class="ubo-auto-filter">
                <option value="">All Types</option>
                <?php foreach ( $all_types as $t ) : ?>
                    <option value="<?php echo esc_attr( $t ); ?>" <?php selected( $filter_type, $t ); ?>><?php echo esc_html( $t ); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Color</label>
            <select name="color" class="ubo-auto-filter">
                <option value="">All Colors</option>
                <?php foreach ( $all_colors as $c ) : ?>
                    <option value="<?php echo esc_attr( $c ); ?>" <?php selected( $filter_color, $c ); ?>><?php echo esc_html( $c ); ?></option>
                <?php endforeach; ?>
            </select>

            <label>Size</label>
            <select name="size" class="ubo-auto-filter">
                <option value="">All Sizes</option>
                <?php foreach ( $all_sizes as $sz ) : ?>
                    <option value="<?php echo esc_attr( $sz ); ?>" <?php selected( $filter_size, $sz ); ?>><?php echo esc_html( $sz ); ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="ubo-btn ubo-btn-primary">🔍 Search</button>
            <?php if ( $has_filters ) : ?>
                <a href="<?php echo esc_url( admin_url('admin.php?page=ubo-sku') ); ?>" class="ubo-btn ubo-btn-secondary">✕ Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="ubo-result-bar">
        <span><strong><?php echo count( $all_skus ); ?></strong> SKUs shown</span>
        <span style="font-size:12px;color:#94a3b8;">🔒 Reserved = units held aside (photoshoots, samples, etc.) · ✅ Available = Stock − Reserved</span>
    </div>

    <?php if ( empty( $all_skus ) ) : ?>
        <div class="ubo-panel"><div class="ubo-empty-state"><div class="ubo-empty-icon">📦</div><p>No products found. Try a different search or check API credentials.</p></div></div>
    <?php else : ?>
        <div class="ubo-table-wrap" data-ubo-threshold="<?php echo (int) $threshold; ?>">
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

                    if ( $total_avail === 0 )            { $stock_label = 'Out of Stock'; $stock_cls = 'ubo-badge ubo-stock-out'; }
                    elseif ( $total_avail <= $threshold ) { $stock_label = 'Low Stock';    $stock_cls = 'ubo-badge ubo-stock-low'; }
                    else                                  { $stock_label = 'In Stock';     $stock_cls = 'ubo-badge ubo-stock-ok'; }
                ?>
                    <tr data-sku="<?php echo esc_attr( $sku ); ?>" data-us-stock="<?php echo (int)( $us_qty ?? 0 ); ?>" data-in-stock="<?php echo (int)( $in_qty ?? 0 ); ?>">
                        <td><span class="ubo-sku-code"><?php echo esc_html( $sku ); ?></span></td>
                        <td style="max-width:200px;"><?php echo esc_html( $meta['name'] ?? $sku ); ?></td>
                        <td style="color:#64748b;font-size:12px;"><?php echo esc_html( $meta['garment_type'] ?? '' ); ?></td>
                        <td style="font-size:12px;"><?php echo esc_html( $meta['color'] ?? '' ); ?></td>
                        <td style="font-size:12px;"><?php echo esc_html( $meta['size'] ?? '' ); ?></td>
                        <td class="<?php echo esc_attr( $us_class ); ?>"><?php echo is_null( $us_qty ) ? '—' : (int) $us_qty; ?></td>
                        <td class="<?php echo esc_attr( $in_class ); ?>"><?php echo is_null( $in_qty ) ? '—' : (int) $in_qty; ?></td>

                        <td>
                            <form class="ubo-reserved-form" method="post" data-site="US" data-sku="<?php echo esc_attr( $sku ); ?>">
                                <?php wp_nonce_field( 'ubo_ajax', 'ubo_reserved_nonce' ); ?>
                                <input type="hidden" name="reserved_sku" value="<?php echo esc_attr( $sku ); ?>" />
                                <input type="hidden" name="reserved_site" value="US" />
                                <div class="ubo-reserved-wrap">
                                    <input type="number" name="reserved_qty" value="<?php echo (int) $us_reserved; ?>" min="0" class="ubo-reserved-input" />
                                    <button type="submit" class="ubo-save-btn">Save</button>
                                </div>
                            </form>
                        </td>
                        <td>
                            <form class="ubo-reserved-form" method="post" data-site="India" data-sku="<?php echo esc_attr( $sku ); ?>">
                                <?php wp_nonce_field( 'ubo_ajax', 'ubo_reserved_nonce' ); ?>
                                <input type="hidden" name="reserved_sku" value="<?php echo esc_attr( $sku ); ?>" />
                                <input type="hidden" name="reserved_site" value="India" />
                                <div class="ubo-reserved-wrap">
                                    <input type="number" name="reserved_qty" value="<?php echo (int) $in_reserved; ?>" min="0" class="ubo-reserved-input" />
                                    <button type="submit" class="ubo-save-btn">Save</button>
                                </div>
                            </form>
                        </td>

                        <td class="qty-avail"><?php echo (int) $total_avail; ?></td>
                        <td><span class="<?php echo esc_attr( $stock_cls ); ?>"><?php echo esc_html( $stock_label ); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
