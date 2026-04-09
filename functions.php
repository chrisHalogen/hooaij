<?php

/**
 * Hooaij Theme Functions
 */

// ─── Load custom inc/ classes ──────────────────────────────────────────────
require_once get_template_directory() . '/inc/class-hooaij-db.php';
require_once get_template_directory() . '/inc/class-hooaij-settings.php';
require_once get_template_directory() . '/inc/class-hooaij-order-handler.php';
require_once get_template_directory() . '/inc/class-hooaij-products-admin.php';
require_once get_template_directory() . '/inc/class-hooaij-orders-admin.php';

// Theme Support & Setup
function hooaij_theme_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');

    // Register Menus
    register_nav_menus(array(
        'header_menu_desktop'     => __('Header Menu Desktop', 'hooaij'),
        'header_menu_mobile'      => __('Header Menu Mobile', 'hooaij'),
        'footer_menu_pages'       => __('Footer Menu Pages', 'hooaij'),
        'footer_menu_other_links' => __('Footer Menu Other Links', 'hooaij'),
    ));
}
add_action('after_setup_theme', 'hooaij_theme_setup');

// Enqueue Scripts & Styles
function hooaij_scripts()
{
    // Font Awesome
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
    // Main style.css (includes imports for css/style.css and admin bar fixes)
    wp_enqueue_style('hooaij-style', get_stylesheet_uri(), array(), wp_get_theme()->get('Version'));
    // Main JS
    wp_enqueue_script('hooaij-script', get_template_directory_uri() . '/js/script.js', array(), wp_get_theme()->get('Version'), true);

    // Checkout JS — only on product pages that need PayPal
    $product_templates = array(
        'page-templates/template-product-tachoscope.php',
        'page-templates/template-product-home-choice.php',
        'page-templates/template-product-pes.php',
    );
    if (is_page_template($product_templates)) {
        $paypal_client_id = hooaij_get_paypal_client_id();
        $exchange_rate    = hooaij_get_exchange_rate();
        $display_currency = hooaij_get_display_currency();

        // PayPal SDK (loaded before checkout.js)
        if (!empty($paypal_client_id)) {
            wp_enqueue_script(
                'paypal-sdk',
                'https://www.paypal.com/sdk/js?client-id=' . urlencode($paypal_client_id) . '&currency=USD&components=buttons,card-fields',
                array(),
                null,
                true
            );
        }

        wp_enqueue_script(
            'hooaij-checkout',
            get_template_directory_uri() . '/js/checkout.js',
            array('jquery'),
            wp_get_theme()->get('Version'),
            true
        );

        wp_localize_script('hooaij-checkout', 'hooaijCheckout', array(
            'ajaxUrl'         => admin_url('admin-ajax.php'),
            'paypalClientId'  => $paypal_client_id,
            'paypalMode'      => hooaij_get_paypal_mode(),
            'nonce'           => wp_create_nonce('hooaij_checkout_nonce'),
            'currency'        => 'USD',
            'exchangeRate'    => $exchange_rate,
            'displayCurrency' => $display_currency,
        ));
    }
}
add_action('wp_enqueue_scripts', 'hooaij_scripts');

/**
 * Custom Admin Menu: HID THEME
 */
function hooaij_register_admin_menu()
{
    add_menu_page(
        'HID Theme Options',      // Page title
        'HID THEME',              // Menu title
        'manage_options',         // Capability
        'hid-theme-options',      // Menu slug
        'hooaij_admin_dashboard', // Callback function
        'dashicons-admin-generic', // Icon
        2                         // Position
    );

    add_submenu_page(
        'hid-theme-options',
        'Initialization',
        'Initialization',
        'manage_options',
        'hid-theme-init',
        'hooaij_admin_init_page'
    );

    add_submenu_page(
        'hid-theme-options',
        'Monitoring',
        'Monitoring',
        'manage_options',
        'hid-theme-monitoring',
        'hooaij_admin_monitoring_page'
    );

    add_submenu_page(
        'hid-theme-options',
        'Settings',
        'Settings',
        'manage_options',
        'hid-theme-settings',
        'hooaij_admin_settings_page'
    );

    add_submenu_page(
        'hid-theme-options',
        'Products',
        'Products',
        'manage_options',
        'hid-theme-products',
        'hooaij_admin_products_page'
    );

    add_submenu_page(
        'hid-theme-options',
        'Orders',
        'Orders',
        'manage_options',
        'hid-theme-orders',
        'hooaij_admin_orders_page'
    );

    // Remove the automatically added duplicate submenu item
    remove_submenu_page('hid-theme-options', 'hid-theme-options');
}
add_action('admin_menu', 'hooaij_register_admin_menu');

