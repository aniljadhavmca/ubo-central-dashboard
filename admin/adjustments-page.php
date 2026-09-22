<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

UBO_Adjustments::handle_form();
$log = UBO_Adjustments::get_log( 200 );

$reasons = [
    'new_stock'  => '📦 New Stock Received',
    'damaged'    => '⚠️ Damaged Inventory',
    'sample'     => '🎁 Sample',
    'return'     => '↩️ Return',
    'correction' => '✏️ Stock Correction',
    'transfer'   => '🔄 Stock Transfer',
    'order'      => '🤖 Auto — Order Placed',
];
$reasons_plain = [
    'new_stock' => 'New Stock Received', 'damaged' => 'Damaged Inventory',
    'sample' => 'Sample', 'return' => 'Return', 'correction' => 'Stock Correction',
    'transfer' => 'Stock Transfer', 'order' => 'Auto — Order Placed',
];
?>
<div class="ubo-wrap">

    <div class="ubo-page-header">
        <div>
            <h1>🔧 Inventory Adjustments</h1>
            <div class="ubo-subtitle">Manually adjust stock levels — every change is logged with reason and user</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:400px 1fr;gap:24px;align-items:start;">

        <div class="ubo-form-card">
            <h2>New Adjustment</h2>
            <form method="post">
                <?php wp_nonce_field( 'ubo_adjustment', 'ubo_adj_nonce' ); ?>

                <div class="ubo-field">
                    <label>SKU <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="sku" required placeholder="e.g. UBOM-LW-BLK-M" />
                    <div class="ubo-hint">Enter the exact product SKU</div>
                </div>

                <div class="ubo-field">
                    <label>Store <span style="color:#ef4444;">*</span></label>
                    <select name="site" required>
                        <option value="US">🇺🇸 US Store</option>
                        <option value="India">🇮🇳 India Store</option>
                    </select>
                </div>

                <div class="ubo-field">
                    <label>Quantity Change <span style="color:#ef4444;">*</span></label>
                    <input type="number" name="adjustment" required placeholder="e.g. 50 to add, -5 to remove" />
                    <div class="ubo-hint">Use a negative number to reduce stock</div>
                </div>

                <div class="ubo-field">
                    <label>Reason <span style="color:#ef4444;">*</span></label>
                    <select name="reason" required>
                        <option value="">— Select a reason —</option>
                        <?php foreach ( $reasons as $val => $label ) : ?>
                            <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ubo-field">
                    <label>Note <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                    <textarea name="note" placeholder="Add any extra details about this adjustment…"></textarea>
                </div>

                <button type="submit" class="ubo-btn ubo-btn-primary" style="width:100%;justify-content:center;">Save Adjustment</button>
            </form>
        </div>

        <div>
            <div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:12px;">Adjustment History</div>
            <?php if ( empty( $log ) ) : ?>
                <div class="ubo-panel"><div class="ubo-empty-state"><div class="ubo-empty-icon">📋</div><p>No adjustments recorded yet.</p></div></div>
            <?php else : ?>
                <div class="ubo-table-wrap">
                    <table class="ubo-table">
                        <thead>
                            <tr><th>Date</th><th>SKU</th><th>Store</th><th>Change</th><th>Reason</th><th>Note</th><th>By</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ( $log as $row ) :
                            $cls   = $row->adjustment > 0 ? 'adj-pos' : 'adj-neg';
                            $sign  = $row->adjustment > 0 ? '+' : '';
                            $rlabel = $reasons_plain[ $row->reason ] ?? $row->reason;
                            $store_cls = $row->site === 'US' ? 'ubo-store-us' : 'ubo-store-india';
                            $flag      = $row->site === 'US' ? '🇺🇸' : '🇮🇳';
                        ?>
                            <tr>
                                <td style="white-space:nowrap;font-size:12px;color:#64748b;"><?php echo esc_html( date( 'M j, Y g:i A', strtotime( $row->created_at ) ) ); ?></td>
                                <td><span class="ubo-sku-code"><?php echo esc_html( $row->sku ); ?></span></td>
                                <td><span class="ubo-store-tag <?php echo esc_attr( $store_cls ); ?>"><?php echo $flag . ' ' . esc_html( $row->site ); ?></span></td>
                                <td class="<?php echo esc_attr( $cls ); ?>" style="font-size:15px;"><?php echo esc_html( $sign . $row->adjustment ); ?></td>
                                <td style="font-size:12px;"><?php echo esc_html( $rlabel ); ?></td>
                                <td style="font-size:12px;color:#64748b;max-width:180px;"><?php echo esc_html( $row->note ); ?></td>
                                <td style="font-size:12px;"><?php echo esc_html( $row->user_login ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>
