    </div><!-- /.page-wrapper -->

    <footer class="footer">
        <div class="container">
            <div class="footer-top">
                <div class="footer-widget">
                    <img src="https://hooaij.com/wp-content/uploads/2026/02/new-logo.png"
                        style="max-width: 150px; margin-bottom: 20px;" alt="<?php bloginfo('name'); ?> Logo">
                    <p>Delivering Products & Services at Exquisite Value. We provide custom-designed solutions that
                        combine innovation, safety, and performance.</p>
                    <div class="footer-social">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div class="footer-widget">
                    <h4>Pages</h4>
                    <?php
                    if (has_nav_menu('footer_menu_pages')) {
                        wp_nav_menu(array(
                            'theme_location' => 'footer_menu_pages',
                            'container'      => false,
                            'menu_class'     => 'footer-links' // Custom class
                        ));
                    }
                    ?>
                </div>

                <div class="footer-widget">
                    <h4>Other Links</h4>
                    <?php
                    if (has_nav_menu('footer_menu_other_links')) {
                        wp_nav_menu(array(
                            'theme_location' => 'footer_menu_other_links',
                            'container'      => false,
                            'menu_class'     => 'footer-links' // Custom class
                        ));
                    }
                    ?>
                </div>

                <div class="footer-widget">
                    <h4>Contact Info</h4>
                    <ul>
                        <li style="display: flex; gap: 10px;">
                            <i class="fas fa-phone-alt" style="color: var(--primary); margin-top: 5px;"></i>
                            <span>+234 803 580 9083</span>
                        </li>
                        <li style="display: flex; gap: 10px;">
                            <i class="fas fa-envelope" style="color: var(--primary); margin-top: 5px;"></i>
                            <span>info@hooaij.com</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Hooaij. All Rights Reserved. Designed by <strong>Halogenius Ideas</strong>.</p>
            </div>
        </div>
    </footer>

<?php wp_footer(); ?>
</body>
</html>
