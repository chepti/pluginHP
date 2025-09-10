jQuery(document).ready(function($) {
    // Constants
    const SNAP_THRESHOLD = 10; // Pixels to snap to grid
    const GRID_SIZE = 4; // Grid size in pixels
    const ANIMATION_DURATION = 200; // Animation duration in milliseconds

    // Cache DOM elements
    const $timeline = $('.hpt-timeline');
    const $topics = $('.hpt-timeline-topic');
    const $items = $('.hpt-timeline-item');

    // Initialize drag and drop functionality
    function initDragDrop() {
        // Make items draggable
        $items.draggable({
            containment: 'parent',
            grid: [GRID_SIZE, GRID_SIZE],
            snap: true,
            snapTolerance: SNAP_THRESHOLD,
            zIndex: 1000,
            start: onDragStart,
            drag: onDrag,
            stop: onDragStop
        });

        // Make topics droppable
        $topics.droppable({
            accept: '.hpt-timeline-item',
            tolerance: 'pointer',
            hoverClass: 'hpt-topic-hover',
            over: onDropOver,
            out: onDropOut,
            drop: onDrop
        });
    }

    // Drag event handlers
    function onDragStart(event, ui) {
        const $item = $(ui.helper);
        
        // Add dragging class for styling
        $item.addClass('hpt-dragging');
        
        // Store original position
        $item.data('originalPosition', {
            top: ui.position.top,
            left: ui.position.left
        });
        
        // Create ghost element
        const $ghost = $item.clone()
            .addClass('hpt-ghost')
            .css({
                opacity: 0.5,
                position: 'absolute',
                zIndex: 999
            });
        
        $item.after($ghost);
    }

    function onDrag(event, ui) {
        const $item = $(ui.helper);
        const $topic = $item.closest('.hpt-timeline-topic');
        
        // Update position indicator
        updatePositionIndicator($item, $topic, ui.position);
        
        // Scroll timeline if needed
        handleTimelineScroll(event);
    }

    function onDragStop(event, ui) {
        const $item = $(ui.helper);
        
        // Remove dragging class
        $item.removeClass('hpt-dragging');
        
        // Remove ghost element
        $('.hpt-ghost').remove();
        
        // Remove position indicator
        $('.hpt-position-indicator').remove();
        
        // If dropped outside valid target, return to original position
        if (!$item.data('droppedSuccessfully')) {
            const originalPosition = $item.data('originalPosition');
            $item.animate(originalPosition, ANIMATION_DURATION);
        }
        
        // Clear stored data
        $item.removeData('originalPosition droppedSuccessfully');
    }

    // Drop event handlers
    function onDropOver(event, ui) {
        const $topic = $(this);
        const $item = $(ui.draggable);
        
        // Add hover class to topic
        $topic.addClass('hpt-topic-hover');
        
        // Show position indicator
        showPositionIndicator($item, $topic, ui.position);
    }

    function onDropOut(event, ui) {
        const $topic = $(this);
        
        // Remove hover class from topic
        $topic.removeClass('hpt-topic-hover');
        
        // Hide position indicator
        hidePositionIndicator();
    }

    function onDrop(event, ui) {
        const $topic = $(this);
        const $item = $(ui.draggable);
        const dropPosition = calculateDropPosition(event, $topic);
        
        // Mark item as successfully dropped
        $item.data('droppedSuccessfully', true);
        
        // Animate item to final position
        const finalPosition = calculateFinalPosition($item, $topic, dropPosition);
        $item.animate(finalPosition, ANIMATION_DURATION, function() {
            // After animation, save the new position
            saveItemPosition($item, $topic, dropPosition);
        });
        
        // Remove hover class and position indicator
        $topic.removeClass('hpt-topic-hover');
        hidePositionIndicator();
    }

    // Helper functions
    function calculateDropPosition(event, $topic) {
        const topicOffset = $topic.offset();
        const relativeX = event.pageX - topicOffset.left;
        return relativeX / $topic.width();
    }

    function calculateFinalPosition($item, $topic, position) {
        const topicWidth = $topic.width();
        const itemWidth = $item.width();
        
        return {
            left: Math.round(position * (topicWidth - itemWidth) / GRID_SIZE) * GRID_SIZE,
            top: 0 // Always align to top
        };
    }

    function showPositionIndicator($item, $topic, position) {
        // Remove any existing indicator
        hidePositionIndicator();
        
        // Create new indicator
        const $indicator = $('<div>', {
            class: 'hpt-position-indicator',
            css: {
                height: $topic.height(),
                left: position.left,
                top: 0
            }
        });
        
        $topic.append($indicator);
    }

    function hidePositionIndicator() {
        $('.hpt-position-indicator').remove();
    }

    function updatePositionIndicator($item, $topic, position) {
        const $indicator = $('.hpt-position-indicator');
        if ($indicator.length) {
            $indicator.css('left', position.left);
        }
    }

    function handleTimelineScroll(event) {
        const timelineOffset = $timeline.offset();
        const timelineWidth = $timeline.width();
        const scrollSpeed = 10;
        
        // Scroll left
        if (event.pageX < timelineOffset.left + 50) {
            $timeline.scrollLeft($timeline.scrollLeft() - scrollSpeed);
        }
        
        // Scroll right
        if (event.pageX > timelineOffset.left + timelineWidth - 50) {
            $timeline.scrollLeft($timeline.scrollLeft() + scrollSpeed);
        }
    }

    function saveItemPosition($item, $topic, position) {
        $.ajax({
            url: hpt_globals.ajax_url,
            type: 'POST',
            data: {
                action: 'hpt_save_item_position',
                nonce: hpt_globals.nonce,
                item_id: $item.data('item-id'),
                topic_id: $topic.data('topic-id'),
                position: position
            },
            success: function(response) {
                if (!response.success) {
                    console.error('Failed to save item position:', response.data);
                    // Optionally revert to original position
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error while saving item position:', error);
                // Optionally revert to original position
            }
        });
    }

    // Initialize drag and drop when document is ready
    initDragDrop();

    // Handle window resize
    let resizeTimeout;
    $(window).on('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            // Recalculate positions after resize
            $items.each(function() {
                const $item = $(this);
                const $topic = $item.closest('.hpt-timeline-topic');
                const position = $item.position().left / $topic.width();
                const finalPosition = calculateFinalPosition($item, $topic, position);
                $item.css(finalPosition);
            });
        }, 250);
    });
});
