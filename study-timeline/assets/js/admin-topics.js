wp.domReady(function () {
    const container = document.getElementById('topics-timeline-container');
    if (!container) {
        return;
    }

    // --- Data and Variables ---
    const timelineId = adminTimelineData.timelineId;
    const initialTopics = adminTimelineData.topics;

    // --- Timeline Initialization ---
    const items = new vis.DataSet(initialTopics.map(topic => {
        return {
            id: topic.id,
            content: topic.title,
            start: topic.start_date,
            end: topic.end_date,
            style: `background-color: ${topic.color}; border-color: #333; color: #000;`,
            editable: true,
            type: 'range'
        };
    }));

    const options = {
        height: '100%',
        editable: {
            updateTime: true, // drag items horizontally
            updateGroup: false, // drag items from one group to another
            remove: false, // delete an item by pressing the delete key
            overrideItems: false
        },
        // Set initial view to show a reasonable range
        start: new Date(new Date().getFullYear(), 0, 1),
        end: new Date(new Date().getFullYear(), 11, 31),
        onMove: function(item, callback) {
            // When an item is moved, update its data and the table view
            updateTableAndSave(item);
            callback(item); // confirm the move
        }
    };

    const timeline = new vis.Timeline(container, items, options);

    // --- Functions ---
    function updateTableAndSave(item) {
        // Find the table row and update the date cells
        const row = document.querySelector(`#topic-row-${item.id}`);
        if (row) {
            row.querySelector('.start-date-cell').textContent = item.start.toISOString().split('T')[0];
            row.querySelector('.end-date-cell').textContent = item.end.toISOString().split('T')[0];
        }
        
        // Use wp.ajax to save the change
        wp.ajax.post('study_timeline_update_topic_dates', {
            _ajax_nonce: adminTimelineData.nonce,
            topic_id: item.id,
            start_date: item.start.toISOString().split('T')[0],
            end_date: item.end.toISOString().split('T')[0],
        })
        .done(function(response) {
            console.log('Topic updated!', response);
        })
        .fail(function(response) {
            console.error('Failed to update topic.', response);
            alert('Error: Could not save topic changes.');
        });
    }

    // Add click listener to table rows to focus timeline
    document.querySelectorAll('#topics-list-table tbody tr').forEach(row => {
        row.addEventListener('click', function(e) {

            // Handle edit button click
            if (e.target.classList.contains('edit-topic-btn')) {
                e.preventDefault();
                e.stopPropagation(); // Prevents the row click from firing
                const topicId = this.dataset.topicId;
                const titleElement = this.querySelector('.topic-title-text');
                const currentTitle = titleElement.textContent;
                
                const newTitle = prompt('Enter the new name for the topic:', currentTitle);

                if (newTitle && newTitle !== currentTitle) {
                    // Optimistically update the UI
                    titleElement.textContent = newTitle;
                    const timelineItem = items.get(topicId);
                    timelineItem.content = newTitle;
                    items.update(timelineItem);

                    // Save the change via AJAX
                    wp.ajax.post('study_timeline_update_topic_title', {
                        _ajax_nonce: adminTimelineData.nonce,
                        topic_id: topicId,
                        new_title: newTitle,
                    }).fail(function() {
                        // Revert on failure
                        alert('Error: Could not save the new title.');
                        titleElement.textContent = currentTitle;
                        timelineItem.content = currentTitle;
                        items.update(timelineItem);
                    });
                }
                return; // Stop further processing
            }

            // This part only runs if the edit button was NOT clicked
            const topicId = this.dataset.topicId;
            if(topicId) {
                timeline.focus(topicId);
                timeline.setSelection(topicId);
            }
        });
    });

});
