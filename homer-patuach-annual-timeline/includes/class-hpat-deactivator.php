<?php
/**
 * קלאס לטיפול בהסרה של התוסף
 */

class HPAT_Deactivator {

    /**
     * הסרה של התוסף
     */
    public static function deactivate() {
        // ניקוי transientים
        self::clear_transients();

        // הסרת scheduled events אם קיימים
        self::clear_scheduled_events();
    }

    /**
     * ניקוי transientים
     */
    private static function clear_transients() {
        global $wpdb;

        // מחיקת כל ה-transientים של התוסף
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_hpat_%'
            )
        );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_hpat_%'
            )
        );
    }

    /**
     * הסרת scheduled events
     */
    private static function clear_scheduled_events() {
        // הסרת cron jobs אם קיימים
        wp_clear_scheduled_hook('hpat_daily_maintenance');
        wp_clear_scheduled_hook('hpat_weekly_cleanup');
    }
}
