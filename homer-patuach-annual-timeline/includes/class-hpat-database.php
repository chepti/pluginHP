<?php
/**
 * קלאס לניהול מסד הנתונים של התוסף
 */

class HPAT_Database {

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
        global $wpdb;
        $table = $wpdb->prefix . 'hpat_timelines';

        $result = $wpdb->insert(
            $table,
            array(
                'group_id' => $group_id,
                'group_name' => $group_name,
                'academic_year' => $academic_year,
                'created_by' => $user_id
            ),
            array('%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', 'שגיאה ביצירת ציר הזמן');
        }

        return $wpdb->insert_id;
    }

    /**
     * קבלת ציר זמן לפי מזהה
     */
    public static function get_timeline($timeline_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'hpat_timelines';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $timeline_id
            ),
            ARRAY_A
        );
    }

    /**
     * קבלת ציר זמן לפי קבוצת לימוד ושנת לימוד
     */
    public static function get_timeline_by_group($group_id, $academic_year) {
        global $wpdb;
        $table = $wpdb->prefix . 'hpat_timelines';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE group_id = %s AND academic_year = %s",
                $group_id,
                $academic_year
            ),
            ARRAY_A
        );
    }

    /**
     * קבלת כל הנושאים של ציר זמן מסוים
     */
    public static function get_timeline_topics($timeline_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'hpat_timeline_topics';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE timeline_id = %d ORDER BY position ASC, start_date ASC",
                $timeline_id
            ),
            ARRAY_A
        );
    }

    /**
     * יצירת נושא חדש לציר זמן
     */
    public static function create_timeline_topic($timeline_id, $title, $start_date, $end_date, $color, $position, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'hpat_timeline_topics';

        $result = $wpdb->insert(
            $table,
            array(
                'timeline_id' => $timeline_id,
                'title' => $title,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'color' => $color,
                'position' => $position,
                'created_by' => $user_id
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', 'שגיאה ביצירת הנושא');
        }

        return $wpdb->insert_id;
    }

    /**
     * עדכון נושא בציר זמן
     */
    public static function update_timeline_topic($topic_id, $data, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'hpat_timeline_topics';

        $update_data = array();
        $update_format = array();

        if (isset($data['title'])) {
            $update_data['title'] = $data['title'];
            $update_format[] = '%s';
        }

        if (isset($data['start_date'])) {
            $update_data['start_date'] = $data['start_date'];
            $update_format[] = '%s';
        }

        if (isset($data['end_date'])) {
            $update_data['end_date'] = $data['end_date'];
            $update_format[] = '%s';
        }

        if (isset($data['color'])) {
            $update_data['color'] = $data['color'];
            $update_format[] = '%s';
        }

        if (isset($data['position'])) {
            $update_data['position'] = $data['position'];
            $update_format[] = '%d';
        }

        if (empty($update_data)) {
            return new WP_Error('no_data', 'לא סופקו נתונים לעדכון');
        }

        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $topic_id),
            $update_format,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', 'שגיאה בעדכון הנושא');
        }

        return $result;
    }

    /**
     * מחיקת נושא מציר זמן
     */
    public static function delete_timeline_topic($topic_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'hpat_timeline_topics';

        $result = $wpdb->delete(
            $table,
            array('id' => $topic_id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', 'שגיאה במחיקת הנושא');
        }

        return $result;
    }

    /**
     * קבלת פריטים בציר זמן
     */
    public static function get_timeline_items($timeline_id) {
        global $wpdb;
        $items_table = $wpdb->prefix . 'hpat_timeline_items';
        $topics_table = $wpdb->prefix . 'hpat_timeline_topics';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT i.*, t.title as topic_title, t.color as topic_color,
                        p.post_title, p.post_excerpt, p.guid as post_url
                 FROM $items_table i
                 LEFT JOIN $topics_table t ON i.topic_id = t.id
                 LEFT JOIN {$wpdb->posts} p ON i.post_id = p.ID
                 WHERE i.timeline_id = %d
                 ORDER BY i.item_date ASC, i.lane ASC",
                $timeline_id
            ),
            ARRAY_A
        );
    }

    /**
     * יצירת פריט חדש בציר זמן
     */
    public static function create_timeline_item($timeline_id, $topic_id, $post_id, $item_date, $lane, $shape, $color, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'hpat_timeline_items';

        $result = $wpdb->insert(
            $table,
            array(
                'timeline_id' => $timeline_id,
                'topic_id' => $topic_id,
                'post_id' => $post_id,
                'item_date' => $item_date,
                'lane' => $lane,
                'item_shape' => $shape,
                'item_color' => $color,
                'added_by' => $user_id
            ),
            array('%d', '%d', '%d', '%s', '%d', '%s', '%s', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', 'שגיאה ביצירת הפריט');
        }

        return $wpdb->insert_id;
    }

    /**
     * עדכון פריט בציר זמן
     */
    public static function update_timeline_item($item_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'hpat_timeline_items';

        $update_data = array();
        $update_format = array();

        if (isset($data['item_date'])) {
            $update_data['item_date'] = $data['item_date'];
            $update_format[] = '%s';
        }

        if (isset($data['lane'])) {
            $update_data['lane'] = $data['lane'];
            $update_format[] = '%d';
        }

        if (isset($data['item_shape'])) {
            $update_data['item_shape'] = $data['item_shape'];
            $update_format[] = '%s';
        }

        if (isset($data['item_color'])) {
            $update_data['item_color'] = $data['item_color'];
            $update_format[] = '%s';
        }

        if (empty($update_data)) {
            return new WP_Error('no_data', 'לא סופקו נתונים לעדכון');
        }

        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $item_id),
            $update_format,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', 'שגיאה בעדכון הפריט');
        }

        return $result;
    }

    /**
     * מחיקת פריט מציר זמן
     */
    public static function delete_timeline_item($item_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'hpat_timeline_items';

        $result = $wpdb->delete(
            $table,
            array('id' => $item_id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', 'שגיאה במחיקת הפריט');
        }

        return $result;
    }

    /**
     * חיפוש פוסטים להוספה לציר זמן
     */
    public static function search_posts($search_term, $limit = 20) {
        global $wpdb;

        $search_term = '%' . $wpdb->esc_like($search_term) . '%';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title, post_excerpt, guid
                 FROM {$wpdb->posts}
                 WHERE post_type = 'post'
                 AND post_status = 'publish'
                 AND (post_title LIKE %s OR post_content LIKE %s)
                 ORDER BY post_date DESC
                 LIMIT %d",
                $search_term,
                $search_term,
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * קבלת כל הצירים הזמנים
     */
    public static function get_all_timelines() {
        global $wpdb;
        $table = $wpdb->prefix . 'hpat_timelines';

        return $wpdb->get_results(
            "SELECT * FROM $table ORDER BY academic_year DESC, group_name ASC",
            ARRAY_A
        );
    }

    /**
     * קבלת ציר זמן עם נושאים ופריטים
     */
    public static function get_full_timeline_data($timeline_id) {
        $timeline = self::get_timeline($timeline_id);
        if (!$timeline) {
            return false;
        }

        $timeline['topics'] = self::get_timeline_topics($timeline_id);
        $timeline['items'] = self::get_timeline_items($timeline_id);

        return $timeline;
    }
}
