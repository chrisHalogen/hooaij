<?php

/**
 * Hooaij Order Handler
 * Checkout form rendering, AJAX processing, PayPal verification, email dispatch
 */

if (! defined('ABSPATH')) exit;

// ─── Register AJAX Actions ─────────────────────────────────────────────────
add_action('wp_ajax_nopriv_hooaij_process_order', 'hooaij_ajax_process_order');
add_action('wp_ajax_hooaij_process_order', 'hooaij_ajax_process_order');

// ─── Checkout Form Renderer ────────────────────────────────────────────────

/**
 * Render a guest checkout form for a given product SKU.
 *
 * @param string $sku        Product SKU
 * @param string $plan_tier  Optional plan tier for subscriptions
 */
function hooaij_render_checkout_form($sku, $plan_tier = '')
{
    $product = hooaij_get_product($sku);
    if (! $product) return;

    $price_ngn    = floatval($product->price_ngn);
    $price_usd    = hooaij_ngn_to_usd($price_ngn);
    $display_price = hooaij_format_price($price_ngn);
    $is_tachoscope = ($product->product_type === 'tachoscope');
    $form_id       = 'checkout-form-' . esc_attr($sku);
    $btn_id        = 'paypal-button-container-' . esc_attr($sku);

    $country_codes = [
        '+234' => '🇳🇬 Nigeria (+234)',
        '+1'   => '🇺🇸 USA (+1)',
        '+44'  => '🇬🇧 UK (+44)',
        '+233' => '🇬🇭 Ghana (+233)',
        '+254' => '🇰🇪 Kenya (+254)',
        '+27'  => '🇿🇦 South Africa (+27)',
        '+251' => '🇪🇹 Ethiopia (+251)',
        '+255' => '🇹🇿 Tanzania (+255)',
        '+256' => '🇺🇬 Uganda (+256)',
        '+221' => '🇸🇳 Senegal (+221)',
        '+212' => '🇲🇦 Morocco (+212)',
        '+20'  => '🇪🇬 Egypt (+20)',
        '+49'  => '🇩🇪 Germany (+49)',
        '+33'  => '🇫🇷 France (+33)',
        '+971' => '🇦🇪 UAE (+971)',
    ];
?>
    <div class="hooaij-checkout-form checkout-panel"
        id="<?php echo $form_id; ?>"
        data-sku="<?php echo esc_attr($sku); ?>"
        data-price-ngn="<?php echo esc_attr($price_ngn); ?>"
        data-price-usd="<?php echo esc_attr($price_usd); ?>"
        data-type="<?php echo esc_attr($product->product_type); ?>"
        data-plan="<?php echo esc_attr($plan_tier); ?>">

        <h4 style="margin-bottom: 20px; font-size: 1.1rem; color: var(--dark); font-family: 'Source Sans Pro', sans-serif;">
            <i class="fas fa-user-check" style="color: var(--primary); margin-right: 8px;"></i>
            Your Details
        </h4>

        <!-- Full Name -->
        <div class="form-group">
            <label class="form-label" for="<?php echo $form_id; ?>-name">Full Name <span style="color:var(--primary)">*</span></label>
            <input type="text" id="<?php echo $form_id; ?>-name" class="form-control checkout-name"
                placeholder="John Doe" required>
        </div>

        <!-- Phone -->
        <div class="form-group">
            <label class="form-label">Phone Number <span style="color:var(--primary)">*</span></label>
            <div class="phone-input-group">
                <select class="form-control country-code-select" style="width:175px; flex-shrink:0;">
                    <?php foreach ($country_codes as $code => $label): ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($code, '+234'); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="tel" class="form-control phone-number-input"
                    placeholder="803 580 9083" required>
            </div>
            <input type="hidden" class="checkout-phone-full">
        </div>

        <!-- Email -->
        <div class="form-group">
            <label class="form-label" for="<?php echo $form_id; ?>-email">Email Address <span style="color:var(--primary)">*</span></label>
            <input type="email" id="<?php echo $form_id; ?>-email" class="form-control checkout-email"
                placeholder="you@example.com" required>
        </div>

        <?php if ($is_tachoscope): ?>
            <!-- Quantity (tachoscope only) -->
            <div class="form-group">
                <label class="form-label" for="<?php echo $form_id; ?>-qty">Quantity <span style="color:var(--primary)">*</span></label>
                <input type="number" id="<?php echo $form_id; ?>-qty" class="form-control checkout-qty"
                    value="1" min="1" max="50" required>
            </div>

            <!-- Shipping Address (required for tachoscope) -->
            <div class="form-group">
                <label class="form-label" for="<?php echo $form_id; ?>-address">
                    Shipping Address <span style="color:var(--primary)">*</span>
                </label>
                <textarea id="<?php echo $form_id; ?>-address" class="form-control checkout-address"
                    rows="3" placeholder="Full delivery address including city and state..." required></textarea>
            </div>
        <?php endif; ?>

        <!-- Hidden Fields -->
        <input type="hidden" class="checkout-sku" value="<?php echo esc_attr($sku); ?>">
        <input type="hidden" class="checkout-plan-tier" value="<?php echo esc_attr($plan_tier); ?>">
        <input type="hidden" class="checkout-nonce" value="<?php echo wp_create_nonce('hooaij_checkout_nonce'); ?>">

        <!-- Price Summary -->
        <div class="checkout-price-summary">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <div>
                    <strong style="font-size:0.9rem; color:var(--dark);">Total to Pay:</strong>
                    <?php if ($is_tachoscope): ?>
                        <small style="color:var(--text-muted); font-size:0.8rem;"> (× quantity)</small>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="checkout-price-display">$<?php echo esc_html(number_format($price_usd, 2)); ?></span>
                    <small style="display:block; text-align:right; color:var(--text-muted); font-size:0.75rem;">
                        ≈ <?php echo hooaij_format_naira($price_ngn); ?> @ ₦<?php echo number_format(hooaij_get_exchange_rate(), 0); ?>/$1
                    </small>
                </div>
            </div>
        </div>

        <!-- Validation Error Display -->
        <div class="checkout-error" style="display:none; background:#fdf2f2; border:1px solid #f5c6cb; border-radius:var(--border-radius); padding:12px 16px; margin-bottom:15px; color:#c0392b; font-size:0.9rem;">
            <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>
            <span class="checkout-error-message"></span>
        </div>

        <!-- PayPal Button Container -->
        <div id="<?php echo $btn_id; ?>" class="paypal-btn-container" style="margin-top:15px; min-height:55px;">
            <div class="paypal-loading" style="text-align:center; padding:15px; color:var(--text-muted); font-size:0.9rem;">
                <i class="fas fa-spinner fa-spin"></i> Loading payment options...
            </div>
        </div>

        <p style="text-align:center; font-size:0.75rem; color:var(--text-muted); margin-top:10px;">
            <i class="fas fa-lock" style="margin-right:4px;"></i>
            Secured by PayPal · Credit/Debit Card accepted
        </p>

    </div>
<?php
}

