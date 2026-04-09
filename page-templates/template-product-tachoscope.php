<?php

/**
 * Template Name: Product Tachoscope Page
 */
get_header();

// Fetch all active tachoscope products from DB
$products = hooaij_get_products_by_type('tachoscope');
$rate      = hooaij_get_exchange_rate();

// Build JS product map grouped by SKU prefix (category)
$category_map = [];
$prefix_map = [
    'B'    => ['label' => 'Bicycle',      'icon' => 'fas fa-bicycle'],
    'T'    => ['label' => 'Tricycle',     'icon' => 'fas fa-motorcycle'],
    'MB'   => ['label' => 'Motor Bike',   'icon' => 'fas fa-motorcycle'],
    'C'    => ['label' => 'Car',          'icon' => 'fas fa-car'],
    'BUTR' => ['label' => 'Bus & Truck',  'icon' => 'fas fa-truck-moving'],
    'H'    => ['label' => 'Handheld',     'icon' => 'fas fa-hand-holding'],
];

foreach ($products as $prod) {
    $sku_parts = explode('-', $prod->sku, 2);
    $prefix = $sku_parts[0];
    // BUTR prefix has a hyphen within, so handle specially
    if (strpos($prod->sku, 'BUTR-') === 0) {
        $prefix = 'BUTR';
    }
    if (!isset($category_map[$prefix])) {
        $category_map[$prefix] = [];
    }
    $p_usd = hooaij_ngn_to_usd($prod->price_ngn);
    $features_arr = is_string($prod->features) ? json_decode($prod->features, true) : ($prod->features ?? []);
    $category_map[$prefix][] = [
        'sku'        => $prod->sku,
        'name'       => $prod->name,
        'price_ngn'  => (float) $prod->price_ngn,
        'price_usd'  => round($p_usd, 2),
        'features'   => is_array($features_arr) ? $features_arr : [],
        'length_cm'  => $prod->length_cm ?? null,
        'width_cm'   => $prod->width_cm  ?? null,
        'height_cm'  => $prod->height_cm ?? null,
        'weight_kg'  => $prod->weight_kg ?? null,
    ];
}

$products_json = wp_json_encode($category_map);
$rate_json     = wp_json_encode((float) $rate);

// Also include description in each product entry
foreach ($category_map as $prefix => &$entries) {
    foreach ($entries as &$entry) {
        // Find the matching product for description
        foreach ($products as $prod) {
            if ($prod->sku === $entry['sku']) {
                $entry['description'] = $prod->description ?? '';
                break;
            }
        }
    }
}
unset($entries, $entry);
$products_json = wp_json_encode($category_map);
?>

<!-- Product Hero Section -->
<section class="page-hero">
    <div class="container animate-up">
        <h1>Tachoscope Device</h1>
        <p>A professional-grade digital speed measuring device engineered for Engineers, Technologists and Technicians.</p>
    </div>
</section>

