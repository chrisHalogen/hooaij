<?php

/**
 * Hooaij Orders Admin — All Orders view under HID THEME > Orders
 */

if (! defined('ABSPATH')) exit;

/**
 * Orders admin page callback
 */
function hooaij_admin_orders_page()
{
    global $wpdb;
    $orders_table = $wpdb->prefix . 'hooaij_orders';
    $base_url     = admin_url('admin.php?page=hid-theme-orders');

    // ── Handle status update ─────────────────────────────────────────────
    if (isset($_GET['action'], $_GET['id'], $_GET['_wpnonce']) && in_array($_GET['action'], ['mark_completed', 'mark_refunded'])) {
        if (wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'hooaij_update_order_' . intval($_GET['id']))) {
            $new_status = ($_GET['action'] === 'mark_completed') ? 'completed' : 'refunded';
            $wpdb->update($orders_table, ['status' => $new_status], ['id' => intval($_GET['id'])]);
            echo '<div class="notice notice-success is-dismissible"><p>Order status updated to <strong>' . esc_html($new_status) . '</strong>.</p></div>';
        }
    }

    // ── Determine view ───────────────────────────────────────────────────
    $action   = sanitize_text_field($_GET['action'] ?? 'list');
    $view_id  = intval($_GET['id'] ?? 0);

    // ── Order Detail View ────────────────────────────────────────────────
    if ($action === 'view' && $view_id > 0) {
        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$orders_table} WHERE id = %d", $view_id));
        if (! $order) {
            echo '<div class="wrap"><p>Order not found. <a href="' . esc_url($base_url) . '">Back to Orders</a></p></div>';
            return;
        }

        $status_colors = [
            'completed' => '#27ae60',
            'pending'   => '#f39c12',
            'failed'    => '#e74c3c',
            'refunded'  => '#95a5a6',
        ];
        $color = $status_colors[$order->status] ?? '#888';
