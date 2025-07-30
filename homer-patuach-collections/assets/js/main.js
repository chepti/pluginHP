jQuery(document).ready(function($) {
    'use strict';

    // --- DOM Elements ---
    const $body = $('body');
    const $modalOverlay = $('#hpc-modal-overlay');

    // --- Event Handlers ---

    // Open the modal
    $body.on('click', '#hpc-open-modal-button', function(e) {
        e.preventDefault();
        const postId = $(this).data('post-id');
        if (postId) {
            $modalOverlay.removeClass('hpc-modal-hidden');
            fetchUserCollections(postId);
        }
    });

    // Close the modal
    function closeModal() {
        $modalOverlay.addClass('hpc-modal-hidden');
    }

    $body.on('click', '#hpc-close-modal-button', closeModal);
    $modalOverlay.on('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === "Escape" && !$modalOverlay.hasClass('hpc-modal-hidden')) {
            closeModal();
        }
    });


    // --- AJAX Functions ---

    /**
     * Fetch the current user's collections and render them in the modal.
     */
    function fetchUserCollections(postId) {
        const collectionsList = $('#hpc-user-collections-list');
        collectionsList.html('<p>טוען אוספים...</p>');

        $.ajax({
            url: hpc_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'hpc_get_user_collections',
                nonce: hpc_ajax_object.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.length > 0) {
                        let html = '<ul class="hpc-collections-list">';
                        response.data.forEach(function(collection) {
                            const checkedClass = collection.is_in_collection ? 'hpc-checked' : '';
                            const checkedIcon = collection.is_in_collection ? '✔' : '+';
                            const collectionNameHtml = collection.url 
                                ? `<a href="${collection.url}" target="_blank" title="פתח את האוסף בלשונית חדשה">${collection.name}</a>`
                                : collection.name;

                            html += `<li class="${checkedClass}" data-collection-id="${collection.id}">
                                        <button class="hpc-collection-toggle">
                                            <span class="hpc-toggle-icon">${checkedIcon}</span>
                                            <span class="hpc-collection-name">${collectionNameHtml}</span>
                                        </button>
                                     </li>`;
                        });
                        html += '</ul>';
                        collectionsList.html(html);
                    } else {
                        collectionsList.html('<p class="hpc-no-collections-message">עדיין לא יצרת אוספים.</p>');
                    }
                } else {
                    collectionsList.html('<p>שגיאה בטעינת האוספים.</p>');
                }
            },
            error: function() {
                collectionsList.html('<p>שגיאת שרת. נסה שוב מאוחר יותר.</p>');
            }
        });
    }

    /**
     * Handle clicking on a collection to add/remove the post.
     */
    $body.on('click', '.hpc-collection-toggle', function(e) {
        e.preventDefault();
        const $button = $(this);
        const $listItem = $button.closest('li');
        const collectionId = $listItem.data('collection-id');
        const postId = $('#hpc-open-modal-button').data('post-id');

        if (!collectionId || !postId) {
            console.error('HPC Error: Missing data for collection toggle.');
            return;
        }
        
        togglePostInCollection(postId, collectionId, $button);
    });

    /**
     * AJAX call to toggle a post's status in a collection.
     */
    function togglePostInCollection(postId, collectionId, $button) {
        $button.prop('disabled', true);
        const $listItem = $button.closest('li');
        const $icon = $button.find('.hpc-toggle-icon');

        $.ajax({
            url: hpc_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'hpc_add_post_to_collection',
                nonce: hpc_ajax_object.nonce,
                post_id: postId,
                collection_id: collectionId
            },
            success: function(response) {
                if (response.success) {
                    // Instead of reloading, just update the UI
                    if (response.data.action === 'added') {
                        $listItem.addClass('hpc-checked');
                        $icon.text('✔');
                    } else if (response.data.action === 'removed') {
                        $listItem.removeClass('hpc-checked');
                        $icon.text('+');
                    }
                    // Re-enable the button for further actions
                    $button.prop('disabled', false);
                } else {
                    alert('שגיאה: ' + (response.data.message || 'לא ניתן היה לעדכן את האוסף.'));
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                alert('אירעה שגיאה בלתי צפויה.');
                $button.prop('disabled', false);
            }
        });
    }


    /**
     * Create a new collection.
     */
    function createNewCollection() {
        const input = $('#hpc-new-collection-name');
        const collectionName = input.val().trim();
        const postId = $('#hpc-open-modal-button').data('post-id');


        if (!collectionName) {
            alert('יש להזין שם לאוסף.');
            return;
        }

        $('#hpc-create-collection-button').prop('disabled', true).text('יוצר...');

        $.ajax({
            url: hpc_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'hpc_create_new_collection',
                nonce: hpc_ajax_object.nonce,
                name: collectionName,
            },
            success: function(response) {
                if (response.success) {
                    fetchUserCollections(postId);
                    input.val('');
                } else {
                    alert(response.data.message || 'שגיאה ביצירת האוסף.');
                }
            },
            error: function() {
                alert('שגיאת שרת. נסה שוב מאוחר יותר.');
            },
            complete: function() {
                $('#hpc-create-collection-button').prop('disabled', false).text('צור');
            }
        });
    }

    $body.on('click', '#hpc-create-collection-button', createNewCollection);

    // --- Toggle Search Area on Profile Page ---
    $body.on('click', '.hpc-open-search-button', function() {
        const $button = $(this);
        $button.next('.hpc-search-area').slideToggle(200);
    });

    // --- Post Search on Profile Page ---
    let searchTimeout;
    $body.on('keyup', '.hpc-post-search-input', function() {
        clearTimeout(searchTimeout);
        const $input = $(this);
        const query = $input.val().trim();
        const collectionId = $input.data('collection-id');
        const $resultsContainer = $input.closest('.hpc-collection-item').find('.hpc-search-results');

        if (query.length < 3) {
            $resultsContainer.empty().hide();
            return;
        }

        searchTimeout = setTimeout(() => {
            $.ajax({
                url: hpc_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'hpc_search_posts_for_collection',
                    nonce: hpc_ajax_object.nonce,
                    query: query,
                    collection_id: collectionId
                },
                success: function(response) {
                    if (response.success) {
                        let html = '';
                        if (response.data.length > 0) {
                            html = '<ul>';
                            response.data.forEach(function(post) {
                                const buttonText = post.is_in_collection ? 'נוסף' : 'הוסף';
                                const buttonDisabled = post.is_in_collection ? 'disabled' : '';
                                const addedClass = post.is_in_collection ? 'is-added' : '';
                                html += `<li>
                                    <span class="hpc-search-result-title">${post.title}</span>
                                    <button class="hpc-add-searched-post-button ${addedClass}" data-post-id="${post.id}" data-collection-id="${collectionId}" ${buttonDisabled}>
                                        ${buttonText}
                                    </button>
                                </li>`;
                            });
                            html += '</ul>';
                        } else {
                            html = '<p>לא נמצאו תוצאות.</p>';
                        }
                        $resultsContainer.html(html).show();
                    }
                }
            });
        }, 300); // Debounce for 300ms
    });
    
    // Handle adding a post from search results
    $body.on('click', '.hpc-add-searched-post-button', function() {
        const $button = $(this);
        const postId = $button.data('post-id');
        const collectionId = $button.data('collection-id');

        $button.prop('disabled', true);

        $.ajax({
            url: hpc_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'hpc_add_post_to_collection',
                nonce: hpc_ajax_object.nonce,
                post_id: postId,
                collection_id: collectionId
            },
            success: function(response) {
                if (response.success) {
                    // Instead of reloading, just update the button state
                     $button.text('נוסף').addClass('is-added');
                } else {
                    alert('שגיאה: ' + (response.data.message || 'לא ניתן היה להוסיף את הפוסט.'));
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                alert('אירעה שגיאה בלתי צפויה.');
                $button.prop('disabled', false);
            }
        });
    });


}); 