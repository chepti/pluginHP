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
                $popupOverlay.removeClass('hpg-popup-hidden');
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
}); 