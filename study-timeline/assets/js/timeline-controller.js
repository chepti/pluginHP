// Study Timeline Controller
// This file will contain the main JavaScript logic for the timeline.

let hiddenItems = new Set(); // Global set to store hidden item IDs

document.addEventListener('DOMContentLoaded', function () {
    // Find all timeline containers on the page
    const wrappers = document.querySelectorAll('.study-timeline-wrapper');
    if (!wrappers.length) {
        return; // No timelines on this page
    }

    // Initialize each timeline found
    wrappers.forEach(initTimelineAndRepo);
});

function initTimelineAndRepo(wrapper) {
    const container = wrapper.querySelector('.study-timeline-container');
    const repoContainer = wrapper.querySelector('.repository-items');
    
    initRepository(repoContainer);
    const timelineId = studyTimelineData.timelineId; // Get data from wp_localize_script
    initTimeline(container, timelineId);
}

function saveUserPreferences(hiddenItemsArray) {
    const { timelineId, nonce, apiBaseUrl } = studyTimelineData;
    fetch(`${apiBaseUrl}user-prefs/${timelineId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': nonce
        },
        body: JSON.stringify({ hidden_items: hiddenItemsArray })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            console.log('Preferences saved.');
        }
    })
    .catch(error => console.error('Error saving preferences:', error));
}


function initRepository(repoContainer) {
    const nonce = studyTimelineData.nonce;
    const apiBaseUrl = studyTimelineData.apiBaseUrl;

    fetch(`${apiBaseUrl}repository`, {
        headers: { 'X-WP-Nonce': nonce }
    })
    .then(response => response.json())
    .then(items => {
        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'repo-item';
            div.draggable = true;
            div.textContent = item.title;
            div.dataset.id = item.id;
            div.dataset.postType = item.post_type;

            div.addEventListener('dragstart', function (e) {
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    id: item.id,
                    content: item.title,
                    postType: item.post_type
                }));
                e.dataTransfer.effectAllowed = 'copy';
            });

            repoContainer.appendChild(div);
        });
    });
}

function initTimeline(container, timelineId) {
    const nonce = studyTimelineData.nonce;
    const apiBaseUrl = studyTimelineData.apiBaseUrl;

    // First, fetch user preferences
    fetch(`${apiBaseUrl}user-prefs/${timelineId}`, {
        headers: { 'X-WP-Nonce': nonce }
    })
    .then(response => response.json())
    .then(prefs => {
        hiddenItems = new Set(prefs.hidden_items || []);
        
        // After getting preferences, fetch the timeline data
        return fetch(`${apiBaseUrl}timeline/${timelineId}`, {
            headers: { 'X-WP-Nonce': nonce }
        });
    })
    .then(response => response.json())
    .then(data => {
        renderTimeline(container, data);
    })
    .catch(error => {
        console.error('Error initializing timeline:', error);
        container.innerHTML = `<p>Error loading timeline data.</p>`;
    });
}

function renderTimeline(container, data) {
    // 1. Define the Groups/Lanes
    const groups = new vis.DataSet([
        { id: 0, content: 'מערכים' },
        { id: 1, content: 'מצגות' },
        { id: 2, content: 'מדיה' },
        { id: 3, content: 'למידה עצמאית' }
    ]);

    // 2. Process Topics into Background Items for Vis.js
    const backgroundItems = new vis.DataSet(data.topics.map(topic => {
        return {
            id: `topic_${topic.id}`,
            content: topic.title,
            start: topic.start_date,
            end: topic.end_date,
            type: 'background',
            className: 'timeline-topic-band',
            style: `background-color: ${topic.color};`
        };
    }));

    // 3. Process Items, filtering out hidden ones
    const visibleItems = data.items.filter(item => !hiddenItems.has(item.id));
    const timelineItems = new vis.DataSet(visibleItems.map(item => {
        return {
            id: item.id,
            content: item.post_title, // Initially just the title
            start: item.item_date,
            group: item.item_lane, // This assigns the item to a group/lane
            subgroup: `sg_${item.id}`, // Prevents stacking within the same time point
            subgroupStack: {stack: false},
            post_type: item.post_type, // Add post_type for template logic
            thumbnail_url: item.thumbnail_url // Add thumbnail_url for close zoom
        };
    }));

    // 4. Configure Timeline Options
    const options = {
        // Basic configuration
        stack: true, // Allow items to stack
        stackSubgroups: true,
        width: '100%',
        height: '100%',
        margin: {
            item: 20
        },
        // Zoom options
        zoomMin: 1000 * 60 * 60 * 24 * 7, // A week
        zoomMax: 1000 * 60 * 60 * 24 * 365 * 2, // Two years
        // Set initial view to show the whole year
        start: new Date(new Date().getFullYear(), 0, 1),
        end: new Date(new Date().getFullYear(), 11, 31),

        // Template function to customize item appearance based on zoom
        template: function (item, element, data) {
            if (!item || !item.post_type) { return ''; }

            const timeline = element.closest('.vis-timeline'); // Get the timeline instance
            const zoomLevel = timeline.getWindow().end - timeline.getWindow().start;

            // Define zoom thresholds (in milliseconds)
            const ZOOM_LEVEL_ICON_ONLY = 1000 * 60 * 60 * 24 * 90; // 3 months
            const ZOOM_LEVEL_TITLE = 1000 * 60 * 60 * 24 * 30; // 1 month

            let iconClass = 'dashicons-admin-post'; // Default icon
            switch (item.post_type) {
                case 'mamarim': // Replace with your actual post types
                    iconClass = 'dashicons-text-page';
                    break;
                case 'matzgot':
                    iconClass = 'dashicons-media-presentation';
                    break;

                case 'media':
                    iconClass = 'dashicons-media-video';
                    break;
            }

            // Far zoom: Icon only
            if (zoomLevel > ZOOM_LEVEL_ICON_ONLY) {
                return `<div class="timeline-item-icon-only"><span class="dashicons ${iconClass}"></span></div>`;
            }

            // Medium zoom: Icon and Title
            if (zoomLevel > ZOOM_LEVEL_TITLE) {
                return `<div class="timeline-item-with-title">
                            <span class="dashicons ${iconClass}"></span>
                            <span class="item-title">${item.content}</span>
                            <button class="hide-item-btn" title="הסתר פריט זה">&times;</button>
                        </div>`;
            }

            // Close zoom: Thumbnail (if available) and Title
            let thumbnailHtml = item.thumbnail_url
                ? `<img src="${item.thumbnail_url}" alt="${item.content}" class="item-thumbnail"/>`
                : `<div class="item-thumbnail-placeholder"><span class="dashicons ${iconClass}"></span></div>`;

            return `<div class="timeline-item-detailed">
                        ${thumbnailHtml}
                        <div class="item-content-wrapper">
                            <span class="item-title">${item.content}</span>
                        </div>
                        <button class="hide-item-btn" title="הסתר פריט זה">&times;</button>
                    </div>`;
        },

        // Group-related options
        groupOrder: 'id', // Order groups by their ID
    };

    // 5. Create the Timeline instance
    const timeline = new vis.Timeline(container, timelineItems, options);
    timeline.setGroups(groups);
    timeline.setItems(backgroundItems); // Load background items
    
    // Add zoom-level listener to redraw on zoom
    timeline.on('rangechanged', function(properties) {
        // Redraw to apply the new template based on zoom
        timeline.redraw();
        // We will implement the zoom logic here in the next step.
        const zoomLevel = properties.end - properties.start; // Milliseconds
        // This is where we'll update item templates based on the zoom level.
    });

    // Handle clicks on items (e.g., for hiding)
    timeline.on('click', function(properties) {
        const { item } = properties;
        if (item && properties.event.target.classList.contains('hide-item-btn')) {
            const itemData = timeline.itemsData.get(item);
            if (!itemData) return;

            hiddenItems.add(itemData.id);
            saveUserPreferences(Array.from(hiddenItems));
            
            timeline.itemsData.remove(item);
        }
    });

    // Make the timeline a drop target
    container.addEventListener('dragover', function(e) {
        e.preventDefault();
    });

    container.addEventListener('drop', function(e) {
        e.preventDefault();
        const itemData = JSON.parse(e.dataTransfer.getData('text/plain'));
        
        // Determine the time and group where the item was dropped
        const props = timeline.getEventProperties(e);
        const dropTime = props.time;
        const dropGroup = props.group;

        if (dropGroup === null || dropGroup === undefined) {
            alert('Please drop the item onto one of the lanes.');
            return;
        }

        const newItem = {
            id: `new_${new Date().getTime()}`, // Temporary ID
            content: itemData.content,
            start: dropTime,
            group: dropGroup,
            post_id: itemData.id,
            post_type: itemData.postType,
        };

        // Visually add the item with a temporary ID
        const tempId = newItem.id;
        timeline.itemsData.add(newItem);

        // Now, save the item to the database
        const timelineId = studyTimelineData.timelineId;
        const nonce = studyTimelineData.nonce;
        const apiBaseUrl = studyTimelineData.apiBaseUrl;

        fetch(`${apiBaseUrl}timeline/${timelineId}/item`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce
            },
            body: JSON.stringify({
                post_id: itemData.id,
                item_date: props.time.toISOString().slice(0, 19).replace('T', ' '), // Format to 'YYYY-MM-DD HH:MM:SS'
                item_lane: dropGroup
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to save the item.');
            }
            return response.json();
        })
        .then(savedItem => {
            // It was saved successfully! Let's update the item in the timeline
            // with the permanent ID from the database.
            timeline.itemsData.remove(tempId);
            
            const finalItem = {
                ...newItem,
                id: savedItem.id, // Use the permanent ID
            };
            timeline.itemsData.add(finalItem);
            console.log('Item saved successfully with ID:', savedItem.id);
        })
        .catch(error => {
            console.error('Error saving item:', error);
            alert('An error occurred while saving the item. The item will be removed.');
            timeline.itemsData.remove(tempId); // Remove the temporary item on failure
        });
    });
}
