jQuery(function($) {
    'use strict';

    // Debounce function to limit how often a function can run.
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    const $container = $('#hpg-results-container');
    const $loader = $('#hpg-loader');
    const $form = $('#hpg-filters');

    function getFilteredPosts() {
        const formData = $form.serializeArray();
        const data = {
            action: 'hpg_filter_posts',
            nonce: hpg_globals.nonce
        };
        
        // Convert form data to a format suitable for the AJAX request
        $.each(formData, function(i, field){
            data[field.name] = field.value;
        });

        $.ajax({
            url: hpg_globals.ajax_url,
            type: 'POST',
            data: data,
            beforeSend: function() {
                $loader.show();
                $container.css('opacity', 0.5);
            },
            success: function(response) {
                if (response.success) {
                    $container.html(response.data.html);
                } else {
                    $container.html('<p class="hpg-no-results">An error occurred.</p>');
                }
            },
            error: function() {
                $container.html('<p class="hpg-no-results">A network error occurred. Please try again.</p>');
            },
            complete: function() {
                $loader.hide();
                $container.css('opacity', 1);
            }
        });
    }

    // Event listener for dropdowns
    $form.on('change', 'select', getFilteredPosts);

    // Event listener for search input with debounce
    $form.on('keyup', 'input[type="search"]', debounce(getFilteredPosts, 500));
    $form.on('submit', function(e) {
        e.preventDefault(); // Prevent form submission
    });

    // Event listener for the new "Clear Filters" button
    $(document).on('click', '#hpg-clear-filters', function(e) {
        e.preventDefault();
        
        // Reset the form fields to their default values
        $form[0].reset(); 
        
        // Trigger the AJAX request to show all posts
        getFilteredPosts();
    });

    // Event listener for Like button (uses event delegation)
    $(document).on('click', '.hpg-like-btn', function(e) {
        e.preventDefault();
        
        const $this = $(this);
        const post_id = $this.data('post-id');
        const $likeCountSpan = $this.find('.hpg-like-count');

        if ($this.hasClass('liked')) {
            // Optional: implement unlike functionality or just prevent multiple likes
            return; 
        }

        $.ajax({
            url: hpg_globals.ajax_url,
            type: 'POST',
            data: {
                action: 'hpg_like_post',
                nonce: hpg_globals.nonce,
                post_id: post_id
            },
            success: function(response) {
                if (response.success) {
                    $likeCountSpan.text(response.data.new_count);
                    $this.addClass('liked');
                }
            },
            error: function() {
                // You might want to show an error to the user
            }
        });
    });
}); 