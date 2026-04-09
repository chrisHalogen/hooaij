<?php
/**
 * Template Name: Bookings Page
 */
get_header();
?>

<!-- Page Hero -->
        <section class="page-hero">
            <div class="container animate-up">
                <h1>Book An Appointment</h1>
                <p>Schedule a consultation to discuss our solutions tailored to your needs.</p>
            </div>
        </section>

        <!-- Bookings Form -->
        <section class="section section-light">
            <div class="container">
                <div class="animate-up"
                    style="max-width: 800px; margin: 0 auto; background: var(--white); padding: 50px; border-radius: var(--border-radius-lg); box-shadow: var(--shadow-md); border-top: 5px solid var(--secondary);">
                    <div class="text-center" style="margin-bottom: 40px;">
                        <span class="section-subtitle">Let's Talk</span>
                        <h2 class="section-title center">Appointment Form</h2>
                    </div>

                    <form id="hooaij-booking-form" action="#" method="POST">
                        <?php wp_nonce_field('hooaij_appointment_booking', 'hooaij_booking_nonce'); ?>
                        <div id="form-message" style="margin-top: 15px; display: none;"></div>
                        <div class="grid-2" style="gap: 20px; align-items: start; margin-bottom: 20px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" placeholder="John" required>
                                <div id="first_name-error" class="error-message" style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: none;"></div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Doe" required>
                                <div id="last_name-error" class="error-message" style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: none;"></div>
                            </div>
                        </div>

                        <div class="grid-2" style="gap: 20px; align-items: start; margin-bottom: 20px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="example@domain.com" required>
                                <div id="email-error" class="error-message" style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: none;"></div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" placeholder="+234 803 580 9083" required>
                                <div id="phone-error" class="error-message" style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: none;"></div>
                            </div>
                        </div>

                        <div class="grid-2" style="gap: 20px; align-items: start; margin-bottom: 20px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" for="date">Preferred Date</label>
                                <input type="date" id="date" name="date" class="form-control" required>
                                <div id="date-error" class="error-message" style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: none;"></div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label class="form-label" for="service">Interested Product/Service</label>
                                <select id="service" name="service" class="form-control" required>
                                    <option value="" disabled selected>-- Select an option --</option>
                                    <option value="Home Choice Security System">Home Choice Security System</option>
                                    <option value="SNSD Project">SNSD Project</option>
                                    <option value="Performance Evaluation Software">Performance Evaluation Software</option>
                                    <option value="General Consultation">General Consultation</option>
                                    <option value="Other">Other</option>
                                </select>
                                <div id="service-error" class="error-message" style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: none;"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="message">Appointment Details / Notes</label>
                            <textarea id="message" name="message" class="form-control contact-message"
                                placeholder="Please provide any extra details regarding your requested appointment..."
                                required style="resize: none;"></textarea>
                            <div id="message-error" class="error-message" style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: none;"></div>
                        </div>

                        <!-- Math Captcha -->
                        <div class="form-group" style="margin-top: 30px;">
                            <label class="form-label">Spam Check: <span id="captcha-question" style="font-weight: 700; color: var(--primary);"></span></label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="number" id="captcha_input" name="captcha_input" class="form-control" placeholder="Enter the sum" required style="max-width: 150px;">
                                <input type="hidden" id="captcha_answer" name="captcha_answer">
                                <button type="button" id="refresh-captcha" class="btn btn-outline" style="padding: 10px 20px;"><i class="fas fa-sync-alt"></i> New</button>
                            </div>
                            <div id="captcha-error" class="error-message" style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: none;"></div>
                        </div>

                        <div class="text-center" style="margin-top: 40px;">
                            <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem; min-width: 250px;">
                                <span id="btn-text">Confirm Booking <i class="fas fa-calendar-check"></i></span>
                                <span id="btn-spinner" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Processing...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <div id="status-modal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; width: 90%; max-width: 450px; background: none; border: none;">
            <div id="modal-content" style="background: var(--white); padding: 40px; border-radius: var(--border-radius-lg); text-align: center; box-shadow: var(--shadow-lg); border-top: 5px solid #27ae60; position: relative;">
                <!-- Close Button -->
                <button type="button" id="close-modal-x" style="position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 1.5rem; color: #888; cursor: pointer;">&times;</button>
                
                <div id="modal-icon" style="font-size: 3rem; color: #27ae60; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 id="modal-title" style="margin: 0 0 15px; color: var(--dark);">Request Received!</h3>
                <p id="modal-text" style="color: var(--text-color); margin-bottom: 25px;">
                    Thank you! We've received your booking request and will contact you shortly to confirm.
                </p>
                <button type="button" id="close-modal" class="btn btn-primary" style="padding: 12px 30px;">OK</button>
            </div>
        </div>
    <?php get_footer(); ?>