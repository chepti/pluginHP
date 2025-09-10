jQuery(document).ready(function($) {
    // Only run on timeline topic taxonomy pages
    if (!$('body').hasClass('taxonomy-timeline_topic')) {
        return;
    }

    // Initialize date pickers with Hebrew localization
    if ($.datepicker) {
        $.datepicker.regional['he'] = {
            closeText: 'סגור',
            prevText: '&#x3C;הקודם',
            nextText: 'הבא&#x3E;',
            currentText: 'היום',
            monthNames: ['ינואר','פברואר','מרץ','אפריל','מאי','יוני',
            'יולי','אוגוסט','ספטמבר','אוקטובר','נובמבר','דצמבר'],
            monthNamesShort: ['ינו','פבר','מרץ','אפר','מאי','יוני',
            'יולי','אוג','ספט','אוק','נוב','דצמ'],
            dayNames: ['ראשון','שני','שלישי','רביעי','חמישי','שישי','שבת'],
            dayNamesShort: ['א\'','ב\'','ג\'','ד\'','ה\'','ו\'','שבת'],
            dayNamesMin: ['א\'','ב\'','ג\'','ד\'','ה\'','ו\'','שבת'],
            weekHeader: 'Wk',
            dateFormat: 'dd/mm/yy',
            firstDay: 0,
            isRTL: true,
            showMonthAfterYear: false,
            yearSuffix: ''
        };
        $.datepicker.setDefaults($.datepicker.regional['he']);

        // Convert date inputs to datepicker
        $('#hpt_start_date, #hpt_end_date').datepicker({
            changeMonth: true,
            changeYear: true,
            yearRange: 'c-10:c+10',
            onSelect: function(dateText, inst) {
                // Convert to YYYY-MM-DD format for the input
                var date = $(this).datepicker('getDate');
                var isoDate = date.getFullYear() + '-' + 
                             ('0' + (date.getMonth() + 1)).slice(-2) + '-' + 
                             ('0' + date.getDate()).slice(-2);
                $(this).val(isoDate);
            }
        });
    }

    // Initialize color picker
    if ($.fn.wpColorPicker) {
        $('#hpt_color').wpColorPicker({
            defaultColor: '#3498db',
            change: function(event, ui) {
                updateTopicPreview();
            }
        });
    }

    // Add topic preview
    var $form = $('form#edittag, form#addtag');
    if ($form.length) {
        var $preview = $('<div>', {
            class: 'hpt-topic-preview',
            html: '<h3>תצוגה מקדימה</h3><div class="hpt-preview-timeline"><div class="hpt-preview-topic"></div></div>'
        });
        $form.append($preview);

        // Update preview when inputs change
        $form.on('change', 'input, select', updateTopicPreview);
        updateTopicPreview();
    }

    // Update the topic preview
    function updateTopicPreview() {
        var $previewTopic = $('.hpt-preview-topic');
        var color = $('#hpt_color').val() || '#3498db';
        var title = $('#tag-name').val() || 'נושא חדש';
        var startDate = $('#hpt_start_date').val();
        var endDate = $('#hpt_end_date').val();

        $previewTopic.css({
            'background-color': color,
            'width': '100%',
            'height': '40px',
            'border-radius': '4px',
            'position': 'relative',
            'margin': '20px 0',
            'cursor': 'pointer',
            'transition': 'all 0.2s'
        }).html('<div class="hpt-preview-topic-content" style="padding: 8px; color: white;">' + 
                '<h4 style="margin: 0; font-size: 14px;">' + title + '</h4>' +
                (startDate && endDate ? '<small style="opacity: 0.8;">' + startDate + ' - ' + endDate + '</small>' : '') +
                '</div>');

        // Add hover effect
        $previewTopic.hover(
            function() {
                $(this).css('transform', 'translateY(-2px)');
            },
            function() {
                $(this).css('transform', 'none');
            }
        );
    }

    // Validate dates on form submit
    $form.on('submit', function(e) {
        var startDate = $('#hpt_start_date').val();
        var endDate = $('#hpt_end_date').val();

        if (!startDate || !endDate) {
            e.preventDefault();
            alert('יש להזין תאריך התחלה ותאריך סיום.');
            return false;
        }

        var start = new Date(startDate);
        var end = new Date(endDate);

        if (end < start) {
            e.preventDefault();
            alert('תאריך הסיום חייב להיות מאוחר מתאריך ההתחלה.');
            return false;
        }

        return true;
    });
});
