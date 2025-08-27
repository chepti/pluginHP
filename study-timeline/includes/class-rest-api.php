<?php
/**
 * Handles the REST API endpoints for the Study Timeline plugin.
 */
class Study_Timeline_REST_API {

    protected $namespace = 'study-timeline/v1';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        // Route to get a full timeline data (topics and items)
        register_rest_route( $this->namespace, '/timeline/(?P<id>[\d]+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_timeline_data' ],
                'permission_callback' => [ $this, 'can_view_timeline' ],
                'args'                => [
                    'id' => [
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        }
                    ],
                ],
            ],
        ] );

        // Route to get repository items for dragging
        register_rest_route( $this->namespace, '/repository', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_repository_items' ],
                'permission_callback' => [ $this, 'can_view_timeline' ], // Same permission
            ],
        ] );

        // Route to get filter data for the repository
        register_rest_route( $this->namespace, '/repository/filters', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_repository_filters' ],
                'permission_callback' => [ $this, 'can_view_timeline' ],
            ],
        ]);

        // Route to save/update timeline items
        register_rest_route( $this->namespace, '/timeline/(?P<id>[\d]+)/item', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'add_timeline_item' ],
                'permission_callback' => [ $this, 'can_edit_timeline' ],
                'args'                => [
                    'post_id'   => [ 'required' => true, 'validate_callback' => 'is_numeric' ],
                    'item_date' => [ 'required' => true, 'validate_callback' => [ $this, 'is_valid_datetime' ] ],
                    'item_lane' => [ 'required' => true, 'validate_callback' => 'is_numeric' ],
                    'item_shape' => [ 'required' => true, 'validate_callback' => function($param) { return in_array($param, ['square', 'circle', 'triangle', 'star']); } ],
                    'item_color' => [ 'required' => false, 'sanitize_callback' => 'sanitize_hex_color' ],
                ],
            ],
        ] );

        // Routes for user preferences
        register_rest_route( $this->namespace, '/user-prefs/(?P<timeline_id>[\d]+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_user_preferences' ],
                'permission_callback' => [ $this, 'can_view_timeline' ],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE, // Using POST to update
                'callback'            => [ $this, 'update_user_preferences' ],
                'permission_callback' => [ $this, 'can_view_timeline' ], // Any logged in user can save their own prefs
            ],
        ] );
    }

    /**
     * Get user preferences for a given timeline (e.g., hidden items).
     */
    public function get_user_preferences( $request ) {
        $user_id = get_current_user_id();
        $timeline_id = (int) $request['timeline_id'];
        $meta_key = "hidden_timeline_items_{$timeline_id}";

        $hidden_items = get_user_meta( $user_id, $meta_key, true );

        if ( ! is_array( $hidden_items ) ) {
            $hidden_items = [];
        }

        return new WP_REST_Response( [ 'hidden_items' => $hidden_items ], 200 );
    }

    /**
     * Update user preferences for a given timeline.
     */
    public function update_user_preferences( $request ) {
        $user_id = get_current_user_id();
        $timeline_id = (int) $request['timeline_id'];
        $meta_key = "hidden_timeline_items_{$timeline_id}";

        $params = $request->get_json_params();
        $hidden_items = isset( $params['hidden_items'] ) && is_array( $params['hidden_items'] )
            ? array_map( 'intval', $params['hidden_items'] )
            : [];

        update_user_meta( $user_id, $meta_key, $hidden_items );

        return new WP_REST_Response( [ 'success' => true, 'hidden_items' => $hidden_items ], 200 );
    }

    /**
     * Add a new item to a timeline.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function add_timeline_item( $request ) {
        global $wpdb;
        $timeline_id = (int) $request['id'];
        $params = $request->get_json_params();

        $table_name = $wpdb->prefix . 'study_timeline_items';

        $result = $wpdb->insert(
            $table_name,
            [
                'timeline_id'      => $timeline_id,
                'post_id'          => $params['post_id'],
                'item_date'        => $params['item_date'],
                'item_lane'        => $params['item_lane'],
                'item_shape'       => $params['item_shape'],
                'item_color'       => $params['item_color'] ?? '',
                'added_by_user_id' => get_current_user_id(),
                'creation_date'    => current_time( 'mysql', 1 ),
            ],
            [ '%d', '%d', '%s', '%d', '%s', '%s', '%d', '%s' ]
        );

        if ( $result === false ) {
            return new WP_Error( 'db_insert_error', 'Could not insert item into the database.', [ 'status' => 500 ] );
        }

        $new_item_id = $wpdb->insert_id;
        // Fetch the newly created item to return it fully formed
        $new_item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $new_item_id ) );

        // Enhance with post data for immediate correct rendering on the frontend
        if ($new_item) {
            $post = get_post( $new_item->post_id );
            if ( $post ) {
                $new_item->post_title = esc_html($post->post_title);
                $new_item->post_type = $post->post_type;
                $new_item->thumbnail_url = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );
            }
        }

        return new WP_REST_Response( $new_item, 201 );
    }

    /**
     * Custom validation for datetime string.
     */
    public function is_valid_datetime( $date_str ) {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $date_str);
        return $d && $d->format('Y-m-d H:i:s') === $date_str;
    }

    /**
     * Check if a given request has permission to edit timeline data.
     * For now, any logged-in user can edit. Should be more specific later.
     */
    public function can_edit_timeline( $request ) {
        return is_user_logged_in();
    }

    /**
     * Get a list of draggable items for the repository.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_repository_items( $request ) {
        // Define which categories are relevant for the timeline.
        $category_slugs = [
            'מערך-שיעור' => 0, // Maps slug to lane 0
            'פעילות'      => 1, // Maps slug to lane 1
        ];

        // --- Start Filtering Logic ---
        $tax_query = [
            'relation' => 'AND',
        ];

        // Filter by main categories (lesson-plan, activity)
        $category_ids = get_terms([
            'slug'   => array_keys($category_slugs),
            'fields' => 'ids',
            'hide_empty' => false,
        ]);
        $tax_query[] = [
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => $category_ids,
        ];

        // Filter by Subject (custom taxonomy)
        $subject_id = $request->get_param( 'subject_id' );
        if ( ! empty( $subject_id ) && is_numeric( $subject_id ) ) {
            $tax_query[] = [
                'taxonomy' => 'Subjects', // Replaced placeholder
                'field'    => 'term_id',
                'terms'    => (int) $subject_id,
            ];
        }

        // Filter by Grade (custom taxonomy)
        $grade_id = $request->get_param( 'grade_id' );
        if ( ! empty( $grade_id ) && is_numeric( $grade_id ) ) {
            $tax_query[] = [
                'taxonomy' => 'Classes', // Replaced placeholder
                'field'    => 'term_id',
                'terms'    => (int) $grade_id,
            ];
        }
        // --- End Filtering Logic ---

        $args = [
            'post_type'      => 'post', 
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'tax_query'      => $tax_query,
        ];

        $query = new WP_Query( $args );
        $items = [];

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                $post_categories = wp_get_post_categories($post_id, ['fields' => 'slugs']);
                
                // Determine which lane this post belongs to
                $lane = null;
                foreach($post_categories as $slug) {
                    if (isset($category_slugs[$slug])) {
                        $lane = $category_slugs[$slug];
                        break;
                    }
                }

                $items[] = [
                    'id'    => $post_id,
                    'title' => get_the_title(),
                    'lane'  => $lane, // Pass the pre-determined lane to the frontend
                ];
            }
        }
        wp_reset_postdata();

        return new WP_REST_Response( $items, 200 );
    }

    /**
     * Route to get all available terms for repository filters.
     */
    public function get_repository_filters( $request ) {
        // Replace with actual taxonomy slugs
        $taxonomies = [
            'subjects' => 'Subjects',
            'grades'   => 'Classes',
        ];

        $filters = [];
        foreach ($taxonomies as $key => $slug) {
            $terms = get_terms( [
                'taxonomy'   => $slug,
                'hide_empty' => false, // Set to false to show all possible filters
            ] );
            // Ensure we don't return an error object
            if ( ! is_wp_error( $terms ) ) {
                $filters[$key] = $terms;
            } else {
                $filters[$key] = [];
            }
        }

        return new WP_REST_Response( $filters, 200 );
    }

    /**
     * Get full data for a single timeline.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_timeline_data( $request ) {
        global $wpdb;
        $timeline_id = (int) $request['id'];

        $tables = [
            'topics' => $wpdb->prefix . 'study_timeline_topics',
            'items'  => $wpdb->prefix . 'study_timeline_items',
        ];

        // Fetch topics
        $topics = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, title, start_date, end_date, color FROM {$tables['topics']} WHERE timeline_id = %d ORDER BY position ASC",
            $timeline_id
        ) );

        // Fetch items
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, post_id, item_date, item_lane, item_shape, item_color FROM {$tables['items']} WHERE timeline_id = %d",
            $timeline_id
        ) );
        
        // Enhance items with post data
        foreach ( $items as $item ) {
            $post = get_post( $item->post_id );
            if ( $post ) {
                $item->post_title = esc_html($post->post_title);
                $item->post_type = $post->post_type;
                $item->post_url = get_permalink($post->ID);
                $item->thumbnail_url = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );
            } else {
                $item->post_title = 'Invalid Post';
                $item->post_type = 'invalid';
                $item->post_url = '#';
                $item->thumbnail_url = '';
            }
        }

        $data = [
            'id'     => $timeline_id,
            'topics' => $topics,
            'items'  => $items,
        ];

        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Check if a given request has permission to view timeline data.
     *
     * For now, we just check if the user is logged in.
     * In the future, this should check for group/class membership.
     *
     * @param  WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function can_view_timeline( $request ) {
        // For now, let's keep it simple. Any logged-in user can view.
        // This should be expanded to check for group/class membership.
        return is_user_logged_in();
    }
}
