<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

$site_filter  = sanitize_text_field( $_GET['site']         ?? 'US' );
$stock_filter = sanitize_text_field( $_GET['stock_status'] ?? '' );
$search       = sanitize_text_field( $_GET['search']       ?? '' );

$params = [ 'per_page' => 100 ];
if ( $stock_filter ) $params['stock_status'] = $stock_filter;
if ( $search )       $params['search']       = $search;

$products    = UBO_Inventory::fetch( $site_filter, $params );
$has_error   = isset( $products['error'] );
$has_filters = $stock_filter || $search;
$threshold   = (int) get_option( 'ubo_low_stock_threshold', UBO_LOW_STOCK_THRESHOLD );

// Build a set of SKUs that have been manually adjusted for this site
$adjusted_skus = [];
if ( ! $has_error && ! empty( $products ) ) {
    global $wpdb;
    $table = $wpdb->prefix . 'ubo_stock_adjustments';
    $rows  = $wpdb->get_results(
        $wpdb->prepare( "SELECT DISTINCT sku FROM {$table} WHERE site = %s", $site_filter )
    );
    foreach ( $rows as $r ) $adjusted_skus[ $r->sku ] = true;
}

$reasons = [
    'new_stock'  => '📦 New Stock Received',
    'damaged'    => '⚠️ Damaged Inventory',
    'sample'     => '🎁 Sample',
    'return'     => '↩️ Return',
    'correction' => '✏️ Stock Correction',
    'transfer'   => '🔄 Stock Transfer',
];

