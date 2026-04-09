<?php

/**
 * Template Name: Contact Page
 */
get_header();
?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container animate-up">
        <h1>Contact Us</h1>
        <p>We'd love to hear from you. Reach out with any questions or inquiries.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="grid-2 animate-up">
            <div
                style="background: var(--white); padding: 50px; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md);">
                <span class="section-subtitle">Get In Touch</span>
                <h2 class="section-title">Send Us a Message</h2>
                <p style="margin-bottom: 30px;">Fill out the form below and our team will get back to you
                    promptly.</p>

                <form id="hooaij-contact-form" method="POST">
                    <?php wp_nonce_field('hooaij_contact_form', 'hooaij_contact_nonce'); ?>
                    <input type="hidden" id="captcha_answer" name="captcha_answer" value="">

                    <div class="form-group">
                        <label class="form-label" for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" required>
                        <div class="error-message" id="name-error" style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: none;"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="example@domain.com" required>
                        <div class="error-message" id="email-error" style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: none;"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control" placeholder="How can we help?" required>
                        <div class="error-message" id="subject-error" style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: none;"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="message">Message</label>
                        <textarea id="message" name="message" class="form-control contact-message" placeholder="Your message here..." required></textarea>
                        <div class="error-message" id="message-error" style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: none;"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" id="captcha-label">Spam Check: <span id="captcha-question"></span></label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" id="captcha_input" name="captcha_input" class="form-control" placeholder="Enter the sum" required style="flex: 1;">
                            <button type="button" id="refresh-captcha" class="button button-secondary" style="padding: 10px 15px; font-size: 0.9rem;">
                                <i class="fas fa-redo-alt"></i> New
                            </button>
                        </div>
                        <div class="error-message" id="captcha-error" style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: none;"></div>
                    </div>

                    <button type="submit" id="submit-btn" class="btn btn-primary btn-block">
                        <span id="btn-text">Send Message</span>
                        <span id="btn-spinner" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Sending...</span>
                        <i class="fas fa-paper-plane"></i>
                    </button>

                    <div id="form-message" style="margin-top: 15px; display: none;"></div>
                </form>

            </div>

            <div style="padding: 30px;">
                <span class="section-subtitle">Information</span>
                <h2 class="section-title">Contact Information</h2>
                <p class="intro-text-large">Have a direct question or want to chat? Reach out to us through the
                    details below.</p>

                <ul style="margin-top: 40px; display: flex; flex-direction: column; gap: 30px;">
                    <li style="display: flex; gap: 20px; align-items: center;">
                        <div
                            style="width: 60px; height: 60px; background: rgba(229,120,37,0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 1.5rem;">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0; font-size: 1.2rem;">Phone Number</h4>
                            <p style="margin: 5px 0 0; color: var(--text-color); font-size: 1.1rem;">+234 803
                                580 9083</p>
                        </div>
                    </li>
                    <li style="display: flex; gap: 20px; align-items: center;">
                        <div
                            style="width: 60px; height: 60px; background: rgba(229,120,37,0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 1.5rem;">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0; font-size: 1.2rem;">Email Address</h4>
                            <p style="margin: 5px 0 0; color: var(--text-color); font-size: 1.1rem;">
                                info@hooaij.com</p>
                        </div>
                    </li>
                    <li style="display: flex; gap: 20px; align-items: center;">
                        <div
                            style="width: 60px; height: 60px; background: rgba(229,120,37,0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 1.5rem;">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h4 style="margin: 0; font-size: 1.2rem;">Our Location</h4>
                            <p style="margin: 5px 0 0; color: var(--text-color); font-size: 1.1rem;">123
                                Innovation Drive, Tech City</p>
                        </div>
                    </li>
                </ul>

                <div style="margin-top: 50px;">
                    <h4 style="margin-bottom: 20px; font-size: 1.2rem;">Connect With Us</h4>
                    <div style="display: flex; gap: 15px;">
                        <a href="#"
                            style="width: 50px; height: 50px; background: var(--dark-alt); color: var(--white); display: flex; align-items: center; justify-content: center; border-radius: var(--border-radius); font-size: 1.2rem; transition: var(--transition);">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#"
                            style="width: 50px; height: 50px; background: var(--dark-alt); color: var(--white); display: flex; align-items: center; justify-content: center; border-radius: var(--border-radius); font-size: 1.2rem; transition: var(--transition);">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#"
                            style="width: 50px; height: 50px; background: var(--dark-alt); color: var(--white); display: flex; align-items: center; justify-content: center; border-radius: var(--border-radius); font-size: 1.2rem; transition: var(--transition);">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#"
                            style="width: 50px; height: 50px; background: var(--dark-alt); color: var(--white); display: flex; align-items: center; justify-content: center; border-radius: var(--border-radius); font-size: 1.2rem; transition: var(--transition);">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Status Modal (Fixed to Viewport) -->
<div id="status-modal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; width: 90%; max-width: 450px; background: none; border: none;">
    <div id="modal-content" style="background: var(--white); padding: 40px; border-radius: var(--border-radius-lg); text-align: center; box-shadow: var(--shadow-lg); border-top: 5px solid #27ae60; position: relative;">
        <!-- Close Button -->
        <button type="button" id="close-modal-x" style="position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 1.5rem; color: #888; cursor: pointer;">&times;</button>
        
        <div id="modal-icon" style="font-size: 3rem; color: #27ae60; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3 id="modal-title" style="margin: 0 0 15px; color: var(--dark);">Message Sent Successfully!</h3>
        <p id="modal-text" style="color: var(--text-color); margin-bottom: 25px;">
            Thank you! We've received your message and will get back to you soon.
        </p>
        <button type="button" id="close-modal" class="btn btn-primary" style="padding: 12px 30px;">OK</button>
    </div>
</div>
<?php get_footer(); ?>