// ─── AJAX: Process PayPal Order ────────────────────────────────────────────

/**
 * AJAX handler: Verify PayPal payment and create order record.
 */
function hooaij_ajax_process_order()
{
    // 1. Verify nonce
    if (! isset($_POST['nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'hooaij_checkout_nonce')) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh and try again.']);
    }

    // 2. Sanitize & collect inputs
    $customer_name    = sanitize_text_field(wp_unslash($_POST['customer_name'] ?? ''));
    $customer_email   = sanitize_email(wp_unslash($_POST['customer_email'] ?? ''));
    $customer_phone   = sanitize_text_field(wp_unslash($_POST['customer_phone'] ?? ''));
    $shipping_address = sanitize_textarea_field(wp_unslash($_POST['shipping_address'] ?? ''));
    $sku              = sanitize_text_field(wp_unslash($_POST['sku'] ?? ''));
    $plan_tier        = sanitize_text_field(wp_unslash($_POST['plan_tier'] ?? ''));
    $quantity         = max(1, intval($_POST['quantity'] ?? 1));
    $paypal_order_id  = sanitize_text_field(wp_unslash($_POST['paypal_order_id'] ?? ''));

    // 3. Validate required fields
    if (empty($customer_name)) {
        wp_send_json_error(['message' => 'Full name is required.']);
    }
    if (! is_email($customer_email)) {
        wp_send_json_error(['message' => 'A valid email address is required.']);
    }
    if (empty($customer_phone)) {
        wp_send_json_error(['message' => 'Phone number is required.']);
    }
    if (empty($sku)) {
        wp_send_json_error(['message' => 'Product selection is invalid.']);
    }
    if (empty($paypal_order_id)) {
        wp_send_json_error(['message' => 'No payment reference received.']);
    }

    // 4. Fetch product from DB
    $product = hooaij_get_product($sku);
    if (! $product) {
        wp_send_json_error(['message' => 'The selected product could not be found.']);
    }

    // 5. Validate shipping address for tachoscope
    if ($product->product_type === 'tachoscope' && empty($shipping_address)) {
        wp_send_json_error(['message' => 'Shipping address is required for device orders.']);
    }

    // 6. Calculate expected amounts
    $rate         = hooaij_get_exchange_rate();
    $amount_ngn   = $product->price_ngn * $quantity;
    $amount_usd   = hooaij_ngn_to_usd($amount_ngn);

    // 7. Verify PayPal capture
    $capture = hooaij_verify_paypal_capture($paypal_order_id, $amount_usd);
    if (is_wp_error($capture)) {
        // Send declined email to customer
        hooaij_dispatch_email('declined', [
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'product_name'  => $product->name,
            'amount_usd'    => number_format($amount_usd, 2),
            'order_date'    => date('F j, Y, g:i a'),
            'retry_url'     => get_permalink(),
        ]);
        wp_send_json_error(['message' => 'Payment verification failed: ' . $capture->get_error_message()]);
    }

    $paypal_capture_id = $capture['capture_id'];

    // 8. Generate receipt & unique code
    $receipt_no   = hooaij_generate_receipt_no();
    $unique_code  = ($product->product_type === 'subscription') ? hooaij_generate_unique_code() : null;
    $order_date   = current_time('mysql');

    // 9. Insert order into DB
    global $wpdb;
    $orders_table = $wpdb->prefix . 'hooaij_orders';

    $inserted = $wpdb->insert($orders_table, [
        'receipt_no'        => $receipt_no,
        'customer_name'     => $customer_name,
        'customer_email'    => $customer_email,
        'customer_phone'    => $customer_phone,
        'shipping_address'  => $shipping_address,
        'product_sku'       => $sku,
        'product_name'      => $product->name,
        'order_type'        => $product->product_type,
        'quantity'          => $quantity,
        'plan_tier'         => $plan_tier,
        'amount_ngn'        => $amount_ngn,
        'amount_usd'        => $amount_usd,
        'exchange_rate_used' => $rate,
        'paypal_order_id'   => $paypal_order_id,
        'paypal_capture_id' => $paypal_capture_id,
        'unique_code'       => $unique_code,
        'status'            => 'completed',
        'created_at'        => $order_date,
    ]);

    if (! $inserted) {
        wp_send_json_error(['message' => 'Order could not be saved. Please contact support with your PayPal reference: ' . $paypal_order_id]);
    }

    $order_id = $wpdb->insert_id;

    // 10. If subscription: create subscription record
    if ($product->product_type === 'subscription') {
        $subs_table  = $wpdb->prefix . 'hooaij_subscriptions';
        $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));
        $wpdb->insert($subs_table, [
            'order_id'     => $order_id,
            'customer_email' => $customer_email,
            'product_sku'  => $sku,
            'plan_tier'    => $plan_tier,
            'unique_code'  => $unique_code,
            'status'       => 'active',
            'start_date'   => $order_date,
            'expiry_date'  => $expiry_date,
        ]);
    }

    // 11. Shared email vars
    $email_vars = [
        'receipt_no'        => $receipt_no,
        'customer_name'     => $customer_name,
        'customer_email'    => $customer_email,
        'customer_phone'    => $customer_phone,
        'shipping_address'  => $shipping_address,
        'product_name'      => $product->name,
        'sku'               => $sku,
        'order_type'        => $product->product_type,
        'quantity'          => $quantity,
        'plan_tier'         => ucfirst($plan_tier),
        'quantity_or_plan'  => ($product->product_type === 'tachoscope') ? "Qty: {$quantity}" : ucfirst($plan_tier) . ' Plan',
        'amount_usd'        => number_format($amount_usd, 2),
        'amount_ngn'        => number_format($amount_ngn, 0),
        'exchange_rate'     => number_format($rate, 0),
        'paypal_order_id'   => $paypal_order_id,
        'paypal_capture_id' => $paypal_capture_id,
        'unique_code'       => $unique_code ?? '',
        'order_date'        => date('F j, Y \a\t g:i a', strtotime($order_date)),
        'products_url'      => esc_url(site_url('/products/')),
        'retry_url'         => esc_url(get_permalink()),
        'admin_orders_url'  => esc_url(admin_url('admin.php?page=hid-theme-orders')),
        'company_website'   => esc_url(get_option('hooaij_company_website', site_url())),
        'site_url'          => esc_url(site_url()),
        'year'              => date('Y'),
    ];

    // 12. Send customer success email
    hooaij_dispatch_email('success_customer', $email_vars);

    // 13. Send admin notification emails
    hooaij_dispatch_email('success_admin', $email_vars);

    // 14. If tachoscope: send sister company email
    if ($product->product_type === 'tachoscope') {
        $email_vars['length_cm'] = $product->length_cm ?? 'N/A';
        $email_vars['width_cm']  = $product->width_cm ?? 'N/A';
        $email_vars['height_cm'] = $product->height_cm ?? 'N/A';
        $email_vars['weight_kg'] = $product->weight_kg ?? 'N/A';
        hooaij_dispatch_email('sister_company', $email_vars);
    }

    // 15. Return success response
    wp_send_json_success([
        'receipt_no'    => $receipt_no,
        'unique_code'   => $unique_code,
        'product_name'  => esc_html($product->name),
        'amount_usd'    => number_format($amount_usd, 2),
        'amount_ngn'    => number_format($amount_ngn, 0),
        'order_type'    => $product->product_type,
        'order_date'    => date('F j, Y \a\t g:i a', strtotime($order_date)),
        'message'       => 'Payment confirmed! Your order has been placed successfully.',
    ]);
}