if ( ! function_exists( 'ubo_inv_stock_class' ) ) {
    function ubo_inv_stock_class( $qty, $threshold ) {
        if ( is_null( $qty ) || $qty === '' ) return 'qty-na';
        $qty = (int) $qty;
        if ( $qty === 0 )         return 'qty-zero';
        if ( $qty <= $threshold ) return 'qty-low';
        return 'qty-ok';
    }
}
if ( ! function_exists( 'ubo_inv_badge' ) ) {
    function ubo_inv_badge( $status ) {
        $map = [
            'instock'     => [ 'In Stock',     'ubo-stock-ok'  ],
            'outofstock'  => [ 'Out of Stock', 'ubo-stock-out' ],
            'onbackorder' => [ 'Backorder',    'ubo-stock-low' ],
        ];
        $d = $map[ $status ] ?? [ ucfirst( $status ), 'ubo-badge-default' ];
        return '<span class="ubo-badge ' . esc_attr( $d[1] ) . '">' . esc_html( $d[0] ) . '</span>';
    }
}
?>
<div class="ubo-wrap">

    <div class="ubo-page-header">
        <div>
            <h1>🗂️ Inventory</h1>
            <div class="ubo-subtitle">Browse and edit product prices &amp; stock — changes sync directly to WooCommerce</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <span class="ubo-sync-badge">
                <?php echo $site_filter === 'US' ? '🇺🇸' : '🇮🇳'; ?>
                <?php echo esc_html( $site_filter ); ?> Store
            </span>
        </div>
    </div>

    <!-- Filter bar -->
    <form method="get" id="ubo-inv-filter-form">
        <input type="hidden" name="page" value="ubo-inventory" />
        <div class="ubo-filter-bar">

            <label>Store</label>
            <select name="site" class="ubo-auto-filter">
                <option value="US"    <?php selected( $site_filter, 'US' ); ?>>🇺🇸 US Store</option>
                <option value="India" <?php selected( $site_filter, 'India' ); ?>>🇮🇳 India Store</option>
            </select>

            <label>Stock</label>
            <select name="stock_status" class="ubo-auto-filter">
                <option value="">All Stock</option>
                <option value="instock"     <?php selected( $stock_filter, 'instock' ); ?>>In Stock</option>
                <option value="outofstock"  <?php selected( $stock_filter, 'outofstock' ); ?>>Out of Stock</option>
                <option value="onbackorder" <?php selected( $stock_filter, 'onbackorder' ); ?>>On Backorder</option>
            </select>

            <div class="ubo-search-wrap">
                <span class="ubo-search-icon">🔍</span>
                <input type="search" name="search" value="<?php echo esc_attr( $search ); ?>"
                    placeholder="Search products…" autocomplete="off" />
            </div>

            <button type="submit" class="ubo-btn ubo-btn-primary">🔍 Search</button>
            <?php if ( $has_filters ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ubo-inventory&site=' . urlencode( $site_filter ) ) ); ?>" class="ubo-btn ubo-btn-secondary">✕ Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ( $has_error ) : ?>
        <div class="ubo-notice ubo-notice-error">❌ <?php echo esc_html( $products['error'] ); ?></div>
    <?php elseif ( empty( $products ) ) : ?>
        <div class="ubo-panel"><div class="ubo-empty-state"><div class="ubo-empty-icon">🗂️</div><p>No products found. Try a different filter or check API credentials.</p></div></div>
    <?php else : ?>

        <div class="ubo-result-bar">
            <span><strong><?php echo count( $products ); ?></strong> products</span>
            <span style="font-size:12px;color:#94a3b8;">Click <strong>Edit</strong> on any row to update price, sale price or stock quantity</span>
        </div>

        <div class="ubo-table-wrap">
            <table class="ubo-table ubo-inv-table">
                <thead>
                    <tr>
                        <th style="width:32px;"></th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Sale Price</th>
                        <th>Stock</th>
                        <th>Qty</th>
                        <th>Categories</th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $products as $product ) :
                    $cats       = implode( ', ', array_column( $product['categories'] ?? [], 'name' ) );
                    $has_vars   = $product['type'] === 'variable' && ! empty( $product['variations_data'] );
                    $qty        = $product['stock_quantity'] ?? null;
                    $qty_class  = ubo_inv_stock_class( $qty, $threshold );
                    $pid        = (int) $product['id'];
                ?>
                    <tr class="ubo-inv-parent-row" data-pid="<?php echo $pid; ?>">
                        <td class="ubo-expand-cell">
                            <?php if ( $has_vars ) : ?>
                                <button class="ubo-expand-btn" data-pid="<?php echo $pid; ?>" title="Show variations">▶</button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="ubo-product-name-cell">
                                <?php echo ubo_thumb( $product['images'][0]['src'] ?? '', 32, $product['name'] ); ?>
                                <div>
                                    <div class="ubo-product-name">
                                        <?php echo esc_html( $product['name'] ); ?>
                                        <?php if ( ! empty( $product['sku'] ) && isset( $adjusted_skus[ $product['sku'] ] ) ) : ?>
                                            <span class="ubo-adjusted-tag">adjusted</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:11px;color:#94a3b8;">ID: <?php echo $pid; ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="ubo-sku-code"><?php echo esc_html( $product['sku'] ?: '—' ); ?></span></td>
                        <td><span class="ubo-type-tag ubo-type-<?php echo esc_attr( $product['type'] ); ?>"><?php echo esc_html( $product['type'] ); ?></span></td>
                        <td class="ubo-price-cell"><?php echo $product['price'] ? '<span class="ubo-price">$' . esc_html( $product['price'] ) . '</span>' : '—'; ?></td>
                        <td class="ubo-price-cell"><?php echo $product['sale_price'] ? '<span class="ubo-sale-price">$' . esc_html( $product['sale_price'] ) . '</span>' : '—'; ?></td>
                        <td><?php echo ubo_inv_badge( $product['stock_status'] ?? 'instock' ); ?></td>
                        <td class="<?php echo esc_attr( $qty_class ); ?>"><?php echo is_null( $qty ) ? '—' : (int) $qty; ?></td>
                        <td style="font-size:12px;color:#64748b;"><?php echo esc_html( $cats ); ?></td>
                        <td>
                            <?php if ( $product['type'] !== 'variable' ) : ?>
                                <button class="ubo-btn ubo-btn-secondary ubo-edit-btn"
                                    data-id="<?php echo $pid; ?>"
                                    data-type="product"
                                    data-parent="0"
                                    data-site="<?php echo esc_attr( $site_filter ); ?>"
                                    data-name="<?php echo esc_attr( $product['name'] ); ?>"
                                    data-sku="<?php echo esc_attr( $product['sku'] ); ?>"
                                    data-price="<?php echo esc_attr( $product['price'] ?? '' ); ?>"
                                    data-sale="<?php echo esc_attr( $product['sale_price'] ?? '' ); ?>"
                                    data-qty="<?php echo esc_attr( $qty ?? '' ); ?>"
                                    style="height:28px;padding:0 10px;font-size:12px;">✏️ Edit</button>
                            <?php else : ?>
                                <span style="font-size:11px;color:#94a3b8;">↓ variations</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php if ( $has_vars ) : ?>
                        <tr class="ubo-variations-row" id="ubo-vars-<?php echo $pid; ?>" style="display:none;">
                            <td colspan="10" style="padding:0;background:#f8fafc;">
                                <table class="ubo-table ubo-var-table">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;padding-left:40px;">↳</th>
                                            <th>Variation</th>
                                            <th>SKU</th>
                                            <th colspan="2">Price</th>
                                            <th>Sale Price</th>
                                            <th>Stock</th>
                                            <th>Qty</th>
                                            <th></th>
                                            <th style="width:80px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ( $product['variations_data'] as $v ) :
                                        $attrs     = implode( ' / ', array_map( fn($a) => $a['option'], $v['attributes'] ?? [] ) );
                                        $vqty      = $v['stock_quantity'] ?? null;
                                        $vqty_cls  = ubo_inv_stock_class( $vqty, $threshold );
                                        $vid       = (int) $v['id'];
                                    ?>
                                        <tr class="ubo-var-row">
                                            <td style="padding-left:40px;color:#94a3b8;">↳</td>
                                            <td>
                                                <span style="font-size:13px;color:#334155;"><?php echo esc_html( $attrs ?: 'Default' ); ?></span>
                                                <?php if ( ! empty( $v['sku'] ) && isset( $adjusted_skus[ $v['sku'] ] ) ) : ?>
                                                    <span class="ubo-adjusted-tag">adjusted</span>
                                                <?php endif; ?>
                                                <span style="font-size:11px;color:#94a3b8;margin-left:6px;">ID: <?php echo $vid; ?></span>
                                            </td>
                                            <td><span class="ubo-sku-code"><?php echo esc_html( $v['sku'] ?: '—' ); ?></span></td>
                                            <td colspan="2" class="ubo-price-cell"><?php echo $v['price'] ? '<span class="ubo-price">$' . esc_html( $v['price'] ) . '</span>' : '—'; ?></td>
                                            <td class="ubo-price-cell"><?php echo $v['sale_price'] ? '<span class="ubo-sale-price">$' . esc_html( $v['sale_price'] ) . '</span>' : '—'; ?></td>
                                            <td><?php echo ubo_inv_badge( $v['stock_status'] ?? 'instock' ); ?></td>
                                            <td class="<?php echo esc_attr( $vqty_cls ); ?>"><?php echo is_null( $vqty ) ? '—' : (int) $vqty; ?></td>
                                            <td></td>
                                            <td>
                                                <button class="ubo-btn ubo-btn-secondary ubo-edit-btn"
                                                    data-id="<?php echo $vid; ?>"
                                                    data-type="variation"
                                                    data-parent="<?php echo $pid; ?>"
                                                    data-site="<?php echo esc_attr( $site_filter ); ?>"
                                                    data-name="<?php echo esc_attr( $product['name'] . ( $attrs ? ' — ' . $attrs : '' ) ); ?>"
                                                    data-sku="<?php echo esc_attr( $v['sku'] ); ?>"
                                                    data-price="<?php echo esc_attr( $v['price'] ?? '' ); ?>"
                                                    data-sale="<?php echo esc_attr( $v['sale_price'] ?? '' ); ?>"
                                                    data-qty="<?php echo esc_attr( $vqty ?? '' ); ?>"
                                                    style="height:28px;padding:0 10px;font-size:12px;">✏️ Edit</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    <?php endif; ?>

                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>

