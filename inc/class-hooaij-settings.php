<?php

/**
 * Hooaij Admin Settings Page
 * Manages PayPal keys, currency display, exchange rate and notification emails
 */

if (! defined('ABSPATH')) exit;

/**
 * Admin settings page callback — registered in functions.php
 */
function hooaij_admin_settings_page()
{

    // ── Handle Form Save ──────────────────────────────────────────────────
    if (isset($_POST['hooaij_save_settings']) && check_admin_referer('hooaij_settings_action', 'hooaij_settings_nonce')) {

        $fields = [
            'hooaij_display_currency'          => 'sanitize_text_field',
            'hooaij_exchange_rate'             => 'floatval',
            'hooaij_paypal_mode'               => 'sanitize_text_field',
            'hooaij_paypal_live_client_id'     => 'sanitize_text_field',
            'hooaij_paypal_live_secret'        => 'sanitize_text_field',
            'hooaij_paypal_test_client_id'     => 'sanitize_text_field',
            'hooaij_paypal_test_secret'        => 'sanitize_text_field',
            'hooaij_admin_notification_emails' => 'sanitize_textarea_field',
            'hooaij_sister_company_emails'     => 'sanitize_textarea_field',
            'hooaij_company_website'           => 'esc_url_raw',
        ];

        foreach ($fields as $key => $sanitizer) {
            if (isset($_POST[$key])) {
                update_option($key, call_user_func($sanitizer, wp_unslash($_POST[$key])));
            }
        }

        echo '<div class="notice notice-success is-dismissible"><p><strong>Settings saved successfully.</strong></p></div>';
    }

    // ── Read Current Values ───────────────────────────────────────────────
    $display_currency  = get_option('hooaij_display_currency', 'USD');
    $exchange_rate     = get_option('hooaij_exchange_rate', 1400);
    $paypal_mode       = get_option('hooaij_paypal_mode', 'live');
    $live_client_id    = get_option('hooaij_paypal_live_client_id', '');
    $live_secret       = get_option('hooaij_paypal_live_secret', '');
    $test_client_id    = get_option('hooaij_paypal_test_client_id', '');
    $test_secret       = get_option('hooaij_paypal_test_secret', '');
    $admin_emails      = get_option('hooaij_admin_notification_emails', '');
    $sister_emails     = get_option('hooaij_sister_company_emails', '');
    $company_website   = get_option('hooaij_company_website', '');
?>

    <style>
        .hooaij-settings-section {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 25px 30px;
            margin-bottom: 25px;
            max-width: 900px;
        }

        .hooaij-settings-section h2 {
            margin-top: 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #e57825;
            color: #121212;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .hooaij-settings-section h2 .dashicons {
            color: #e57825;
        }

        .form-table th {
            width: 220px;
        }

        .badge-sandbox {
            background: #f0ad4e;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-live {
            background: #5cb85c;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .mode-indicator {
            display: inline-block;
            margin-left: 10px;
            vertical-align: middle;
        }
    </style>

    <div class="wrap">
        <h1><span class="dashicons dashicons-admin-settings" style="font-size:28px; color:#e57825; margin-right:8px;"></span> HID Theme Settings</h1>
        <p style="color: #666; max-width: 700px;">Configure PayPal integration, pricing display, and notification settings for the Hooaij Products &amp; Orders system.</p>

        <form method="post" action="">
            <?php wp_nonce_field('hooaij_settings_action', 'hooaij_settings_nonce'); ?>

            <!-- ── Display & Currency ─────────────────────────────────── -->
            <div class="hooaij-settings-section">
                <h2><span class="dashicons dashicons-money-alt"></span> Display &amp; Currency</h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="hooaij_display_currency">Display Prices In</label></th>
                        <td>
                            <select name="hooaij_display_currency" id="hooaij_display_currency">
                                <option value="USD" <?php selected($display_currency, 'USD'); ?>>USD ($) — Default &amp; PayPal Compatible</option>
                                <option value="NGN" <?php selected($display_currency, 'NGN'); ?>>NGN (₦) — Nigerian Naira</option>
                            </select>
                            <p class="description">
                                <strong>Default is USD</strong> for seamless PayPal checkout. Product prices are stored in NGN internally and converted to USD using the exchange rate below.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hooaij_exchange_rate">Exchange Rate (NGN per $1 USD)</label></th>
                        <td>
                            <input type="number" name="hooaij_exchange_rate" id="hooaij_exchange_rate"
                                value="<?php echo esc_attr($exchange_rate); ?>"
                                class="regular-text" step="1" min="1">
                            <p class="description">
                                This rate is used to convert NGN prices to USD for product display and PayPal checkout amounts.
                                Default: <strong>1400</strong>. Update when the exchange rate changes.
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ── PayPal Configuration ───────────────────────────────── -->
            <div class="hooaij-settings-section">
                <h2>
                    <span class="dashicons dashicons-cart"></span> PayPal Configuration
                    <span class="mode-indicator">
                        <?php if ($paypal_mode === 'live'): ?>
                            <span class="badge-live">LIVE MODE ACTIVE</span>
                        <?php else: ?>
                            <span class="badge-sandbox">SANDBOX MODE ACTIVE</span>
                        <?php endif; ?>
                    </span>
                </h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="hooaij_paypal_mode">Active Mode</label></th>
                        <td>
                            <select name="hooaij_paypal_mode" id="hooaij_paypal_mode">
                                <option value="sandbox" <?php selected($paypal_mode, 'sandbox'); ?>>Sandbox (Testing)</option>
                                <option value="live" <?php selected($paypal_mode, 'live'); ?>>Live (Production)</option>
                            </select>
                            <p class="description">Set to <strong>Sandbox</strong> for testing, <strong>Live</strong> for real payments.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <strong>Sandbox Keys</strong>
                            <span class="badge-sandbox" style="display:block; margin-top:5px;">Testing</span>
                        </th>
                        <td>
                            <p>
                                <label for="hooaij_paypal_test_client_id"><strong>Client ID</strong></label><br>
                                <input type="text" name="hooaij_paypal_test_client_id" id="hooaij_paypal_test_client_id"
                                    value="<?php echo esc_attr($test_client_id); ?>"
                                    class="large-text" placeholder="AYourSandboxClientID...">
                            </p>
                            <p>
                                <label for="hooaij_paypal_test_secret"><strong>Secret Key</strong></label><br>
                                <input type="password" name="hooaij_paypal_test_secret" id="hooaij_paypal_test_secret"
                                    value="<?php echo esc_attr($test_secret); ?>"
                                    class="large-text" placeholder="••••••••••••••••">
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <strong>Live Keys</strong>
                            <span class="badge-live" style="display:block; margin-top:5px;">Production</span>
                        </th>
                        <td>
                            <p>
                                <label for="hooaij_paypal_live_client_id"><strong>Client ID</strong></label><br>
                                <input type="text" name="hooaij_paypal_live_client_id" id="hooaij_paypal_live_client_id"
                                    value="<?php echo esc_attr($live_client_id); ?>"
                                    class="large-text" placeholder="AYourLiveClientID...">
                            </p>
                            <p>
                                <label for="hooaij_paypal_live_secret"><strong>Secret Key</strong></label><br>
                                <input type="password" name="hooaij_paypal_live_secret" id="hooaij_paypal_live_secret"
                                    value="<?php echo esc_attr($live_secret); ?>"
                                    class="large-text" placeholder="••••••••••••••••">
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ── Notifications ──────────────────────────────────────── -->
            <div class="hooaij-settings-section">
                <h2><span class="dashicons dashicons-email-alt"></span> Notifications</h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="hooaij_admin_notification_emails">Admin Notification Emails</label></th>
                        <td>
                            <textarea name="hooaij_admin_notification_emails" id="hooaij_admin_notification_emails"
                                class="large-text" rows="3"
                                placeholder="admin@hooaij.com, manager@hooaij.com"><?php echo esc_textarea($admin_emails); ?></textarea>
                            <p class="description">
                                Comma-separated email addresses. <strong>All listed emails</strong> receive a notification for every successful order (all product types).
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hooaij_sister_company_emails">Sister Company Emails <small>(Tachoscope Orders)</small></label></th>
                        <td>
                            <textarea name="hooaij_sister_company_emails" id="hooaij_sister_company_emails"
                                class="large-text" rows="3"
                                placeholder="install@sisterco.com, dispatch@sisterco.com"><?php echo esc_textarea($sister_emails); ?></textarea>
                            <p class="description">
                                Comma-separated email addresses. Only triggered for <strong>Tachoscope device orders</strong> that require physical delivery and installation.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="hooaij_company_website">Company/Sister Co. Website URL</label></th>
                        <td>
                            <input type="url" name="hooaij_company_website" id="hooaij_company_website"
                                value="<?php echo esc_attr($company_website); ?>"
                                class="regular-text" placeholder="https://innerberg.com">
                            <p class="description">Used as the confirmation link in sister company email notifications.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <p class="submit" style="max-width: 900px;">
                <input type="submit" name="hooaij_save_settings" class="button button-primary button-large" value="Save Settings">
            </p>
        </form>
    </div>
<?php
}