?>
        <style>
            .order-detail-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 25px;
                max-width: 1000px;
                margin-top: 20px;
            }

            .order-detail-card {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 6px;
                padding: 20px 24px;
            }

            .order-detail-card h3 {
                margin: 0 0 15px;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: .5px;
                color: #e57825;
                padding-bottom: 8px;
                border-bottom: 2px solid #e57825;
            }

            .od-row {
                display: flex;
                justify-content: space-between;
                padding: 7px 0;
                border-bottom: 1px dashed #eee;
                font-size: 13px;
            }

            .od-row:last-child {
                border-bottom: none;
            }

            .od-label {
                font-weight: 600;
                color: #121212;
            }

            .od-value {
                color: #4a4a4a;
                text-align: right;
                max-width: 60%;
                word-break: break-word;
            }

            .status-badge {
                padding: 3px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 700;
                text-transform: uppercase;
                color: #fff;
                display: inline-block;
            }
        </style>
        <div class="wrap">
            <h1>Order Detail</h1>
            <a href="<?php echo esc_url($base_url); ?>" class="page-title-action">← Back to Orders</a>

            <div style="display:flex; align-items:center; gap:15px; margin:15px 0;">
                <h2 style="margin:0;">Order #<?php echo esc_html($order->receipt_no); ?></h2>
                <span class="status-badge" style="background:<?php echo esc_attr($color); ?>">
                    <?php echo esc_html(ucfirst($order->status)); ?>
                </span>
                <?php if ($order->status === 'pending'): ?>
                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'mark_completed', 'id' => $order->id], $base_url), 'hooaij_update_order_' . $order->id)); ?>"
                        class="button button-primary" style="background:#27ae60; border-color:#27ae60;">Mark Completed</a>
                <?php endif; ?>
                <?php if (in_array($order->status, ['completed', 'pending'])): ?>
                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action' => 'mark_refunded', 'id' => $order->id], $base_url), 'hooaij_update_order_' . $order->id)); ?>"
                        class="button" style="color:#e74c3c; border-color:#e74c3c;"
                        onclick="return confirm('Mark this order as refunded?')">Mark Refunded</a>
                <?php endif; ?>
            </div>

            <div class="order-detail-grid">

                <div class="order-detail-card">
                    <h3>Customer Information</h3>
                    <div class="od-row"><span class="od-label">Name</span><span class="od-value"><?php echo esc_html($order->customer_name); ?></span></div>
                    <div class="od-row"><span class="od-label">Email</span><span class="od-value"><a href="mailto:<?php echo esc_attr($order->customer_email); ?>"><?php echo esc_html($order->customer_email); ?></a></span></div>
                    <div class="od-row"><span class="od-label">Phone</span><span class="od-value"><?php echo esc_html($order->customer_phone); ?></span></div>
                    <?php if ($order->shipping_address): ?>
                        <div class="od-row"><span class="od-label">Shipping Address</span><span class="od-value"><?php echo nl2br(esc_html($order->shipping_address)); ?></span></div>
                    <?php endif; ?>
                </div>

                <div class="order-detail-card">
                    <h3>Order Information</h3>
                    <div class="od-row"><span class="od-label">Product</span><span class="od-value"><?php echo esc_html($order->product_name); ?></span></div>
                    <div class="od-row"><span class="od-label">SKU</span><span class="od-value"><code><?php echo esc_html($order->product_sku); ?></code></span></div>
                    <div class="od-row"><span class="od-label">Type</span><span class="od-value" style="text-transform:capitalize"><?php echo esc_html($order->order_type); ?></span></div>
                    <?php if ($order->order_type === 'tachoscope'): ?>
                        <div class="od-row"><span class="od-label">Quantity</span><span class="od-value"><?php echo esc_html($order->quantity); ?></span></div>
                    <?php endif; ?>
                    <?php if ($order->plan_tier): ?>
                        <div class="od-row"><span class="od-label">Plan</span><span class="od-value"><?php echo esc_html(ucfirst($order->plan_tier)); ?></span></div>
                    <?php endif; ?>
                    <?php if ($order->unique_code): ?>
                        <div class="od-row"><span class="od-label">Activation Code</span><span class="od-value"><code><?php echo esc_html($order->unique_code); ?></code></span></div>
                    <?php endif; ?>
                    <div class="od-row"><span class="od-label">Date</span><span class="od-value"><?php echo esc_html(date('M j, Y g:i a', strtotime($order->created_at))); ?></span></div>
                </div>

                <div class="order-detail-card">
                    <h3>Payment Details</h3>
                    <div class="od-row"><span class="od-label">Amount (NGN)</span><span class="od-value">₦<?php echo number_format($order->amount_ngn, 0); ?></span></div>
                    <div class="od-row"><span class="od-label">Amount (USD)</span><span class="od-value" style="color:#e57825; font-weight:700;">$<?php echo number_format($order->amount_usd, 2); ?></span></div>
                    <div class="od-row"><span class="od-label">Exchange Rate</span><span class="od-value">₦<?php echo number_format($order->exchange_rate_used, 0); ?>/$1</span></div>
                    <div class="od-row"><span class="od-label">PayPal Order ID</span><span class="od-value" style="font-size:11px; word-break:break-all;"><?php echo esc_html($order->paypal_order_id); ?></span></div>
                    <div class="od-row"><span class="od-label">PayPal Capture ID</span><span class="od-value" style="font-size:11px; word-break:break-all;"><?php echo esc_html($order->paypal_capture_id); ?></span></div>
                </div>

                <?php if ($order->notes): ?>
                    <div class="order-detail-card">
                        <h3>Notes</h3>
                        <p style="font-size:13px; color:#4a4a4a; margin:0;"><?php echo nl2br(esc_html($order->notes)); ?></p>
                    </div>
                <?php endif; ?>

            </div>

            <p style="margin-top:20px;">
                <a href="<?php echo esc_url($base_url); ?>" class="button button-secondary">← Back to All Orders</a>
            </p>
        </div>
    <?php
        return;
    }

    // ── Orders List Table ────────────────────────────────────────────────
    $filter_status = sanitize_text_field($_GET['status_filter'] ?? '');
    $filter_type   = sanitize_text_field($_GET['type_filter'] ?? '');
    $search        = sanitize_text_field($_GET['s'] ?? '');
    $where         = 'WHERE 1=1';
    if ($filter_status) $where .= $wpdb->prepare(' AND status = %s', $filter_status);
    if ($filter_type)   $where .= $wpdb->prepare(' AND order_type = %s', $filter_type);
    if ($search)        $where .= $wpdb->prepare(' AND (customer_name LIKE %s OR customer_email LIKE %s OR receipt_no LIKE %s)', "%{$search}%", "%{$search}%", "%{$search}%");

    $orders = $wpdb->get_results("SELECT * FROM {$orders_table} {$where} ORDER BY created_at DESC");

    $status_colors = [
        'completed' => '#27ae60',
        'pending'   => '#f39c12',
        'failed'    => '#e74c3c',
        'refunded'  => '#95a5a6',
    ];
    ?>
    <style>
        .hooaij-orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .1);
        }

        .hooaij-orders-table th,
        .hooaij-orders-table td {
            padding: 10px 12px;
            border: 1px solid #ddd;
            font-size: 13px;
        }

        .hooaij-orders-table th {
            background: #f9f9f9;
            font-weight: 600;
        }

        .hooaij-orders-table tr:hover td {
            background: #fafafa;
        }

        .status-badge {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #fff;
            display: inline-block;
        }

        .badge-tachoscope {
            background: #e57825;
            color: #fff;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        .badge-subscription {
            background: #BF946F;
            color: #fff;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
        }

        .hooaij-row-actions {
            opacity: 1 !important;
            visibility: visible !important;
            display: block !important;
        }

        .hooaij-row-actions a {
            margin-right: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            padding: 2px 6px;
            border-radius: 3px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            color: #2271b1 !important;
            display: inline-block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .hooaij-row-actions a:hover {
            background: #fff;
            border-color: #2271b1;
            text-decoration: underline;
        }

        /* Complete link - identify by href containing mark_completed or text "Complete" */
        .hooaij-row-actions a[href*="mark_completed"],
        .hooaij-row-actions a[style*="color:#27ae60"] {
            color: #46b450 !important;
            border-color: #46b450;
        }

        /* Refund link - identify by href containing mark_refunded or text "Refund" */
        .hooaij-row-actions a[href*="mark_refunded"],
        .hooaij-row-actions a[style*="color:#e74c3c"] {
            color: #dc3232 !important;
            border-color: #dc3232;
        }
    </style>
    <div class="wrap">
        <h1>All Orders</h1>

        <!-- Search & Filter -->
        <form method="get" style="margin:15px 0; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input type="hidden" name="page" value="hid-theme-orders">
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search name, email, receipt…" class="regular-text">
            <select name="status_filter">
                <option value="">All Statuses</option>
                <?php foreach (['pending', 'completed', 'failed', 'refunded'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php selected($filter_status, $s); ?>><?php echo ucfirst($s); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="type_filter">
                <option value="">All Types</option>
                <option value="tachoscope" <?php selected($filter_type, 'tachoscope'); ?>>Tachoscope</option>
                <option value="subscription" <?php selected($filter_type, 'subscription'); ?>>Subscription</option>
            </select>
            <button type="submit" class="button">Filter</button>
            <?php if ($search || $filter_status || $filter_type): ?>
                <a href="<?php echo esc_url($base_url); ?>" class="button">Clear</a>
            <?php endif; ?>
        </form>

        <p style="color:#666;">Showing <strong><?php echo count($orders); ?></strong> order(s).</p>

        <?php if (empty($orders)): ?>
            <div style="background:#fff; padding:30px; text-align:center; border:1px solid #ddd; border-radius:4px;">
                <p>No orders found matching your criteria.</p>
            </div>
        <?php else: ?>
            <table class="hooaij-orders-table">
                <thead>
                    <tr>
                        <th>Receipt No</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order):
                        $view_url      = esc_url(add_query_arg(['action' => 'view', 'id' => $order->id], $base_url));
                        $complete_url  = esc_url(wp_nonce_url(add_query_arg(['action' => 'mark_completed', 'id' => $order->id], $base_url), 'hooaij_update_order_' . $order->id));
                        $refund_url    = esc_url(wp_nonce_url(add_query_arg(['action' => 'mark_refunded', 'id' => $order->id], $base_url), 'hooaij_update_order_' . $order->id));
                        $sc = $status_colors[$order->status] ?? '#888';
                    ?>
                        <tr>
                            <td><a href="<?php echo $view_url; ?>"><strong><?php echo esc_html($order->receipt_no); ?></strong></a></td>
                            <td>
                                <?php echo esc_html($order->customer_name); ?><br>
                                <small style="color:#888;"><?php echo esc_html($order->customer_email); ?></small>
                            </td>
                            <td style="max-width:200px;"><?php echo esc_html($order->product_name); ?>
                                <br><small style="color:#888;"><code><?php echo esc_html($order->product_sku); ?></code></small>
                            </td>
                            <td>
                                <?php if ($order->order_type === 'tachoscope'): ?>
                                    <span class="badge-tachoscope">Device</span>
                                <?php else: ?>
                                    <span class="badge-subscription">Subscription</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color:#e57825;">$<?php echo number_format($order->amount_usd, 2); ?></strong><br>
                                <small style="color:#888;">₦<?php echo number_format($order->amount_ngn, 0); ?></small>
                            </td>
                            <td>
                                <span class="status-badge" style="background:<?php echo esc_attr($sc); ?>">
                                    <?php echo esc_html(ucfirst($order->status)); ?>
                                </span>
                            </td>
                            <td style="white-space:nowrap;">
                                <?php echo esc_html(date('M j, Y', strtotime($order->created_at))); ?><br>
                                <small style="color:#888;"><?php echo esc_html(date('g:i a', strtotime($order->created_at))); ?></small>
                            </td>
                            <td class="hooaij-row-actions">
                                <a href="<?php echo $view_url; ?>">View</a>
                                <?php if ($order->status === 'pending'): ?>
                                    <a href="<?php echo $complete_url; ?>" style="color:#27ae60;">Complete</a>
                                <?php endif; ?>
                                <?php if (in_array($order->status, ['completed', 'pending'])): ?>
                                    <a href="<?php echo $refund_url; ?>" style="color:#e74c3c;"
                                        onclick="return confirm('Mark as refunded?')">Refund</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php
}
