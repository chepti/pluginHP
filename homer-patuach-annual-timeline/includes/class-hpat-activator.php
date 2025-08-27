<?php
/**
 * קלאס לטיפול בהפעלה ראשונה של התוסף
 */

class HPAT_Activator {

    /**
     * הפעלה ראשונה של התוסף
     */
    public static function activate() {
        self::create_database_tables();
        self::create_default_data();
        self::set_default_options();
    }

    /**
     * יצירת טבלאות מסד הנתונים
     */
    private static function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // טבלת צירי זמן שנתיים
        $table_timelines = $wpdb->prefix . 'hpat_timelines';
        $sql_timelines = "CREATE TABLE $table_timelines (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            group_id varchar(100) NOT NULL COMMENT 'מזהה קבוצת הלימוד (subject_class)',
            group_name varchar(255) NOT NULL COMMENT 'שם קבוצת הלימוד',
            academic_year varchar(20) NOT NULL COMMENT 'שנת הלימוד (2024-2025)',
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_group_year (group_id, academic_year)
        ) $charset_collate;";

        // טבלת נושאי לימוד בציר הזמן
        $table_topics = $wpdb->prefix . 'hpat_timeline_topics';
        $sql_topics = "CREATE TABLE $table_topics (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            timeline_id mediumint(9) NOT NULL,
            title varchar(255) NOT NULL COMMENT 'כותרת הנושא',
            start_date date NOT NULL COMMENT 'תאריך התחלה',
            end_date date NOT NULL COMMENT 'תאריך סיום',
            color varchar(7) NOT NULL DEFAULT '#3498db' COMMENT 'צבע הנושא (hex)',
            position smallint(5) NOT NULL DEFAULT 0 COMMENT 'סדר הצגה',
            created_by bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY timeline_id (timeline_id),
            FOREIGN KEY (timeline_id) REFERENCES $table_timelines(id) ON DELETE CASCADE
        ) $charset_collate;";

        // טבלת פריטים בציר הזמן
        $table_items = $wpdb->prefix . 'hpat_timeline_items';
        $sql_items = "CREATE TABLE $table_items (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timeline_id mediumint(9) NOT NULL,
            topic_id mediumint(9) NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            item_date date NOT NULL COMMENT 'תאריך הפריט בציר הזמן',
            lane smallint(5) NOT NULL DEFAULT 0 COMMENT 'רצועה בציר (0-מערכים, 1-מצגות, 2-פעילויות, 3-הערכה)',
            item_shape varchar(20) NOT NULL DEFAULT 'square' COMMENT 'צורת הפריט (square, circle, triangle, star)',
            item_color varchar(7) DEFAULT '' COMMENT 'צבע הפריט (hex) - אם ריק, ישתמש בצבע הנושא',
            added_by bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY timeline_id (timeline_id),
            KEY topic_id (topic_id),
            KEY post_id (post_id),
            FOREIGN KEY (timeline_id) REFERENCES $table_timelines(id) ON DELETE CASCADE,
            FOREIGN KEY (topic_id) REFERENCES $table_topics(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_timelines);
        dbDelta($sql_topics);
        dbDelta($sql_items);
    }

    /**
     * יצירת נתונים ברירת מחדל
     */
    private static function create_default_data() {
        // יצירת ציר זמן לדוגמה אם לא קיים
        global $wpdb;
        $timelines_table = $wpdb->prefix . 'hpat_timelines';

        // בדיקה אם יש כבר נתונים
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $timelines_table");
        if ($count == 0) {
            // יצירת ציר זמן לדוגמה
            $current_year = date('Y');
            $academic_year = $current_year . '-' . ($current_year + 1);

            $wpdb->insert(
                $timelines_table,
                array(
                    'group_id' => 'math_7th_grade',
                    'group_name' => 'מתמטיקה - כיתה ז\'',
                    'academic_year' => $academic_year,
                    'created_by' => 1 // מנהל ראשי
                )
            );

            $timeline_id = $wpdb->insert_id;

            // יצירת נושאים לדוגמה
            $topics_table = $wpdb->prefix . 'hpat_timeline_topics';
            $sample_topics = array(
                array(
                    'title' => 'מספרים ושברים',
                    'start_date' => date('Y-09-01'),
                    'end_date' => date('Y-10-31'),
                    'color' => '#3498db',
                    'position' => 1
                ),
                array(
                    'title' => 'גיאומטריה',
                    'start_date' => date('Y-11-01'),
                    'end_date' => date('Y-12-31'),
                    'color' => '#e74c3c',
                    'position' => 2
                ),
                array(
                    'title' => 'סטטיסטיקה והסתברות',
                    'start_date' => date('Y-01-01'),
                    'end_date' => date('Y-03-31'),
                    'color' => '#2ecc71',
                    'position' => 3
                ),
                array(
                    'title' => 'חזרה ותרגול',
                    'start_date' => date('Y-04-01'),
                    'end_date' => date('Y-06-30'),
                    'color' => '#f39c12',
                    'position' => 4
                )
            );

            foreach ($sample_topics as $topic) {
                $wpdb->insert(
                    $topics_table,
                    array_merge($topic, array(
                        'timeline_id' => $timeline_id,
                        'created_by' => 1
                    ))
                );
            }
        }
    }

    /**
     * הגדרת אפשרויות ברירת מחדל
     */
    private static function set_default_options() {
        add_option('hpat_version', HPAT_VERSION);
        add_option('hpat_default_zoom_level', 30); // ימים
        add_option('hpat_max_zoom_level', 1); // יום
        add_option('hpat_min_zoom_level', 365); // שנה
        add_option('hpat_enable_drag_drop', 1);
        add_option('hpat_timeline_height', 400); // פיקסלים
        add_option('hpat_search_results_limit', 20);
    }
}
