<?php

/**
 * Template Name: Product PES Page
 */
get_header();

// Fetch PES plans (subscription type, SKU prefix 'PES-')
$plans = hooaij_get_products_by_type('subscription', 'PES');
$rate  = hooaij_get_exchange_rate();

// Build a keyed map: sku => { price_usd, price_ngn, features, description }
$pes_map = [];
foreach ($plans as $plan) {
    $p_usd = hooaij_ngn_to_usd($plan->price_ngn);
    $features_arr = is_string($plan->features) ? json_decode($plan->features, true) : ($plan->features ?? []);
    $pes_map[$plan->sku] = [
        'sku'         => $plan->sku,
        'name'        => $plan->name,
        'price_ngn'   => (float) $plan->price_ngn,
        'price_usd'   => round($p_usd, 2),
        'features'    => is_array($features_arr) ? $features_arr : [],
        'description' => $plan->description ?? '',
    ];
}

// Determine initial plan: default to PES-ACAD-BASIC if available, else first entry
$initial_plan = null;
if (isset($pes_map['PES-ACAD-BASIC'])) {
    foreach ($plans as $p) {
        if ($p->sku === 'PES-ACAD-BASIC') {
            $initial_plan = $p;
            break;
        }
    }
}
if (!$initial_plan && !empty($plans)) {
    $initial_plan = $plans[0];
}
$init_tier = $initial_plan ? strtolower(end(explode('-', $initial_plan->sku))) : 'basic';
?>

<!-- Product Hero Section -->
<section class="page-hero">
    <div class="container animate-up">
        <h1 style="font-size:2.2rem;">Performance Evaluation Software</h1>
        <p>Enhancing workforce capabilities through intelligent evaluation and analytics.</p>
    </div>
</section>