// Dashboard Page Callback (Empty placeholder, redirects to Init potentially)
function hooaij_admin_dashboard()
{
    echo '<div class="wrap"><h1>HID THEME Settings</h1><p>Please select Initialization or Monitoring from the menu.</p></div>';
}

/**
 * Array of pages to create mapped to their templates
 */
function hooaij_get_required_pages()
{
    return array(
        'Home'                      => 'page-templates/template-home.php',
        'About Us'                  => 'page-templates/template-about.php',
        'Products'                  => 'page-templates/template-products.php',
        'Bookings'                  => 'page-templates/template-bookings.php',
        'Contact Us'                => 'page-templates/template-contact.php',
        'Privacy Policy'            => 'page-templates/template-privacy.php',
        'Returns and Refunds Policy' => 'page-templates/template-returns.php',
        'Tachoscope Device'         => 'page-templates/template-product-tachoscope.php',
        'Home Choice Security System' => 'page-templates/template-product-home-choice.php',
        'SNSD Project'              => 'page-templates/template-product-snsd.php',
        'Performance Evaluation Software' => 'page-templates/template-product-pes.php',
    );
}

/**
 * Initialization Page
 */
function hooaij_admin_init_page()
{
    if (isset($_POST['hooaij_init_execute']) && check_admin_referer('hooaij_init_action', 'hooaij_init_nonce')) {
        hooaij_execute_initialization();
        echo '<div class="notice notice-success is-dismissible"><p>Initialization Complete! All pages and menus have been generated.</p></div>';
    }

    // Handle DB Tables Init
    if (isset($_POST['hooaij_db_init']) && check_admin_referer('hooaij_db_init_action', 'hooaij_db_init_nonce')) {
        hooaij_create_db_tables();
        echo '<div class="notice notice-success is-dismissible"><p><strong>Database tables initialized successfully.</strong> All three custom tables are now ready.</p></div>';
    }

    // Handle Seed Products
    if (isset($_POST['hooaij_seed_products']) && check_admin_referer('hooaij_seed_action', 'hooaij_seed_nonce')) {
        $result = hooaij_seed_products();
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error is-dismissible"><p>Seed Error: ' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p><strong>' . intval($result) . ' products seeded successfully</strong> into <code>wp_hooaij_products</code>.</p></div>';
        }
    }

    $tables_exist = function_exists('hooaij_tables_exist') ? hooaij_tables_exist() : false;
