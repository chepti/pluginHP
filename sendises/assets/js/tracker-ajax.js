jQuery(document).ready(function($) {
    'use strict';

    // Check if the localized object exists
    if (typeof sendises_ajax_obj === 'undefined') {
        return;
    }

    var postId = sendises_ajax_obj.post_id;
    var ajaxUrl = sendises_ajax_obj.ajax_url;
    var nonce = sendises_ajax_obj.nonce;
    var delay = parseInt(sendises_ajax_obj.mark_as_read_delay, 10);
    
    var alreadyMarked = false;

    /**
     * Sends the AJAX request to mark the post as read.
     */
    function markPostAsRead() {
        if (alreadyMarked) {
            return;
        }
        
        alreadyMarked = true;

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'sendises_mark_read',
                post_id: postId,
                nonce: nonce
            },
            success: function(response) {
                if(response.success) {
                    console.log('SENDISES: Post marked as read.');
                } else {
                    console.error('SENDISES: Error marking post as read.', response.data.message);
                    alreadyMarked = false; // Allow retry if it failed
                }
            },
            error: function(xhr, status, error) {
                console.error('SENDISES: AJAX request failed.', error);
                alreadyMarked = false; // Allow retry if it failed
            }
        });
    }

    // Set a timer to mark the post as read after the specified delay
    if (delay > 0) {
        setTimeout(markPostAsRead, delay);
    }
    
    // Optional: Add a click handler for a button if you have one.
    // Example: $('#mark-as-read-button').on('click', markPostAsRead);
});
