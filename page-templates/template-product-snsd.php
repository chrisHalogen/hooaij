<?php
/**
 * Template Name: Product SNSD Page
 */
get_header();
?>

<!-- Product Hero Section -->
        <section class="page-hero">
            <div class="container animate-up">
                <h1>SNSD Project</h1>
                <p>Creating smart cities, smart towns, and smart communities.</p>
            </div>
        </section>

        <!-- Product Details Grid/Layout -->
        <section class="section">
            <div class="container">
                <div class="product-layout animate-up">

                    <!-- Description Area -->
                    <div class="product-details">
                        <img src="https://hooaij.com/wp-content/uploads/2026/03/snsd.png" alt="SNSD Project Image"
                            style="width: 100%; border-radius: var(--border-radius); margin-bottom: 30px;">

                        <span class="section-subtitle">Overview</span>
                        <h2 class="section-title" style="font-size: 2rem; margin-bottom: 20px;">Description.</h2>

                        <p class="intro-text-large" style="margin-bottom: 30px;">The SNSD package is an all in one
                            project comprising of smart devices and services (for security, traffic etc.) purpose that
                            help to create smart cities, towns and communities and is meant for use by government of
                            Countries, States etc.</p>

                        <p>Our solutions target widespread metropolitan challenges by rolling out advanced security
                            deployments, integrated traffic management mechanisms, and holistic environmental sensors.
                            By interconnecting essential public services, we transform ordinary communities into
                            interconnected, sustainable models for the future.</p>

                        <h3 style="margin-top: 40px; margin-bottom: 15px;">Target Deliverables</h3>
                        <ul style="list-style-type: none;">
                            <li style="margin-bottom: 10px;"><i class="fas fa-city"
                                    style="color: var(--primary); margin-right: 10px;"></i> Full smart city
                                transformation framework</li>
                            <li style="margin-bottom: 10px;"><i class="fas fa-traffic-light"
                                    style="color: var(--primary); margin-right: 10px;"></i> AI-driven traffic monitoring
                                & control</li>
                            <li style="margin-bottom: 10px;"><i class="fas fa-shield-alt"
                                    style="color: var(--primary); margin-right: 10px;"></i> Broad-scale public security
                                reinforcement</li>
                        </ul>
                    </div>

                    <!-- Attributes Box -->
                    <div class="product-sidebar">
                        <h3 style="font-size: 1.5rem; margin-bottom: 10px; font-family: 'Source Sans Pro', sans-serif;">
                            Product Details</h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">Consultation and scheduling information.
                        </p>

                        <div class="attribute-box">
                            <div class="attribute-item">
                                <span class="attribute-key">Brand:</span>
                                <span class="attribute-value">Hooaij</span>
                            </div>
                            <div class="attribute-item">
                                <span class="attribute-key">Fee:</span>
                                <span class="attribute-value fee" style="font-size: 1rem; color: var(--dark);">To be
                                    discussed over appointment.</span>
                            </div>
                        </div>

                        <!-- Call to Action -->
                        <div class="action-box">
                            <p style="margin-bottom: 15px; font-size: 0.95rem; color: #d9534f; font-weight: 600;"><i
                                    class="fas fa-exclamation-circle"></i> This product isn't available for direct
                                purchase.</p>
                            <a href="<?php echo esc_url(site_url('/bookings/')); ?>" class="btn btn-primary btn-block"
                                style="padding: 15px; font-size: 1.1rem; justify-content: center; display: flex; align-items: center; gap: 10px;">Book
                                An Appointment <i class="fas fa-calendar-alt"></i></a>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    <?php get_footer(); ?>