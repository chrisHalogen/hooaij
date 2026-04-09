<?php

/**
 * Hooaij Database Layer
 * Handles custom table creation, seeding, and utility functions
 */

if (! defined('ABSPATH')) exit;

/**
 * Create all custom DB tables using dbDelta
 */
function hooaij_create_db_tables()
{
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $charset_collate = $wpdb->get_charset_collate();

    // ── Products Table ────────────────────────────────────────────────────
    $products_table = $wpdb->prefix . 'hooaij_products';
    $sql_products   = "CREATE TABLE {$products_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        sku VARCHAR(100) NOT NULL,
        product_type VARCHAR(50) NOT NULL DEFAULT 'tachoscope',
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price_ngn DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        length_cm DECIMAL(8,2) NULL,
        width_cm DECIMAL(8,2) NULL,
        height_cm DECIMAL(8,2) NULL,
        weight_kg DECIMAL(8,3) NULL,
        features LONGTEXT NULL,
        metadata LONGTEXT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY sku (sku)
    ) {$charset_collate};";

    // ── Orders Table ──────────────────────────────────────────────────────
    $orders_table = $wpdb->prefix . 'hooaij_orders';
    $sql_orders   = "CREATE TABLE {$orders_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        receipt_no VARCHAR(100) NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(50) NOT NULL,
        shipping_address TEXT NULL,
        product_sku VARCHAR(100) NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        order_type VARCHAR(50) NOT NULL,
        quantity INT(11) NOT NULL DEFAULT 1,
        plan_tier VARCHAR(50) NULL,
        amount_ngn DECIMAL(12,2) NOT NULL,
        amount_usd DECIMAL(12,2) NOT NULL,
        exchange_rate_used DECIMAL(10,2) NOT NULL DEFAULT 1400.00,
        paypal_order_id VARCHAR(255) NULL,
        paypal_capture_id VARCHAR(255) NULL,
        unique_code VARCHAR(100) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        notes TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY receipt_no (receipt_no)
    ) {$charset_collate};";

    // ── Subscriptions Table ───────────────────────────────────────────────
    $subs_table = $wpdb->prefix . 'hooaij_subscriptions';
    $sql_subs   = "CREATE TABLE {$subs_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id BIGINT(20) UNSIGNED NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        product_sku VARCHAR(100) NOT NULL,
        plan_tier VARCHAR(50) NOT NULL,
        unique_code VARCHAR(100) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        expiry_date DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_code (unique_code)
    ) {$charset_collate};";

    dbDelta($sql_products);
    dbDelta($sql_orders);
    dbDelta($sql_subs);

    return true;
}

/**
 * Seed products from products_seed.json
 *
 * @return int|WP_Error Number of seeded products or WP_Error on failure.
 */
function hooaij_seed_products()
{
    global $wpdb;

    $seed_file = get_template_directory() . '/products_seed.json';
    if (! file_exists($seed_file)) {
        return new WP_Error('seed_file_missing', 'products_seed.json not found in theme directory.');
    }

    $json_content = file_get_contents($seed_file);
    $products     = json_decode($json_content, true);

    if (json_last_error() !== JSON_ERROR_NONE || ! is_array($products)) {
        return new WP_Error('invalid_json', 'products_seed.json contains invalid JSON.');
    }

    $table = $wpdb->prefix . 'hooaij_products';
    $count = 0;

    foreach ($products as $product) {
        $data = [
            'sku'          => sanitize_text_field($product['sku']),
            'product_type' => sanitize_text_field($product['product_type']),
            'name'         => sanitize_text_field($product['name']),
            'description'  => isset($product['description']) ? sanitize_textarea_field($product['description']) : '',
            'price_ngn'    => floatval($product['price_ngn']),
            'features'     => isset($product['features']) ? wp_json_encode($product['features']) : null,
            'metadata'     => isset($product['metadata']) ? wp_json_encode($product['metadata']) : null,
            'status'       => 'active',
        ];

        if (isset($product['length_cm'])) $data['length_cm'] = floatval($product['length_cm']);
        if (isset($product['width_cm']))  $data['width_cm']  = floatval($product['width_cm']);
        if (isset($product['height_cm'])) $data['height_cm'] = floatval($product['height_cm']);
        if (isset($product['weight_kg'])) $data['weight_kg'] = floatval($product['weight_kg']);

        $wpdb->replace($table, $data);
        $count++;
    }

    return $count;
}

/**
 * Get a single product by SKU
 *
 * @param string $sku
 * @return object|null
 */