<!-- ── Edit Modal ── -->
<div id="ubo-edit-overlay" class="ubo-modal-overlay" style="display:none;">
    <div class="ubo-modal">
        <div class="ubo-modal-header">
            <div>
                <div class="ubo-modal-title">✏️ Edit Product</div>
                <div class="ubo-modal-subtitle" id="ubo-modal-subtitle"></div>
            </div>
            <button class="ubo-modal-close" id="ubo-modal-close">✕</button>
        </div>

        <div class="ubo-modal-body">
            <div id="ubo-modal-notice" style="display:none;"></div>

            <div class="ubo-modal-meta">
                <span class="ubo-sku-code" id="ubo-modal-sku"></span>
                <span class="ubo-store-tag" id="ubo-modal-store"></span>
            </div>

            <div class="ubo-modal-fields">
                <div class="ubo-field">
                    <label>Regular Price ($)</label>
                    <input type="number" id="ubo-edit-price" step="0.01" min="0" placeholder="e.g. 29.99" />
                </div>
                <div class="ubo-field">
                    <label>Sale Price ($) <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                    <input type="number" id="ubo-edit-sale" step="0.01" min="0" placeholder="Leave blank to remove sale" />
                </div>
                <div class="ubo-field">
                    <label>Stock Quantity</label>
                    <input type="number" id="ubo-edit-qty" step="1" placeholder="e.g. 50" />
                    <div class="ubo-hint" id="ubo-qty-hint"></div>
                </div>
                <div class="ubo-field" id="ubo-reason-field">
                    <label>Reason for Qty Change <span style="color:#ef4444;">*</span></label>
                    <select id="ubo-edit-reason">
                        <option value="">— Select a reason —</option>
                        <?php foreach ( $reasons as $val => $label ) : ?>
                            <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ubo-field">
                    <label>Note <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                    <textarea id="ubo-edit-note" placeholder="Add context about this change…"></textarea>
                </div>
            </div>
        </div>

        <div class="ubo-modal-footer">
            <button class="ubo-btn ubo-btn-secondary" id="ubo-modal-cancel">Cancel</button>
            <button class="ubo-btn ubo-btn-primary" id="ubo-modal-save">💾 Save Changes</button>
        </div>

        <!-- hidden state -->
        <input type="hidden" id="ubo-edit-id" />
        <input type="hidden" id="ubo-edit-type" />
        <input type="hidden" id="ubo-edit-parent" />
        <input type="hidden" id="ubo-edit-site" />
        <input type="hidden" id="ubo-edit-orig-qty" />
        <input type="hidden" id="ubo-edit-sku" />
        <input type="hidden" id="ubo-edit-orig-price" />
        <input type="hidden" id="ubo-edit-orig-sale" />
    </div>
</div>
