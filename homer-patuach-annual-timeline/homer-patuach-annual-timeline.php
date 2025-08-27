<?php
/**
 * Plugin Name:       Homer Patuach - Annual Timeline
 * Plugin URI:        https://homerpatuach.com/
 * Description:       ציר זמן שנתי אינטראקטיבי ללמידה עם אפשרות גרירה והנחה של פריטים
 * Version:           1.0.0~c3d4e5f6
 * Author:            Chepti
 * Author URI:        https://chepti.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       hpat
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Tested up to:      6.4
 * Requires PHP:      7.4
 */

// מניעת גישה ישירה לקובץ
if (!defined('ABSPATH')) {
    exit;
}

// הגדרות התוסף
define('HPAT_VERSION', '1.0.0~c3d4e5f6');
define('HPAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HPAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HPAT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// טעינת קבצי התוסף
require_once HPAT_PLUGIN_DIR . 'includes/class-hpat-main.php';

// הפעלת התוסף
function hpat_init() {
    $plugin = new HPAT_Main();
    $plugin->run();
}
add_action('plugins_loaded', 'hpat_init');

/**
 * הפעלה ראשונה של התוסף - יצירת טבלאות DB
 */
function hpat_activate() {
    require_once HPAT_PLUGIN_DIR . 'includes/class-hpat-activator.php';
    HPAT_Activator::activate();
}
register_activation_hook(__FILE__, 'hpat_activate');

/**
 * הסרה של התוסף - ניקוי DB
 */
function hpat_deactivate() {
    require_once HPAT_PLUGIN_DIR . 'includes/class-hpat-deactivator.php';
    HPAT_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'hpat_deactivate');
