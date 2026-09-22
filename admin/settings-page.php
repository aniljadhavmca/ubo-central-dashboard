<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_POST['ubo_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ubo_settings_nonce'] ) ), 'ubo_save_settings' ) ) {
    if ( current_user_can( 'manage_options' ) ) {
        $fields = [ 'ubo_us_url', 'ubo_us_ck', 'ubo_us_cs', 'ubo_us_webhook_secret', 'ubo_in_url', 'ubo_in_ck', 'ubo_in_cs', 'ubo_in_webhook_secret', 'ubo_low_stock_threshold' ];
        foreach ( $fields as $field ) {
            update_option( $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ?? '' ) ) );
        }
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }
}
?>
<div class="wrap">
    <h1>UBO Dashboard Settings</h1>
    <form method="post">
        <?php wp_nonce_field( 'ubo_save_settings', 'ubo_settings_nonce' ); ?>

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
        <table class="form-table">
            <tr>
                <th>Low Stock Threshold</th>
                <td>
                    <input class="small-text" type="number" name="ubo_low_stock_threshold" value="<?php echo esc_attr( get_option('ubo_low_stock_threshold', 10) ); ?>" min="1" />
                    <p class="description">Products at or below this quantity will appear in the Alerts panel.</p>
                </td>
            </tr>
        </table>
        <?php submit_button( 'Save Settings' ); ?>
    </form>
</div>
