<?php
/**
 * Plugin Name:       Study Timeline
 * Plugin URI:        https://example.com/
 * Description:       A plugin to create and manage interactive study timelines for study groups.
 * Version:           1.0.0
 * Author:            Chepti
 * Author URI:        
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       study-timeline
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'STUDY_TIMELINE_VERSION', '1.0.0' );
define( 'STUDY_TIMELINE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * Creates the custom database tables needed for the plugin.
 */
function study_timeline_activate() {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table for Timelines - Each timeline is associated with a group.
    $table_name_timelines = $wpdb->prefix . 'study_timelines';
    $sql_timelines = "CREATE TABLE $table_name_timelines (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        group_id mediumint(9) DEFAULT 0 NOT NULL, -- To associate with a study group/class
        owner_id bigint(20) unsigned NOT NULL,
        creation_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_timelines );

    // Table for Timeline Topics/Bands (e.g., "תקופת העתיקות")
    $table_name_topics = $wpdb->prefix . 'study_timeline_topics';
    $sql_topics = "CREATE TABLE $table_name_topics (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        timeline_id mediumint(9) NOT NULL,
        title varchar(255) NOT NULL,
        start_date date NOT NULL,
        end_date date NOT NULL,
        color varchar(20) DEFAULT '#FFFFFF' NOT NULL,
        position smallint(5) DEFAULT 0 NOT NULL,
        PRIMARY KEY  (id),
        KEY timeline_id (timeline_id)
    ) $charset_collate;";
    dbDelta( $sql_topics );

    // Table for Timeline Items (the "pins" on the timeline)
    $table_name_items = $wpdb->prefix . 'study_timeline_items';
    $sql_items = "CREATE TABLE $table_name_items (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        timeline_id mediumint(9) NOT NULL,
        post_id bigint(20) unsigned NOT NULL,
        item_date datetime NOT NULL,
        item_lane smallint(5) DEFAULT 0 NOT NULL, -- 0: מערכים, 1: מצגות, etc.
        added_by_user_id bigint(20) unsigned NOT NULL,
        creation_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY timeline_id (timeline_id),
        KEY post_id (post_id)
    ) $charset_collate;";
    dbDelta( $sql_items );

    // Store the plugin version for future upgrades.
    add_option( 'study_timeline_version', STUDY_TIMELINE_VERSION );
}

/**
 * The code that runs during plugin deactivation.
 * We won't delete data to prevent accidental loss.
 */
function study_timeline_deactivate() {
    // Optional: Add any deactivation logic here.
}

register_activation_hook( __FILE__, 'study_timeline_activate' );
register_deactivation_hook( __FILE__, 'study_timeline_deactivate' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_study_timeline() {

    // Include the REST API class file and initialize it.
    require_once STUDY_TIMELINE_PLUGIN_DIR . 'includes/class-rest-api.php';
    new Study_Timeline_REST_API();

    // Include the Frontend class file and initialize it.
    require_once STUDY_TIMELINE_PLUGIN_DIR . 'includes/class-frontend.php';
    new Study_Timeline_Frontend();

}
run_study_timeline();