function hooaij_get_product($sku)
{
    global $wpdb;
    $table   = $wpdb->prefix . 'hooaij_products';
    $product = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE sku = %s AND status = 'active' LIMIT 1",
        sanitize_text_field($sku)
    ));
    if ($product) {
        $product->features = ! empty($product->features) ? json_decode($product->features, true) : [];
        $product->metadata = ! empty($product->metadata) ? json_decode($product->metadata, true) : [];
    }
    return $product;
}

/**
 * Get all products (active) by type, optionally filtered by SKU prefix
 *
 * @param string $type       'tachoscope' or 'subscription'
 * @param string $sku_prefix Optional prefix to filter SKUs (e.g. 'PES-')
 * @return array
 */
function hooaij_get_products_by_type($type, $sku_prefix = '')
{
    global $wpdb;
    $table = $wpdb->prefix . 'hooaij_products';

    $query = "SELECT * FROM {$table} WHERE product_type = %s AND status = 'active'";
    $args  = [sanitize_text_field($type)];

    if (! empty($sku_prefix)) {
        $query .= " AND sku LIKE %s";
        $args[] = $sku_prefix . '%';
    }

    $query .= " ORDER BY price_ngn ASC";

    $products = $wpdb->get_results($wpdb->prepare($query, $args));

    foreach ($products as &$product) {
        $product->features = ! empty($product->features) ? json_decode($product->features, true) : [];
        $product->metadata = ! empty($product->metadata) ? json_decode($product->metadata, true) : [];
    }
    return $products;
}

/**
 * Format price for front-end display based on admin currency setting.
 * Default display is USD (PayPal compatible). NGN prices are stored in DB and converted.
 *
 * @param float $price_ngn
 * @return string
 */
function hooaij_format_price($price_ngn)
{
    $display_currency = get_option('hooaij_display_currency', 'USD');
    if ($display_currency === 'NGN') {
        return '₦' . number_format($price_ngn, 0);
    }
    $rate = hooaij_get_exchange_rate();
    $usd  = $price_ngn / $rate;
    return '$' . number_format($usd, 2);
}

/**
 * Convert NGN to USD using stored exchange rate
 *
 * @param float $price_ngn
 * @return float
 */
function hooaij_ngn_to_usd($price_ngn)
{
    $rate = hooaij_get_exchange_rate();
    return round($price_ngn / $rate, 2);
}

/**
 * Format a raw NGN amount with currency symbol
 *
 * @param float $amount
 * @return string
 */
function hooaij_format_naira($amount)
{
    return '₦' . number_format($amount, 0);
}

/**
 * Generate a unique receipt number e.g. HOOAIJ-20260326-A8X2K3
 *
 * @return string
 */
function hooaij_generate_receipt_no()
{
    $prefix = 'HOOAIJ';
    $date   = date('Ymd');
    $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    return "{$prefix}-{$date}-{$random}";
}

/**
 * Generate a formatted unique activation code for subscriptions e.g. A1B2-C3D4-E5F6-G7H8
 *
 * @return string
 */
function hooaij_generate_unique_code()
{
    $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $len    = strlen($chars);
    $groups = [];
    for ($i = 0; $i < 4; $i++) {
        $seg = '';
        for ($j = 0; $j < 4; $j++) {
            $seg .= $chars[random_int(0, $len - 1)];
        }
        $groups[] = $seg;
    }
    return implode('-', $groups);
}

/**
 * Check if all three hooaij DB tables exist
 *
 * @return bool
 */
function hooaij_tables_exist()
{
    global $wpdb;
    $tables = [
        $wpdb->prefix . 'hooaij_products',
        $wpdb->prefix . 'hooaij_orders',
        $wpdb->prefix . 'hooaij_subscriptions',
    ];
    foreach ($tables as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return false;
        }
    }
    return true;
}

/**
 * Get row counts for each custom table (used by Monitoring page)
 *
 * @return array
 */
function hooaij_get_table_stats()
{
    global $wpdb;
    $definitions = [
        'products'      => $wpdb->prefix . 'hooaij_products',
        'orders'        => $wpdb->prefix . 'hooaij_orders',
        'subscriptions' => $wpdb->prefix . 'hooaij_subscriptions',
    ];
    $stats = [];
    foreach ($definitions as $key => $table) {
        $exists         = ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table);
        $count          = $exists ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}") : 0;
        $stats[$key]  = ['table' => $table, 'exists' => $exists, 'count' => $count];
    }
    return $stats;
}
