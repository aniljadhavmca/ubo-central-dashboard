<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_POST['ubo_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubo_settings_nonce'] ) ), 'ubo_save_settings' ) ) {
    if ( current_user_can( 'manage_options' ) ) {

        $form_id = sanitize_text_field( wp_unslash( $_POST['ubo_form_id'] ?? '' ) );

        if ( $form_id === 'api' ) {
            $api_fields = [ 'ubo_us_url', 'ubo_us_ck', 'ubo_us_cs', 'ubo_us_webhook_secret', 'ubo_in_url', 'ubo_in_ck', 'ubo_in_cs', 'ubo_in_webhook_secret' ];
            foreach ( $api_fields as $field ) {
                update_option( $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ?? '' ) ) );
            }
        } elseif ( $form_id === 'alerts' ) {
            update_option( 'ubo_low_stock_threshold', sanitize_text_field( wp_unslash( $_POST['ubo_low_stock_threshold'] ?? '10' ) ) );
            update_option( 'ubo_use_wc_threshold', isset( $_POST['ubo_use_wc_threshold'] ) ? '1' : '' );
        }

        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }
}

$use_wc  = (bool) get_option( 'ubo_use_wc_threshold', false );
$thresh  = (int)  get_option( 'ubo_low_stock_threshold', 10 );
?>
<div class="wrap">
    <h1>UBO Dashboard Settings</h1>
    <form method="post">
        <?php wp_nonce_field( 'ubo_save_settings', 'ubo_settings_nonce' ); ?>
        <input type="hidden" name="ubo_form_id" value="api" />

        <h2>US Site — us.unitedbyom.com</h2>
        <table class="form-table">
            <tr><th>Site URL</th><td><input class="regular-text" type="text" name="ubo_us_url" value="<?php echo esc_attr( get_option('ubo_us_url') ); ?>" placeholder="https://us.unitedbyom.com" /></td></tr>
            <tr><th>Consumer Key</th><td><input class="regular-text" type="text" name="ubo_us_ck" value="<?php echo esc_attr( get_option('ubo_us_ck') ); ?>" placeholder="ck_..." /></td></tr>
            <tr><th>Consumer Secret</th><td><input class="regular-text" type="password" name="ubo_us_cs" value="<?php echo esc_attr( get_option('ubo_us_cs') ); ?>" placeholder="cs_..." /></td></tr>
            <tr><th>Webhook Secret</th><td><input class="regular-text" type="password" name="ubo_us_webhook_secret" value="<?php echo esc_attr( get_option('ubo_us_webhook_secret') ); ?>" placeholder="Webhook secret from WooCommerce" /><p class="description">Webhook URL: <code><?php echo esc_url( rest_url('ubo/v1/webhook/US') ); ?></code></p></td></tr>
        </table>

        <h2>India Site — in.unitedbyom.com</h2>
        <table class="form-table">
            <tr><th>Site URL</th><td><input class="regular-text" type="text" name="ubo_in_url" value="<?php echo esc_attr( get_option('ubo_in_url') ); ?>" placeholder="https://in.unitedbyom.com" /></td></tr>
            <tr><th>Consumer Key</th><td><input class="regular-text" type="text" name="ubo_in_ck" value="<?php echo esc_attr( get_option('ubo_in_ck') ); ?>" placeholder="ck_..." /></td></tr>
            <tr><th>Consumer Secret</th><td><input class="regular-text" type="password" name="ubo_in_cs" value="<?php echo esc_attr( get_option('ubo_in_cs') ); ?>" placeholder="cs_..." /></td></tr>
            <tr><th>Webhook Secret</th><td><input class="regular-text" type="password" name="ubo_in_webhook_secret" value="<?php echo esc_attr( get_option('ubo_in_webhook_secret') ); ?>" placeholder="Webhook secret from WooCommerce" /><p class="description">Webhook URL: <code><?php echo esc_url( rest_url('ubo/v1/webhook/India') ); ?></code></p></td></tr>
        </table>

        <?php submit_button( 'Save Settings' ); ?>
    </form>

    <h2>Alert Settings</h2>
    <form method="post">
        <?php wp_nonce_field( 'ubo_save_settings', 'ubo_settings_nonce' ); ?>
        <input type="hidden" name="ubo_form_id" value="alerts" />
        <table class="form-table">
            <tr>
                <th>Low Stock Threshold Source</th>
                <td>
                    <fieldset>
                        <label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                            <input type="checkbox" name="ubo_use_wc_threshold" value="1" <?php checked( $use_wc ); ?> id="ubo-wc-thresh-toggle" />
                            <strong>Use WooCommerce store threshold</strong>
                        </label>
                        <p class="description" style="margin-bottom:12px;">
                            When enabled, the plugin reads the low stock threshold directly from each WooCommerce store's settings
                            (<em>WooCommerce → Settings → Products → Inventory → Low stock threshold</em>).
                            The plugin threshold field below is ignored.
                        </p>
                        <?php if ( $use_wc ) : ?>
                            <p class="description" style="color:#059669;font-weight:500;">
                                ✅ Currently using WooCommerce native threshold.
                                <a href="#" id="ubo-disable-wc-thresh" style="color:#dc2626;margin-left:8px;">Disable &amp; use plugin threshold instead</a>
                            </p>
                        <?php endif; ?>
                    </fieldset>
                </td>
            </tr>
            <tr id="ubo-plugin-thresh-row" <?php echo $use_wc ? 'style="opacity:.45;pointer-events:none;"' : ''; ?>>
                <th>Plugin Low Stock Threshold</th>
                <td>
                    <input class="small-text" type="number" name="ubo_low_stock_threshold"
                        value="<?php echo esc_attr( $thresh ); ?>" min="1" <?php echo $use_wc ? 'disabled' : ''; ?> />
                    <span style="margin-left:8px;color:#64748b;font-size:13px;">units</span>
                    <p class="description">Products at or below this quantity will appear in the Alerts panel.</p>
                </td>
            </tr>
        </table>
        <?php submit_button( 'Save Alert Settings' ); ?>
    </form>
</div>

<script>
(function($) {
    // Toggle plugin threshold row based on WC checkbox
    $('#ubo-wc-thresh-toggle').on('change', function() {
        var disabled = $(this).is(':checked');
        $('#ubo-plugin-thresh-row').css({ opacity: disabled ? .45 : 1, 'pointer-events': disabled ? 'none' : '' });
        $('#ubo-plugin-thresh-row input').prop('disabled', disabled);
    });
    // Disable WC threshold link
    $('#ubo-disable-wc-thresh').on('click', function(e) {
        e.preventDefault();
        $('#ubo-wc-thresh-toggle').prop('checked', false).trigger('change');
        $(this).closest('p').hide();
    });
})(jQuery);
</script>
