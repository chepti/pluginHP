<?php
/**
 * דוגמאות שימוש בתוסף Homer Patuach - Annual Timeline
 * קובץ זה מדגים איך להשתמש בשורטקודים וב-API של התוסף
 */

// מניעת גישה ישירה
if (!defined('ABSPATH')) {
    exit;
}

/**
 * דוגמה 1: ציר זמן בסיסי
 */
function example_basic_timeline() {
    return '[annual_timeline group_id="math_7th_grade" academic_year="2024-2025"]';
}

/**
 * דוגמה 2: ציר זמן מותאם אישית
 */
function example_custom_timeline() {
    return '[annual_timeline
        group_id="science_8th_grade"
        academic_year="2024-2025"
        height="600"
        zoom_level="60"
        show_search="true"
        show_controls="true"
    ]';
}

/**
 * דוגמה 3: ציר זמן מוטמע בקוד PHP
 */
function example_embedded_timeline() {
    if (!function_exists('hpat_render_timeline')) {
        return '<p>התוסף Homer Patuach Annual Timeline אינו פעיל.</p>';
    }

    $atts = array(
        'group_id' => 'history_9th_grade',
        'academic_year' => '2024-2025',
        'height' => 500,
        'show_search' => 'true',
        'show_controls' => 'true'
    );

    return hpat_render_timeline($atts);
}

/**
 * דוגמה 4: מספר צירי זמן בעמוד אחד
 */
function example_multiple_timelines() {
    $output = '<h2>צירי זמן שונים</h2>';

    $timelines = array(
        array(
            'title' => 'מתמטיקה - כיתה ז\'',
            'group_id' => 'math_7th_grade',
            'color' => '#3498db'
        ),
        array(
            'title' => 'היסטוריה - כיתה ט\'',
            'group_id' => 'history_9th_grade',
            'color' => '#e74c3c'
        ),
        array(
            'title' => 'אנגלית - כיתה ח\'',
            'group_id' => 'english_8th_grade',
            'color' => '#2ecc71'
        )
    );

    foreach ($timelines as $timeline) {
        $output .= '<div style="margin-bottom: 40px; border: 1px solid #ddd; border-radius: 8px; padding: 20px;">';
        $output .= '<h3 style="color: ' . $timeline['color'] . ';">' . $timeline['title'] . '</h3>';
        $output .= do_shortcode('[annual_timeline group_id="' . $timeline['group_id'] . '" academic_year="2024-2025" height="400"]');
        $output .= '</div>';
    }

    return $output;
}

/**
 * דוגמה 5: ציר זמן עם JavaScript מותאם אישית
 */
function example_custom_js_timeline() {
    ob_start();
    ?>
    <div id="custom-timeline-wrapper">
        <?php echo do_shortcode('[annual_timeline group_id="custom_subject" academic_year="2024-2025"]'); ?>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // דוגמה להוספת אירועים מותאמים אישית
        $(document).on('hpat:item-added', function(e, data) {
            console.log('פריט חדש נוסף:', data);

            // שליחת התראה למורה
            if (data.item && data.item.added_by) {
                // קוד לשליחת התראה
                sendNotificationToTeacher(data);
            }
        });

        $(document).on('hpat:timeline-updated', function(e, data) {
            console.log('הציר עודכן:', data);

            // עדכון סטטיסטיקות בזמן אמת
            updateTimelineStats(data);
        });

        function sendNotificationToTeacher(data) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'hpat_send_teacher_notification',
                    item_id: data.item.id,
                    timeline_id: data.timeline_id
                }
            });
        }

        function updateTimelineStats(data) {
            // עדכון מונה פריטים
            $('.timeline-item-count').text(data.total_items);
        }
    });
    </script>

    <style>
    #custom-timeline-wrapper {
        position: relative;
    }

    #custom-timeline-wrapper .timeline-item-count {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #667eea;
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
    }
    </style>
    <?php

    return ob_get_clean();
}

/**
 * דוגמה 6: ציר זמן עם פילטר נושאים דינמי
 */
