<?php

/**
 * Template Name: Products Page
 */
get_header();
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container animate-up">
        <h1>Our Products</h1>
        <p>Innovative technology and systems bridging the gap between challenges and solutions.</p>
    </div>
</section>

<!-- Products Section -->
<section class="section section-light">
    <div class="container animate-up">
        <div class="text-center" style="margin-bottom: 50px;">
            <span class="section-subtitle">Catalog</span>
            <h2 class="section-title center">Explore Our Solutions</h2>
            <p style="max-width: 800px; margin: 0 auto;">Our products speak for themselves, take time to explore
                their details and you will be glad you did. Carefully handcrafted systems designed with
                excellence.</p>
        </div>

        <div class="grid-2">

            <!-- Tachoscope -->
            <div class="product-card">
                <img src="https://hooaij.com/wp-content/uploads/2026/04/tachoscope-img.png" alt="Tachoscope Device">
                <div class="product-card-body">
                    <h3 class="product-card-title">Tachoscope Device</h3>
                    <p class="product-card-desc">The Tachoscope is a special digital speed measuring device
                        design with high esthetic and ergonomic features meant for Engineers, Technologists and
                        Technicians. It helps to showcase your profession.</p>
                    <a href="<?php echo esc_url(site_url('/tachoscope-device/')); ?>" class="btn btn-primary">View Details <i
                            class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- Home Choice Security System -->
            <div class="product-card">
                <img src="https://hooaij.com/wp-content/uploads/2026/03/home-choice.png"
                    alt="Home Choice Security System">
                <div class="product-card-body">
                    <h3 class="product-card-title">Home Choice Security System</h3>
                    <p class="product-card-desc">The HCSS package is a project
                        package that comprises of smart devices and services (for security purpose) that help to
                        make our house a smart house meant for private/cooperate individuals with fenced
                        structure.</p>
                    <a href="<?php echo esc_url(site_url('/home-choice-security-system/')); ?>" class="btn btn-primary">View Details <i
                            class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- SNSD project -->
            <div class="product-card">
                <img src="https://hooaij.com/wp-content/uploads/2026/03/snsd.png" alt="SNSD Project">
                <div class="product-card-body">
                    <h3 class="product-card-title">SNSD Project</h3>
                    <p class="product-card-desc" style="margin-bottom: 5px;">The SNSD package is an all in one
                        project comprising of smart devices and services (for security, traffic etc.) purpose
                        that help to create smart cities, towns and communities and is meant for use by
                        government...</p>
                    <p style="font-size: 0.9rem; color: #d9534f; font-weight: 600; margin-bottom: 20px;">* This
                        product isn’t available for direct purchase.</p>
                    <a href="<?php echo esc_url(site_url('/snsd-project/')); ?>" class="btn btn-primary">View Details <i
                            class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- Performance Evaluation Software -->
            <div class="product-card">
                <img src="https://hooaij.com/wp-content/uploads/2026/03/pes.png"
                    alt="Performance Evaluation Software">
                <div class="product-card-body">
                    <h3 class="product-card-title">Performance Evaluation Software</h3>
                    <p class="product-card-desc">The Performance Evaluation Software (PES) is a software that
                        help in evaluating human work effort and all its related performance etc. It is meant
                        for use by Academic Institutions, Public and Civil Service agencies and companies.</p>
                    <a href="<?php echo esc_url(site_url('/performance-evaluation-software/')); ?>" class="btn btn-primary">View Details <i
                            class="fas fa-arrow-right"></i></a>
                </div>
            </div>

        </div>
    </div>
</section>

<?php get_footer(); ?>