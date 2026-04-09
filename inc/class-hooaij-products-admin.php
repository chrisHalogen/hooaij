<?php

/**
 * Hooaij Products Admin — CRUD interface under HID THEME > Products
 */

if (! defined('ABSPATH')) exit;

/**
 * Products admin page callback
 */
function hooaij_admin_products_page()
{
    global $wpdb;
    $table = $wpdb->prefix . 'hooaij_products';

    // ── Handle actions ───────────────────────────────────────────────────

    // Delete
    if (isset($_GET['action'], $_GET['id'], $_GET['_wpnonce']) && $_GET['action'] === 'delete') {
        if (wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'hooaij_delete_product')) {
            $wpdb->delete($table, ['id' => intval($_GET['id'])]);
            echo '<div class="notice notice-success is-dismissible"><p>Product deleted.</p></div>';
        }
    }

    // Toggle status
    if (isset($_GET['action'], $_GET['id'], $_GET['_wpnonce']) && $_GET['action'] === 'toggle_status') {
        if (wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'hooaij_toggle_product')) {
            $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", intval($_GET['id'])));
            if ($product) {
                $new_status = ($product->status === 'active') ? 'inactive' : 'active';
                $wpdb->update($table, ['status' => $new_status], ['id' => $product->id]);
                echo '<div class="notice notice-success is-dismissible"><p>Product status updated to <strong>' . esc_html($new_status) . '</strong>.</p></div>';
            }
        }
    }

    // Save (Add or Edit)
    if (isset($_POST['hooaij_save_product']) && check_admin_referer('hooaij_product_form', 'hooaij_product_nonce')) {
        $product_id   = intval($_POST['product_id'] ?? 0);
        $features_raw = sanitize_textarea_field(wp_unslash($_POST['features'] ?? ''));
        $features_arr = array_filter(array_map('trim', explode("\n", $features_raw)));

        $data = [
            'sku'          => sanitize_text_field(wp_unslash($_POST['sku'] ?? '')),
            'product_type' => sanitize_text_field(wp_unslash($_POST['product_type'] ?? 'tachoscope')),
            'name'         => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'description'  => sanitize_textarea_field(wp_unslash($_POST['description'] ?? '')),
            'price_ngn'    => floatval($_POST['price_ngn'] ?? 0),
            'features'     => wp_json_encode(array_values($features_arr)),
            'status'       => sanitize_text_field(wp_unslash($_POST['status'] ?? 'active')),
            'length_cm'    => ! empty($_POST['length_cm']) ? floatval($_POST['length_cm']) : null,
            'width_cm'     => ! empty($_POST['width_cm']) ? floatval($_POST['width_cm']) : null,
            'height_cm'    => ! empty($_POST['height_cm']) ? floatval($_POST['height_cm']) : null,
            'weight_kg'    => ! empty($_POST['weight_kg']) ? floatval($_POST['weight_kg']) : null,
        ];

        if (empty($data['sku']) || empty($data['name'])) {
            echo '<div class="notice notice-error is-dismissible"><p>SKU and Name are required.</p></div>';
        } else {
            if ($product_id > 0) {
                // SKU is immutable — remove from update payload to prevent accidental or malicious changes
                unset($data['sku']);
                $wpdb->update($table, $data, ['id' => $product_id]);
                echo '<div class="notice notice-success is-dismissible"><p>Product updated successfully.</p></div>';
            } else {
                $wpdb->insert($table, $data);
                echo '<div class="notice notice-success is-dismissible"><p>Product added successfully.</p></div>';
            }
        }
    }

    // ── Determine view ───────────────────────────────────────────────────
    $action     = sanitize_text_field($_GET['action'] ?? 'list');
    $edit_id    = intval($_GET['id'] ?? 0);
    $edit_product = null;
    if (in_array($action, ['edit', 'new']) && $edit_id > 0) {
        $edit_product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $edit_id));
    }

    // ── Fetch list data ──────────────────────────────────────────────────
    $filter_type = sanitize_text_field($_GET['type_filter'] ?? '');
    $search      = sanitize_text_field($_GET['s'] ?? '');
    $where       = 'WHERE 1=1';
    if ($filter_type) $where .= $wpdb->prepare(' AND product_type = %s', $filter_type);
    if ($search) $where .= $wpdb->prepare(' AND (name LIKE %s OR sku LIKE %s)', "%{$search}%", "%{$search}%");
    $products = $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY product_type, price_ngn ASC");

    $base_url  = admin_url('admin.php?page=hid-theme-products');
    $rate      = hooaij_get_exchange_rate();

    // ── Show Add/Edit Form ───────────────────────────────────────────────
    if ($action === 'new' || ($action === 'edit' && $edit_product)) {
        $p = $edit_product;
        $feats = ($p && ! empty($p->features)) ? implode("\n", json_decode($p->features, true)) : '';
?>
        <div class="wrap">
            <h1><?php echo $p ? 'Edit Product' : 'Add New Product'; ?></h1>
            <a href="<?php echo esc_url($base_url); ?>" class="page-title-action">← Back to Products</a>
            <hr>
            <form method="post" action="" style="max-width:800px; margin-top:20px;">
                <?php wp_nonce_field('hooaij_product_form', 'hooaij_product_nonce'); ?>
                <input type="hidden" name="product_id" value="<?php echo $p ? intval($p->id) : 0; ?>">

                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="sku">SKU <?php echo $p ? '' : '<span style="color:red">*</span>'; ?></label></th>
                        <td>
                            <?php if ($p): ?>
                                <input type="text" id="sku" name="sku" class="regular-text" value="<?php echo esc_attr($p->sku); ?>" readonly
                                    style="background:#f0f0f0; color:#555; cursor:not-allowed; border-color:#ccc;"
                                    title="SKU cannot be changed after a product is created">
                                <p class="description"><span style="color:#e57825;">🔒</span> The SKU is a permanent identifier and cannot be edited.</p>
                            <?php else: ?>
                                <input type="text" id="sku" name="sku" class="regular-text" value="" required>
                                <p class="description">Use a short unique code (e.g. <code>B-NS</code>, <code>BUTR-N</code>). Cannot be changed after saving.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="product_type">Product Type <span style="color:red">*</span></label></th>
                        <td>
                            <select id="product_type" name="product_type" onchange="toggleDimensions(this.value)">
                                <option value="tachoscope" <?php selected(($p->product_type ?? 'tachoscope'), 'tachoscope'); ?>>Tachoscope</option>
                                <option value="subscription" <?php selected(($p->product_type ?? ''), 'subscription'); ?>>Subscription</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="name">Product Name <span style="color:red">*</span></label></th>
                        <td><input type="text" id="name" name="name" class="large-text" value="<?php echo esc_attr($p->name ?? ''); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="description">Description</label></th>
                        <td><textarea id="description" name="description" class="large-text" rows="4"><?php echo esc_textarea($p->description ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="price_ngn">Price (NGN) <span style="color:red">*</span></label></th>
                        <td>
                            <input type="number" id="price_ngn" name="price_ngn" class="regular-text" value="<?php echo esc_attr($p->price_ngn ?? ''); ?>" step="0.01" min="0" required>
                            <?php if (! empty($p->price_ngn)): ?>
                                <p class="description">≈ $<?php echo number_format($p->price_ngn / $rate, 2); ?> USD @ ₦<?php echo number_format($rate, 0); ?>/$1</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr id="dimensions-row" style="<?php echo (($p->product_type ?? 'tachoscope') === 'subscription') ? 'display:none' : ''; ?>">
                        <th>Dimensions (cm) &amp; Weight (kg)</th>
                        <td>
                            <label>L: <input type="number" name="length_cm" class="small-text" value="<?php echo esc_attr($p->length_cm ?? ''); ?>" step="0.1"></label>
                            <label style="margin-left:10px">W: <input type="number" name="width_cm" class="small-text" value="<?php echo esc_attr($p->width_cm ?? ''); ?>" step="0.1"></label>
                            <label style="margin-left:10px">H: <input type="number" name="height_cm" class="small-text" value="<?php echo esc_attr($p->height_cm ?? ''); ?>" step="0.1"></label>
                            <label style="margin-left:10px">Wt: <input type="number" name="weight_kg" class="small-text" value="<?php echo esc_attr($p->weight_kg ?? ''); ?>" step="0.001"></label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="features">Features</label></th>
                        <td>
                            <textarea id="features" name="features" class="large-text" rows="5" placeholder="One feature per line"><?php echo esc_textarea($feats); ?></textarea>
                            <p class="description">Enter one feature per line. These display as bullet points on product pages.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="prod_status">Status</label></th>
                        <td>
                            <select id="prod_status" name="status">
                                <option value="active" <?php selected(($p->status ?? 'active'), 'active'); ?>>Active</option>
                                <option value="inactive" <?php selected(($p->status ?? ''), 'inactive'); ?>>Inactive</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <input type="submit" name="hooaij_save_product" class="button button-primary button-large" value="<?php echo $p ? 'Update Product' : 'Add Product'; ?>">
                    <a href="<?php echo esc_url($base_url); ?>" class="button button-secondary button-large" style="margin-left:10px;">Cancel</a>
                </p>
            </form>
        </div>
        <script>
            function toggleDimensions(type) {
                document.getElementById('dimensions-row').style.display = (type === 'tachoscope') ? '' : 'none';
            }
        </script>
    <?php
        return;
    }

    // ── Products List Table ──────────────────────────────────────────────
    ?>
    <style>
        .hooaij-products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .1);
        }

        .hooaij-products-table th,
        .hooaij-products-table td {
            padding: 10px 14px;
            border: 1px solid #ddd;
            font-size: 13px;
        }

        .hooaij-products-table th {
            background: #f9f9f9;
            font-weight: 600;
        }

        .hooaij-products-table tr:hover td {
            background: #fafafa;
        }

        .badge-tachoscope {
            background: #e57825;
            color: #fff;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-subscription {
            background: #BF946F;
            color: #fff;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-active {
            color: #27ae60;
            font-weight: 700;
        }

        .badge-inactive {
            color: #e74c3c;
            font-weight: 700;
        }

        .hooaij-row-actions {
            font-size: 12px;
            opacity: 1 !important;
            visibility: visible !important;
            display: block !important;
        }

        .hooaij-row-actions a {
            margin-right: 8px;
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

        /* Delete link special styling - already has inline color #e74c3c */
        .hooaij-row-actions a[style*="color:#e74c3c"],
        .hooaij-row-actions a[style*="color: #e74c3c"] {
            color: #dc3232 !important;
            border-color: #dc3232;
        }

        /* Toggle status link - we'll identify by href containing toggle_status */
        .hooaij-row-actions a[href*="toggle_status"] {
            color: #e57825 !important;
            border-color: #e57825;
        }
    </style>
    <div class="wrap">
        <h1 class="wp-heading-inline">Products</h1>
        <a href="<?php echo esc_url(add_query_arg(['action' => 'new'], $base_url)); ?>" class="page-title-action">Add New</a>

        <!-- Search & Filter -->
        <form method="get" style="margin:15px 0; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input type="hidden" name="page" value="hid-theme-products">
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search by name or SKU…" class="regular-text">
            <select name="type_filter">
                <option value="">All Types</option>
                <option value="tachoscope" <?php selected($filter_type, 'tachoscope'); ?>>Tachoscope</option>
                <option value="subscription" <?php selected($filter_type, 'subscription'); ?>>Subscription</option>
            </select>
            <button type="submit" class="button">Filter</button>
            <?php if ($search || $filter_type): ?>
                <a href="<?php echo esc_url($base_url); ?>" class="button">Clear</a>
            <?php endif; ?>
        </form>

        <p style="color:#666;">Showing <strong><?php echo count($products); ?></strong> product(s). Exchange rate: ₦<?php echo number_format($rate, 0); ?>/$1</p>

        <?php if (empty($products)): ?>
            <div style="background:#fff; padding:30px; text-align:center; border:1px solid #ddd; border-radius:4px;">
                <p>No products found. <a href="<?php echo esc_url(add_query_arg(['action' => 'new'], $base_url)); ?>">Add your first product</a> or run <strong>Seed Product Data</strong> from the Initialization page.</p>
            </div>
        <?php else: ?>
            <table class="hooaij-products-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Price (NGN)</th>
                        <th>Price (USD)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $prod):
                        $edit_url   = esc_url(add_query_arg(['action' => 'edit', 'id' => $prod->id], $base_url));
                        $del_url    = esc_url(wp_nonce_url(add_query_arg(['action' => 'delete', 'id' => $prod->id], $base_url), 'hooaij_delete_product'));
                        $toggle_url = esc_url(wp_nonce_url(add_query_arg(['action' => 'toggle_status', 'id' => $prod->id], $base_url), 'hooaij_toggle_product'));
                        $usd_price  = number_format($prod->price_ngn / $rate, 2);
                    ?>
                        <tr>
                            <td><code><?php echo esc_html($prod->sku); ?></code></td>
                            <td><?php echo esc_html($prod->name); ?></td>
                            <td>
                                <?php if ($prod->product_type === 'tachoscope'): ?>
                                    <span class="badge-tachoscope">Tachoscope</span>
                                <?php else: ?>
                                    <span class="badge-subscription">Subscription</span>
                                <?php endif; ?>
                            </td>
                            <td>₦<?php echo number_format($prod->price_ngn, 0); ?></td>
                            <td>$<?php echo $usd_price; ?></td>
                            <td>
                                <span class="<?php echo $prod->status === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo ucfirst(esc_html($prod->status)); ?>
                                </span>
                            </td>
                            <td class="hooaij-row-actions">
                                <a href="<?php echo $edit_url; ?>">Edit</a>
                                <a href="<?php echo $toggle_url; ?>"><?php echo $prod->status === 'active' ? 'Deactivate' : 'Activate'; ?></a>
                                <a href="<?php echo $del_url; ?>" style="color:#e74c3c;"
                                    onclick="return confirm('Delete this product? This cannot be undone.')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php
}
