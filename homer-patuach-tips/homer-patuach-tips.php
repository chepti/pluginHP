<?php
/**
 * Plugin Name:       Homer Patuach - Tips
 * Plugin URI:        https://homerpatuach.com/
 * Description:       מערכת טיפים עם בועה צפה, סינון לפי שכבת גיל ותחום דעת.
 * Version:           1.0.3
 * Author:            Chepti
 * Author URI:        https://homerpatuach.com/
 * License:           GPL-2.0+
 * License URI:        http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       homer-patuach-tips
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'HPT_VERSION', '1.0.3' );
define( 'HPT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HPT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once HPT_PLUGIN_DIR . 'includes/class-hpt-cpt.php';
require_once HPT_PLUGIN_DIR . 'includes/class-hpt-admin.php';
require_once HPT_PLUGIN_DIR . 'includes/class-hpt-rest.php';
require_once HPT_PLUGIN_DIR . 'includes/class-hpt-frontend.php';

function hpt_init() {
	$cpt = new HPT_CPT();
	$cpt->register();

	$admin = new HPT_Admin();
	$admin->register();

	$rest = new HPT_REST();
	$rest->register();

	$frontend = new HPT_Frontend();
	$frontend->register();
}
add_action( 'plugins_loaded', 'hpt_init' );
