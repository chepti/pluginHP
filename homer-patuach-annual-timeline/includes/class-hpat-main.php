<?php
/**
 * המחלקה הראשית של התוסף
 * מנהלת את כל הפונקציונליות
 */

class HPAT_Main {

    /**
     * מופע יחיד של המחלקה (Singleton)
     */
    private static $instance = null;

    /**
     * קונסטרוקטור
     */
    private function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_ajax_hooks();
    }

    /**
     * יצירת מופע יחיד
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * טעינת קבצים נדרשים
     */
    private function load_dependencies() {
        require_once HPAT_PLUGIN_DIR . 'includes/class-hpat-activator.php';
        require_once HPAT_PLUGIN_DIR . 'includes/class-hpat-deactivator.php';
        require_once HPAT_PLUGIN_DIR . 'includes/class-hpat-admin.php';
        require_once HPAT_PLUGIN_DIR . 'includes/class-hpat-frontend.php';
        require_once HPAT_PLUGIN_DIR . 'includes/class-hpat-timeline.php';
        require_once HPAT_PLUGIN_DIR . 'includes/class-hpat-database.php';
        require_once HPAT_PLUGIN_DIR . 'includes/class-hpat-ajax.php';
    }

    /**
     * הגדרת hooks לממשק הניהול
     */
    private function define_admin_hooks() {
        $admin = new HPAT_Admin();

        add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));
        add_action('admin_menu', array($admin, 'add_admin_menu'));
    }

    /**
     * הגדרת hooks לממשק הציבורי
     */
    private function define_public_hooks() {
        $frontend = new HPAT_Frontend();

        add_action('wp_enqueue_scripts', array($frontend, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($frontend, 'enqueue_scripts'));
        add_shortcode('annual_timeline', array($frontend, 'render_timeline_shortcode'));
    }

    /**
     * הגדרת hooks ל-AJAX
     */
    private function define_ajax_hooks() {
        $ajax = new HPAT_Ajax();

        // AJAX handlers למשתמשים מחוברים
        add_action('wp_ajax_hpat_get_timeline_data', array($ajax, 'get_timeline_data'));
        add_action('wp_ajax_hpat_save_timeline_item', array($ajax, 'save_timeline_item'));
        add_action('wp_ajax_hpat_update_timeline_topic', array($ajax, 'update_timeline_topic'));
        add_action('wp_ajax_hpat_search_posts', array($ajax, 'search_posts'));
        add_action('wp_ajax_hpat_get_timeline_topics', array($ajax, 'get_timeline_topics'));

        // AJAX handlers למשתמשים לא מחוברים (חיפוש פוסטים)
        add_action('wp_ajax_nopriv_hpat_search_posts', array($ajax, 'search_posts'));
        add_action('wp_ajax_nopriv_hpat_get_timeline_topics', array($ajax, 'get_timeline_topics'));
    }

    /**
     * הפעלת התוסף
     */
    public function run() {
        // יצירת מופעים של המחלקות
        new HPAT_Timeline();
        new HPAT_Database();
    }
}
