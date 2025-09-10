<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   SENDISES
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

global $wpdb;

// Delete options
delete_option('sendises_settings');

// Delete custom tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sendises_user_read_log");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sendises_notifications");
