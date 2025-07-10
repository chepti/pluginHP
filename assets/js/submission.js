jQuery(document).ready(function($) {
    'use strict';

    const openButton = $('#hpg-open-popup-button');
    const closeButton = $('#hpg-close-popup-button');
    const overlay = $('#hpg-popup-overlay');

    // --- Auto-open popup if URL hash is present ---
    if (window.location.hash === '#hpg-popup-overlay') {
        overlay.removeClass('hpg-popup-hidden').addClass('hpg-popup-visible');
        $('body').addClass('hpg-popup-active');
    }

    // --- Open Popup ---
    openButton.on('click', function(e) {
        e.preventDefault();
        
        // Check if the popup overlay exists. If not, the user is likely not logged in.
        if (overlay.length === 0) {
            alert('כדי להוסיף תוכן, יש להתחבר או להירשם לאתר.');
            return;
        }

        overlay.removeClass('hpg-popup-hidden').addClass('hpg-popup-visible');
        $('body').addClass('hpg-popup-active');
    });

    // --- Close Popup ---
    function closePopup() {
        overlay.removeClass('hpg-popup-visible').addClass('hpg-popup-hidden');
        $('body').removeClass('hpg-popup-active');
    }

    closeButton.on('click', closePopup);
    overlay.on('click', function(e) {
        if (e.target === this) {
            closePopup();
        }
    });

    // --- URL Cleanup Logic ---
    // On page load, check for success or error messages in the URL
    // and clean them up after displaying them once.
    const urlParams = new URLSearchParams(window.location.search);
    let newUrl = window.location.pathname;

    // Check for success message, show alert
    if (urlParams.has('post_submitted')) {
        alert('הפוסט שלך נשלח בהצלחה ויעלה לאתר לאחר אישור המנהל. תודה!');
    }

    // Check for error messages, highlight fields
    if (urlParams.has('submission_errors')) {
        const errorJson = decodeURIComponent(urlParams.get('submission_errors'));
        try {
            const errors = JSON.parse(errorJson);
            Object.keys(errors).forEach(fieldId => {
                // For taxonomy chip groups, we need to target the container
                if (fieldId.includes('_tax')) {
                     // Example: hpg_class_tax -> targets the container of chips
                    $('input[name^="' + fieldId + '"]').closest('.hpg-chip-group').addClass('hpg-error');
                } else {
                    $('#' + fieldId).addClass('hpg-error');
                }
            });
        } catch (e) {
            console.error('Could not parse submission errors:', e);
        }
    }
    
    // Preserve the hash if it exists (e.g., to keep the popup open on error)
    if (window.location.hash) {
        newUrl += window.location.hash;
    }

    // If the URL has either of our params, clean it.
    if (urlParams.has('post_submitted') || urlParams.has('submission_errors')) {
         window.history.replaceState({}, document.title, newUrl);
    }
}); 