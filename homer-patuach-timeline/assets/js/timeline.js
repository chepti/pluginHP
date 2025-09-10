jQuery(document).ready(function($) {
    // Constants
    const ZOOM_LEVELS = [0.5, 0.75, 1, 1.25, 1.5, 2];
    let currentZoomIndex = 2; // Start at 1x zoom

    // Cache DOM elements
    const $container = $('.hpt-timeline-container');
    const $content = $('.hpt-timeline-content');
    const $topics = $('.hpt-timeline-topics');
    const $items = $('.hpt-timeline-items');
    const $searchPanel = $('.hpt-search-panel');
    const $searchInput = $('.hpt-search-input');
    const $searchResults = $('.hpt-search-results');
    const $pinDialog = $('.hpt-pin-dialog');
    const $overlay = $('.hpt-overlay');

    // Get timeline data
    const timelineId = $container.data('timeline-id');
    const subjectId = $container.data('subject-id');
    const classId = $container.data('class-id');

    // Initialize timeline
    function initTimeline() {
        initZoom();
        initSearch();
        initDragDrop();
        initPinDialog();
    }

    // Zoom functionality
    function initZoom() {
        $('.hpt-zoom-in').on('click', () => {
            if (currentZoomIndex < ZOOM_LEVELS.length - 1) {
                currentZoomIndex++;
                updateZoom();
            }
        });

        $('.hpt-zoom-out').on('click', () => {
            if (currentZoomIndex > 0) {
                currentZoomIndex--;
                updateZoom();
            }
        });
    }

    function updateZoom() {
        const zoom = ZOOM_LEVELS[currentZoomIndex];
        $content.css('transform', `scaleX(${zoom})`);

        // Show/hide item titles based on zoom level
        if (zoom >= 1) {
            $('.hpt-item-title').show();
        } else {
            $('.hpt-item-title').hide();
        }
    }

    // Search functionality
    function initSearch() {
        let searchTimeout;

        $searchInput.on('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 300);
        });

        // Close search results when clicking outside
        $(document).on('click', (e) => {
            if (!$(e.target).closest('.hpt-search-panel').length) {
                $searchResults.empty();
            }
        });
    }

    function performSearch() {
        const query = $searchInput.val();
        if (query.length < 2) {
            $searchResults.empty();
            return;
        }

        $.ajax({
            url: hpt_globals.ajax_url,
            type: 'POST',
            data: {
                action: 'hpt_search_posts',
                nonce: hpt_globals.nonce,
                query: query,
                subject_id: subjectId,
                class_id: classId
            },
            success: (response) => {
                if (response.success) {
                    displaySearchResults(response.data);
                }
            }
        });
    }

    function displaySearchResults(items) {
        $searchResults.empty();

        items.forEach(item => {
            const $item = $('<div>', {
                class: 'hpt-search-item',
                'data-id': item.id,
                text: item.title
            }).draggable({
                helper: 'clone',
                appendTo: 'body',
                zIndex: 1000,
                start: (e, ui) => {
                    ui.helper.css({
                        'width': '200px',
                        'background': '#fff',
                        'padding': '10px',
                        'border': '1px solid #ddd',
                        'border-radius': '4px',
                        'box-shadow': '0 2px 10px rgba(0,0,0,0.1)'
                    });
                }
            });

            $searchResults.append($item);
        });
    }

    // Drag and drop functionality
    function initDragDrop() {
        $('.hpt-timeline-topic').droppable({
            accept: '.hpt-search-item, .hpt-timeline-item',
            tolerance: 'pointer',
            drop: handleItemDrop
        });

        $('.hpt-timeline-item').draggable({
            axis: 'x',
            containment: 'parent',
            stop: handleItemDragStop
        });
    }

    function handleItemDrop(e, ui) {
        const $topic = $(this);
        const topicId = $topic.data('topic-id');
        const itemId = ui.draggable.data('id');
        const topicOffset = $topic.offset();
        const dropX = e.pageX - topicOffset.left;
        const position = (dropX / $topic.width()) * 100;

        // If this is a new item from search
        if (ui.draggable.hasClass('hpt-search-item')) {
            showPinDialog(itemId, topicId, position);
        }
        // If this is an existing item being moved
        else {
            updateItemPosition(itemId, topicId, position);
        }
    }

    function handleItemDragStop(e, ui) {
        const $item = $(ui.helper);
        const itemId = $item.data('item-id');
        const topicId = $item.data('topic-id');
        const position = ($item.position().left / $item.parent().width()) * 100;

        updateItemPosition(itemId, topicId, position);
    }

    // Pin dialog functionality
    function initPinDialog() {
        $('.hpt-pin-cancel').on('click', closePinDialog);
        $('.hpt-pin-save').on('click', savePinSettings);
    }

    function showPinDialog(itemId, topicId, position) {
        $pinDialog.data({
            itemId: itemId,
            topicId: topicId,
            position: position
        });

        $pinDialog.add($overlay).addClass('open');
    }

    function closePinDialog() {
        $pinDialog.add($overlay).removeClass('open');
    }

    function savePinSettings() {
        const itemId = $pinDialog.data('itemId');
        const topicId = $pinDialog.data('topicId');
        const position = $pinDialog.data('position');
        const type = $pinDialog.find('input[name="pin_type"]:checked').val();
        const color = $pinDialog.find('input[name="pin_color"]').val();

        $.ajax({
            url: hpt_globals.ajax_url,
            type: 'POST',
            data: {
                action: 'hpt_save_pin',
                nonce: hpt_globals.nonce,
                item_id: itemId,
                topic_id: topicId,
                position: position,
                type: type,
                color: color,
                timeline_id: timelineId
            },
            success: (response) => {
                if (response.success) {
                    addItemToTimeline(response.data);
                    closePinDialog();
                }
            }
        });
    }

    function addItemToTimeline(item) {
        const $item = $(`
            <div class="hpt-timeline-item" 
                 data-item-id="${item.id}" 
                 data-topic-id="${item.topic_id}" 
                 style="right: ${item.position}%;">
                <div class="hpt-item-line"></div>
                <div class="hpt-item-shape ${item.type}" style="background-color: ${item.color};"></div>
                <div class="hpt-item-title">${item.title}</div>
            </div>
        `);

        $items.append($item);
        $item.draggable({
            axis: 'x',
            containment: 'parent',
            stop: handleItemDragStop
        });

        // Add animation class
        $item.addClass('pinned');
        setTimeout(() => $item.removeClass('pinned'), 300);
    }

    function updateItemPosition(itemId, topicId, position) {
        $.ajax({
            url: hpt_globals.ajax_url,
            type: 'POST',
            data: {
                action: 'hpt_update_pin_position',
                nonce: hpt_globals.nonce,
                item_id: itemId,
                topic_id: topicId,
                position: position
            },
            success: (response) => {
                if (response.success) {
                    // Update the item's position in the UI
                    $(`.hpt-timeline-item[data-item-id="${itemId}"]`)
                        .css('right', `${position}%`)
                        .data('topic-id', topicId);
                }
            }
        });
    }

    // Initialize everything
    initTimeline();
});