function example_filtered_timeline() {
    ob_start();
    ?>
    <div class="timeline-with-filters">
        <div class="filter-buttons">
            <button class="filter-btn active" data-subject="all">הכל</button>
            <button class="filter-btn" data-subject="math">מתמטיקה</button>
            <button class="filter-btn" data-subject="science">מדעים</button>
            <button class="filter-btn" data-subject="history">היסטוריה</button>
        </div>

        <div class="timeline-container">
            <?php echo do_shortcode('[annual_timeline group_id="all_subjects" academic_year="2024-2025"]'); ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('.filter-btn').on('click', function() {
            const subject = $(this).data('subject');

            // הסרת כיתה active מכל הכפתורים
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');

            // סינון הציר הזמן
            filterTimelineBySubject(subject);
        });

        function filterTimelineBySubject(subject) {
            if (subject === 'all') {
                // הצגת כל הפריטים
                $('.timeline-item').show();
            } else {
                // סינון לפי נושא
                $('.timeline-item').each(function() {
                    const itemSubject = $(this).data('subject');
                    if (itemSubject === subject) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        }
    });
    </script>

    <style>
    .timeline-with-filters {
        margin: 20px 0;
    }

    .filter-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .filter-btn {
        padding: 8px 16px;
        border: 2px solid #667eea;
        background: white;
        color: #667eea;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .filter-btn:hover {
        background: #667eea;
        color: white;
    }

    .filter-btn.active {
        background: #667eea;
        color: white;
    }
    </style>
    <?php

    return ob_get_clean();
}

/**
 * דוגמה 7: יצירת ציר זמן באופן פרוגרמטי
 */
function example_programmatic_timeline() {
    // יצירת ציר זמן חדש
    $timeline_data = array(
        'group_id' => 'programmatic_example',
        'group_name' => 'דוגמה פרוגרמטית',
        'academic_year' => '2024-2025'
    );

    $timeline_id = HPAT_Database::create_timeline(
        $timeline_data['group_id'],
        $timeline_data['group_name'],
        $timeline_data['academic_year'],
        get_current_user_id()
    );

    if (is_wp_error($timeline_id)) {
        return '<p>שגיאה ביצירת ציר הזמן: ' . $timeline_id->get_error_message() . '</p>';
    }

    // יצירת נושאים לדוגמה
    $topics = array(
        array(
            'title' => 'יסודות',
            'start_date' => '2024-09-01',
            'end_date' => '2024-10-31',
            'color' => '#3498db',
            'position' => 1
        ),
        array(
            'title' => 'העמקה',
            'start_date' => '2024-11-01',
            'end_date' => '2024-12-31',
            'color' => '#e74c3c',
            'position' => 2
        )
    );

    foreach ($topics as $topic) {
        HPAT_Database::create_timeline_topic(
            $timeline_id,
            $topic['title'],
            $topic['start_date'],
            $topic['end_date'],
            $topic['color'],
            $topic['position'],
            get_current_user_id()
        );
    }

    // הצגת הציר הזמן
    return do_shortcode('[annual_timeline group_id="programmatic_example" academic_year="2024-2025"]');
}

/**
 * דוגמה 8: ציר זמן עם אינטגרציה ל-BuddyPress
 */
function example_buddypress_integration() {
    if (!function_exists('bp_is_user')) {
        return '<p>תוסף זה דורש BuddyPress.</p>';
    }

    // קבלת מזהה המשתמש הנוכחי
    $user_id = get_current_user_id();
    $group_id = 'user_' . $user_id . '_timeline';

    // יצירת ציר זמן אישי אם לא קיים
    $existing_timeline = HPAT_Database::get_timeline_by_group($group_id, '2024-2025');

    if (!$existing_timeline) {
        HPAT_Database::create_timeline(
            $group_id,
            'הציר האישי שלי',
            '2024-2025',
            $user_id
        );
    }

    $output = '<div class="personal-timeline-header">';
    $output .= '<h3>הציר הלימודי האישי שלך</h3>';
    $output .= '<p>כאן אתה יכול לבנות את הציר הלימודי האישי שלך</p>';
    $output .= '</div>';

    $output .= do_shortcode('[annual_timeline group_id="' . $group_id . '" academic_year="2024-2025"]');

    return $output;
}

/**
 * רישום שורטקודים לדוגמה
 * הפעל את הפונקציות האלה בקובץ functions.php של ה-theme
 */
function register_example_shortcodes() {
    add_shortcode('example_basic_timeline', 'example_basic_timeline');
    add_shortcode('example_custom_timeline', 'example_custom_timeline');
    add_shortcode('example_embedded_timeline', 'example_embedded_timeline');
    add_shortcode('example_multiple_timelines', 'example_multiple_timelines');
    add_shortcode('example_custom_js_timeline', 'example_custom_js_timeline');
    add_shortcode('example_filtered_timeline', 'example_filtered_timeline');
    add_shortcode('example_programmatic_timeline', 'example_programmatic_timeline');
    add_shortcode('example_buddypress_timeline', 'example_buddypress_integration');
}

// הפעל את רישום השורטקודים
// register_example_shortcodes();