<!-- Product Details Grid/Layout -->
<section class="section">
    <div class="container">
        <div class="product-layout animate-up">

            <!-- Description Area -->
            <div class="product-details">
                <img src="https://hooaij.com/wp-content/uploads/2026/03/pes.png"
                    alt="Performance Evaluation Software"
                    style="width:100%; border-radius:var(--border-radius); margin-bottom:30px;">

                <span class="section-subtitle">Overview</span>
                <h2 class="section-title" style="font-size:2rem; margin-bottom:20px;">Description.</h2>

                <p class="intro-text-large" style="margin-bottom:30px;">The Performance Evaluation Software (PES)
                    helps in evaluating human work effort and all related performance metrics. It is designed for
                    Academic Institutions, Public and Civil Service agencies, and companies of all sizes.</p>

                <p id="pes-selected-description" class="product-description-reveal"></p>

                <p>With approximately 27 unique performance models, this software provides analytical insights leading
                    to enriched worker productivity, satisfaction, and comprehensive strategic management data.</p>

                <h3 style="margin-top:40px; margin-bottom:15px;">Target Users</h3>
                <ul style="list-style-type:none;">
                    <li style="margin-bottom:10px;"><i class="fas fa-university" style="color:var(--primary); margin-right:10px;"></i> Academic environments</li>
                    <li style="margin-bottom:10px;"><i class="fas fa-building" style="color:var(--primary); margin-right:10px;"></i> Corporate and company setups</li>
                    <li style="margin-bottom:10px;"><i class="fas fa-landmark" style="color:var(--primary); margin-right:10px;"></i> Public and civil service organizations</li>
                    <li style="margin-bottom:10px;"><i class="fas fa-chart-line" style="color:var(--primary); margin-right:10px;"></i> HR departments and management consultancies</li>
                </ul>
            </div>

            <!-- Sidebar -->
            <div class="product-sidebar">
                <h3 style="font-size:1.5rem; margin-bottom:10px; font-family:'Source Sans Pro', sans-serif;">
                    Plans &amp; Pricing
                </h3>
                <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:20px;">Select a subscription plan and purchase securely via PayPal.</p>

                <?php if (empty($plans)): ?>
                    <!-- Fallback if no DB plans yet -->
                    <div class="attribute-box">
                        <div class="attribute-item">
                            <span class="attribute-key">Brand:</span>
                            <span class="attribute-value">Producing Company</span>
                        </div>
                        <div class="attribute-item">
                            <span class="attribute-key">Fee:</span>
                            <span class="attribute-value fee" style="font-size:1rem; color:var(--dark);">Depends on Selected Package</span>
                        </div>
                    </div>
                    <div class="action-box" style="margin-top:15px;">
                        <p style="margin-bottom:15px; font-size:0.95rem; color:#d9534f; font-weight:600;">
                            <i class="fas fa-exclamation-circle"></i> Pricing plans are being set up. Please book an appointment.
                        </p>
                        <a href="<?php echo esc_url(site_url('/bookings/')); ?>" class="btn btn-primary btn-block"
                            style="padding:15px; font-size:1.1rem; justify-content:center; display:flex; align-items:center; gap:10px;">
                            Book An Appointment <i class="fas fa-calendar-alt"></i>
                        </a>
                    </div>

                <?php else: ?>

                    <!-- Institution + Tier Dropdown Selector -->
                    <div class="plan-selection-v2 product-order-section">

                        <!-- Institution Dropdown -->
                        <div class="form-group" style="margin-bottom:16px;">
                            <label for="pes-institution" style="display:block; font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--dark); margin-bottom:8px;">
                                Institution Type
                            </label>
                            <select id="pes-institution" class="form-control product-variant-select">
                                <option value="">— Choose your institution type —</option>
                                <option value="ACAD">Academic</option>
                                <option value="PUB">Public / Civil Service</option>
                                <option value="CORP">Company / Corporate</option>
                            </select>
                        </div>

                        <!-- Tier Dropdown -->
                        <div class="form-group" style="margin-bottom:20px;">
                            <label for="pes-tier" style="display:block; font-weight:700; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px; color:var(--dark); margin-bottom:8px;">
                                Subscription Tier
                            </label>
                            <select id="pes-tier" class="form-control product-variant-select">
                                <option value="">— Choose a tier —</option>
                                <option value="BASIC">Basic</option>
                                <option value="STD">Standard</option>
                                <option value="PREM">Premium</option>
                            </select>
                        </div>

                        <!-- Dynamic Features Display -->
                        <div id="dynamic-plan-features" class="dynamic-features-box">
                            <h5 style="font-size: 0.95rem; margin-bottom: 12px; color: var(--dark); border-bottom: 1px solid #eee; padding-bottom: 8px;">
                                Included Features:
                            </h5>
                            <ul id="features-list-target" class="plan-features-v2">
                                <!-- Populated by JS -->
                            </ul>
                        </div>

                        <!-- Order Button -->
                        <button id="trigger-checkout-btn" class="btn btn-primary btn-block" style="margin-top:20px; padding:16px; font-size:1.1rem;">
                            Order Now — <span id="current-price-display">$0.00</span> <i class="fas fa-arrow-right"></i>
                        </button>

                        <!-- Unified Checkout Form (Hidden by default) -->
                        <div id="unified-checkout-wrapper" style="display:none; margin-top:25px;">
                            <?php
                            if ($initial_plan) {
                                hooaij_render_checkout_form($initial_plan->sku, $init_tier);
                            }
                            ?>
                        </div>
                    </div>

                <?php endif; ?>

                <p style="text-align:center; font-size:0.78rem; color:var(--text-muted); margin-top:12px;">
                    <i class="fas fa-lock" style="margin-right:4px;"></i> Secured by PayPal · All prices in USD
                </p>
            </div><!-- /.product-sidebar -->

        </div><!-- /.product-layout -->
    </div>
</section>

<script>
    var hooaijPesProducts = <?php echo wp_json_encode($pes_map); ?>;
</script>

<?php get_footer(); ?>