// ─── Helper Functions ──────────────────────────────────────────────────────

/**
 * Get the active PayPal mode: 'live' or 'sandbox'
 */
function hooaij_get_paypal_mode()
{
    return get_option('hooaij_paypal_mode', 'live');
}

/**
 * Get the active PayPal Client ID based on current mode
 */
function hooaij_get_paypal_client_id()
{
    $mode = hooaij_get_paypal_mode();
    return ($mode === 'live')
        ? get_option('hooaij_paypal_live_client_id', '')
        : get_option('hooaij_paypal_test_client_id', '');
}

/**
 * Get the active PayPal Secret based on current mode
 */
function hooaij_get_paypal_secret()
{
    $mode = hooaij_get_paypal_mode();
    return ($mode === 'live')
        ? get_option('hooaij_paypal_live_secret', '')
        : get_option('hooaij_paypal_test_secret', '');
}

/**
 * Get the PayPal API base URL based on current mode
 */
function hooaij_get_paypal_api_base()
{
    return (hooaij_get_paypal_mode() === 'live')
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com';
}

/**
 * Get the NGN→USD exchange rate
 */
function hooaij_get_exchange_rate()
{
    return floatval(get_option('hooaij_exchange_rate', 1400));
}

/**
 * Get the display currency setting
 */
function hooaij_get_display_currency()
{
    return get_option('hooaij_display_currency', 'USD');
}

/**
 * Get admin notification email addresses as an array
 */
function hooaij_get_admin_emails()
{
    $raw = get_option('hooaij_admin_notification_emails', '');
    if (empty(trim($raw))) return [];
    return array_filter(array_map('trim', explode(',', $raw)));
}

/**
 * Get sister company email addresses as an array
 */
function hooaij_get_sister_company_emails()
{
    $raw = get_option('hooaij_sister_company_emails', '');
    if (empty(trim($raw))) return [];
    return array_filter(array_map('trim', explode(',', $raw)));
}
