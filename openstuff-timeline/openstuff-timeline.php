<?php
/**
 * Plugin Name:       OpenStuff Academic Year Timeline
 * Plugin URI:        https://openstuff.co.il/
 * Description:       ציר זמן שנתי מבוסס Gutenberg - ארגון חומרי למידה לפי נושאים עם גרירה ושחרור.
 * Version:           1.0.21
 * Author:            Chepti
 * Author URI:        https://openstuff.co.il/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       openstuff-timeline
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OST_VERSION', '1.0.21' );
define( 'OST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OST_REST_NAMESPACE', 'os-timeline/v1' );

require_once OST_PLUGIN_DIR . 'includes/class-ost-cpt.php';
require_once OST_PLUGIN_DIR . 'includes/class-ost-rest.php';
require_once OST_PLUGIN_DIR . 'includes/class-ost-block.php';
require_once OST_PLUGIN_DIR . 'includes/class-ost-templates.php';
require_once OST_PLUGIN_DIR . 'includes/class-ost-admin.php';
require_once OST_PLUGIN_DIR . 'includes/class-ost-editor-registration.php';

/**
 * Initialize the plugin
 */
function ost_init() {
	$cpt = new OST_CPT();
	$cpt->register();

	$rest = new OST_REST();
	$rest->register_routes();

	$block = new OST_Block();
	$block->register();

	$templates = new OST_Templates();
	$templates->register();

	$admin = new OST_Admin();
	$admin->register();

	$editor_reg = new OST_Editor_Registration();
	$editor_reg->register();
}
add_action( 'init', 'ost_init', 5 );

/**
 * Track timeline views - רק אם GRID לא טוען (תאימות _hpg_view_count)
 */
function ost_track_timeline_views() {
	if ( ! is_singular( 'os_timeline' ) || current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( function_exists( 'hpg_get_post_views' ) ) {
		return; /* GRID מטפל */
	}
	global $post;
	if ( ! $post || $post->post_type !== 'os_timeline' ) {
		return;
	}
	$count = (int) get_post_meta( $post->ID, '_hpg_view_count', true );
	update_post_meta( $post->ID, '_hpg_view_count', $count + 1 );
}
add_action( 'wp_head', 'ost_track_timeline_views', 25 );

/**
 * Activation
 */
function ost_activate() {
	ost_init();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ost_activate' );
