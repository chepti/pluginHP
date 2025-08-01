jQuery(document).ready(function($) {
    'use strict';

    const $menu = $('.hp-bp-user-menu');
    const $trigger = $menu.find('.hp-bp-profile-trigger');
    const $dropdown = $menu.find('.hp-bp-dropdown-menu');

    // Toggle dropdown on trigger click
    $trigger.on('click', function(e) {
        e.stopPropagation();
        const isHidden = $dropdown.attr('aria-hidden') === 'true';
        $dropdown.attr('aria-hidden', !isHidden);
        $trigger.attr('aria-expanded', isHidden);
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if ( !$menu.is(e.target) && $menu.has(e.target).length === 0 ) {
            $dropdown.attr('aria-hidden', 'true');
            $trigger.attr('aria-expanded', 'false');
        }
    });

    // Close dropdown on Escape key
    $(document).on('keydown', function(e) {
        if (e.key === "Escape") {
            $dropdown.attr('aria-hidden', 'true');
            $trigger.attr('aria-expanded', 'false');
        }
    });

    // Handle "Add Post" popup trigger from dropdown
    const $popupTrigger = $('.hpg-open-popup-button');
    if ($popupTrigger.length > 0) {
        const $popupOverlay = $('#hpg-popup-overlay');
        if ($popupOverlay.length > 0) {
            $menu.on('click', '.hpg-open-popup-button', function(e) {
                e.preventDefault();
                // Explicitly set visibility by adding/removing classes
                $popupOverlay.removeClass('hpg-popup-hidden').addClass('hpg-popup-visible');
                $('body').addClass('hpg-popup-active'); // Prevent background scrolling
                
                // Close the dropdown menu as well
                $dropdown.attr('aria-hidden', 'true');
                $trigger.attr('aria-expanded', 'false');
            });
        }
    }

    // --- Tweaks for Register/Settings pages ---
    if ($('body.bp-user.settings').length) {
        
        // 1. Hide unwanted elements
        $('#template-notices').hide();
        $('form .password-field > .description').hide();
        
        // 2. Translate Labels & Descriptions
        // The "Name" field in BP settings is field_1 by default.
        $("label[for='field_1']").contents().first().replaceWith('כינוי');
        $("label[for='signup_password']").text('בחירת סיסמה');

        var visibility_text = $("div.field-visibility-settings");
        if(visibility_text.text().includes('This field may be seen by')){
            visibility_text.text('שדה זה יוצג בפרופיל שלך, בהתאם להגדרות הפרטיות.');
        }
        
        // 3. Fix password strength meter position
        var passwordField = $('#signup_password, #password').parent();
        var strengthMeter = $('.bp-password-strength-results');
        if(passwordField.length && strengthMeter.length) {
            passwordField.css({
                'display': 'flex',
                'align-items': 'center',
                'gap': '10px',
                'flex-wrap': 'wrap'
            });
            strengthMeter.css('margin', '0');
        }
    }

    /**
     * ===============================================
     * Report Content Modal
     * ===============================================
     */
    const reportModal = $('#hpg-report-modal');
    const reportForm = $('#hpg-report-form');

    // --- Open Modal ---
    $('body').on('click', '.hpg-report-button', function() {
        const postId = $(this).data('post-id');
        if (postId) {
            $('#hpg-report-post-id').val(postId);
            reportModal.addClass('visible');
        }
    });

    // --- Close Modal ---
    function closeReportModal() {
        reportModal.removeClass('visible');
        // Reset form on close
        if (reportForm.length) {
            reportForm[0].reset();
        }
        $('#hpg-report-details-wrapper').hide();
        $('#hpg-report-feedback').hide().empty().removeClass('success error');
        $('#hpg-submit-report-button').prop('disabled', false).text('שליחת דיווח');
    }

    reportModal.on('click', '.hpg-modal-close', closeReportModal);
    reportModal.on('click', function(e) {
        if ($(e.target).is(reportModal)) {
            closeReportModal();
        }
    });

    // --- Show/Hide Details Textarea ---
    $('#hpg-report-reason').on('change', function() {
        const reason = $(this).val();
        const detailsWrapper = $('#hpg-report-details-wrapper');
        if (reason === 'content_error' || reason === 'offensive_content') {
            detailsWrapper.show();
        } else {
            detailsWrapper.hide();
        }
    });

    // --- Handle Form Submission (AJAX) ---
    if (reportForm.length) {
        reportForm.on('submit', function(e) {
            e.preventDefault();

            const submitButton = $('#hpg-submit-report-button');
            const feedbackDiv = $('#hpg-report-feedback');
            
            // Disable button and show loading text
            submitButton.prop('disabled', true).text('שולח...');
            feedbackDiv.hide().empty().removeClass('success error');

            // Prepare data
            const formData = {
                action: 'hpg_handle_report_submission',
                security: hp_bp_ajax_obj.report_nonce,
                post_id: $('#hpg-report-post-id').val(),
                reason: $('#hpg-report-reason').val(),
                details: $('#hpg-report-details').val()
            };

            // Send AJAX request
            $.post(hp_bp_ajax_obj.ajax_url, formData, function(response) {
                if (response.success) {
                    feedbackDiv.addClass('success').text(response.data.message).show();
                    // Close modal after a short delay
                    setTimeout(closeReportModal, 3000);
                } else {
                    feedbackDiv.addClass('error').text(response.data.message).show();
                    // Re-enable button on error
                    submitButton.prop('disabled', false).text('שליחת דיווח');
                }
            }).fail(function() {
                feedbackDiv.addClass('error').text('אירעה שגיאת רשת. נסה שוב.').show();
                submitButton.prop('disabled', false).text('שליחת דיווח');
            });
        });
    }

}); 