<!-- Product Details -->
<section class="section">
    <div class="container">
        <div class="product-layout animate-up">

            <!-- Description Area -->
            <div class="product-details">
                <img src="https://hooaij.com/wp-content/uploads/2026/04/tachoscope-img.png" alt="Tachoscope Device"
                    style="width:100%; border-radius:var(--border-radius); margin-bottom:30px;">

                <span class="section-subtitle">Overview</span>
                <h2 class="section-title" style="font-size:2rem; margin-bottom:20px;">Description.</h2>

                <p class="intro-text-large" style="margin-bottom:30px;">The Tachoscope is a special digital speed
                    measuring device designed with high esthetic and ergonomic features — meant for Engineers,
                    Technologists and Technicians. It helps to showcase your profession with precision and style.</p>

                <p id="tacho-selected-description" class="product-description-reveal"></p>

                <p>Designed with precision in mind, the Tachoscope offers unparalleled accuracy. Built using advanced
                    technology components that ensure longevity and reliable readings in diverse industrial or
                    professional environments.</p>

                <!-- Dynamic features (update when variant changes) -->
                <h3 style="margin-top:40px; margin-bottom:15px;">Key Benefits</h3>
                <ul id="tachoscope-features-list" style="list-style-type:none; margin-bottom: 25px;">
                    <li style="margin-bottom:10px;"><i class="fas fa-check-circle" style="color:var(--primary); margin-right:10px;"></i> High Esthetic Design</li>
                    <li style="margin-bottom:10px;"><i class="fas fa-check-circle" style="color:var(--primary); margin-right:10px;"></i> Ergonomic Handling</li>
                    <li style="margin-bottom:10px;"><i class="fas fa-check-circle" style="color:var(--primary); margin-right:10px;"></i> Professional Grade Accuracy</li>
                </ul>

                <!-- Sister Company Notice -->
                <?php $sister_url = get_option('hooaij_company_website', ''); ?>
                <?php if ($sister_url): ?>
                    <div style="background: rgba(229, 120, 37, 0.05); border-left: 4px solid var(--primary); padding: 18px 22px; border-radius: 0 8px 8px 0; margin-top: 30px;">
                        <h4 style="margin: 0 0 10px; font-size: 1rem; color: var(--dark);">Professional Installation Services</h4>
                        <p style="margin: 0; font-size: 0.92rem; line-height: 1.5;">Need specialized installation for your Tachoscope? Visit our sister company for professional deployment services:
                            <a href="<?php echo esc_url($sister_url); ?>" target="_blank" style="color: var(--primary); font-weight: 700; text-decoration: underline;">Visit Website <i class="fas fa-external-link-alt" style="font-size: 0.8rem;"></i></a>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Physical Specs (shown when variant selected) -->
                <div id="tachoscope-specs" style="display:none; margin-top:30px;">
                    <h3 style="margin-bottom:15px;">Physical Specifications</h3>
                    <div class="specs-grid-display">
                        <div class="spec-cell"><span class="spec-val" id="spec-length">—</span><span class="spec-lbl">Length</span></div>
                        <div class="spec-cell"><span class="spec-val" id="spec-width">—</span><span class="spec-lbl">Width</span></div>
                        <div class="spec-cell"><span class="spec-val" id="spec-height">—</span><span class="spec-lbl">Height</span></div>
                        <div class="spec-cell"><span class="spec-val" id="spec-weight">—</span><span class="spec-lbl">Weight</span></div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="product-sidebar">
                <h3 style="font-size:1.5rem; margin-bottom:5px; font-family:'Source Sans Pro', sans-serif;">
                    Select Your Device
                </h3>
                <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:24px;">Choose your vehicle type, then select a variant.</p>

                <?php if (empty($products)): ?>
                    <div style="background:var(--light); border:1px solid var(--border-color); border-radius:var(--border-radius); padding:20px; text-align:center; color:var(--text-muted);">
                        <i class="fas fa-box-open" style="font-size:2rem; margin-bottom:10px; display:block;"></i>
                        Products are being updated. Please check back shortly.
                    </div>
                <?php else: ?>

                    <!-- ── Step 1: Vehicle Type Dropdown ── -->
                    <div class="form-group" style="margin-bottom:18px;">
                        <label for="tachoscope-vehicle-type" style="display:block; font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--dark); margin-bottom:8px;">
                            Step 1 — Vehicle Type
                        </label>
                        <select id="tachoscope-vehicle-type" class="form-control product-variant-select">
                            <option value="">— Choose a vehicle type —</option>
                            <?php foreach ($prefix_map as $prefix => $cat): ?>
                                <?php if (!isset($category_map[$prefix])) continue; ?>
                                <option value="<?php echo esc_attr($prefix); ?>"><?php echo esc_html($cat['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- ── Step 2: Variant Dropdown (revealed after Step 1) ── -->
                    <div id="tachoscope-variant-group" class="form-group" style="display:none; margin-bottom:18px;">
                        <label for="tachoscope-variant" style="display:block; font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--dark); margin-bottom:8px;">
                            Step 2 — Select Variant
                        </label>
                        <select id="tachoscope-variant" class="form-control product-variant-select">
                            <option value="">— Choose a variant —</option>
                        </select>
                    </div>

                    <!-- ── Price Display ── -->
                    <div class="attribute-box" id="tachoscope-price-box" style="display:none;">
                        <div class="attribute-item">
                            <span class="attribute-key">Brand:</span>
                            <span class="attribute-value">Hooaij</span>
                        </div>
                        <div class="attribute-item">
                            <span class="attribute-key">Price:</span>
                            <span class="attribute-value fee">
                                <span id="tachoscope-price-display" style="font-size:1.4rem; color:var(--primary); font-weight:700;">—</span>
                            </span>
                        </div>
                        <div class="attribute-item" style="display:block; padding-top:0; border:none;">
                            <small id="tachoscope-ngn-equiv" style="color:var(--text-muted); font-size:0.8rem;"></small>
                        </div>
                    </div>

                    <!-- ── Action Box ── -->
                    <div class="action-box product-order-section" id="tachoscope-action-box" style="display:none;">
                        <h4 style="margin-bottom:15px; font-size:1.1rem;">Place Your Order</h4>

                        <button id="tachoscope-order-btn" class="btn btn-primary btn-block"
                            style="font-size:1.05rem; padding:14px; justify-content:center; display:flex; align-items:center; gap:10px;">
                            Order Now <i class="fas fa-shopping-cart"></i>
                        </button>

                        <!-- Checkout Form (hidden by default) -->
                        <div id="tachoscope-checkout-wrapper" style="margin-top:20px; display:none;">
                            <?php
                            $first_product = reset($products);
                            if ($first_product) {
                                hooaij_render_checkout_form($first_product->sku);
                            }
                            ?>
                        </div>
                    </div>

                    <!-- ── Special AIDS Notice ── -->
                    <div class="special-aids-notice">
                        <p><strong>Special AIDS variant available:</strong> Certain vehicle categories include a <em>Special AIDS</em> variant featuring automated steering redirection for operators with physical limitations. Select the relevant vehicle type to see if this option is available.</p>
                    </div>

                <?php endif; ?>

                <p style="text-align:center; font-size:0.78rem; color:var(--text-muted); margin-top:12px;">
                    <i class="fas fa-lock" style="margin-right:4px;"></i> Secured by PayPal
                </p>

            </div><!-- /.product-sidebar -->

        </div><!-- /.product-layout -->
    </div>
</section>

<script>
    var hooaijTachoProducts = <?php echo $products_json; ?>;
</script>

<?php get_footer(); ?>