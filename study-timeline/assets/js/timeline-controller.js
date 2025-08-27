// Study Timeline Controller
// This file will contain the main JavaScript logic for the timeline.

document.addEventListener('DOMContentLoaded', function () {
    const timelineContainer = document.querySelector('.study-timeline-container');
    if (timelineContainer) {
        initTimeline(timelineContainer);
    }
});

function initTimeline(container) {
    const { timelineId, nonce, apiBaseUrl } = studyTimelineData;

    // Global timeline instance
    let timeline;

    const timelineOptions = {
        stack: false,
        width: '100%',
        height: '400px', // Adjust as needed
        zoomMin: 1000 * 60 * 60 * 24 * 7, // 1 week
        zoomMax: 1000 * 60 * 60 * 24 * 365 * 2, // 2 years
        orientation: 'top',
        template: itemTemplate,
        onDrop: handleDrop,
        snap: null // Allow free dropping
    };

    fetch(`${apiBaseUrl}timeline/${timelineId}`, { headers: { 'X-WP-Nonce': nonce } })
        .then(response => response.json())
        .then(data => {
            const groups = createLanes(3); // 3 lanes for items
            const items = new vis.DataSet(data.items.map(formatItem));
            const backgroundItems = new vis.DataSet(data.topics.map(formatTopic));

            timeline = new vis.Timeline(container, items, groups, timelineOptions);
            timeline.setItems(backgroundItems);

            initRepository(data.topics);
        })
        .catch(error => console.error('Error loading timeline data:', error));

    function createLanes(numberOfLanes) {
        const groups = new vis.DataSet();
        for (let i = 0; i < numberOfLanes; i++) {
            groups.add({ id: i, content: `רצועה ${i + 1}` });
        }
        return groups;
    }

    function formatItem(item) {
        return {
            id: item.id,
            content: item.post_title,
            start: item.item_date,
            group: item.item_lane,
            shape: item.item_shape, // custom property for template
            color: item.item_color, // custom property for template
        };
    }

    function formatTopic(topic) {
        return {
            id: `topic_${topic.id}`,
            content: topic.title,
            start: topic.start_date,
            end: topic.end_date,
            type: 'background',
            style: `background-color: ${topic.color}33;`, // Add transparency
            className: 'timeline-topic-band',
            color: topic.color // Store original color for later use
        };
    }

    function itemTemplate(item, element, data) {
        const zoomLevel = timeline.getWindow().end - timeline.getWindow().start;
        const oneMonth = 1000 * 60 * 60 * 24 * 30;

        element.className += ` timeline-item-shape-${item.shape}`;
        element.style.borderColor = item.color;
        element.style.backgroundColor = `${item.color}99`;

        if (zoomLevel > oneMonth) {
            // Zoomed out: shape only
            return '';
        } else {
            // Zoomed in: title + icon
            return `
                <span class="item-title">${item.content}</span>
                <span class="dashicons ${getIconForShape(item.shape)}"></span>
            `;
        }
    }
    
    function getIconForShape(shape) {
        switch (shape) {
            case 'square': return 'dashicons-media-text';
            case 'triangle': return 'dashicons-format-video';
            case 'circle': return 'dashicons-search';
            case 'star': return 'dashicons-star-filled';
            default: return 'dashicons-admin-post';
        }
    }

    function handleDrop(data) {
        const { time, group: lane, what, event } = data;
        if (what === 'item') {
            const postId = event.dataTransfer.getData('text/plain');
            const postTitle = event.dataTransfer.getData('text/title');
            
            const backgroundBands = timeline.getBackgroundItems();
            const droppedOnTopic = backgroundBands.find(band => {
                const start = new Date(band.start).getTime();
                const end = new Date(band.end).getTime();
                return time >= start && time <= end;
            });

            const defaultColor = droppedOnTopic ? droppedOnTopic.color : '#cccccc';

            showAddItemModal({ postId, postTitle, time, lane, defaultColor });
        }
    }

    function showAddItemModal({ postId, postTitle, time, lane, defaultColor }) {
        // Create and show a modal for shape and color selection
        // (Implementation is complex, so this is a simplified placeholder)
        const shape = prompt("בחר צורה: square, circle, triangle, star", "square");
        if (!shape) return; // User cancelled
        
        const color = prompt("בחר צבע (השאר ריק לצבע ברירת מחדל):", defaultColor);

        const newItemData = {
            post_id: postId,
            item_date: new Date(time).toISOString().slice(0, 19).replace('T', ' '),
            item_lane: lane,
            item_shape: shape,
            item_color: color || defaultColor
        };

        saveNewItem(newItemData);
    }
    
    function saveNewItem(itemData) {
        fetch(`${apiBaseUrl}timeline/${timelineId}/item`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
            body: JSON.stringify(itemData)
        })
        .then(response => response.json())
        .then(savedItem => {
            if (savedItem.id) {
                timeline.itemsData.add(formatItem(savedItem));
            } else {
                throw new Error('Failed to save item.');
            }
        })
        .catch(error => {
            console.error('Error saving item:', error);
            alert('שגיאה בשמירת הפריט.');
        });
    }

    function initRepository(topics) {
        const repoContainer = document.getElementById('repository-items-container');
        const searchInput = document.getElementById('repo-search-input');

        let allItems = [];

        function renderRepoItems(itemsToRender) {
            repoContainer.innerHTML = '';
            itemsToRender.forEach(item => {
                const div = document.createElement('div');
                div.className = 'repo-item';
                div.draggable = true;
                div.textContent = item.title;
                div.addEventListener('dragstart', (e) => {
                    e.dataTransfer.setData('text/plain', item.id);
                    e.dataTransfer.setData('text/title', item.title);
                    e.dataTransfer.effectAllowed = 'copy';
                });
                repoContainer.appendChild(div);
            });
        }

        function filterItems() {
            const query = searchInput.value.toLowerCase();
            const filtered = allItems.filter(item => item.title.toLowerCase().includes(query));
            renderRepoItems(filtered);
        }

        searchInput.addEventListener('input', filterItems);

        fetch(`${apiBaseUrl}repository`, { headers: { 'X-WP-Nonce': nonce } })
            .then(response => response.json())
            .then(items => {
                allItems = items;
                renderRepoItems(allItems);
            });
    }
}