?>
    <div class="wrap">
        <h1>Theme Initialization</h1>

        <!-- Quick Actions: Re-seed Product Data -->
        <div style="background:#fff9f4; border:1px solid #e57825; border-radius:6px; padding:20px 28px; max-width:800px; margin-bottom:30px;">
            <h2 style="margin-top:0; border-bottom:2px solid #e57825; padding-bottom:10px; color:#121212;">
                🔄 Re-seed Product Data
                <span style="background:#e57825; color:#fff; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; margin-left:10px; vertical-align:middle;">Quick Action</span>
            </h2>
            <p>Reloads all products from <code>products_seed.json</code> directly into <code>wp_hooaij_products</code> without going through the full initialisation wizard.</p>
            <p>Uses <code>REPLACE INTO</code> — existing SKUs are <strong>updated in place</strong>, new SKUs are inserted. <strong>Orphaned SKUs are not automatically deleted</strong> — remove them via the <em>Products</em> admin page first if needed.</p>
            <form method="post" action="">
                <?php wp_nonce_field('hooaij_seed_action', 'hooaij_seed_nonce'); ?>
                <p class="submit" style="margin-bottom:0;">
                    <input type="submit" name="hooaij_seed_products" class="button button-primary"
                        value="🔄 Re-seed Products Now"
                        <?php echo !$tables_exist ? 'disabled title="Initialize DB Tables first (Step 2)"' : ''; ?>>
                    <?php if (!$tables_exist): ?>
                        <span style="margin-left:10px; color:#e74c3c; font-size:13px;">⚠ DB tables not yet initialized — run Step 2 first</span>
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <!-- Step 1: Theme Pages & Menus -->
        <div style="background:#fff; border:1px solid #ddd; border-radius:6px; padding:24px 28px; max-width:800px; margin-bottom:24px;">
            <h2 style="margin-top:0; border-bottom:2px solid #e57825; padding-bottom:10px; color:#121212;">
                Step 1 — Theme Pages &amp; Menus
            </h2>
            <p>Clicking the button below will:</p>
            <ul>
                <li>Create all required pages for the theme if they do not exist.</li>
                <li>Assign the correct specific page templates to them.</li>
                <li>Set the "Home" page as the front page.</li>
                <li>Create standard menus and assign items to them.</li>
            </ul>
            <form method="post" action="">
                <?php wp_nonce_field('hooaij_init_action', 'hooaij_init_nonce'); ?>
                <p class="submit" style="margin-bottom:0;">
                    <input type="submit" name="hooaij_init_execute" class="button button-primary button-large" value="Execute Theme Initialization">
                </p>
            </form>
        </div>



        <!-- Step 2: Database Tables -->
        <div style="background:#fff; border:1px solid #ddd; border-radius:6px; padding:24px 28px; max-width:800px; margin-bottom:24px;">
            <h2 style="margin-top:0; border-bottom:2px solid #e57825; padding-bottom:10px; color:#121212;">
                Step 2 — Database Setup
                <?php if ($tables_exist): ?>
                    <span style="background:#27ae60; color:#fff; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; margin-left:10px; vertical-align:middle;">Active</span>
                <?php else: ?>
                    <span style="background:#f39c12; color:#fff; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; margin-left:10px; vertical-align:middle;">Not Initialized</span>
                <?php endif; ?>
            </h2>
            <p>Creates the three custom database tables required for the Products &amp; Orders system: <code>wp_hooaij_products</code>, <code>wp_hooaij_orders</code>, <code>wp_hooaij_subscriptions</code>.</p>
            <p><em>Safe to run multiple times — uses <code>dbDelta()</code> which only modifies schemas, never deletes data.</em></p>
            <form method="post" action="">
                <?php wp_nonce_field('hooaij_db_init_action', 'hooaij_db_init_nonce'); ?>
                <p class="submit" style="margin-bottom:0;">
                    <input type="submit" name="hooaij_db_init" class="button button-primary button-large" value="Initialize Database Tables">
                </p>
            </form>
        </div>

        <!-- Step 3: Seed Products -->
        <div style="background:#fff; border:1px solid #ddd; border-radius:6px; padding:24px 28px; max-width:800px;">
            <h2 style="margin-top:0; border-bottom:2px solid #e57825; padding-bottom:10px; color:#121212;">
                Step 3 — Seed Product Data
            </h2>
            <p>Loads all products from <code>products_seed.json</code> into the <code>wp_hooaij_products</code> table.</p>
            <p><em>Safe to re-run — uses <code>REPLACE INTO</code> so existing products will be updated, not duplicated. Run <strong>Step 2</strong> first.</em></p>
            <form method="post" action="">
                <?php wp_nonce_field('hooaij_seed_action', 'hooaij_seed_nonce'); ?>
                <p class="submit" style="margin-bottom:0;">
                    <input type="submit" name="hooaij_seed_products" class="button button-primary button-large"
                        value="Seed Product Data"
                        <?php echo !$tables_exist ? 'disabled title="Initialize DB Tables first (Step 2)"' : ''; ?>>
                    <?php if (!$tables_exist): ?>
                        <span style="margin-left:10px; color:#e74c3c; font-size:13px;">⚠ Please run Step 2 first</span>
                    <?php endif; ?>
                </p>
            </form>
        </div>
    </div>
