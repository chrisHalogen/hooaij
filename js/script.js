document.addEventListener('DOMContentLoaded', () => {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuBtn && navLinks) {
        mobileMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            navLinks.classList.toggle('active');
            
            // Toggle icon from bars to times
            const icon = mobileMenuBtn.querySelector('i');
            if(navLinks.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if(navLinks.classList.contains('active') && !navLinks.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                navLinks.classList.remove('active');
                const icon = mobileMenuBtn.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
        
        // Close menu when clicking a link
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                const icon = mobileMenuBtn.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            });
        });
    }

    // Sticky Header
    const header = document.querySelector('.header');
    
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // Scroll Reveal Animation Setup
    const animationElements = document.querySelectorAll('.animate-up');
    
    const revealElements = () => {
        const windowHeight = window.innerHeight;
        const revealPoint = 100;
        
        animationElements.forEach(element => {
            const revealTop = element.getBoundingClientRect().top;
            if (revealTop < windowHeight - revealPoint) {
                element.classList.add('visible');
            }
        });
    };
    
    // Initial check on page load
    revealElements();
    
    // Check on scroll
    window.addEventListener('scroll', revealElements);

    // ─── Shared Form Logic (Contact & Bookings) ────────────────────────────
    const initFormLogic = (formId, ajaxAction, nonceName) => {
        const form = document.getElementById(formId);
        if (!form) return;

        const captchaQuestion = form.querySelector('#captcha-question');
        const captchaAnswerHidden = form.querySelector('#captcha_answer');
        const refreshCaptchaBtn = form.querySelector('#refresh-captcha');
        const submitBtn = form.querySelector('button[type="submit"]');
        const btnText = form.querySelector('#btn-text');
        const btnSpinner = form.querySelector('#btn-spinner');
        
        const statusModal = document.getElementById('status-modal');
        const modalContent = document.getElementById('modal-content');
        const modalIcon = document.getElementById('modal-icon');
        const modalTitle = document.getElementById('modal-title');
        const modalText = document.getElementById('modal-text');
        const closeModal = document.getElementById('close-modal');

        // Helper: Show Status Modal
        const showStatus = (isSuccess, title, message) => {
            if (!statusModal || !modalIcon || !modalTitle || !modalText || !modalContent) return;

            if (isSuccess) {
                modalIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
                modalIcon.style.color = '#27ae60';
                modalContent.style.borderTopColor = '#27ae60';
            } else {
                modalIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                modalIcon.style.color = '#e74c3c';
                modalContent.style.borderTopColor = '#e74c3c';
            }

            modalTitle.textContent = title;
            modalText.textContent = message;
            statusModal.style.display = 'block';
        };

        // 1. Generate Math Captcha
        const generateCaptcha = () => {
            const num1 = Math.floor(Math.random() * 9) + 1;
            const num2 = Math.floor(Math.random() * (12 - num1)) + 1;
            const sum = num1 + num2;
            
            if (captchaQuestion) captchaQuestion.textContent = `${num1} + ${num2}`;
            if (captchaAnswerHidden) captchaAnswerHidden.value = sum;
        };

        generateCaptcha();

        if (refreshCaptchaBtn) {
            refreshCaptchaBtn.addEventListener('click', generateCaptcha);
        }

        // 2. Handle Form Submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Clear previous errors
            form.querySelectorAll('.error-message').forEach(err => {
                err.style.display = 'none';
                err.textContent = '';
            });

            const formData = new FormData(form);
            formData.append('action', ajaxAction);

            // Show loading state
            if (submitBtn) submitBtn.disabled = true;
            if (btnText) btnText.style.display = 'none';
            if (btnSpinner) btnSpinner.style.display = 'inline-block';

            try {
                const response = await fetch('/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    const successTitle = formId === 'hooaij-contact-form' ? 'Message Sent!' : 'Request Received!';
                    const successMsg = result.data && result.data.message ? result.data.message : 'Thank you! We have received your submission.';
                    
                    showStatus(true, successTitle, successMsg);
                    form.reset();
                    generateCaptcha();
                } else {
                    const errorMsg = result.data && result.data.message ? result.data.message : 'Submission failed.';
                    
                    if (errorMsg.toLowerCase().includes('captcha')) {
                        const captchaErr = form.querySelector('#captcha-error');
                        if (captchaErr) {
                            captchaErr.textContent = errorMsg;
                            captchaErr.style.display = 'block';
                        }
                    } else {
                        showStatus(false, 'Submission Failed', errorMsg);
                    }
                }
            } catch (error) {
                console.error('Submission error:', error);
                showStatus(false, 'Unexpected Error', 'Something went wrong. Please try again or contact us directly.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
                if (btnText) btnText.style.display = 'inline-block';
                if (btnSpinner) btnSpinner.style.display = 'none';
            }
        });

        // Close Modal
        const closeActions = [closeModal, form.querySelector('#close-modal-x'), document.getElementById('close-modal-x')];
        closeActions.forEach(btn => {
            if (btn) {
                btn.addEventListener('click', () => {
                    statusModal.style.display = 'none';
                });
            }
        });
    };

    // Initialize both forms
    initFormLogic('hooaij-contact-form', 'hooaij_contact_form', 'hooaij_contact_nonce');
    initFormLogic('hooaij-booking-form', 'hooaij_appointment_booking', 'hooaij_booking_nonce');
});
