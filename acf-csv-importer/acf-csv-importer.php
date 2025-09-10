<?php
/**
 * Plugin Name: ACF CSV Importer
 * Plugin URI:  https://github.com/chepti
 * Description: תוסף לייבוא נתונים מקובץ CSV לפוסטים ושדות ACF. פשוט, מאובטח ומותאם אישית.    
 * Version:     1.1.0
 * Author:      Chepti
 * Author URI:  https://github.com/chepti
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: acf-csv-importer
 * Domain Path: /languages
 */

// מניעת גישה ישירה לקובץ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// הגדרת קבועים של התוסף
define( 'ACF_CSV_IMPORTER_VERSION', '1.1.0' );
define( 'ACF_CSV_IMPORTER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACF_CSV_IMPORTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * המחלקה הראשית של התוסף
 */
final class ACF_CSV_Importer {

    private static $_instance = null;

    /**
     * יצירת מופע יחיד של המחלקה (Singleton)
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * קונסטרוקטור
     */
    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    /**
     * אתחול התוסף
     */
    public function init() {
        // בדיקה האם ACF פעיל
        if ( ! class_exists( 'ACF' ) ) {
            add_action( 'admin_notices', array( $this, 'acf_not_active_notice' ) );
            return;
        }

        // טעינת קבצי תרגום
        load_plugin_textdomain( 'acf-csv-importer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

        // טעינת קבצים נדרשים
        $this->includes();

        // אתחול המחלקות
        new ACF_CSV_Importer_Admin();
        new ACF_CSV_Importer_Ajax();
    }

    /**
     * טעינת קבצים
     */
    private function includes() {
        require_once ACF_CSV_IMPORTER_PLUGIN_DIR . 'includes/class-importer-admin.php';
        require_once ACF_CSV_IMPORTER_PLUGIN_DIR . 'includes/class-importer-ajax.php';
    }

    /**
     * הצגת הודעה אם ACF אינו פעיל
     */
    public function acf_not_active_notice() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'ACF CSV Importer requires Advanced Custom Fields (ACF) to be active. Please activate ACF.', 'acf-csv-importer' ); ?></p>
        </div>
        <?php
    }
}

/**
 * פונקציה להרצת התוסף
 */
function acf_csv_importer() {
    return ACF_CSV_Importer::instance();
}

// הרצת התוסף
acf_csv_importer();