// ─── PayPal Verification ───────────────────────────────────────────────────

/**
 * Verify a PayPal order capture via the REST API.
 *
 * @param string $paypal_order_id
 * @param float  $expected_usd
 * @return array|WP_Error Returns capture data array or WP_Error on failure.
 */
function hooaij_verify_paypal_capture($paypal_order_id, $expected_usd)
{
    $client_id = hooaij_get_paypal_client_id();
    $secret    = hooaij_get_paypal_secret();
    $api_base  = hooaij_get_paypal_api_base();

    if (empty($client_id) || empty($secret)) {
        return new WP_Error('paypal_not_configured', 'PayPal credentials are not configured.');
    }

    // Step 1: Get access token
    $token_response = wp_remote_post($api_base . '/v1/oauth2/token', [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $secret),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ],
        'body'    => 'grant_type=client_credentials',
        'timeout' => 20,
    ]);

    if (is_wp_error($token_response)) {
        return new WP_Error('paypal_token_error', 'Could not connect to PayPal: ' . $token_response->get_error_message());
    }

    $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
    if (empty($token_body['access_token'])) {
        return new WP_Error('paypal_token_invalid', 'PayPal authentication failed.');
    }

    $access_token = $token_body['access_token'];

    // Step 2: Fetch order details
    $order_response = wp_remote_get($api_base . '/v2/checkout/orders/' . $paypal_order_id, [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
        ],
        'timeout' => 20,
    ]);

    if (is_wp_error($order_response)) {
        return new WP_Error('paypal_order_error', 'Could not fetch order from PayPal: ' . $order_response->get_error_message());
    }

    $order_data = json_decode(wp_remote_retrieve_body($order_response), true);

    // Step 3: Validate status
    if (empty($order_data['status']) || $order_data['status'] !== 'COMPLETED') {
        return new WP_Error('paypal_not_completed', 'PayPal order is not completed. Status: ' . ($order_data['status'] ?? 'unknown'));
    }

    // Step 4: Validate capture amount
    $capture    = $order_data['purchase_units'][0]['payments']['captures'][0] ?? null;
    if (! $capture) {
        return new WP_Error('paypal_no_capture', 'No capture record found in PayPal order.');
    }

    $captured_amount   = floatval($capture['amount']['value'] ?? 0);
    $captured_currency = strtoupper($capture['amount']['currency_code'] ?? '');
    $tolerance         = 0.10; // $0.10 tolerance

    if ($captured_currency !== 'USD') {
        return new WP_Error('paypal_wrong_currency', 'Unexpected currency in PayPal capture: ' . $captured_currency);
    }

    if (abs($captured_amount - $expected_usd) > $tolerance) {
        return new WP_Error('paypal_amount_mismatch', sprintf(
            'Amount mismatch. Expected $%.2f, got $%.2f.',
            $expected_usd,
            $captured_amount
        ));
    }

    return [
        'capture_id'       => $capture['id'],
        'captured_amount'  => $captured_amount,
        'status'           => $capture['status'],
    ];
}

