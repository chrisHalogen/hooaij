<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

    <!-- Header Navigation -->
    <header class="header">
        <div class="container nav-container">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
                <img src="https://hooaij.com/wp-content/uploads/2026/02/new-logo.png" alt="<?php bloginfo('name'); ?> Logo">
            </a>

            <nav class="nav-links">
                <?php
                if (has_nav_menu('header_menu_desktop')) {
                    wp_nav_menu(array(
                        'theme_location' => 'header_menu_desktop',
                        'container'      => false,
                        'menu_class'     => 'header-nav-list',
                    ));
                } else {
                    // Fallback
                    echo '<ul class="header-nav-list">';
                    echo '<li><a href="'.esc_url(home_url('/')).'">Home</a></li>';
                    echo '</ul>';
                }
                ?>
            </nav>

            <div class="nav-actions">
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <button class="mobile-menu-btn"><i class="fas fa-bars"></i></button>
            </div>
        </div>
    </header>

    <div class="page-wrapper">
