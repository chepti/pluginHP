/**
 * Homer Patuach Annual Timeline JavaScript
 * Handles interactive timeline functionality
 */

(function($) {
    'use strict';

    class HPAT_Timeline {
        constructor(container) {
            this.container = $(container);
            this.timeline = null;
            this.timelineData = null;
            this.searchResults = [];
            this.isDragging = false;

            this.init();
        }

        init() {
            this.loadTimelineData();
            this.bindEvents();
            this.initDragAndDrop();
        }

        loadTimelineData() {
            const self = this;
            const timelineData = this.container.find('#hpat-timeline-data');

            if (timelineData.length > 0) {
                this.timelineData = JSON.parse(timelineData.html());
                this.renderTimeline();
            } else {
                // Load data via AJAX
                const timelineId = this.container.data('timeline-id');
                if (timelineId) {
                    $.ajax({
                        url: hpat_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'hpat_get_timeline_data',
                            timeline_id: timelineId,
                            nonce: hpat_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                self.timelineData = response.data;
                                self.renderTimeline();
                            } else {
                                self.showError(response.data.message || hpat_ajax.strings.error);
                            }
                        },
                        error: function() {
                            self.showError(hpat_ajax.strings.error);
                        }
                    });
                }
            }
        }

        renderTimeline() {
            const self = this;
            const container = this.container.find('.hpat-timeline-container')[0];

            if (!container || !this.timelineData) {
                return;
            }

            // Prepare data for Vis.js
            const groups = this.prepareGroups();
            const items = this.prepareItems();

            // Timeline options
            const options = {
                height: this.container.find('.hpat-timeline-container').height(),
                minHeight: 300,
                start: moment().subtract(1, 'months').format('YYYY-MM-DD'),
                end: moment().add(2, 'months').format('YYYY-MM-DD'),
                zoomMin: 1000 * 60 * 60 * 24, // 1 day
                zoomMax: 1000 * 60 * 60 * 24 * 365, // 1 year
                editable: {
                    add: false,
                    updateTime: true,
                    updateGroup: true,
                    remove: false
                },
                locale: 'he',
                direction: 'right-to-left',
                margin: {
                    item: 10,
                    axis: 20
                },
                orientation: {
                    axis: 'top',
                    item: 'top'
                }
            };

            // Create timeline
            this.timeline = new vis.Timeline(container, items, groups, options);

            // Bind timeline events
            this.timeline.on('select', function(properties) {
                self.onItemSelect(properties);
            });

            this.timeline.on('itemover', function(properties) {
                self.onItemHover(properties, true);
            });

            this.timeline.on('itemout', function(properties) {
                self.onItemHover(properties, false);
            });
        }

        prepareGroups() {
            return [
                { id: 0, content: hpat_ajax.strings.lanes ? hpat_ajax.strings.lanes.lesson_plans : 'מערכי שיעור' },
                { id: 1, content: hpat_ajax.strings.lanes ? hpat_ajax.strings.lanes.presentations : 'מצגות' },
                { id: 2, content: hpat_ajax.strings.lanes ? hpat_ajax.strings.lanes.interactive : 'פעילויות אינטראקטיביות' },
                { id: 3, content: hpat_ajax.strings.lanes ? hpat_ajax.strings.lanes.assessment : 'הערכה' }
            ];
        }

        prepareItems() {
            if (!this.timelineData || !this.timelineData.items) {
                return [];
            }

            return this.timelineData.items.map(item => {
                const topic = this.timelineData.topics.find(t => t.id == item.topic_id);
                const shapeSymbol = this.getShapeSymbol(item.item_shape);

                return {
                    id: item.id,
                    group: item.lane,
                    content: `<div class="item-content">
                                <span class="item-shape">${shapeSymbol}</span>
                                <span class="item-title">${item.post_title}</span>
                              </div>`,
                    start: item.item_date,
                    title: this.getItemTooltip(item, topic),
                    style: this.getItemStyle(item, topic),
                    className: `timeline-item-shape-${item.item_shape}`,
                    postData: item
                };
            });
        }

        getShapeSymbol(shape) {
            const symbols = {
                square: '■',
                circle: '●',
                triangle: '▲',
                star: '★'
            };
            return symbols[shape] || '■';
        }

        getItemTooltip(item, topic) {
            return `<strong>${item.post_title}</strong><br>
                    נושא: ${topic ? topic.title : 'לא ידוע'}<br>
                    תאריך: ${moment(item.item_date).format('DD/MM/YYYY')}`;
        }

        getItemStyle(item, topic) {
            const color = item.item_color || (topic ? topic.color : '#3498db');
            return `background-color: ${color}; border-color: ${color}; color: white;`;
        }

        bindEvents() {
            const self = this;

            // Zoom controls
            this.container.find('.hpat-zoom-in').on('click', function() {
                if (self.timeline) {
                    self.timeline.zoomIn(0.5);
                }
            });

            this.container.find('.hpat-zoom-out').on('click', function() {
                if (self.timeline) {
                    self.timeline.zoomOut(0.5);
                }
            });

            this.container.find('.hpat-fit-to-screen').on('click', function() {
                if (self.timeline) {
                    self.timeline.fit();
                }
            });

            // Search functionality
            this.container.find('#hpat-search-button').on('click', function() {
                self.performSearch();
            });

            this.container.find('#hpat-search-input').on('keypress', function(e) {
                if (e.which === 13) {
                    self.performSearch();
                }
            });

            // Modal events
            this.container.find('.hpat-modal-close').on('click', function() {
                self.closeModal();
            });

            this.container.find('#hpat-item-config-cancel').on('click', function() {
                self.closeModal();
            });

            this.container.find('#hpat-item-config-save').on('click', function() {
                self.saveItemConfiguration();
            });

            // Shape and color selection
            this.container.on('click', '.hpat-shape-option', function() {
                $(this).siblings().removeClass('selected');
                $(this).addClass('selected');
            });

            this.container.on('click', '.hpat-color-option', function() {
                $(this).siblings().removeClass('selected');
                $(this).addClass('selected');
            });
        }

        initDragAndDrop() {
            const self = this;

            // Make search results draggable
            this.container.on('dragstart', '.hpat-search-result-item', function(e) {
                const postId = $(this).data('post-id');
                e.originalEvent.dataTransfer.setData('text/plain', postId);
                e.originalEvent.dataTransfer.effectAllowed = 'copy';
                self.isDragging = true;
                $(this).addClass('dragging');
            });

            this.container.on('dragend', '.hpat-search-result-item', function(e) {
                self.isDragging = false;
                $(this).removeClass('dragging');
            });

            // Timeline drop zone
            const timelineContainer = this.container.find('.hpat-timeline-container');
            timelineContainer.on('dragover', function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'copy';
                $(this).addClass('hpat-drop-zone-active');
            });

            timelineContainer.on('dragleave', function(e) {
                $(this).removeClass('hpat-drop-zone-active');
            });

            timelineContainer.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('hpat-drop-zone-active');

                if (!self.isDragging) {
                    return;
                }

                const postId = e.originalEvent.dataTransfer.getData('text/plain');
                if (postId) {
                    self.handleItemDrop(postId, e);
                }
            });
        }

        handleItemDrop(postId, event) {
            // Find the timeline position where the item was dropped
            const rect = event.currentTarget.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const timelineDate = this.timeline ? this.timeline.getEventProperties(event).time : null;

            if (timelineDate) {
                this.showItemConfigurationModal(postId, timelineDate);
            }
        }

        showItemConfigurationModal(postId, date) {
            const modal = this.container.find('#hpat-item-config-modal');
            modal.data('post-id', postId);
            modal.data('item-date', date);
            modal.fadeIn(300);
        }

        closeModal() {
            this.container.find('.hpat-modal').fadeOut(300);
        }

        saveItemConfiguration() {
            const modal = this.container.find('#hpat-item-config-modal');
            const postId = modal.data('post-id');
            const itemDate = modal.data('item-date');

            const selectedShape = modal.find('.hpat-shape-option.selected').data('shape') || 'square';
            const selectedColor = modal.find('.hpat-color-option.selected').data('color') || '';

            this.addItemToTimeline(postId, itemDate, selectedShape, selectedColor);
            this.closeModal();
        }

        addItemToTimeline(postId, itemDate, shape, color) {
            const self = this;
            const timelineId = this.container.data('timeline-id');

            $.ajax({
                url: hpat_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hpat_save_timeline_item',
                    timeline_id: timelineId,
                    post_id: postId,
                    item_date: moment(itemDate).format('YYYY-MM-DD'),
                    shape: shape,
                    color: color,
                    nonce: hpat_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showSuccess(response.data.message || 'הפריט נוסף בהצלחה');
                        self.loadTimelineData(); // Reload timeline data
                    } else {
                        self.showError(response.data.message || hpat_ajax.strings.error);
                    }
                },
                error: function() {
                    self.showError(hpat_ajax.strings.error);
                }
            });
        }

        performSearch() {
            const self = this;
            const searchTerm = this.container.find('#hpat-search-input').val().trim();

            if (!searchTerm) {
                this.container.find('#hpat-search-results').empty();
                return;
            }

            $.ajax({
                url: hpat_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'hpat_search_posts',
                    search_term: searchTerm,
                    nonce: hpat_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.displaySearchResults(response.data);
                    } else {
                        self.showError(response.data.message || hpat_ajax.strings.error);
                    }
                },
                error: function() {
                    self.showError(hpat_ajax.strings.error);
                }
            });
        }

        displaySearchResults(results) {
            const resultsContainer = this.container.find('#hpat-search-results');
            resultsContainer.empty();

            if (results.length === 0) {
                resultsContainer.html('<p>לא נמצאו תוצאות</p>');
                return;
            }

            results.forEach(post => {
                const resultHtml = `
                    <div class="hpat-search-result-item" data-post-id="${post.ID}" draggable="true">
                        <div class="hpat-search-result-thumb">
                            <img src="${post.thumbnail_url || ''}" alt="${post.post_title}">
                        </div>
                        <div class="hpat-search-result-info">
                            <strong class="hpat-search-result-title">${post.post_title}</strong>
                            <div class="hpat-search-result-excerpt">${post.post_excerpt || ''}</div>
                            <div class="hpat-search-result-meta">
                                <span>תחום: ${post.subjects ? post.subjects.join(', ') : 'לא צוין'}</span>
                            </div>
                        </div>
                        <div class="hpat-drag-handle">⋮⋮</div>
                    </div>
                `;
                resultsContainer.append(resultHtml);
            });
        }

        onItemSelect(properties) {
            if (properties.items.length > 0) {
                const itemId = properties.items[0];
                // Handle item selection - could open item details modal
                console.log('Selected item:', itemId);
            }
        }

        onItemHover(properties, isHover) {
            // Handle item hover effects
            const item = this.container.find(`.vis-item[data-id="${properties.item}"]`);
            if (isHover) {
                item.addClass('hpat-item-hover');
            } else {
                item.removeClass('hpat-item-hover');
            }
        }

        showError(message) {
            this.showMessage(message, 'error');
        }

        showSuccess(message) {
            this.showMessage(message, 'success');
        }

        showMessage(message, type) {
            // Create notification element
            const notification = $(`<div class="hpat-notification hpat-${type}">${message}</div>`);

            // Add to container
            this.container.append(notification);

            // Animate in
            notification.fadeIn(300);

            // Remove after 3 seconds
            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    }

    // Initialize timelines when document is ready
    $(document).ready(function() {
        $('.hpat-annual-timeline-wrapper').each(function() {
            new HPAT_Timeline(this);
        });
    });

})(jQuery);
