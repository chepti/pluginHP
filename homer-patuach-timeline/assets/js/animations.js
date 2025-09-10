jQuery(document).ready(function($) {
    // Constants
    const ANIMATION_DURATION = 300; // Duration in milliseconds
    const ANIMATION_EASING = 'easeInOutCubic'; // jQuery UI easing function

    // Add easing function if not available
    if (typeof $.easing.easeInOutCubic !== 'function') {
        $.easing.easeInOutCubic = function(x) {
            return x < 0.5 ?
                4 * x * x * x :
                1 - Math.pow(-2 * x + 2, 3) / 2;
        };
    }

    // Cache DOM elements
    const $timeline = $('.hpt-timeline');
    const $topics = $('.hpt-timeline-topic');
    const $items = $('.hpt-timeline-item');

    // Initialize animations
    function initAnimations() {
        // Add entrance animation to topics
        animateTopicsEntrance();

        // Add hover animations
        addHoverAnimations();

        // Add drag and drop animations
        addDragDropAnimations();

        // Add scroll animations
        addScrollAnimations();

        // Add zoom animations
        addZoomAnimations();
    }

    // Animate topics entrance
    function animateTopicsEntrance() {
        $topics.each(function(index) {
            const $topic = $(this);
            const delay = index * 100; // Stagger effect

            $topic.css({
                opacity: 0,
                transform: 'translateY(20px)'
            }).delay(delay).animate({
                opacity: 1,
                transform: 'translateY(0)'
            }, {
                duration: ANIMATION_DURATION,
                easing: ANIMATION_EASING,
                queue: true
            });
        });
    }

    // Add hover animations
    function addHoverAnimations() {
        // Topic hover effect
        $topics.hover(
            function() {
                $(this).find('.hpt-timeline-topic-content')
                    .stop(true, false)
                    .animate({
                        transform: 'translateY(-2px)',
                        boxShadow: '0 4px 12px rgba(0,0,0,0.2)'
                    }, {
                        duration: 200,
                        easing: 'easeOutQuad'
                    });
            },
            function() {
                $(this).find('.hpt-timeline-topic-content')
                    .stop(true, false)
                    .animate({
                        transform: 'translateY(0)',
                        boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
                    }, {
                        duration: 150,
                        easing: 'easeInQuad'
                    });
            }
        );

        // Item hover effect
        $items.hover(
            function() {
                const $item = $(this);
                const $tooltip = $item.find('.hpt-timeline-item-tooltip');

                $item.stop(true, false)
                    .animate({
                        transform: 'scale(1.1)',
                        zIndex: 2
                    }, {
                        duration: 200,
                        easing: 'easeOutBack',
                        step: function(now, fx) {
                            if (fx.prop === 'zIndex') {
                                this.style[fx.prop] = Math.round(now);
                            }
                        }
                    });

                $tooltip.stop(true, false)
                    .animate({
                        opacity: 1,
                        transform: 'translateY(-4px)'
                    }, {
                        duration: 200,
                        easing: 'easeOutQuad'
                    });
            },
            function() {
                const $item = $(this);
                const $tooltip = $item.find('.hpt-timeline-item-tooltip');

                $item.stop(true, false)
                    .animate({
                        transform: 'scale(1)',
                        zIndex: 1
                    }, {
                        duration: 150,
                        easing: 'easeInQuad',
                        step: function(now, fx) {
                            if (fx.prop === 'zIndex') {
                                this.style[fx.prop] = Math.round(now);
                            }
                        }
                    });

                $tooltip.stop(true, false)
                    .animate({
                        opacity: 0,
                        transform: 'translateY(0)'
                    }, {
                        duration: 150,
                        easing: 'easeInQuad'
                    });
            }
        );
    }

    // Add drag and drop animations
    function addDragDropAnimations() {
        // Dragging animation
        $items.on('dragstart', function() {
            $(this).addClass('is-dragging')
                .animate({
                    opacity: 0.8,
                    transform: 'scale(1.1)',
                    boxShadow: '0 8px 16px rgba(0,0,0,0.2)'
                }, {
                    duration: 200,
                    easing: 'easeOutQuad'
                });
        });

        // Drop animation
        $topics.on('drop', function(event, ui) {
            const $droppedItem = $(ui.draggable);
            const $dropZone = $(this);

            // Calculate final position
            const dropPosition = {
                left: event.pageX - $dropZone.offset().left,
                top: event.pageY - $dropZone.offset().top
            };

            // Animate the drop
            $droppedItem.removeClass('is-dragging')
                .animate({
                    left: dropPosition.left,
                    top: dropPosition.top,
                    opacity: 1,
                    transform: 'scale(1)',
                    boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
                }, {
                    duration: 300,
                    easing: 'easeOutBounce'
                });

            // Add ripple effect
            addRippleEffect($dropZone, event.pageX, event.pageY);
        });
    }

    // Add scroll animations
    function addScrollAnimations() {
        let isScrolling = false;
        let scrollTimeout;

        $timeline.on('scroll', function() {
            if (!isScrolling) {
                isScrolling = true;
                $timeline.addClass('is-scrolling');
            }

            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(function() {
                isScrolling = false;
                $timeline.removeClass('is-scrolling');
            }, 150);
        });
    }

    // Add zoom animations
    function addZoomAnimations() {
        $('.hpt-zoom-in, .hpt-zoom-out').on('click', function() {
            const $button = $(this);
            
            // Button press animation
            $button.animate({
                transform: 'scale(0.9)'
            }, {
                duration: 100,
                easing: 'easeInQuad',
                complete: function() {
                    $button.animate({
                        transform: 'scale(1)'
                    }, {
                        duration: 200,
                        easing: 'easeOutBack'
                    });
                }
            });
        });
    }

    // Add ripple effect
    function addRippleEffect($element, x, y) {
        const $ripple = $('<div>', {
            class: 'hpt-ripple',
            css: {
                left: x - $element.offset().left,
                top: y - $element.offset().top
            }
        });

        $element.append($ripple);

        $ripple.animate({
            width: 300,
            height: 300,
            opacity: 0
        }, {
            duration: 600,
            easing: 'easeOutQuad',
            complete: function() {
                $ripple.remove();
            }
        });
    }

    // Initialize animations when document is ready
    initAnimations();

    // Re-initialize animations when content changes
    $(document).on('hpt:contentUpdated', function() {
        initAnimations();
    });
});