<?php
}

function hooaij_execute_initialization()
{
    $pages = hooaij_get_required_pages();

    foreach ($pages as $title => $template) {
        $page_check = get_page_by_title($title);
        $new_page_id = 0;

        if (!isset($page_check->ID)) {
            $new_page_id = wp_insert_post(array(
                'post_title'     => $title,
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'post_author'    => 1,
            ));
        } else {
            $new_page_id = $page_check->ID;
        }

        // Update the page template
        if ($new_page_id) {
            update_post_meta($new_page_id, '_wp_page_template', $template);

            // Set home as front page
            if ($title === 'Home') {
                update_option('show_on_front', 'page');
                update_option('page_on_front', $new_page_id);
            }
        }
    }

    // Auto-create Menus
    $locations = get_theme_mod('nav_menu_locations');

    // Header Menu
    $header_menu_exists = wp_get_nav_menu_object('Main Menu');
    if (!$header_menu_exists) {
        $menu_id = wp_create_nav_menu('Main Menu');
        if (!is_wp_error($menu_id)) {
            $locations['header_menu_desktop'] = $menu_id;
            $locations['header_menu_mobile'] = $menu_id;

            // Add items
            $menu_items = array('Home', 'Products', 'Bookings');
            foreach ($menu_items as $item_title) {
                $page = get_page_by_title($item_title);
                if ($page) {
                    wp_update_nav_menu_item($menu_id, 0, array(
                        'menu-item-title'   => $item_title,
                        'menu-item-object-id' => $page->ID,
                        'menu-item-object'  => 'page',
                        'menu-item-type'    => 'post_type',
                        'menu-item-status'  => 'publish'
                    ));
                }
            }
            // InnerBerg external link
            wp_update_nav_menu_item($menu_id, 0, array(
                'menu-item-title'   => 'InnerBerg',
                'menu-item-url'     => 'https://innerberg.com',
                'menu-item-type'    => 'custom',
                'menu-item-status'  => 'publish'
            ));

            // Add Contact Us after InnerBerg
            $contact_page = get_page_by_title('Contact Us');
            if ($contact_page) {
                wp_update_nav_menu_item($menu_id, 0, array(
                    'menu-item-title'   => 'Contact Us',
                    'menu-item-object-id' => $contact_page->ID,
                    'menu-item-object'  => 'page',
                    'menu-item-type'    => 'post_type',
                    'menu-item-status'  => 'publish'
                ));
            }
        }
    }

    // Footer Pages
    $footer_pages_exists = wp_get_nav_menu_object('Footer Pages');
    if (!$footer_pages_exists) {
        $menu_id = wp_create_nav_menu('Footer Pages');
        if (!is_wp_error($menu_id)) {
            $locations['footer_menu_pages'] = $menu_id;
            $menu_items = array('Home', 'About Us', 'Products', 'Bookings', 'Contact Us');
            foreach ($menu_items as $item_title) {
                $page = get_page_by_title($item_title);
                if ($page) {
                    wp_update_nav_menu_item($menu_id, 0, array(
                        'menu-item-title'   => $item_title,
                        'menu-item-object-id' => $page->ID,
                        'menu-item-object'  => 'page',
                        'menu-item-type'    => 'post_type',
                        'menu-item-status'  => 'publish'
                    ));
                }
            }
        }
    }

    // Footer Other Links
    $footer_other_exists = wp_get_nav_menu_object('Footer Other Links');
    if (!$footer_other_exists) {
        $menu_id = wp_create_nav_menu('Footer Other Links');
        if (!is_wp_error($menu_id)) {
            $locations['footer_menu_other_links'] = $menu_id;
            $menu_items = array('Privacy Policy', 'Returns and Refunds Policy');
            foreach ($menu_items as $item_title) {
                $page = get_page_by_title($item_title);
                if ($page) {
                    wp_update_nav_menu_item($menu_id, 0, array(
                        'menu-item-title'   => $item_title,
                        'menu-item-object-id' => $page->ID,
                        'menu-item-object'  => 'page',
                        'menu-item-type'    => 'post_type',
                        'menu-item-status'  => 'publish'
                    ));
                }
            }
            // InnerBerg external link
            wp_update_nav_menu_item($menu_id, 0, array(
                'menu-item-title'   => 'InnerBerg',
                'menu-item-url'     => 'https://innerberg.com',
                'menu-item-type'    => 'custom',
                'menu-item-status'  => 'publish'
            ));
        }
    }

    // Save menu locations
    set_theme_mod('nav_menu_locations', $locations);
}

