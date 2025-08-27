<?php
/**
 * קלאס לניהול לוגיקת הציר הזמן
 */

class HPAT_Timeline {

    /**
     * קונסטרוקטור
     */
    public function __construct() {
        // לא נדרש פעולות נוספות בקונסטרוקטור
    }

    /**
     * יצירת ציר זמן חדש
     */
    public static function create_timeline($group_id, $group_name, $academic_year, $user_id) {
        return HPAT_Database::create_timeline($group_id, $group_name, $academic_year, $user_id);
    }

    /**
     * הוספת פריט לציר זמן
     */
    public static function add_item_to_timeline($timeline_id, $post_id, $topic_id, $item_date, $lane = 0, $shape = 'square', $color = '', $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return HPAT_Database::create_timeline_item(
            $timeline_id,
            $topic_id,
            $post_id,
            $item_date,
            $lane,
            $shape,
            $color,
            $user_id
        );
    }

    /**
     * הסרת פריט מציר זמן
     */
    public static function remove_item_from_timeline($item_id) {
        return HPAT_Database::delete_timeline_item($item_id);
    }

    /**
     * עדכון פריט בציר זמן
     */
    public static function update_timeline_item($item_id, $data) {
        return HPAT_Database::update_timeline_item($item_id, $data);
    }

    /**
     * יצירת נושא חדש לציר זמן
     */
    public static function create_topic($timeline_id, $title, $start_date, $end_date, $color = '#3498db', $position = 0, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return HPAT_Database::create_timeline_topic(
            $timeline_id,
            $title,
            $start_date,
            $end_date,
            $color,
            $position,
            $user_id
        );
    }

    /**
     * עדכון נושא בציר זמן
     */
    public static function update_topic($topic_id, $data, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return HPAT_Database::update_timeline_topic($topic_id, $data, $user_id);
    }

    /**
     * מחיקת נושא מציר זמן
     */
    public static function delete_topic($topic_id) {
        return HPAT_Database::delete_timeline_topic($topic_id);
    }

    /**
     * קבלת נתוני ציר זמן בפורמט המתאים ל-Vis.js
     */
    public static function get_timeline_visualization_data($timeline_id) {
        $timeline_data = HPAT_Database::get_full_timeline_data($timeline_id);

        if (!$timeline_data) {
            return false;
        }

        $visualization_data = array(
            'timeline' => $timeline_data,
            'groups' => array(),
            'items' => array()
        );

        // יצירת קבוצות (רצועות) לציר הזמן
        $lanes = array(
            0 => array('id' => 0, 'content' => 'מערכי שיעור', 'style' => 'background-color: #f8f9fa;'),
            1 => array('id' => 1, 'content' => 'מצגות', 'style' => 'background-color: #e9ecef;'),
            2 => array('id' => 2, 'content' => 'פעילויות אינטראקטיביות', 'style' => 'background-color: #dee2e6;'),
            3 => array('id' => 3, 'content' => 'הערכה', 'style' => 'background-color: #ced4da;')
        );

        foreach ($lanes as $lane) {
            $visualization_data['groups'][] = $lane;
        }

        // יצירת פריטים לציר הזמן
        foreach ($timeline_data['items'] as $item) {
            $topic = $this->find_topic_by_id($timeline_data['topics'], $item['topic_id']);

            $vis_item = array(
                'id' => $item['id'],
                'group' => $item['lane'],
                'content' => $this->get_item_content($item, $topic),
                'start' => $item['item_date'],
                'title' => $this->get_item_tooltip($item, $topic),
                'style' => $this->get_item_style($item, $topic),
                'className' => 'timeline-item-shape-' . $item['item_shape']
            );

            $visualization_data['items'][] = $vis_item;
        }

        return $visualization_data;
    }

    /**
     * חיפוש נושא לפי מזהה
     */
    private static function find_topic_by_id($topics, $topic_id) {
        foreach ($topics as $topic) {
            if ($topic['id'] == $topic_id) {
                return $topic;
            }
        }
        return false;
    }

    /**
     * קבלת תוכן הפריט להצגה
     */
    private static function get_item_content($item, $topic) {
        $shape_symbol = '';
        switch ($item['item_shape']) {
            case 'square':
                $shape_symbol = '■';
                break;
            case 'circle':
                $shape_symbol = '●';
                break;
            case 'triangle':
                $shape_symbol = '▲';
                break;
            case 'star':
                $shape_symbol = '★';
                break;
        }

        return '<div class="item-content">' .
               '<span class="item-shape">' . $shape_symbol . '</span>' .
               '<span class="item-title">' . esc_html($item['post_title']) . '</span>' .
               '</div>';
    }

    /**
     * קבלת tooltip לפריט
     */
    private static function get_item_tooltip($item, $topic) {
        $tooltip = '<strong>' . esc_html($item['post_title']) . '</strong><br>';
        $tooltip .= 'נושא: ' . esc_html($topic['title']) . '<br>';
        $tooltip .= 'תאריך: ' . date_i18n('j בM Y', strtotime($item['item_date'])) . '<br>';

        if (!empty($item['post_excerpt'])) {
            $tooltip .= '<br>' . wp_trim_words($item['post_excerpt'], 10);
        }

        return $tooltip;
    }

    /**
     * קבלת סגנון לפריט
     */
    private static function get_item_style($item, $topic) {
        $color = !empty($item['item_color']) ? $item['item_color'] : $topic['color'];
        return 'background-color: ' . $color . '; border-color: ' . $color . ';';
    }

    /**
     * ולידציה של תאריך פריט
     */
    public static function validate_item_date($item_date, $topic_id) {
        $topic = HPAT_Database::get_timeline_topics(0); // נצטרך לשנות את זה

        // כאן נוסיף לוגיקה לבדיקת שהתאריך נופל בתוך טווח הנושא
        return true; // זמנית
    }

    /**
     * חישוב מיקום רצועה אופטימלי לפריט חדש
     */
    public static function calculate_optimal_lane($timeline_id, $item_date) {
        global $wpdb;
        $table = $wpdb->prefix . 'hpat_timeline_items';

        // קבלת מספר הפריטים בכל רצועה בתאריך מסוים
        $lane_counts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT lane, COUNT(*) as count
                 FROM $table
                 WHERE timeline_id = %d AND item_date = %s
                 GROUP BY lane",
                $timeline_id,
                $item_date
            ),
            ARRAY_A
        );

        // מציאת הרצועה עם הכי פחות פריטים
        $min_count = PHP_INT_MAX;
        $optimal_lane = 0;

        for ($i = 0; $i < 4; $i++) { // 4 רצועות
            $count = 0;
            foreach ($lane_counts as $lane_count) {
                if ($lane_count['lane'] == $i) {
                    $count = $lane_count['count'];
                    break;
                }
            }

            if ($count < $min_count) {
                $min_count = $count;
                $optimal_lane = $i;
            }
        }

        return $optimal_lane;
    }

    /**
     * קבלת נתוני ציר זמן לקריאה בלבד
     */
    public static function get_timeline_readonly_data($timeline_id) {
        return self::get_timeline_visualization_data($timeline_id);
    }

    /**
     * קבלת נתוני ציר זמן לעריכה
     */
    public static function get_timeline_editable_data($timeline_id) {
        $data = self::get_timeline_visualization_data($timeline_id);

        if ($data) {
            $data['can_edit'] = current_user_can('edit_others_posts');
            $data['current_user_id'] = get_current_user_id();
        }

        return $data;
    }
}
