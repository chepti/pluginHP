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
        const parentSelect = $('#hpc-collection-group');
        collectionsList.html('<p>טוען אוספים...</p>');
        parentSelect.prop('disabled', true).empty().append('<option value="">ללא קבוצה</option>');

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
                    const data = response.data || {};
                    const collections = Array.isArray(data.collections) ? data.collections : [];
                    const parents = Array.isArray(data.groups) ? data.groups : [];

                    // Render collections list
                    if (collections.length > 0) {
                        let html = '<ul class="hpc-collections-list">';
                        collections.forEach(function(collection) {
                            const checkedClass = collection.is_in_collection ? 'hpc-checked' : '';
                            const checkedIcon = collection.is_in_collection ? '✔' : '+';
                            const parentBadge = collection.group_name ? `<span class="hpc-parent-badge" title="קבוצת אוספים">${collection.group_name}</span>` : '';
                            const collectionNameHtml = collection.url 
                                ? `<a href="${collection.url}" target="_blank" title="פתח את האוסף בלשונית חדשה">${collection.name}</a>`
                                : collection.name;

                            html += `<li class="${checkedClass}" data-collection-id="${collection.id}">
                                        <button class="hpc-collection-toggle">
                                            <span class="hpc-toggle-icon">${checkedIcon}</span>
                                            <span class="hpc-collection-name">${collectionNameHtml}</span>
                                            ${parentBadge}
                                        </button>
                                     </li>`;
                        });
                        html += '</ul>';
                        collectionsList.html(html);
                    } else {
                        collectionsList.html('<p class="hpc-no-collections-message">עדיין לא יצרת אוספים.</p>');
                    }

                    // Populate parents dropdown
                    if (parents.length > 0) {
                        parents.forEach(function(parent) {
                            parentSelect.append(`<option value="${parent.id}">${parent.name}</option>`);
                        });
                        parentSelect.prop('disabled', false);
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
        const parentId = $('#hpc-collection-group').val();
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
                group_id: parentId || ''
            },
            success: function(response) {
                if (response.success) {
                    fetchUserCollections(postId);
                    input.val('');
                    $('#hpc-collection-group').val('');
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

    // Create new group (top-level, global) and add it to the dropdown
    $body.on('click', '#hpc-create-group-button', function() {
        const $btn = $(this);
        const $input = $('#hpc-new-group-name');
        const name = $input.val().trim();
        if (!name) { alert('יש להזין שם לקבוצה.'); return; }
        $btn.prop('disabled', true).text('יוצר...');
        $.ajax({
            url: hpc_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'hpc_create_collection_group',
                nonce: hpc_ajax_object.nonce,
                name: name
            }
        }).done(function(response){
            if (response.success) {
                // append to dropdown and select it
                const data = response.data || {};
                if (data.id && data.name) {
                    const $sel = $('#hpc-collection-group');
                    $sel.append(`<option value="${data.id}">${data.name}</option>`);
                    $sel.val(String(data.id));
                    $input.val('');
                }
            } else {
                alert(response.data && response.data.message ? response.data.message : 'שגיאה ביצירת קבוצה.');
            }
        }).fail(function(){
            alert('שגיאת שרת. נסה שוב מאוחר יותר.');
        }).always(function(){
            $btn.prop('disabled', false).text('צור קבוצה');
        });
    });

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

    // New: Handle saving the collection description
    if ($('.hpc-save-description-button').length) {
        $('.hpc-collections-grid').on('click', '.hpc-save-description-button', function() {
            const button = $(this);
            const collection_id = button.data('collection-id');
            const textarea = button.siblings('.hpc-collection-description-input');
            const description = textarea.val();
            const successMsg = button.siblings('.hpc-save-success-msg');

            if (button.hasClass('processing')) {
                return;
            }
            button.addClass('processing').text('שומר...');

            $.post(hpc_ajax_object.ajax_url, {
                action: 'hpc_update_collection_description',
                nonce: hpc_ajax_object.nonce,
                collection_id: collection_id,
                description: description
            })
            .done(function(response) {
                if (response.success) {
                    successMsg.fadeIn();
                    setTimeout(function() {
                        successMsg.fadeOut();
                    }, 2000); // Fade out after 2 seconds
                } else {
                    alert(response.data.message || 'Error saving description.');
                }
            })
            .fail(function() {
                alert('Server error while saving description.');
            })
            .always(function() {
                button.removeClass('processing').text('שמור תיאור');
            });
        });
    }

    // New: Handle liking a collection
    if ($('.hpc-archive-meta').length) {
        $('.hpc-archive-meta').on('click', '.hpc-like-button', function() {
            const button = $(this);
            const collection_id = button.data('collection-id');
            
            if (button.hasClass('processing')) {
                return; // Prevent multiple clicks
            }
            button.addClass('processing');

            $.post(hpc_ajax_object.ajax_url, {
                action: 'hpc_like_collection',
                nonce: hpc_ajax_object.nonce,
                collection_id: collection_id
            })
            .done(function(response) {
                if (response.success) {
                    // Update like count
                    $('.hpc-likes-count .count').text(response.data.new_count);
                    // Toggle button class and text
                    button.toggleClass('liked', response.data.user_has_liked);
                    button.find('.like-text').text(response.data.user_has_liked ? 'אהבתי' : 'לייק');
                } else {
                    alert(response.data.message || 'אירעה שגיאה.');
                }
            })
            .fail(function() {
                alert('אירעה שגיאת שרת. נסה שוב מאוחר יותר.');
            })
            .always(function() {
                button.removeClass('processing');
            });
        });
    }

}); 