/**
 * Monitoring Page
 */
function hooaij_admin_monitoring_page()
{
    $pages = hooaij_get_required_pages();
    $locations = get_theme_mod('nav_menu_locations');
?>
    <style>
        .monitoring-table {
            width: 100%;
            max-width: 900px;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .monitoring-table th,
        .monitoring-table td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .monitoring-table th {
            background: #f9f9f9;
        }

        .status-ok {
            color: green;
            font-weight: bold;
        }

        .status-err {
            color: red;
            font-weight: bold;
        }

        .dashicons {
            vertical-align: middle;
        }
    </style>
    <div class="wrap">
        <h1>System Monitoring</h1>

        <h2>Page & Template Verification</h2>
        <table class="monitoring-table">
            <thead>
                <tr>
                    <th>Page Title</th>
                    <th>Status</th>
                    <th>Template Assigned</th>
                    <th>Match Validation</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $title => $expected_template):
                    $page = get_page_by_title($title);
                    $exists = isset($page->ID);
                    $assigned_template = $exists ? get_post_meta($page->ID, '_wp_page_template', true) : '';
                    $template_matches = ($assigned_template === $expected_template);
                ?>
                    <tr>
                        <td><?php echo esc_html($title); ?></td>
                        <td>
                            <?php if ($exists): ?>
                                <span class="status-ok"><span class="dashicons dashicons-yes-alt"></span> Present</span>
                            <?php else: ?>
                                <span class="status-err"><span class="dashicons dashicons-dismiss"></span> Missing</span>
                            <?php endif; ?>
                        </td>
                        <td><code style="font-size: 11px;"><?php echo esc_html($assigned_template ? $assigned_template : 'None'); ?></code></td>
                        <td>
                            <?php if ($template_matches): ?>
                                <span class="status-ok"><span class="dashicons dashicons-yes-alt"></span> Valid</span>
                            <?php else: ?>
                                <span class="status-err"><span class="dashicons dashicons-dismiss"></span> Mismatch</span><br />
                                <small>Expected: <code><?php echo esc_html($expected_template); ?></code></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2 style="margin-top: 40px;">Menu Verification</h2>
        <table class="monitoring-table">
            <thead>
                <tr>
                    <th>Location ID</th>
                    <th>Status</th>
                    <th>Menu Assigned</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $menu_locations_config = array(
                    'header_menu_desktop' => 'Header Menu Desktop',
                    'header_menu_mobile' => 'Header Menu Mobile',
                    'footer_menu_pages' => 'Footer Menu Pages',
                    'footer_menu_other_links' => 'Footer Menu Other Links'
                );

                foreach ($menu_locations_config as $loc_key => $loc_name):
                    $has_menu = isset($locations[$loc_key]) && $locations[$loc_key] > 0;
                    $menu_obj = $has_menu ? wp_get_nav_menu_object($locations[$loc_key]) : false;
                ?>
                    <tr>
                        <td><?php echo esc_html($loc_name); ?> (<code><?php echo esc_html($loc_key); ?></code>)</td>
                        <td>
                            <?php if ($has_menu && $menu_obj): ?>
                                <span class="status-ok"><span class="dashicons dashicons-yes-alt"></span> Assigned</span>
                            <?php else: ?>
                                <span class="status-err"><span class="dashicons dashicons-dismiss"></span> Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $menu_obj ? esc_html($menu_obj->name) : '<em>None</em>'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php
}

