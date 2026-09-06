<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$site_filter   = sanitize_text_field( $_GET['site'] ?? 'US' );
$stock_filter  = sanitize_text_field( $_GET['stock_status'] ?? '' );

$params = [];
if ( $stock_filter ) $params['stock_status'] = $stock_filter;

$products = UBO_Inventory::fetch( $site_filter, $params );
?>
<div class="wrap">
    <h1>Inventory — <?php echo esc_html( $site_filter ); ?> Site</h1>

    <form method="get" style="margin-bottom:16px;">
        <input type="hidden" name="page" value="ubo-inventory" />
        <select name="site">
            <option value="US"    <?php selected( $site_filter, 'US' ); ?>>US Site</option>
            <option value="India" <?php selected( $site_filter, 'India' ); ?>>India Site</option>
        </select>
        <select name="stock_status">
            <option value="">All Stock</option>
            <option value="instock"     <?php selected( $stock_filter, 'instock' ); ?>>In Stock</option>
            <option value="outofstock"  <?php selected( $stock_filter, 'outofstock' ); ?>>Out of Stock</option>
            <option value="onbackorder" <?php selected( $stock_filter, 'onbackorder' ); ?>>On Backorder</option>
        </select>
        <button class="button">Filter</button>
    </form>

    <?php if ( isset( $products['error'] ) ) : ?>
        <div class="notice notice-error"><p><?php echo esc_html( $products['error'] ); ?></p></div>
    <?php elseif ( empty( $products ) ) : ?>
        <p>No products found.</p>
    <?php else : ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>SKU</th><th>Type</th>
                    <th>Price</th><th>Sale Price</th><th>Stock Status</th><th>Qty</th><th>Categories</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $products as $product ) :
                $cats = implode( ', ', array_column( $product['categories'] ?? [], 'name' ) );
            ?>
                <tr>
                    <td><?php echo esc_html( $product['id'] ); ?></td>
                    <td><?php echo esc_html( $product['name'] ); ?></td>
                    <td><?php echo esc_html( $product['sku'] ); ?></td>
                    <td><?php echo esc_html( $product['type'] ); ?></td>
                    <td><?php echo esc_html( $product['price'] ); ?></td>
                    <td><?php echo esc_html( $product['sale_price'] ); ?></td>
                    <td><?php echo esc_html( $product['stock_status'] ); ?></td>
                    <td><?php echo esc_html( $product['stock_quantity'] ?? '—' ); ?></td>
                    <td><?php echo esc_html( $cats ); ?></td>
                </tr>

                <?php if ( $product['type'] === 'variable' && ! empty( $product['variations_data'] ) ) :
                    foreach ( $product['variations_data'] as $v ) :
                        $attrs = implode( ', ', array_map( fn($a) => $a['option'], $v['attributes'] ?? [] ) );
                ?>
                    <tr style="background:#f9f9f9; font-size:12px;">
                        <td style="padding-left:24px;">↳ <?php echo esc_html( $v['id'] ); ?></td>
                        <td colspan="2" style="color:#555;"><?php echo esc_html( $attrs ); ?></td>
                        <td>variation</td>
                        <td><?php echo esc_html( $v['price'] ); ?></td>
                        <td><?php echo esc_html( $v['sale_price'] ); ?></td>
                        <td><?php echo esc_html( $v['stock_status'] ); ?></td>
                        <td><?php echo esc_html( $v['stock_quantity'] ?? '—' ); ?></td>
                        <td></td>
                    </tr>
                <?php endforeach; endif; ?>

            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
