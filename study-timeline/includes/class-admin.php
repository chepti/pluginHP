<?php
/**
 * Handles the admin interface for the Study Timeline plugin.
 */
class Study_Timeline_Admin {

    public function __construct() {
        // Safety checks before registering hooks
        if (!function_exists('add_action')) {
            return;
        }

        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        if (function_exists('admin_url')) {
            add_action( 'admin_post_study_timeline_add_timeline', [ $this, 'handle_add_timeline' ] );
            add_action( 'admin_post_study_timeline_add_topic', [ $this, 'handle_add_topic' ] );
            add_action( 'admin_post_study_timeline_delete_topic', [ $this, 'handle_delete_topic' ] );
        }

        // AJAX handler for updating topic dates
        add_action( 'wp_ajax_study_timeline_update_topic_dates', [ $this, 'handle_update_topic_dates' ] );
        // AJAX handler for updating topic title
        add_action( 'wp_ajax_study_timeline_update_topic_title', [ $this, 'handle_update_topic_title' ] );
    }

    /**
     * Add admin menu pages.
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Study Timelines', 'study-timeline' ), // Page Title
            __( 'Timelines', 'study-timeline' ),      // Menu Title
            'manage_options',                         // Capability
            'study-timeline',                         // Menu Slug
            [ $this, 'render_main_admin_page' ],      // Callback function
            'dashicons-chart-line',                   // Icon
            25                                        // Position
        );

        add_submenu_page(
            'study-timeline',                         // Parent Slug
            __( 'Manage Topics', 'study-timeline' ),  // Page Title
            __( 'Manage Topics', 'study-timeline' ),  // Menu Title
            'manage_options',                         // Capability
            'study-timeline-topics',                  // Menu Slug
            [ $this, 'render_topics_admin_page' ]      // Callback function
        );
    }

    /**
     * Enqueue scripts and styles for the admin pages.
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        // Only load on our specific topics page.
        // The hook suffix is 'timelines_page_study-timeline-topics' for the submenu page.
        if ( $hook_suffix !== 'timelines_page_study-timeline-topics' ) {
            return;
        }

        // --- Register assets specifically for the admin page ---
        wp_register_style(
            'vis-timeline-style',
            'https://unpkg.com/vis-timeline@latest/styles/vis-timeline-graph2d.min.css',
            [],
            '7.7.0'
        );
        wp_register_script(
            'vis-timeline-script',
            'https://unpkg.com/vis-timeline@latest/standalone/umd/vis-timeline-graph2d.min.js',
            [],
            '7.7.0',
            true
        );
        // --- End Registration ---

        // Enqueue Vis.js 
        wp_enqueue_style( 'vis-timeline-style' );
        wp_enqueue_script( 'vis-timeline-script' );

        // Enqueue our new admin-specific JS file
        $plugin_url = plugin_dir_url( dirname( __FILE__ ) );
        wp_enqueue_script(
            'study-timeline-admin-topics',
            $plugin_url . 'assets/js/admin-topics.js',
            [ 'vis-timeline-script', 'wp-util' ], // wp-util for ajax
            STUDY_TIMELINE_VERSION,
            true
        );
    }

    /**
     * Render the page for managing topics of a specific timeline.
     */
    public function render_topics_admin_page() {
        // Basic security and input check
        if ( ! isset( $_GET['timeline_id'] ) || ! is_numeric( $_GET['timeline_id'] ) ) {
            wp_die( 'A valid timeline ID is required.' );
        }
        $timeline_id = (int) $_GET['timeline_id'];

        // Safety check - make sure WordPress database is available
        if (!isset($GLOBALS['wpdb'])) {
            wp_die( 'Database connection not available.' );
        }

        global $wpdb;
        $timelines_table = $wpdb->prefix . 'study_timelines';
        $topics_table = $wpdb->prefix . 'study_timeline_topics';
        
        $timeline = $wpdb->get_row( $wpdb->prepare( "SELECT name FROM $timelines_table WHERE id = %d", $timeline_id ) );
        $topics = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $topics_table WHERE timeline_id = %d ORDER BY start_date ASC", $timeline_id ) );

        // Pass data to our admin script
        wp_localize_script('study-timeline-admin-topics', 'adminTimelineData', [
            'timelineId' => $timeline_id,
            'topics'     => $topics,
            'nonce'      => wp_create_nonce('wp_rest') // Re-using the REST API nonce for our admin-ajax
        ]);

        if ( ! $timeline ) {
            wp_die( 'Timeline not found.' );
        }
        ?>
        <div class="wrap">
            <h1><?php printf( __( 'Manage Topics for: %s', 'study-timeline' ), '<em>' . esc_html( $timeline->name ) . '</em>' ); ?></h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=study-timeline' ) ); ?>">&larr; <?php _e( 'Back to All Timelines', 'study-timeline' ); ?></a>
            
            <div id="topics-editor-wrapper" class="wp-clearfix" style="margin-top: 20px;">
                
                <div id="topics-timeline-container" style="height: 150px; border: 1px solid #ccc; margin-bottom: 20px;">
                    <!-- The visual timeline editor will be rendered here -->
                </div>

                <div id="col-container">
                    <div id="col-left">
                        <div class="col-wrap">
                            <h2><?php _e( 'Add New Topic', 'study-timeline' ); ?></h2>
                            <form id="add-topic-form" class="form-wrap" method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                <p><?php _e('Set the topic\'s date range visually by dragging it on the timeline above after adding it.', 'study-timeline'); ?></p>
                                <input type="hidden" name="action" value="study_timeline_add_topic">
                                <input type="hidden" name="timeline_id" value="<?php echo $timeline_id; ?>">
                                <!-- Dates will be added by JS, or can be kept for fallback -->
                                <input type="hidden" name="start_date" id="start_date_hidden">
                                <input type="hidden" name="end_date" id="end_date_hidden">
                                <?php wp_nonce_field( 'study_timeline_add_topic_nonce' ); ?>
                                
                                <div class="form-field">
                                    <label for="topic_title"><?php _e( 'Topic Name', 'study-timeline' ); ?></label>
                                    <input name="topic_title" id="topic_title" type="text" required>
                                </div>
                                <div class="form-field">
                                    <label for="color"><?php _e( 'Color', 'study-timeline' ); ?></label>
                                    <input name="color" id="color" type="color" value="#e5e5e5">
                                </div>

                                <?php submit_button( __( 'Add Topic to Timeline', 'study-timeline' ) ); ?>
                            </form>
                        </div>
                    </div>
                    <div id="col-right">
                        <div class="col-wrap">
                            <h2><?php _e( 'Current Topics', 'study-timeline' ); ?></h2>
                            <p><?php _e('Click a topic in the list to select it. Click and drag the topic or its edges on the timeline above to edit.', 'study-timeline'); ?></p>
                            <table id="topics-list-table" class="wp-list-table widefat fixed striped">
                                <thead>
                                <tr>
                                    <th><?php _e( 'Name', 'study-timeline' ); ?></th>
                                    <th><?php _e( 'Start Date', 'study-timeline' ); ?></th>
                                    <th><?php _e( 'End Date', 'study-timeline' ); ?></th>
                                    <th><?php _e( 'Color', 'study-timeline' ); ?></th>
                                </tr>
                            </thead>
                            <tbody id="topics-list-body">
                                <?php if ( empty( $topics ) ) : ?>
                                    <tr id="no-topics-row"><td colspan="4"><?php _e( 'No topics yet.', 'study-timeline' ); ?></td></tr>
                                <?php else : ?>
                                    <?php foreach ( $topics as $topic ) : ?>
                                        <tr id="topic-row-<?php echo $topic->id; ?>" data-topic-id="<?php echo $topic->id; ?>">
                                            <td>
                                                <strong class="topic-title-text"><?php echo esc_html( $topic->title ); ?></strong>
                                                <div class="row-actions">
                                                    <span class="edit"><a href="#" class="edit-topic-btn"><?php _e( 'Edit', 'study-timeline' ); ?></a> | </span>
                                                    <span class="trash"><a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=study_timeline_delete_topic&topic_id=' . $topic->id . '&timeline_id=' . $timeline_id ), 'study_timeline_delete_topic_nonce' ); ?>" class="submitdelete" onclick="return confirm('Are you sure you want to delete this topic?');"><?php _e( 'Delete', 'study-timeline' ); ?></a></span>
                                                </div>
                                            </td>
                                            <td class="start-date-cell"><?php echo esc_html( $topic->start_date ); ?></td>
                                            <td class="end-date-cell"><?php echo esc_html( $topic->end_date ); ?></td>
                                            <td class="color-cell" style="background-color: <?php echo esc_attr( $topic->color ); ?>;"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Render the main admin page for listing timelines.
     */
    public function render_main_admin_page() {
        // Safety check - make sure WordPress database is available
        if (!isset($GLOBALS['wpdb'])) {
            wp_die( 'Database connection not available.' );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'study_timelines';
        $timelines = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC" );
        ?>
        <div class="wrap">
            <h1><?php _e( 'Study Timelines', 'study-timeline' ); ?> <a href="#" id="add-new-timeline-btn" class="page-title-action"><?php _e( 'Add New', 'study-timeline' ); ?></a></h1>
            
            <div id="add-timeline-form-wrapper" style="display: none; margin-bottom: 20px; padding: 20px; background: #fff; border: 1px solid #ccd0d4;">
                <h2><?php _e( 'Add New Timeline', 'study-timeline' ); ?></h2>
                <form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="study_timeline_add_timeline">
                    <?php wp_nonce_field( 'study_timeline_add_timeline_nonce' ); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="timeline_name"><?php _e( 'Timeline Name', 'study-timeline' ); ?></label></th>
                            <td><input name="timeline_name" id="timeline_name" type="text" class="regular-text" required></td>
                        </tr>
                    </table>
                    <?php submit_button( __( 'Create Timeline', 'study-timeline' ) ); ?>
                </form>
            </div>

            <h2><?php _e( 'Existing Timelines', 'study-timeline' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column">ID</th>
                        <th scope="col" class="manage-column">Name</th>
                        <th scope="col" class="manage-column">Shortcode</th>
                        <th scope="col" class="manage-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $timelines ) ) : ?>
                        <tr>
                            <td colspan="4"><?php _e( 'No timelines found. Create one to get started!', 'study-timeline' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $timelines as $timeline ) : ?>
                            <tr>
                                <td><?php echo (int) $timeline->id; ?></td>
                                <td><strong><a href="<?php echo esc_url( admin_url( 'admin.php?page=study-timeline-topics&timeline_id=' . $timeline->id ) ); ?>"><?php echo esc_html( $timeline->name ); ?></a></strong></td>
                                <td><code>[study_timeline id="<?php echo (int) $timeline->id; ?>"]</code></td>
                                <td><a href="<?php echo esc_url( admin_url( 'admin.php?page=study-timeline-topics&timeline_id=' . $timeline->id ) ); ?>"><?php _e('Manage Topics', 'study-timeline');?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <script>
                document.getElementById('add-new-timeline-btn').addEventListener('click', function(e) {
                    e.preventDefault();
                    var form = document.getElementById('add-timeline-form-wrapper');
                    form.style.display = form.style.display === 'none' ? 'block' : 'none';
                });
            </script>
        </div>
        <?php
    }

    /**
     * Handle the form submission for adding a new timeline.
     */
    public function handle_add_timeline() {
        // Safety check - make sure WordPress database is available
        if (!isset($GLOBALS['wpdb'])) {
            wp_die( 'Database connection not available.' );
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'study_timeline_add_timeline_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have permission to do this.' );
        }

        if ( empty( $_POST['timeline_name'] ) ) {
            wp_die( 'Timeline name is required.' );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'study_timelines';

        $wpdb->insert(
            $table_name,
            [
                'name'          => sanitize_text_field( $_POST['timeline_name'] ),
                'owner_id'      => get_current_user_id(),
                'creation_date' => current_time( 'mysql' ),
            ],
            [ '%s', '%d', '%s' ]
        );

        // Redirect back to the main admin page
        wp_redirect( admin_url( 'admin.php?page=study-timeline&timeline_added=1' ) );
        exit;
    }

    /**
     * Handle the form submission for adding a new topic.
     */
    public function handle_add_topic() {
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'study_timeline_add_topic_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have permission to do this.' );
        }

        $timeline_id = (int) $_POST['timeline_id'];
        $title = sanitize_text_field( $_POST['topic_title'] );
        // Set default dates if they are not provided, e.g., for new items from the form
        $start_date = !empty($_POST['start_date']) ? sanitize_text_field( $_POST['start_date'] ) : date('Y-m-d');
        $end_date = !empty($_POST['end_date']) ? sanitize_text_field( $_POST['end_date'] ) : date('Y-m-d', strtotime('+1 week'));
        $color = sanitize_hex_color( $_POST['color'] );

        if ( empty($title) || empty($timeline_id) ) {
            wp_die('Topic name is required.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'study_timeline_topics';
        $wpdb->insert(
            $table_name,
            ['timeline_id' => $timeline_id, 'title' => $title, 'start_date' => $start_date, 'end_date' => $end_date, 'color' => $color],
            ['%d', '%s', '%s', '%s', '%s']
        );

        wp_redirect( admin_url( 'admin.php?page=study-timeline-topics&timeline_id=' . $timeline_id . '&topic_added=1' ) );
        exit;
    }

    /**
     * Handle deleting a topic.
     */
    public function handle_delete_topic() {
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'study_timeline_delete_topic_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have permission to do this.' );
        }
        
        $topic_id = (int) $_GET['topic_id'];
        $timeline_id = (int) $_GET['timeline_id'];

        global $wpdb;
        $table_name = $wpdb->prefix . 'study_timeline_topics';
        $wpdb->delete( $table_name, [ 'id' => $topic_id ], [ '%d' ] );

        wp_redirect( admin_url( 'admin.php?page=study-timeline-topics&timeline_id=' . $timeline_id . '&topic_deleted=1' ) );
        exit;
    }

    /**
     * Handle AJAX request to update topic dates.
     */
    public function handle_update_topic_dates() {
        check_ajax_referer('wp_rest'); // Check nonce

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied.', 403 );
        }
        
        $topic_id = (int)$_POST['topic_id'];
        $start_date = sanitize_text_field( $_POST['start_date'] );
        $end_date = sanitize_text_field( $_POST['end_date'] );
        
        if ( empty($topic_id) || empty($start_date) || empty($end_date) ) {
            wp_send_json_error( 'Missing required fields.', 400 );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'study_timeline_topics';
        
        $result = $wpdb->update(
            $table_name,
            [ 'start_date' => $start_date, 'end_date' => $end_date ],
            [ 'id' => $topic_id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );

        if ($result === false) {
            wp_send_json_error( 'Database update failed.', 500 );
        }

        wp_send_json_success( 'Topic dates updated successfully.' );
    }

    /**
     * Handle AJAX request to update topic title.
     */
    public function handle_update_topic_title() {
        check_ajax_referer('wp_rest'); 

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied.', 403 );
        }
        
        $topic_id = (int)$_POST['topic_id'];
        $new_title = sanitize_text_field( $_POST['new_title'] );
        
        if ( empty($topic_id) || empty($new_title) ) {
            wp_send_json_error( 'Missing required fields.', 400 );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'study_timeline_topics';
        
        $result = $wpdb->update(
            $table_name,
            [ 'title' => $new_title ],
            [ 'id' => $topic_id ],
            [ '%s' ],
            [ '%d' ]
        );

        if ($result === false) {
            wp_send_json_error( 'Database update failed.', 500 );
        }

        wp_send_json_success( [ 'new_title' => $new_title ] );
    }
}
