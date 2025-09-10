<?php

if (!defined('WPINC')) {
    die;
}

/**
 * Handles the admin UI for reports and settings.
 */
class SENDISES_Admin_UI {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'handle_export_action']);
    }

    public function add_admin_menu() {
        add_menu_page(
            __('SENDISES Tracker', 'sendises'),
            __('SENDISES', 'sendises'),
            'manage_options',
            'sendises-admin',
            [$this, 'create_admin_page'],
            'dashicons-visibility',
            25
        );
    }

    public function create_admin_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'reports';
        ?>
        <div class="wrap sendises-wrap">
            <h1><?php _e('SENDISES Content Tracker', 'sendises'); ?></h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=sendises-admin&tab=reports" class="nav-tab <?php echo $active_tab == 'reports' ? 'nav-tab-active' : ''; ?>"><?php _e('Reports', 'sendises'); ?></a>
                <a href="?page=sendises-admin&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Settings', 'sendises'); ?></a>
                 <a href="?page=sendises-admin&tab=notifications" class="nav-tab <?php echo $active_tab == 'notifications' ? 'nav-tab-active' : ''; ?>"><?php _e('Notifications Log', 'sendises'); ?></a>
            </h2>
            
            <?php
            if ($active_tab == 'reports') {
                $this->render_reports_tab();
            } elseif ($active_tab == 'settings') {
                $this->render_settings_tab();
            } else {
                 $this->render_notifications_log_tab();
            }
            ?>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('sendises_settings_group', 'sendises_settings', [$this, 'sanitize_settings']);

        // Settings Sections
        add_settings_section('sendises_ses_section', __('Amazon SES Settings', 'sendises'), null, 'sendises_settings_page');

        // Settings Fields
        add_settings_field('ses_access_key', __('AWS Access Key ID', 'sendises'), [$this, 'render_field_access_key'], 'sendises_settings_page', 'sendises_ses_section');
        add_settings_field('ses_secret_key', __('AWS Secret Access Key', 'sendises'), [$this, 'render_field_secret_key'], 'sendises_settings_page', 'sendises_ses_section');
        add_settings_field('ses_region', __('AWS Region', 'sendises'), [$this, 'render_field_region'], 'sendises_settings_page', 'sendises_ses_section');
        add_settings_field('ses_from_email', __('"From" Email Address', 'sendises'), [$this, 'render_field_from_email'], 'sendises_settings_page', 'sendises_ses_section');
        add_settings_field('notification_user_role', __('User Role for Notifications', 'sendises'), [$this, 'render_field_user_role'], 'sendises_settings_page', 'sendises_ses_section');
    }
    
    public function sanitize_settings($input) {
        $new_input = [];
        if (isset($input['ses_access_key'])) {
            $new_input['ses_access_key'] = sanitize_text_field($input['ses_access_key']);
        }
        if (isset($input['ses_secret_key'])) {
            // Do not display the secret key back, but save it.
            if (!empty($input['ses_secret_key']) && strpos($input['ses_secret_key'], '***') === false) {
                 $new_input['ses_secret_key'] = sanitize_text_field($input['ses_secret_key']);
            } else {
                 $options = get_option('sendises_settings');
                 $new_input['ses_secret_key'] = $options['ses_secret_key'];
            }
        }
        if (isset($input['ses_region'])) {
            $new_input['ses_region'] = sanitize_text_field($input['ses_region']);
        }
        if (isset($input['ses_from_email'])) {
            $new_input['ses_from_email'] = sanitize_email($input['ses_from_email']);
        }
        if (isset($input['notification_user_role'])) {
            $new_input['notification_user_role'] = sanitize_text_field($input['notification_user_role']);
        }
        return $new_input;
    }

    public function render_field_access_key() {
        $options = get_option('sendises_settings');
        printf('<input type="text" name="sendises_settings[ses_access_key]" value="%s" class="regular-text">',
            isset($options['ses_access_key']) ? esc_attr($options['ses_access_key']) : ''
        );
    }

    public function render_field_secret_key() {
        $options = get_option('sendises_settings');
        $value = isset($options['ses_secret_key']) && !empty($options['ses_secret_key']) ? '************' : '';
        echo '<input type="password" name="sendises_settings[ses_secret_key]" value="' . $value . '" class="regular-text" placeholder="' . __('Leave blank to keep unchanged', 'sendises') .'">';
    }

    public function render_field_region() {
        $options = get_option('sendises_settings');
        $selected_region = isset($options['ses_region']) ? $options['ses_region'] : '';
        
        $regions = [
            'us-east-1'      => 'US East (N. Virginia)',
            'us-east-2'      => 'US East (Ohio)',
            'us-west-1'      => 'US West (N. California)',
            'us-west-2'      => 'US West (Oregon)',
            'af-south-1'     => 'Africa (Cape Town)',
            'ap-south-1'     => 'Asia Pacific (Mumbai)',
            'ap-northeast-2' => 'Asia Pacific (Seoul)',
            'ap-southeast-1' => 'Asia Pacific (Singapore)',
            'ap-southeast-2' => 'Asia Pacific (Sydney)',
            'ap-northeast-1' => 'Asia Pacific (Tokyo)',
            'ca-central-1'   => 'Canada (Central)',
            'eu-central-1'   => 'Europe (Frankfurt)',
            'eu-west-1'      => 'Europe (Ireland)',
            'eu-west-2'      => 'Europe (London)',
            'eu-south-1'     => 'Europe (Milan)',
            'eu-west-3'      => 'Europe (Paris)',
            'eu-north-1'     => 'Europe (Stockholm)',
            'me-south-1'     => 'Middle East (Bahrain)',
            'sa-east-1'      => 'South America (São Paulo)',
        ];

        echo '<select name="sendises_settings[ses_region]">';
        echo '<option value="">' . __('— Select a Region —', 'sendises') . '</option>';
        foreach ($regions as $code => $name) {
            printf('<option value="%s" %s>%s</option>', esc_attr($code), selected($selected_region, $code, false), esc_html($name));
        }
        echo '</select>';
    }
    
    public function render_field_from_email() {
        $options = get_option('sendises_settings');
        printf('<input type="email" name="sendises_settings[ses_from_email]" value="%s" class="regular-text" placeholder="e.g., noreply@example.com">',
            isset($options['ses_from_email']) ? esc_attr($options['ses_from_email']) : ''
        );
        echo '<p class="description">' . __('This email must be verified in your Amazon SES account.', 'sendises') . '</p>';
    }
    
    public function render_field_user_role() {
        $options = get_option('sendises_settings');
        $selected_role = isset($options['notification_user_role']) ? $options['notification_user_role'] : '';
        
        echo '<select name="sendises_settings[notification_user_role]">';
        echo '<option value="">' . __('— Select a Role —', 'sendises') . '</option>';
        wp_dropdown_roles($selected_role);
        echo '</select>';
        echo '<p class="description">' . __('Users with this role will receive new post notifications.', 'sendises') . '</p>';
    }


    private function render_settings_tab() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('sendises_settings_group');
            do_settings_sections('sendises_settings_page');
            submit_button();
            ?>
        </form>
        <?php
    }

    private function render_reports_tab() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sendises_user_read_log';
        
        // Filters
        $selected_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $selected_post = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        
        $sql = "SELECT r.user_id, r.post_id, r.opened_at, r.read_status, u.display_name, p.post_title 
                FROM {$table_name} r
                JOIN {$wpdb->users} u ON r.user_id = u.ID
                JOIN {$wpdb->posts} p ON r.post_id = p.ID";
        
        $where = [];
        if ($selected_user) {
            $where[] = $wpdb->prepare("r.user_id = %d", $selected_user);
        }
        if ($selected_post) {
            $where[] = $wpdb->prepare("r.post_id = %d", $selected_post);
        }
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY r.opened_at DESC";

        $results = $wpdb->get_results($sql);
        
        // Render filters UI
        $this->render_report_filters($selected_user, $selected_post);

        ?>
        <div class="sendises-report-table">
            <form method="get" class="sendises-export-form">
                <input type="hidden" name="page" value="sendises-admin" />
                <input type="hidden" name="tab" value="reports" />
                <input type="hidden" name="action" value="export_csv" />
                 <?php
                 if ($selected_user) echo '<input type="hidden" name="user_id" value="'.esc_attr($selected_user).'" />';
                 if ($selected_post) echo '<input type="hidden" name="post_id" value="'.esc_attr($selected_post).'" />';
                 wp_nonce_field('sendises_export_nonce', '_wpnonce_export');
                 ?>
                <p><button type="submit" class="button button-primary"><?php _e('Export to CSV', 'sendises'); ?></button></p>
            </form>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('User', 'sendises'); ?></th>
                        <th><?php _e('Post', 'sendises'); ?></th>
                        <th><?php _e('Opened At', 'sendises'); ?></th>
                        <th><?php _e('Status', 'sendises'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($results)): ?>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?php echo esc_html($row->display_name); ?></td>
                                <td><a href="<?php echo get_permalink($row->post_id); ?>" target="_blank"><?php echo esc_html($row->post_title); ?></a></td>
                                <td><?php echo esc_html($row->opened_at); ?></td>
                                <td><?php echo $row->read_status ? '<span class="status-read">'.__('Read', 'sendises').'</span>' : '<span class="status-unread">'.__('Unread', 'sendises').'</span>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4"><?php _e('No data found.', 'sendises'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function render_report_filters($selected_user, $selected_post) {
        $all_users = get_users(['fields' => ['ID', 'display_name']]);
        $all_posts = get_posts(['posts_per_page' => -1, 'post_status' => 'publish']);
        ?>
        <form method="get" class="sendises-filters">
            <input type="hidden" name="page" value="sendises-admin" />
            <input type="hidden" name="tab" value="reports" />
            
            <select name="user_id">
                <option value="0"><?php _e('All Users', 'sendises'); ?></option>
                <?php foreach($all_users as $user): ?>
                    <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($selected_user, $user->ID); ?>>
                        <?php echo esc_html($user->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="post_id">
                <option value="0"><?php _e('All Posts', 'sendises'); ?></option>
                <?php foreach($all_posts as $post): ?>
                    <option value="<?php echo esc_attr($post->ID); ?>" <?php selected($selected_post, $post->ID); ?>>
                        <?php echo esc_html($post->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="button"><?php _e('Filter', 'sendises'); ?></button>
            <a href="?page=sendises-admin&tab=reports" class="button"><?php _e('Clear Filters', 'sendises'); ?></a>
        </form>
        <?php
    }

    private function render_notifications_log_tab() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sendises_notifications';

        $results = $wpdb->get_results("SELECT n.*, p.post_title FROM {$table_name} n LEFT JOIN {$wpdb->posts} p ON n.post_id = p.ID ORDER BY n.sent_at DESC");

        ?>
         <div class="sendises-report-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Post', 'sendises'); ?></th>
                        <th><?php _e('Sent To', 'sendises'); ?></th>
                        <th><?php _e('Sent At', 'sendises'); ?></th>
                        <th><?php _e('Status', 'sendises'); ?></th>
                        <th><?php _e('Message', 'sendises'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($results)): ?>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><a href="<?php echo get_edit_post_link($row->post_id); ?>" target="_blank"><?php echo esc_html($row->post_title ?: 'N/A'); ?></a></td>
                                <td><?php echo esc_html($row->sent_to); ?></td>
                                <td><?php echo esc_html($row->sent_at); ?></td>
                                <td>
                                    <?php if ($row->delivery_status === 'success'): ?>
                                        <span class="status-success"><?php _e('Success', 'sendises'); ?></span>
                                    <?php else: ?>
                                        <span class="status-error"><?php _e('Error', 'sendises'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($row->error_message ?: $row->delivery_status); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5"><?php _e('No notifications logged yet.', 'sendises'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }


    public function handle_export_action() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'export_csv') {
            return;
        }
        if (!isset($_GET['_wpnonce_export']) || !wp_verify_nonce($_GET['_wpnonce_export'], 'sendises_export_nonce')) {
            wp_die('Invalid nonce.');
        }
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sendises_user_read_log';
        
        // Respect filters from the report page
        $selected_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $selected_post = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        
        $sql = "SELECT r.user_id, r.post_id, r.opened_at, r.read_status, u.display_name, u.user_email, p.post_title 
                FROM {$table_name} r
                JOIN {$wpdb->users} u ON r.user_id = u.ID
                JOIN {$wpdb->posts} p ON r.post_id = p.ID";
        
        $where = [];
        if ($selected_user) {
            $where[] = $wpdb->prepare("r.user_id = %d", $selected_user);
        }
        if ($selected_post) {
            $where[] = $wpdb->prepare("r.post_id = %d", $selected_post);
        }
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY r.opened_at DESC";

        $results = $wpdb->get_results($sql, ARRAY_A);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=sendises-report-' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        // Add BOM to fix UTF-8 in Excel
        fputs($output, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
        fputcsv($output, ['User Name', 'User Email', 'Post Title', 'Opened At', 'Read Status']);

        if (!empty($results)) {
            foreach ($results as $row) {
                $row['read_status'] = $row['read_status'] ? 'Read' : 'Unread';
                unset($row['user_id']);
                unset($row['post_id']);
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }
}

new SENDISES_Admin_UI();