// ─── Email Dispatcher ──────────────────────────────────────────────────────

/**
 * Send a templated email.
 *
 * @param string $type    'success_customer' | 'success_admin' | 'declined' | 'sister_company'
 * @param array  $vars    Key-value pairs for template variable replacement
 */
function hooaij_dispatch_email($type, $vars)
{
    $templates = [
        'success_customer' => 'email-payment-success-customer.html',
        'success_admin'    => 'email-payment-success-admin.html',
        'declined'         => 'email-payment-declined-customer.html',
        'sister_company'   => 'email-new-order-sister-company.html',
        'contact_form'     => 'email-contact-form.html',
        'appointment_booking' => 'email-appointment-booking.html',
    ];

    if (! isset($templates[$type])) return;

    $template_file = get_template_directory() . '/email-templates/' . $templates[$type];
    if (! file_exists($template_file)) return;

    $html = file_get_contents($template_file);

    // Replace {{variables}} with actual values
    foreach ($vars as $key => $value) {
        $html = str_replace('{{' . $key . '}}', esc_html((string) $value), $html);
    }

    // Handle conditional blocks based on order type
    $order_type = $vars['order_type'] ?? '';

    if ($order_type === 'subscription') {
        // Show subscription blocks, remove tachoscope blocks
        $html = preg_replace('/\{\{#if_subscription\}\}(.*?)\{\{\/if_subscription\}\}/s', '$1', $html);
        $html = preg_replace('/\{\{#if_tachoscope\}\}(.*?)\{\{\/if_tachoscope\}\}/s', '', $html);
        $html = preg_replace('/\{\{#if_plan\}\}(.*?)\{\{\/if_plan\}\}/s', '$1', $html);
        $html = preg_replace('/\{\{#if_quantity\}\}(.*?)\{\{\/if_quantity\}\}/s', '', $html);
    } elseif ($order_type === 'tachoscope') {
        // Show tachoscope blocks, remove subscription blocks
        $html = preg_replace('/\{\{#if_tachoscope\}\}(.*?)\{\{\/if_tachoscope\}\}/s', '$1', $html);
        $html = preg_replace('/\{\{#if_subscription\}\}(.*?)\{\{\/if_subscription\}\}/s', '', $html);
        $html = preg_replace('/\{\{#if_quantity\}\}(.*?)\{\{\/if_quantity\}\}/s', '$1', $html);
        $html = preg_replace('/\{\{#if_plan\}\}(.*?)\{\{\/if_plan\}\}/s', '', $html);
    } else {
        // Remove all conditional blocks
        $html = preg_replace('/\{\{#if_[a-z_]+\}\}(.*?)\{\{\/if_[a-z_]+\}\}/s', '', $html);
    }

    // Remove any remaining unresolved template tags
    $html = preg_replace('/\{\{[a-z_]+\}\}/', '', $html);

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    // Determine recipients
    switch ($type) {
        case 'success_customer':
        case 'declined':
            $to = [$vars['customer_email'] ?? ''];
            break;
        case 'success_admin':
        case 'contact_form':
        case 'appointment_booking':
            $to = hooaij_get_admin_emails();
            break;
        case 'sister_company':
            $to = hooaij_get_sister_company_emails();
            break;
        default:
            $to = [];
    }

    if (empty($to)) return;

    // Determine subject
    switch ($type) {
        case 'success_customer':
            $subject = 'Payment Confirmed – Receipt #' . ($vars['receipt_no'] ?? '');
            break;
        case 'success_admin':
            $subject = 'New Order Received – ' . ($vars['receipt_no'] ?? '') . ' | ' . ($vars['product_name'] ?? '');
            break;
        case 'declined':
            $subject = 'Payment Not Processed – ' . ($vars['product_name'] ?? '');
            break;
        case 'contact_form':
            $subject = 'New Message from Hooaij Contact Form';
            break;
        case 'appointment_booking':
            $subject = 'New Appointment Request from Hooaij Portal';
            break;
        case 'sister_company':
            $subject = '[ACTION REQUIRED] New Tachoscope Order – ' . ($vars['receipt_no'] ?? '');
            break;
        default:
            $subject = 'Hooaij Notification';
    }

    // Send to each recipient individually
    foreach ($to as $email) {
        if (is_email($email)) {
            wp_mail($email, $subject, $html, $headers);
        }
    }
}
