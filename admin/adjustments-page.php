<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

UBO_Adjustments::handle_form();

$log = UBO_Adjustments::get_log( 200 );

$reasons = [
    'new_stock'     => 'New Stock Received',
    'damaged'       => 'Damaged Inventory',
    'sample'        => 'Sample',
    'return'        => 'Return',
    'correction'    => 'Stock Correction',
    'transfer'      => 'Stock Transfer',
];
?>
<style>
.ubo-wrap { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 24px 20px; max-width: 1200px; }
.ubo-wrap h1 { font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 24px; }
.ubo-adj-form { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.07); margin-bottom: 32px; max-width: 600px; }
.ubo-adj-form h2 { font-size: 15px; font-weight: 700; margin-bottom: 16px; color: #1e293b; }
.ubo-field { margin-bottom: 14px; }
.ubo-field label { display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .04em; }
.ubo-field input, .ubo-field select, .ubo-field textarea { width: 100%; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; }
.ubo-field textarea { height: 70px; resize: vertical; }
.ubo-adj-form .button-primary { margin-top: 6px; }
.ubo-table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
.ubo-table th { background: #1e293b; color: #fff; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; }
.ubo-table td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.ubo-table tr:last-child td { border-bottom: none; }
.adj-pos { color: #16a34a; font-weight: 700; }
.adj-neg { color: #dc2626; font-weight: 700; }
</style>

<div class="ubo-wrap">
    <h1>🔧 Inventory Adjustments</h1>

    <div class="ubo-adj-form">
        <h2>New Adjustment</h2>
        <form method="post">
            <?php wp_nonce_field( 'ubo_adjustment', 'ubo_adj_nonce' ); ?>
            <div class="ubo-field">
                <label>SKU</label>
                <input type="text" name="sku" required placeholder="e.g. UBOM-LW-BLK-M" />
            </div>
            <div class="ubo-field">
                <label>Store</label>
                <select name="site" required>
                    <option value="US">🇺🇸 US Store</option>
                    <option value="India">🇮🇳 India Store</option>
                </select>
            </div>
            <div class="ubo-field">
                <label>Adjustment Qty (use negative to reduce)</label>
                <input type="number" name="adjustment" required placeholder="e.g. +50 or -5" />
            </div>
            <div class="ubo-field">
                <label>Reason</label>
                <select name="reason" required>
                    <option value="">— Select reason —</option>
                    <?php foreach ( $reasons as $val => $label ) : ?>
                        <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ubo-field">
                <label>Note (optional)</label>
                <textarea name="note" placeholder="Additional details…"></textarea>
            </div>
            <button type="submit" class="button button-primary">Save Adjustment</button>
        </form>
    </div>

    <h2 style="font-size:16px;font-weight:700;color:#1e293b;margin-bottom:14px;">Adjustment Log</h2>

    <?php if ( empty( $log ) ) : ?>
        <p style="color:#94a3b8;">No adjustments recorded yet.</p>
    <?php else : ?>
        <table class="ubo-table">
            <thead>
                <tr><th>Date</th><th>SKU</th><th>Store</th><th>Adjustment</th><th>Reason</th><th>Note</th><th>By</th></tr>
            </thead>
            <tbody>
            <?php foreach ( $log as $row ) :
                $cls = $row->adjustment > 0 ? 'adj-pos' : 'adj-neg';
                $sign = $row->adjustment > 0 ? '+' : '';
                $reason_label = $reasons[ $row->reason ] ?? $row->reason;
            ?>
                <tr>
                    <td style="white-space:nowrap;font-size:12px;"><?php echo esc_html( date( 'M j, Y g:i A', strtotime( $row->created_at ) ) ); ?></td>
                    <td><code style="font-size:11px;"><?php echo esc_html( $row->sku ); ?></code></td>
                    <td><?php echo $row->site === 'US' ? '🇺🇸 US' : '🇮🇳 India'; ?></td>
                    <td class="<?php echo $cls; ?>"><?php echo $sign . esc_html( $row->adjustment ); ?></td>
                    <td><?php echo esc_html( $reason_label ); ?></td>
                    <td style="font-size:12px;color:#64748b;"><?php echo esc_html( $row->note ); ?></td>
                    <td style="font-size:12px;"><?php echo esc_html( $row->user_login ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
