<?php

if (!defined('WPINC')) {
    die;
}

class SENDISES_Tracker_Functions {

    public static function activate() {
        self::create_tables();
    }

    public static function deactivate() {
        // Optional: Add any deactivation logic here if needed.
    }

    /**
     * Create custom database tables.
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table for user read log
        $table_name_log = $wpdb->prefix . 'sendises_user_read_log';
        $sql_log = "CREATE TABLE $table_name_log (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            post_id bigint(20) UNSIGNED NOT NULL,
            opened_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            read_status tinyint(1) DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_post (user_id,post_id),
            KEY post_id (post_id)
        ) $charset_collate;";
        dbDelta($sql_log);

        // Table for notifications log
        $table_name_notifications = $wpdb->prefix . 'sendises_notifications';
        $sql_notifications = "CREATE TABLE $table_name_notifications (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            sent_to varchar(255) NOT NULL,
            sent_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            delivery_status varchar(20) NOT NULL,
            error_message text,
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        dbDelta($sql_notifications);
    }

    /**
     * Record post view when a logged-in user visits a single post.
     */
    public static function record_post_view() {
        if (is_single() && is_user_logged_in()) {
            global $wpdb;
            $user_id = get_current_user_id();
            $post_id = get_the_ID();
            $table_name = $wpdb->prefix . 'sendises_user_read_log';

            // Check if a record already exists
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_name WHERE user_id = %d AND post_id = %d",
                $user_id,
                $post_id
            ));

            // If not, insert a new record
            if (null === $existing) {
                $wpdb->insert(
                    $table_name,
                    [
                        'user_id'   => $user_id,
                        'post_id'   => $post_id,
                        'opened_at' => current_time('mysql'),
                    ],
                    ['%d', '%d', '%s']
                );
            }
        }
    }

    /**
     * AJAX handler to mark a post as read.
     */
    public static function mark_post_as_read() {
        check_ajax_referer('sendises_mark_read_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'User not logged in.']);
            return;
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (empty($post_id)) {
            wp_send_json_error(['message' => 'Invalid Post ID.']);
            return;
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $table_name = $wpdb->prefix . 'sendises_user_read_log';

        $result = $wpdb->update(
            $table_name,
            ['read_status' => 1],
            ['user_id' => $user_id, 'post_id' => $post_id],
            ['%d'],
            ['%d', '%d']
        );

        if (false === $result) {
            wp_send_json_error(['message' => 'Failed to update status.']);
        } else {
            wp_send_json_success(['message' => 'Post marked as read.']);
        }
    }
}

// Hook to record post view
add_action('wp_head', ['SENDISES_Tracker_Functions', 'record_post_view']);

// AJAX hooks for marking as read
add_action('wp_ajax_sendises_mark_read', ['SENDISES_Tracker_Functions', 'mark_post_as_read']);
