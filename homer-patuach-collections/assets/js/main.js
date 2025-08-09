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
     * Fetch subjects and populate the dropdown.
     */
    function fetchSubjects() {
        const $dropdown = $('#hpc-subject-dropdown');

        $.ajax({
            url: hpc_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'hpc_get_subjects_for_collections',
                nonce: hpc_ajax_object.nonce,
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    $dropdown.html('<option value="0">בחר תחום דעת (אופציונלי)</option>'); // Reset
                    response.data.forEach(function(subject) {
                        $dropdown.append(`<option value="${subject.id}">${subject.name}</option>`);
                    });
                    $dropdown.show();
                } else {
                    $dropdown.hide();
                }
            },
            error: function() {
                $dropdown.hide();
            }
        });
    }

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
                    // Add the new collection to the list without a full refresh
                    const collectionsList = $('#hpc-user-collections-list ul');
                    const collectionNameHtml = response.data.name;
                    const newCollectionHtml = `<li data-collection-id="${response.data.id}">
                                                <button class="hpc-collection-toggle">
                                                    <span class="hpc-toggle-icon">+</span>
                                                    <span class="hpc-collection-name">${collectionNameHtml}</span>
                                                </button>
                                             </li>`;
                    if(collectionsList.length > 0) {
                        collectionsList.append(newCollectionHtml);
                    } else {
                        // If it's the first collection, create the list
                        $('#hpc-user-collections-list').html('<ul class="hpc-collections-list">' + newCollectionHtml + '</ul>');
                    }
                    
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

    // --- Subject Filter Chip Logic ---
    $body.on('click', '.hpc-subject-chip', function() {
        const $chip = $(this);
        const subjectId = $chip.data('subject-id');

        // Toggle active class on chips
        $('.hpc-subject-chip').removeClass('active');
        $chip.addClass('active');

        // Filter the grid
        if (subjectId === 'all') {
            $('.hpc-collection-item').show();
        } else {
            $('.hpc-collection-item').hide();
            $('.hpc-collection-item[data-subject-id="' + subjectId + '"]').show();
        }
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

    // Use a more specific container for the event listener
    $('.hpc-collections-grid').on('click', '.hpc-save-subject-button', function() {
        const button = $(this);
        const collection_id = button.data('collection-id');
        const selector = $('#hpc-subject-selector-' + collection_id);
        const subject_id = selector.val();
        const successMsg = button.siblings('.hpc-subject-save-success-msg');

        if (button.hasClass('processing')) {
            return;
        }
        button.addClass('processing').text('שומר...');

        $.post(hpc_ajax_object.ajax_url, {
            action: 'hpc_update_collection_subject',
            nonce: hpc_ajax_object.nonce,
            collection_id: collection_id,
            subject_id: subject_id
        })
        .done(function(response) {
            if (response.success) {
                successMsg.fadeIn();
                setTimeout(function() {
                    successMsg.fadeOut();
                }, 2000);
                // Also update the data-subject-id on the parent item for filtering
                button.closest('.hpc-collection-item').attr('data-subject-id', subject_id);
            } else {
                alert(response.data.message || 'Error saving subject.');
            }
        })
        .fail(function() {
            alert('Server error while saving subject.');
        })
        .always(function() {
            button.removeClass('processing').text('שמור');
        });
    });

});