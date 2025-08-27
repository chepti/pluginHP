<?php
/**
 * קלאס לטיפול בבקשות AJAX של התוסף
 */

class HPAT_Ajax {

    /**
     * קונסטרוקטור
     */
    public function __construct() {
        // לא נדרש פעולות נוספות בקונסטרוקטור
    }

    /**
     * קבלת נתוני ציר זמן מלאים
     */
    public function get_timeline_data() {
        // בדיקת הרשאות
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hpat_timeline_nonce')) {
            wp_send_json_error(array('message' => 'אין הרשאה לבצע פעולה זו'));
            return;
        }

        $timeline_id = isset($_POST['timeline_id']) ? intval($_POST['timeline_id']) : 0;

        if (!$timeline_id) {
            wp_send_json_error(array('message' => 'מזהה ציר זמן לא תקין'));
            return;
        }

        $timeline_data = HPAT_Database::get_full_timeline_data($timeline_id);

        if (!$timeline_data) {
            wp_send_json_error(array('message' => 'לא נמצאו נתונים לציר הזמן'));
            return;
        }

        wp_send_json_success($timeline_data);
    }

    /**
     * שמירת פריט חדש בציר זמן
     */
    public function save_timeline_item() {
        // בדיקת הרשאות
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'יש להתחבר כדי לבצע פעולה זו'));
            return;
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hpat_timeline_nonce')) {
            wp_send_json_error(array('message' => 'אין הרשאה לבצע פעולה זו'));
            return;
        }

        $timeline_id = isset($_POST['timeline_id']) ? intval($_POST['timeline_id']) : 0;
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
        $item_date = isset($_POST['item_date']) ? sanitize_text_field($_POST['item_date']) : '';
        $lane = isset($_POST['lane']) ? intval($_POST['lane']) : 0;
        $shape = isset($_POST['shape']) ? sanitize_text_field($_POST['shape']) : 'square';
        $color = isset($_POST['color']) ? sanitize_text_field($_POST['color']) : '';

        // ולידציה
        if (!$timeline_id || !$post_id || !$topic_id || !$item_date) {
            wp_send_json_error(array('message' => 'נתונים חסרים'));
            return;
        }

        // בדיקה שהציר הזמן קיים
        $timeline = HPAT_Database::get_timeline($timeline_id);
        if (!$timeline) {
            wp_send_json_error(array('message' => 'ציר זמן לא קיים'));
            return;
        }

        // בדיקה שהנושא קיים ושייך לציר הזמן
        $topics = HPAT_Database::get_timeline_topics($timeline_id);
        $topic_exists = false;
        foreach ($topics as $topic) {
            if ($topic['id'] == $topic_id) {
                $topic_exists = true;
                break;
            }
        }

        if (!$topic_exists) {
            wp_send_json_error(array('message' => 'נושא לא קיים או לא שייך לציר הזמן'));
            return;
        }

        // בדיקה שהפוסט קיים
        if (!get_post($post_id)) {
            wp_send_json_error(array('message' => 'פוסט לא קיים'));
            return;
        }

        // ולידציית צורה
        $allowed_shapes = array('square', 'circle', 'triangle', 'star');
        if (!in_array($shape, $allowed_shapes)) {
            $shape = 'square';
        }

        // ולידציית צבע (hex color)
        if (!empty($color) && !preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            $color = '';
        }

        // יצירת הפריט
        $result = HPAT_Database::create_timeline_item(
            $timeline_id,
            $topic_id,
            $post_id,
            $item_date,
            $lane,
            $shape,
            $color,
            get_current_user_id()
        );

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }

        wp_send_json_success(array(
            'message' => 'הפריט נוסף בהצלחה לציר הזמן',
            'item_id' => $result
        ));
    }

    /**
     * עדכון נושא בציר זמן
     */
    public function update_timeline_topic() {
        // בדיקת הרשאות - רק עורכים ומעלה יכולים לערוך נושאים
        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error(array('message' => 'אין הרשאה לבצע פעולה זו'));
            return;
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hpat_timeline_nonce')) {
            wp_send_json_error(array('message' => 'אין הרשאה לבצע פעולה זו'));
            return;
        }

        $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
        $data = array();

        if (isset($_POST['title'])) {
            $data['title'] = sanitize_text_field($_POST['title']);
        }

        if (isset($_POST['start_date'])) {
            $data['start_date'] = sanitize_text_field($_POST['start_date']);
        }

        if (isset($_POST['end_date'])) {
            $data['end_date'] = sanitize_text_field($_POST['end_date']);
        }

        if (isset($_POST['color'])) {
            $color = sanitize_text_field($_POST['color']);
            if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
                $data['color'] = $color;
            }
        }

        if (isset($_POST['position'])) {
            $data['position'] = intval($_POST['position']);
        }

        if (!$topic_id || empty($data)) {
            wp_send_json_error(array('message' => 'נתונים חסרים'));
            return;
        }

        $result = HPAT_Database::update_timeline_topic($topic_id, $data, get_current_user_id());

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }

        wp_send_json_success(array('message' => 'הנושא עודכן בהצלחה'));
    }

    /**
     * חיפוש פוסטים
     */
    public function search_posts() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hpat_timeline_nonce')) {
            wp_send_json_error(array('message' => 'אין הרשאה לבצע פעולה זו'));
            return;
        }

        $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : get_option('hpat_search_results_limit', 20);

        if (empty($search_term)) {
            wp_send_json_success(array()); // החזר מערך ריק אם אין מונח חיפוש
            return;
        }

        $posts = HPAT_Database::search_posts($search_term, $limit);

        // הוספת מידע נוסף לכל פוסט
        foreach ($posts as &$post) {
            $post['thumbnail_url'] = get_the_post_thumbnail_url($post['ID'], 'thumbnail');
            $post['permalink'] = get_permalink($post['ID']);

            // קבלת טקסונומיות
            $post['subjects'] = wp_get_post_terms($post['ID'], 'subject', array('fields' => 'names'));
            $post['classes'] = wp_get_post_terms($post['ID'], 'class', array('fields' => 'names'));
            $post['categories'] = wp_get_post_terms($post['ID'], 'category', array('fields' => 'names'));
        }

        wp_send_json_success($posts);
    }

    /**
     * קבלת נושאי ציר זמן
     */
    public function get_timeline_topics() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hpat_timeline_nonce')) {
            wp_send_json_error(array('message' => 'אין הרשאה לבצע פעולה זו'));
            return;
        }

        $timeline_id = isset($_POST['timeline_id']) ? intval($_POST['timeline_id']) : 0;

        if (!$timeline_id) {
            wp_send_json_error(array('message' => 'מזהה ציר זמן לא תקין'));
            return;
        }

        $topics = HPAT_Database::get_timeline_topics($timeline_id);

        if (empty($topics)) {
            wp_send_json_success(array());
            return;
        }

        wp_send_json_success($topics);
    }
}
