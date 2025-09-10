<?php
/**
 * Plugin Name:       SENDISES - Content Tracker
 * Plugin URI:        https://example.com/
 * Description:       Tracks post reading for logged-in users and sends notifications via Amazon SES.
 * Version:           1.5.0
 * Author:            Chepti
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sendises
 * Domain Path:       /languages
 */

if (!defined('WPINC')) {
    die;
}

define('SENDISES_VERSION', '1.0.0');
define('SENDISES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SENDISES_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Finds the path to the AWS SDK autoloader.
 * Supports both Composer and manual installations.
 *
 * @return string|false The path to the autoloader, or false if not found.
 */
function sendises_get_autoloader_path() {
    $composer_autoloader = SENDISES_PLUGIN_DIR . 'vendor/autoload.php';
    $manual_autoloader = SENDISES_PLUGIN_DIR . 'vendor/aws-autoloader.php';

    if (file_exists($composer_autoloader)) {
        return $composer_autoloader;
    }
    if (file_exists($manual_autoloader)) {
        return $manual_autoloader;
    }
    return false;
}

// Check for AWS SDK and notify admin if not found
add_action('admin_init', 'sendises_check_aws_sdk');
function sendises_check_aws_sdk() {
    if (!sendises_get_autoloader_path()) {
        add_action('admin_notices', 'sendises_aws_sdk_missing_notice');
    }
}

function sendises_aws_sdk_missing_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php _e('<strong>SENDISES Plugin:</strong> The AWS SDK for PHP is not installed. Please download it and place it in the plugin\'s "vendor" directory.', 'sendises'); ?></p>
    </div>
    <?php
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
class SENDISES_Core {

    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }

    private function load_dependencies() {
        require_once SENDISES_PLUGIN_DIR . 'includes/tracker-functions.php';
        require_once SENDISES_PLUGIN_DIR . 'includes/admin-ui.php';
        
        // Only load SES files if an AWS SDK autoloader is found
        if (sendises_get_autoloader_path()) {
            require_once SENDISES_PLUGIN_DIR . 'includes/ses-integration.php';
            require_once SENDISES_PLUGIN_DIR . 'includes/email-automation.php';
        }
    }

    private function define_hooks() {
        // Activation and deactivation
        register_activation_hook(__FILE__, ['SENDISES_Tracker_Functions', 'activate']);
        register_deactivation_hook(__FILE__, ['SENDISES_Tracker_Functions', 'deactivate']);

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    public function enqueue_frontend_assets() {
        if (is_single() && is_user_logged_in()) {
            wp_enqueue_script(
                'sendises-ajax',
                SENDISES_PLUGIN_URL . 'assets/js/tracker-ajax.js',
                ['jquery'],
                SENDISES_VERSION,
                true
            );
            
            // Pass data to script
            wp_localize_script('sendises-ajax', 'sendises_ajax_obj', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('sendises_mark_read_nonce'),
                'post_id'  => get_the_ID(),
                'mark_as_read_delay' => 15000 // 15 seconds
            ]);
        }
    }
    
    public function enqueue_admin_assets($hook) {
        // Only load on our admin page
        if ('toplevel_page_sendises-admin' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'sendises-admin-style',
            SENDISES_PLUGIN_URL . 'assets/css/admin-style.css',
            [],
            SENDISES_VERSION
        );
    }
}

// Initialize the plugin
function run_sendises() {
    return SENDISES_Core::get_instance();
}
run_sendises();