// ─── Contact Form AJAX Handler ─────────────────────────────────────────────
add_action('wp_ajax_nopriv_hooaij_contact_form', 'hooaij_ajax_contact_form');
add_action('wp_ajax_hooaij_contact_form', 'hooaij_ajax_contact_form');

function hooaij_ajax_contact_form()
{
    // 1. Verify Nonce
    if (!isset($_POST['hooaij_contact_nonce']) || !wp_verify_nonce($_POST['hooaij_contact_nonce'], 'hooaij_contact_form')) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.']);
    }

    // 2. Sanitize Inputs
    $name    = sanitize_text_field($_POST['name'] ?? '');
    $email   = sanitize_email($_POST['email'] ?? '');
    $subject = sanitize_text_field($_POST['subject'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    $captcha_input  = intval($_POST['captcha_input'] ?? 0);
    $captcha_answer = intval($_POST['captcha_answer'] ?? 0);

    // 3. Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        wp_send_json_error(['message' => 'All fields are required.']);
    }

    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Please provide a valid email address.']);
    }

    if ($captcha_input !== $captcha_answer) {
        wp_send_json_error(['message' => 'Incorrect captcha answer. Please try again.']);
    }

    // 4. Dispatch Email
    $email_vars = [
        'name'       => $name,
        'email'      => $email,
        'subject'    => $subject,
        'message'    => nl2br($message),
        'timestamp'  => current_time('F j, Y, g:i a'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'year'       => date('Y'),
    ];

    if (function_exists('hooaij_dispatch_email')) {
        hooaij_dispatch_email('contact_form', $email_vars);
        wp_send_json_success(['message' => 'Your message has been sent successfully.']);
    } else {
        wp_send_json_error(['message' => 'The email system is currently unavailable. Please try again later.']);
    }
}

// ─── Appointment Booking AJAX Handler ──────────────────────────────────────
add_action('wp_ajax_nopriv_hooaij_appointment_booking', 'hooaij_ajax_appointment_booking');
add_action('wp_ajax_hooaij_appointment_booking', 'hooaij_ajax_appointment_booking');

function hooaij_ajax_appointment_booking()
{
    // 1. Verify Nonce
    if (!isset($_POST['hooaij_booking_nonce']) || !wp_verify_nonce($_POST['hooaij_booking_nonce'], 'hooaij_appointment_booking')) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh the page and try again.']);
    }

    // 2. Sanitize Inputs
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name  = sanitize_text_field($_POST['last_name'] ?? '');
    $email      = sanitize_email($_POST['email'] ?? '');
    $phone      = sanitize_text_field($_POST['phone'] ?? '');
    $date       = sanitize_text_field($_POST['date'] ?? '');
    $service    = sanitize_text_field($_POST['service'] ?? '');
    $message    = sanitize_textarea_field($_POST['message'] ?? '');
    $captcha_input  = intval($_POST['captcha_input'] ?? 0);
    $captcha_answer = intval($_POST['captcha_answer'] ?? 0);

    // 3. Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($date) || empty($service) || empty($message)) {
        wp_send_json_error(['message' => 'All fields are required.']);
    }

    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Please provide a valid email address.']);
    }

    if ($captcha_input !== $captcha_answer) {
        wp_send_json_error(['message' => 'Incorrect captcha answer. Please try again.']);
    }

    // 4. Dispatch Email
    $email_vars = [
        'name'       => $first_name . ' ' . $last_name,
        'email'      => $email,
        'phone'      => $phone,
        'date'       => $date,
        'service'    => $service,
        'message'    => nl2br($message),
        'timestamp'  => current_time('F j, Y, g:i a'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'year'       => date('Y'),
    ];

    if (function_exists('hooaij_dispatch_email')) {
        hooaij_dispatch_email('appointment_booking', $email_vars);
        wp_send_json_success(['message' => 'Your booking request has been sent successfully.']);
    } else {
        wp_send_json_error(['message' => 'The email system is currently unavailable. Please try again later.']);
    }
}
