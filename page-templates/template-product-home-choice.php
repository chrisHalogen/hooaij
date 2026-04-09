<?php

/**
 * Template Name: Product Home Choice Page
 */
get_header();

// Fetch HCSS plans (subscription type, SKU prefix 'HCSN-')
$plans = hooaij_get_products_by_type('subscription', 'HCSN');
$rate  = hooaij_get_exchange_rate();
?>

<!-- Product Hero Section -->
<section class="page-hero">
    <div class="container animate-up">
        <h1 style="font-size:2.5rem;">Home Choice Security System</h1>
        <p>Bringing smart security into private and corporate properties.</p>
    </div>
</section>

<!-- Product Details Grid/Layout -->
<section class="section">
    <div class="container">
        <div class="product-layout animate-up">

            <!-- Description Area -->
            <div class="product-details">
                <img src="https://hooaij.com/wp-content/uploads/2026/03/home-choice.png"
                    alt="Home Choice Security System"
                    style="width:100%; border-radius:var(--border-radius); margin-bottom:30px;">

                <span class="section-subtitle">Overview</span>
                <h2 class="section-title" style="font-size:2rem; margin-bottom:20px;">Description.</h2>

                <p class="intro-text-large" style="margin-bottom:30px;">The HCSS package is a project package that
                    comprises smart devices and services (for security purposes) that help to make your house a smart
                    house — meant for private/corporate individuals with a fenced structure.</p>

                <p>Through robust installation of connected sensors, cameras, and automated monitoring systems, your
                    home becomes vastly safer and more adaptive. Prevent unauthorized access and receive immediate
                    alerts for complete peace of mind.</p>

                <h3 style="margin-top:40px; margin-bottom:15px;">Smart Integration Features</h3>
                <ul style="list-style-type:none;">
                    <li style="margin-bottom:10px;"><i class="fas fa-satellite-dish" style="color:var(--primary); margin-right:10px;"></i> Network-wide remote access</li>
                    <li style="margin-bottom:10px;"><i class="fas fa-video" style="color:var(--primary); margin-right:10px;"></i> Advanced camera surveillance setups</li>
                    <li style="margin-bottom:10px;"><i class="fas fa-lock" style="color:var(--primary); margin-right:10px;"></i> Smart perimeter locking and alarm triggers</li>
                    <li style="margin-bottom:10px;"><i class="fas fa-mobile-alt" style="color:var(--primary); margin-right:10px;"></i> Mobile app notifications and control</li>
                </ul>
            </div>

            <!-- Sidebar -->
            <div class="product-sidebar">
                <h3 style="font-size:1.5rem; margin-bottom:10px; font-family:'Source Sans Pro', sans-serif;">
                    Plans &amp; Pricing
                </h3>
                <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:20px;">Choose a plan and complete your order securely.</p>

                <?php if (empty($plans)): ?>
                    <!-- Fallback if no DB plans yet -->
                    <div class="attribute-box">
                        <div class="attribute-item">
                            <span class="attribute-key">Brand:</span>
                            <span class="attribute-value">Hooaij</span>
                        </div>
                        <div class="attribute-item">
                            <span class="attribute-key">Fee:</span>
                            <span class="attribute-value fee" style="font-size:1rem; color:var(--dark);">To be discussed over appointment</span>
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

                    <!-- Dynamic Plan Selector -->
                    <div class="plan-selection-v2 product-order-section">
                        <div class="plan-radio-group">
                            <?php 
                            $initial_plan = !empty($plans) ? $plans[0] : null;
                            foreach ($plans as $index => $plan):
                                $p_usd = hooaij_ngn_to_usd($plan->price_ngn);
                                $tier  = strtolower(str_replace(['HCSN-', 'hcsn-'], '', $plan->sku));
                                $selected = ($tier === 'basic') ? 'checked' : '';
                                if ($selected) {
                                    $initial_plan = $plan;
                                }
                            ?>
                                <label class="plan-radio-item <?php echo $selected ? 'active' : ''; ?>" data-tier="<?php echo esc_attr($tier); ?>">
                                    <input type="radio" name="plan_selection" value="<?php echo esc_attr($plan->sku); ?>" 
                                           data-price-usd="<?php echo number_format($p_usd, 2, '.', ''); ?>"
                                           data-price-ngn="<?php echo number_format($plan->price_ngn, 2, '.', ''); ?>"
                                           data-features='<?php echo json_encode($plan->features); ?>'
                                           data-name="<?php echo esc_attr($plan->name); ?>"
                                           <?php echo $selected; ?>>
                                    <div class="plan-radio-info">
                                        <span class="plan-radio-name"><?php echo esc_html($plan->name); ?></span>
                                        <span class="plan-radio-price">$<?php echo number_format($p_usd, 2); ?></span>
                                    </div>
                                    <div class="radio-check"></div>
                                </label>
                            <?php endforeach; ?>
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
                                $init_tier = strtolower(str_replace(['HCSN-', 'hcsn-'], '', $initial_plan->sku));
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

<?php get_footer(); ?>