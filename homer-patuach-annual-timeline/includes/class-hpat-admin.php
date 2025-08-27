<?php
/**
 * קלאס לניהול הממשק הניהולי של התוסף
 */

class HPAT_Admin {

    /**
     * קונסטרוקטור
     */
    public function __construct() {
        // לא נדרש פעולות נוספות בקונסטרוקטור
    }

    /**
     * טעינת סגנונות CSS לממשק הניהול
     */
    public function enqueue_styles($hook) {
        // טעינה רק בעמודי הניהול של התוסף
        if (strpos($hook, 'hpat') !== false) {
            wp_enqueue_style(
                'hpat-admin-styles',
                HPAT_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                HPAT_VERSION
            );
        }
    }

    /**
     * טעינת סקריפטים JavaScript לממשק הניהול
     */
    public function enqueue_scripts($hook) {
        // טעינה רק בעמודי הניהול של התוסף
        if (strpos($hook, 'hpat') !== false) {
            wp_enqueue_script(
                'hpat-admin-script',
                HPAT_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                HPAT_VERSION,
                true
            );

            wp_localize_script(
                'hpat-admin-script',
                'hpat_admin_ajax',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('hpat_admin_nonce'),
                    'strings' => array(
                        'loading' => __('טוען...', 'hpat'),
                        'error' => __('אירעה שגיאה', 'hpat'),
                        'success' => __('הפעולה הושלמה בהצלחה', 'hpat'),
                        'confirm_delete' => __('האם אתה בטוח שברצונך למחוק פריט זה?', 'hpat')
                    )
                )
            );
        }
    }

    /**
     * הוספת תפריט ניהול
     */
    public function add_admin_menu() {
        add_menu_page(
            __('ציר זמן שנתי', 'hpat'),
            __('ציר זמן שנתי', 'hpat'),
            'manage_options',
            'hpat-dashboard',
            array($this, 'display_dashboard_page'),
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            'hpat-dashboard',
            __('לוח בקרה', 'hpat'),
            __('לוח בקרה', 'hpat'),
            'manage_options',
            'hpat-dashboard',
            array($this, 'display_dashboard_page')
        );

        add_submenu_page(
            'hpat-dashboard',
            __('ניהול צירי זמן', 'hpat'),
            __('צירי זמן', 'hpat'),
            'manage_options',
            'hpat-timelines',
            array($this, 'display_timelines_page')
        );

        add_submenu_page(
            'hpat-dashboard',
            __('הגדרות', 'hpat'),
            __('הגדרות', 'hpat'),
            'manage_options',
            'hpat-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * הצגת עמוד לוח הבקרה
     */
    public function display_dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="hpat-dashboard-grid">
                <!-- סטטיסטיקות כלליות -->
                <div class="hpat-dashboard-card">
                    <h3><?php _e('סטטיסטיקות כלליות', 'hpat'); ?></h3>
                    <?php $this->display_general_stats(); ?>
                </div>

                <!-- צירי זמן אחרונים -->
                <div class="hpat-dashboard-card">
                    <h3><?php _e('צירי זמן אחרונים', 'hpat'); ?></h3>
                    <?php $this->display_recent_timelines(); ?>
                </div>

                <!-- פעילות אחרונה -->
                <div class="hpat-dashboard-card">
                    <h3><?php _e('פעילות אחרונה', 'hpat'); ?></h3>
                    <?php $this->display_recent_activity(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * הצגת עמוד ניהול צירי זמן
     */
    public function display_timelines_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="hpat-admin-actions">
                <button id="hpat-create-timeline" class="button button-primary">
                    <?php _e('צור ציר זמן חדש', 'hpat'); ?>
                </button>
            </div>

            <div id="hpat-timelines-list">
                <?php $this->display_timelines_list(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * הצגת עמוד ההגדרות
     */
    public function display_settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('hpat_settings_nonce', 'hpat_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('גובה ברירת המחדל לציר הזמן', 'hpat'); ?></th>
                        <td>
                            <input type="number" name="hpat_timeline_height" value="<?php echo esc_attr(get_option('hpat_timeline_height', 400)); ?>" min="200" max="800">
                            <p class="description"><?php _e('גובה הציר הזמן בפיקסלים', 'hpat'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('רמת הגדלה ברירת מחדל', 'hpat'); ?></th>
                        <td>
                            <input type="number" name="hpat_default_zoom_level" value="<?php echo esc_attr(get_option('hpat_default_zoom_level', 30)); ?>" min="1" max="365">
                            <p class="description"><?php _e('מספר הימים שיוצגו ברירת מחדל', 'hpat'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('אפשר גרירה והנחה', 'hpat'); ?></th>
                        <td>
                            <input type="checkbox" name="hpat_enable_drag_drop" value="1" <?php checked(get_option('hpat_enable_drag_drop', 1)); ?>>
                            <p class="description"><?php _e('אפשר למשתמשים לגרור פריטים לציר הזמן', 'hpat'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('מספר תוצאות חיפוש מקסימלי', 'hpat'); ?></th>
                        <td>
                            <input type="number" name="hpat_search_results_limit" value="<?php echo esc_attr(get_option('hpat_search_results_limit', 20)); ?>" min="5" max="100">
                            <p class="description"><?php _e('מספר הפריטים המקסימלי שיחזור בחיפוש', 'hpat'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * הצגת סטטיסטיקות כלליות
     */
    private function display_general_stats() {
        global $wpdb;

        $timelines_table = $wpdb->prefix . 'hpat_timelines';
        $topics_table = $wpdb->prefix . 'hpat_timeline_topics';
        $items_table = $wpdb->prefix . 'hpat_timeline_items';

        $total_timelines = $wpdb->get_var("SELECT COUNT(*) FROM $timelines_table");
        $total_topics = $wpdb->get_var("SELECT COUNT(*) FROM $topics_table");
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $items_table");

        ?>
        <div class="hpat-stats-grid">
            <div class="hpat-stat-item">
                <span class="hpat-stat-number"><?php echo number_format_i18n($total_timelines); ?></span>
                <span class="hpat-stat-label"><?php _e('צירי זמן', 'hpat'); ?></span>
            </div>
            <div class="hpat-stat-item">
                <span class="hpat-stat-number"><?php echo number_format_i18n($total_topics); ?></span>
                <span class="hpat-stat-label"><?php _e('נושאים', 'hpat'); ?></span>
            </div>
            <div class="hpat-stat-item">
                <span class="hpat-stat-number"><?php echo number_format_i18n($total_items); ?></span>
                <span class="hpat-stat-label"><?php _e('פריטים', 'hpat'); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * הצגת צירי זמן אחרונים
     */
    private function display_recent_timelines() {
        $timelines = HPAT_Database::get_all_timelines();
        $recent_timelines = array_slice($timelines, 0, 5);

        if (empty($recent_timelines)) {
            echo '<p>' . __('אין צירי זמן', 'hpat') . '</p>';
            return;
        }

        echo '<ul class="hpat-recent-list">';
        foreach ($recent_timelines as $timeline) {
            echo '<li>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=hpat-timelines&timeline_id=' . $timeline['id'])) . '">';
            echo esc_html($timeline['group_name']) . ' - ' . esc_html($timeline['academic_year']);
            echo '</a>';
            echo '</li>';
        }
        echo '</ul>';
    }

    /**
     * הצגת פעילות אחרונה
     */
    private function display_recent_activity() {
        global $wpdb;
        $items_table = $wpdb->prefix . 'hpat_timeline_items';

        $recent_items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT i.*, t.group_name, p.post_title
                 FROM $items_table i
                 LEFT JOIN {$wpdb->prefix}hpat_timelines t ON i.timeline_id = t.id
                 LEFT JOIN {$wpdb->posts} p ON i.post_id = p.ID
                 ORDER BY i.created_at DESC
                 LIMIT %d",
                10
            ),
            ARRAY_A
        );

        if (empty($recent_items)) {
            echo '<p>' . __('אין פעילות', 'hpat') . '</p>';
            return;
        }

        echo '<ul class="hpat-activity-list">';
        foreach ($recent_items as $item) {
            echo '<li>';
            echo '<span class="hpat-activity-time">' . date_i18n('j/n H:i', strtotime($item['created_at'])) . '</span>';
            echo '<span class="hpat-activity-text">';
            printf(
                __('נוסף פריט "%s" לציר "%s"', 'hpat'),
                esc_html($item['post_title']),
                esc_html($item['group_name'])
            );
            echo '</span>';
            echo '</li>';
        }
        echo '</ul>';
    }

    /**
     * הצגת רשימת צירי זמן
     */
    private function display_timelines_list() {
        $timelines = HPAT_Database::get_all_timelines();

        if (empty($timelines)) {
            echo '<p>' . __('אין צירי זמן. צור ציר זמן ראשון!', 'hpat') . '</p>';
            return;
        }

        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('שם הקבוצה', 'hpat'); ?></th>
                    <th><?php _e('שנת לימוד', 'hpat'); ?></th>
                    <th><?php _e('נושאים', 'hpat'); ?></th>
                    <th><?php _e('פריטים', 'hpat'); ?></th>
                    <th><?php _e('פעולות', 'hpat'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($timelines as $timeline): ?>
                    <tr>
                        <td><?php echo esc_html($timeline['group_name']); ?></td>
                        <td><?php echo esc_html($timeline['academic_year']); ?></td>
                        <td><?php echo $this->get_topics_count($timeline['id']); ?></td>
                        <td><?php echo $this->get_items_count($timeline['id']); ?></td>
                        <td>
                            <a href="#" class="button button-small hpat-edit-timeline" data-timeline-id="<?php echo $timeline['id']; ?>">
                                <?php _e('ערוך', 'hpat'); ?>
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=hpat-timelines&timeline_id=' . $timeline['id'] . '&action=view')); ?>" class="button button-small">
                                <?php _e('צפה', 'hpat'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * קבלת מספר נושאים בציר זמן
     */
    private function get_topics_count($timeline_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'hpat_timeline_topics';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE timeline_id = %d", $timeline_id));
    }

    /**
     * קבלת מספר פריטים בציר זמן
     */
    private function get_items_count($timeline_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'hpat_timeline_items';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE timeline_id = %d", $timeline_id));
    }

    /**
     * שמירת הגדרות
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['hpat_settings_nonce'], 'hpat_settings_nonce')) {
            return;
        }

        $settings = array(
            'hpat_timeline_height',
            'hpat_default_zoom_level',
            'hpat_enable_drag_drop',
            'hpat_search_results_limit'
        );

        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                if ($setting === 'hpat_enable_drag_drop') {
                    update_option($setting, 1);
                } else {
                    update_option($setting, intval($_POST[$setting]));
                }
            } else {
                if ($setting === 'hpat_enable_drag_drop') {
                    update_option($setting, 0);
                }
            }
        }

        echo '<div class="notice notice-success is-dismissible"><p>' . __('ההגדרות נשמרו!', 'hpat') . '</p></div>';
    }
}
