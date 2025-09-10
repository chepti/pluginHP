jQuery(document).ready(function($) {
    // Constants
    const SEARCH_DELAY = 300; // Delay in milliseconds before performing search
    const MIN_SEARCH_LENGTH = 2; // Minimum characters required to start search
    const MAX_RESULTS = 10; // Maximum number of results to display

    // Cache DOM elements
    const $searchContainer = $('.hpt-search-container');
    const $searchInput = $('.hpt-search-input');
    const $searchResults = $('.hpt-search-results');
    const $timeline = $('.hpt-timeline');

    // Initialize search functionality
    function initSearch() {
        let searchTimeout;

        // Handle input changes
        $searchInput.on('input', function() {
            const query = $(this).val();
            
            clearTimeout(searchTimeout);
            
            if (query.length < MIN_SEARCH_LENGTH) {
                hideResults();
                return;
            }

            searchTimeout = setTimeout(function() {
                performSearch(query);
            }, SEARCH_DELAY);
        });

        // Handle focus/blur events
        $searchInput.on('focus', function() {
            $searchContainer.addClass('is-focused');
            if ($(this).val().length >= MIN_SEARCH_LENGTH) {
                showResults();
            }
        });

        // Close search results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.hpt-search-container').length) {
                hideResults();
            }
        });

        // Handle keyboard navigation
        $searchInput.on('keydown', function(e) {
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    navigateResults('down');
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    navigateResults('up');
                    break;
                case 'Enter':
                    e.preventDefault();
                    selectCurrentResult();
                    break;
                case 'Escape':
                    e.preventDefault();
                    hideResults();
                    break;
            }
        });
    }

    // Perform search via AJAX
    function performSearch(query) {
        const subjectId = $timeline.data('subject-id');

        $.ajax({
            url: hpt_globals.ajax_url,
            type: 'POST',
            data: {
                action: 'hpt_search_items',
                nonce: hpt_globals.nonce,
                search: query,
                subject_id: subjectId
            },
            beforeSend: function() {
                $searchResults.html('<div class="hpt-search-loading">מחפש...</div>');
                showResults();
            },
            success: function(response) {
                if (response.success && response.data) {
                    displayResults(response.data);
                } else {
                    $searchResults.html('<div class="hpt-search-error">שגיאה בחיפוש. נסו שוב.</div>');
                }
            },
            error: function() {
                $searchResults.html('<div class="hpt-search-error">שגיאה בחיפוש. נסו שוב.</div>');
            }
        });
    }

    // Display search results
    function displayResults(items) {
        if (!items.length) {
            $searchResults.html('<div class="hpt-search-empty">לא נמצאו תוצאות.</div>');
            return;
        }

        const $list = $('<div>', {
            class: 'hpt-search-results-list'
        });

        items.slice(0, MAX_RESULTS).forEach((item, index) => {
            const $item = createResultItem(item, index);
            $list.append($item);
        });

        $searchResults.empty().append($list);

        if (items.length > MAX_RESULTS) {
            $searchResults.append(
                $('<div>', {
                    class: 'hpt-search-more',
                    text: `ועוד ${items.length - MAX_RESULTS} תוצאות...`
                })
            );
        }
    }

    // Create a single result item
    function createResultItem(item, index) {
        const $item = $('<div>', {
            class: 'hpt-search-item',
            'data-item-id': item.id,
            'data-type': item.type,
            'data-index': index,
            tabindex: 0
        });

        // Add thumbnail if available
        if (item.thumbnail) {
            $item.append(
                $('<div>', {
                    class: 'hpt-search-item-thumb'
                }).append(
                    $('<img>', {
                        src: item.thumbnail,
                        alt: item.title
                    })
                )
            );
        }

        // Add item content
        const $content = $('<div>', {
            class: 'hpt-search-item-content'
        });

        $content.append(
            $('<h4>', {
                class: 'hpt-search-item-title',
                text: item.title
            })
        );

        if (item.excerpt) {
            $content.append(
                $('<p>', {
                    class: 'hpt-search-item-excerpt',
                    text: item.excerpt
                })
            );
        }

        // Add metadata
        const $meta = $('<div>', {
            class: 'hpt-search-item-meta'
        });

        // Add type indicator
        $meta.append(
            $('<span>', {
                class: 'hpt-search-item-type ' + item.type,
                title: getTypeLabel(item.type)
            })
        );

        $content.append($meta);
        $item.append($content);

        // Make item draggable
        $item.draggable({
            helper: 'clone',
            appendTo: 'body',
            zIndex: 1000,
            start: function(event, ui) {
                $(ui.helper).addClass('hpt-dragging');
                hideResults();
            }
        });

        return $item;
    }

    // Get label for item type
    function getTypeLabel(type) {
        const types = {
            square: 'מערך או דף עבודה',
            circle: 'פעילות אינטראקטיבית',
            triangle: 'מדיה (סרטון, מצגת)',
            star: 'הערכה'
        };
        return types[type] || type;
    }

    // Show search results
    function showResults() {
        $searchResults.addClass('active');
        $searchContainer.addClass('has-results');
    }

    // Hide search results
    function hideResults() {
        $searchResults.removeClass('active');
        $searchContainer.removeClass('has-results');
        $searchResults.find('.hpt-search-item').removeClass('selected');
    }

    // Navigate through results with keyboard
    function navigateResults(direction) {
        const $items = $searchResults.find('.hpt-search-item');
        const $selected = $items.filter('.selected');
        let $next;

        if (!$selected.length) {
            $next = direction === 'down' ? $items.first() : $items.last();
        } else {
            const currentIndex = $selected.data('index');
            if (direction === 'down') {
                $next = $items.filter(`[data-index=${currentIndex + 1}]`);
                if (!$next.length) $next = $items.first();
            } else {
                $next = $items.filter(`[data-index=${currentIndex - 1}]`);
                if (!$next.length) $next = $items.last();
            }
        }

        $items.removeClass('selected');
        $next.addClass('selected').focus();
    }

    // Select the currently highlighted result
    function selectCurrentResult() {
        const $selected = $searchResults.find('.hpt-search-item.selected');
        if ($selected.length) {
            // Trigger drag start programmatically
            const event = jQuery.Event('mousedown');
            $selected.trigger(event);
            hideResults();
        }
    }

    // Initialize search when document is ready
    initSearch();
});
