<?php
/**
 * Plugin Name:       Homer Patuach - Collections
 * Plugin URI:        https://homerpatuach.com/
 * Description:       Allows users to create and manage collections of posts.
 * Version:           1.3.1
 * Author:            Chepti
 * Author URI:        https://homerpatuach.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       homer-patuach-collections
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'HP_COLLECTIONS_VERSION', '1.0.0' );
define( 'HP_COLLECTIONS_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register the "Collection" taxonomy.
 * This will be used to group posts into user-created collections.
 */
function hpc_register_collection_taxonomy() {

    $labels = array(
        'name'              => _x( 'Collections', 'taxonomy general name', 'homer-patuach-collections' ),
        'singular_name'     => _x( 'Collection', 'taxonomy singular name', 'homer-patuach-collections' ),
        'search_items'      => __( 'Search Collections', 'homer-patuach-collections' ),
        'all_items'         => __( 'All Collections', 'homer-patuach-collections' ),
        'parent_item'       => __( 'Parent Collection', 'homer-patuach-collections' ),
        'parent_item_colon' => __( 'Parent Collection:', 'homer-patuach-collections' ),
        'edit_item'         => __( 'Edit Collection', 'homer-patuach-collections' ),
        'update_item'       => __( 'Update Collection', 'homer-patuach-collections' ),
        'add_new_item'      => __( 'Add New Collection', 'homer-patuach-collections' ),
        'new_item_name'     => __( 'New Collection Name', 'homer-patuach-collections' ),
        'menu_name'         => __( 'Collections', 'homer-patuach-collections' ),
    );

    $args = array(
        'hierarchical'      => false, // Collections are not nested like categories
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'collection', 'with_front' => false ),
        'public'            => true,
        'show_in_rest'      => true, // Make it available to the block editor & REST API
        'meta_box_cb'       => false, // We will create a custom UI, not the default meta box.
    );

    register_taxonomy( 'collection', array( 'post' ), $args );

}
add_action( 'init', 'hpc_register_collection_taxonomy', 0 );


/**
 * Enqueue scripts and styles for the collections feature.
 */
function hpc_enqueue_assets() {
    // Load on single posts for the "Add to Collection" button, and on BuddyPress profile pages for the collections tab.
    if ( (is_single() && 'post' === get_post_type()) || (function_exists('bp_is_user') && bp_is_user()) ) {
        wp_enqueue_style(
            'hpc-styles',
            HP_COLLECTIONS_PLUGIN_DIR_URL . 'assets/css/style.css',
            [],
            HP_COLLECTIONS_VERSION
        );

        wp_enqueue_script(
            'hpc-main-js',
            HP_COLLECTIONS_PLUGIN_DIR_URL . 'assets/js/main.js',
            ['jquery'],
            HP_COLLECTIONS_VERSION,
            true // Load in footer
        );
    }
}
add_action( 'wp_enqueue_scripts', 'hpc_enqueue_assets' );


/**
 * Add the "Add to Collection" button and modal to the single post page.
 * Hooks before the comments form for better theme compatibility.
 */
function hpc_add_collections_ui() {
    // Only on single post pages for logged-in users.
    if ( is_single() && 'post' === get_post_type() && is_user_logged_in() ) {
        
        $button_html = '<div class="hpc-button-container"><button id="hpc-open-modal-button" class="hpc-button" data-post-id="' . get_the_ID() . '">הוסף לאוסף</button></div>';
        
        $modal_html = '
        <div id="hpc-modal-overlay" class="hpc-modal-hidden">
            <div id="hpc-modal-container">
                <header id="hpc-modal-header">
                    <h2>הוסף לאוסף</h2>
                    <button id="hpc-close-modal-button">&times;</button>
                </header>
                <div id="hpc-modal-content">
                    <div id="hpc-user-collections-list">
                        <!-- User\'s collections will be loaded here via AJAX -->
                        <p>טוען אוספים...</p>
                    </div>
                    <div id="hpc-new-collection-form">
                        <input type="text" id="hpc-new-collection-name" placeholder="או צור אוסף חדש..." />
                        <button id="hpc-create-collection-button">צור</button>
                    </div>
                </div>
            </div>
        </div>';

        // Echo the button and the modal.
        echo $button_html . $modal_html;
    }
}
// This hook was incorrect. We will use the wrapper function below.
// add_action( 'comments_template', 'hpc_add_collections_ui', 10 );

/**
 * Append the collections UI to the end of the post content.
 * This is a more reliable hook than 'comments_template'.
 * @param string $content The post content.
 * @return string The modified post content.
 */
function hpc_add_collections_ui_to_content( $content ) {
    // Only add the button to the main post content on single post pages for logged-in users.
    if ( is_single() && 'post' === get_post_type() && in_the_loop() && is_main_query() && is_user_logged_in() ) {
        // We need to capture the output of our function
        ob_start();
        hpc_add_collections_ui(); // This function now only generates the HTML
        $collections_ui = ob_get_clean();
        return $content . $collections_ui;
    }
    return $content;
}

/**
 * =================================================================
 * AJAX HANDLERS
 * =================================================================
 */

/**
 * Localize script to pass PHP variables to JS, like the AJAX URL and nonce.
 */
function hpc_localize_script() {
    // We need the ajax object on single posts and BP user profiles.
    if ( (is_single() && 'post' === get_post_type()) || (function_exists('bp_is_user') && bp_is_user()) ) {
        wp_localize_script( 'hpc-main-js', 'hpc_ajax_object', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'hpc_collections_nonce' ),
        ) );
    }
}
add_action( 'wp_enqueue_scripts', 'hpc_localize_script', 99 );


/**
 * AJAX handler to get a user's collections.
 */
function hpc_get_user_collections() {
    // Security check
    check_ajax_referer( 'hpc_collections_nonce', 'nonce' );

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( ['message' => 'User not logged in.'] );
    }

    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

    // Get terms with meta query
    $collections = get_terms( array(
        'taxonomy'   => 'collection',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key'     => 'hpc_user_id',
                'value'   => $user_id,
                'compare' => '=',
            ),
        ),
    ) );
    
    $data = [];
    if ( ! is_wp_error( $collections ) && ! empty($collections) ) {
        foreach ( $collections as $collection ) {
            // Check if the current post is already in this collection
            $is_in_collection = has_term( $collection->term_id, 'collection', $post_id );
            $collection_link = get_term_link( $collection );

            $data[] = [
                'id' => $collection->term_id,
                'name' => esc_html( $collection->name ),
                'url' => is_wp_error($collection_link) ? '' : esc_url($collection_link),
                'is_in_collection' => $is_in_collection,
            ];
        }
    }
    
    wp_send_json_success( $data );
}
add_action( 'wp_ajax_hpc_get_user_collections', 'hpc_get_user_collections' );


/**
 * AJAX handler to create a new collection.
 */
function hpc_create_new_collection() {
    // Security check
    check_ajax_referer( 'hpc_collections_nonce', 'nonce' );

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( ['message' => 'User not logged in.'] );
    }

    if ( empty( $_POST['name'] ) ) {
        wp_send_json_error( ['message' => 'Collection name is required.'] );
    }

    $collection_name = sanitize_text_field( $_POST['name'] );
    
    // We don't need the (user_id) suffix anymore, but we should still check
    // if a user already has a collection with the same name.
    $existing_terms = get_terms([
        'taxonomy' => 'collection',
        'name' => $collection_name,
        'hide_empty' => false,
        'meta_query' => [
            [
                'key' => 'hpc_user_id',
                'value' => $user_id,
            ]
        ]
    ]);

    if (!empty($existing_terms)) {
         wp_send_json_error( ['message' => 'כבר קיים אוסף בשם זה.'] );
    }


    $result = wp_insert_term( $collection_name, 'collection' );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( ['message' => $result->get_error_message()] );
    }

    // Add user ID as term meta
    add_term_meta( $result['term_id'], 'hpc_user_id', $user_id, true );

    wp_send_json_success( [
        'id'   => $result['term_id'],
        'name' => $collection_name,
    ] );
}
add_action( 'wp_ajax_hpc_create_new_collection', 'hpc_create_new_collection' );


/**
 * AJAX handler to add a post to a collection.
 */
function hpc_add_post_to_collection() {
    // Security check
    if (!check_ajax_referer('hpc_collections_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Nonce check failed.']);
        return;
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'User not logged in.']);
        return;
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $collection_id = isset($_POST['collection_id']) ? intval($_POST['collection_id']) : 0;

    if (!$post_id || !$collection_id) {
        wp_send_json_error(['message' => 'Invalid data provided.']);
        return;
    }

    // --- CRITICAL VALIDATION STEP ---
    // Get the term object to ensure it exists before proceeding.
    $term = get_term($collection_id, 'collection');
    if (!$term || is_wp_error($term)) {
        wp_send_json_error(['message' => 'Invalid collection specified.']);
        return;
    }

    // Verify the collection belongs to the user by checking term meta
    $collection_user_id = get_term_meta($term->term_id, 'hpc_user_id', true);
    if ((int) $collection_user_id !== $user_id) {
        wp_send_json_error(['message' => 'Invalid collection ownership.']);
        return;
    }

    // Verify the post is a 'post'
    if (get_post_type($post_id) !== 'post') {
        wp_send_json_error(['message' => 'Invalid post.']);
        return;
    }

    $action = '';
    $term_ids = wp_get_object_terms($post_id, 'collection', ['fields' => 'ids']);

    if (in_array($collection_id, $term_ids)) {
        // Post is in collection, so remove it
        $action = 'removed';
        $term_ids = array_diff($term_ids, [$collection_id]);
    } else {
        // Post is not in collection, so add it
        $action = 'added';
        $term_ids[] = $collection_id;
    }

    $result = wp_set_object_terms($post_id, $term_ids, 'collection', false);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => 'שגיאה בעדכון מסד הנתונים.']);
    } else {
        wp_send_json_success(['message' => 'פעולה הושלמה בהצלחה.', 'action' => $action]);
    }
}
add_action('wp_ajax_hpc_add_post_to_collection', 'hpc_add_post_to_collection');


/**
 * A temporary capabilities filter to allow a user to edit a specific term.
 */
function hpc_allow_term_edit_for_this_request( $allcaps, $caps, $args ) {
    // We are checking for 'edit_term'. $args are [$cap, $user_id, $term_id].
    if ( isset( $args[0] ) && 'edit_term' === $args[0] && isset( $args[2] ) ) {
        // The ownership check is performed in the AJAX handler before adding this filter.
        // So, if we are here, we can grant permission for this specific request.
        $allcaps['edit_term'] = true;
    }
    return $allcaps;
}


/**
 * AJAX handler to update a collection's description.
 */
function hpc_update_collection_description() {
    // Security check
    check_ajax_referer('hpc_collections_nonce', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => 'User not logged in.']);
    }

    $collection_id = isset($_POST['collection_id']) ? intval($_POST['collection_id']) : 0;
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

    if (!$collection_id) {
        wp_send_json_error(['message' => 'Invalid data provided.']);
    }

    // Verify the collection belongs to the user
    $collection_user_id = get_term_meta($collection_id, 'hpc_user_id', true);
    if ((int) $collection_user_id !== $user_id) {
        wp_send_json_error(['message' => 'Invalid collection ownership.']);
    }

    // Temporarily grant permission to edit the term.
    add_filter( 'user_has_cap', 'hpc_allow_term_edit_for_this_request', 10, 3 );

    // Update the term description
    $result = wp_update_term($collection_id, 'collection', [
        'description' => $description,
    ]);

    // Immediately remove the filter to restore original permissions.
    remove_filter( 'user_has_cap', 'hpc_allow_term_edit_for_this_request', 10, 3 );


    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => 'Description updated successfully.']);
}
add_action('wp_ajax_hpc_update_collection_description', 'hpc_update_collection_description');


/**
 * AJAX handler to search for posts to add to a collection.
 */
function hpc_search_posts_for_collection() {
    // Security check
    check_ajax_referer( 'hpc_collections_nonce', 'nonce' );

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( ['message' => 'User not logged in.'] );
    }
    
    $search_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    $collection_id = isset($_POST['collection_id']) ? intval($_POST['collection_id']) : 0;

    if (empty($search_query) || !$collection_id) {
        wp_send_json_success( [] ); // Return empty success if no query
        return;
    }

    // Verify collection ownership
    $collection_user_id = get_term_meta( $collection_id, 'hpc_user_id', true );
    if ( (int)$collection_user_id !== $user_id ) {
        wp_send_json_error( ['message' => 'Invalid collection ownership.'] );
    }

    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        's'              => $search_query, // 's' is the search parameter
    );

    $query = new WP_Query($args);
    $posts = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $posts[] = [
                'id' => $post_id,
                'title' => get_the_title(),
                'is_in_collection' => has_term($collection_id, 'collection', $post_id)
            ];
        }
    }
    wp_reset_postdata();

    wp_send_json_success($posts);
}
add_action('wp_ajax_hpc_search_posts_for_collection', 'hpc_search_posts_for_collection');


/**
 * =================================================================
 * COLLECTION METADATA (LIKES & VIEWS)
 * =================================================================
 */

// --- 1. Helper functions ---

function hpc_get_collection_likes( $term_id ) {
    $count = get_term_meta( $term_id, 'hpc_like_count', true );
    return $count ? (int) $count : 0;
}

function hpc_get_collection_views( $term_id ) {
    $count = get_term_meta( $term_id, 'hpc_view_count', true );
    return $count ? (int) $count : 0;
}

function hpc_has_user_liked_collection( $term_id, $user_id ) {
    if ( ! $user_id ) return false;
    $liked_collections = get_user_meta( $user_id, 'hpc_liked_collections', true );
    if ( ! is_array( $liked_collections ) ) {
        $liked_collections = [];
    }
    return in_array( $term_id, $liked_collections );
}

// --- 2. Track Views ---

function hpc_track_collection_views() {
    if ( is_tax('collection') ) {
        $term = get_queried_object();
        if ( $term && isset($term->term_id) ) {
            // Simple tracking without checking for unique users to keep it lightweight.
            $count = hpc_get_collection_views( $term->term_id );
            update_term_meta( $term->term_id, 'hpc_view_count', $count + 1 );
        }
    }
}
add_action( 'wp_head', 'hpc_track_collection_views' );

// --- 3. Handle Likes via AJAX ---

function hpc_like_collection_ajax_handler() {
    check_ajax_referer( 'hpc_collections_nonce', 'nonce' );

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( ['message' => 'יש להתחבר כדי לסמן בלייק.'] );
    }

    $term_id = isset( $_POST['collection_id'] ) ? intval( $_POST['collection_id'] ) : 0;
    if ( ! $term_id ) {
        wp_send_json_error( ['message' => 'אוסף לא חוקי.'] );
    }

    $liked_collections = get_user_meta( $user_id, 'hpc_liked_collections', true );
    if ( ! is_array( $liked_collections ) ) {
        $liked_collections = [];
    }

    $current_likes = hpc_get_collection_likes( $term_id );
    $user_has_liked = in_array( $term_id, $liked_collections );

    if ( $user_has_liked ) {
        // --- Unlike ---
        $new_count = max( 0, $current_likes - 1 );
        // Remove term_id from user's liked list
        $liked_collections = array_diff( $liked_collections, [$term_id] );
    } else {
        // --- Like ---
        $new_count = $current_likes + 1;
        // Add term_id to user's liked list
        $liked_collections[] = $term_id;
    }

    update_term_meta( $term_id, 'hpc_like_count', $new_count );
    update_user_meta( $user_id, 'hpc_liked_collections', $liked_collections );

    wp_send_json_success([
        'new_count' => $new_count,
        'user_has_liked' => ! $user_has_liked,
    ]);
}
add_action('wp_ajax_hpc_like_collection', 'hpc_like_collection_ajax_handler');
 
/**
 * Add the creator's name to the collection archive page title.
 */
function hpc_add_creator_to_collection_archive_title( $title ) {
    if ( is_tax('collection') ) {
        $term = get_queried_object();
        if ($term && isset($term->term_id)) {
            $user_id = get_term_meta($term->term_id, 'hpc_user_id', true);

            // Get stats
            $views = hpc_get_collection_views( $term->term_id );
            $likes = hpc_get_collection_likes( $term->term_id );
            $user_has_liked = hpc_has_user_liked_collection( $term->term_id, get_current_user_id() );
            $like_btn_class = $user_has_liked ? 'hpc-like-button liked' : 'hpc-like-button';
            $like_btn_text = $user_has_liked ? 'אהבתי' : 'לייק';

            // Meta line
            $meta_html = '
            <div class="hpc-archive-meta">
                <span class="hpc-meta-item"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5zm0 10c-2.48 0-4.5-2.02-4.5-4.5S9.52 5.5 12 5.5s4.5 2.02 4.5 4.5-2.02 4.5-4.5 4.5zm0-7c-1.38 0-2.5 1.12-2.5 2.5s1.12 2.5 2.5 2.5 2.5-1.12 2.5-2.5-1.12-2.5-2.5-2.5z"/></svg> ' . number_format_i18n($views) . '</span>
                <span class="hpc-meta-item hpc-likes-count"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg> <span class="count">' . number_format_i18n($likes) . '</span></span>';

            if (is_user_logged_in()) {
                $meta_html .= '
                <button class="' . esc_attr($like_btn_class) . '" data-collection-id="' . esc_attr($term->term_id) . '">
                    <span class="like-text">' . $like_btn_text . '</span>
                </button>';
            }

            $meta_html .= '</div>';


            if ($user_id) {
                $user_info = get_userdata($user_id);
                if ($user_info) {
                    $creator_name = esc_html($user_info->display_name);
                    $creator_html = $creator_name;

                    // If BuddyPress is active, link to the user's profile
                    if ( function_exists('bp_core_get_user_domain') ) {
                        $user_link = bp_core_get_user_domain($user_id);
                        $creator_html = '<a href="' . esc_url($user_link) . '">' . $creator_name . '</a>';
                    }

                    // The new structure with divs for separate lines and a space
                    $title = '<div class="hpc-archive-title-wrapper">';
                    $title .= '<h1 class="hpc-archive-main-title">' . esc_html($term->name) . '</h1>';
                    $title .= '<div class="hpc-archive-creator">אוסף מאת ' . $creator_html . '</div>';
                    $title .= $meta_html; // Add the meta line here
                    $title .= '</div>';
                }
            }
        }
    }
    return $title;
}
add_filter( 'get_the_archive_title', 'hpc_add_creator_to_collection_archive_title', 11 );


/**
 * =================================================================
 * BUDDYPRESS INTEGRATION
 * =================================================================
 */

/**
 * Add a new "My Collections" tab to the user's profile.
 */
function hpc_setup_nav() {
    // Exit if BuddyPress is not active
    if ( ! function_exists( 'buddypress' ) ) {
        return;
    }

    bp_core_new_nav_item( array(
        'name'                => 'האוספים שלי',
        'slug'                => 'collections',
        'position'            => 2, // Right after "My Posts" which is at 1
        'screen_function'     => 'hpc_collections_screen',
        'default_subnav_slug' => 'collections',
        'user_has_access'     => bp_is_my_profile(), // Only show on the logged-in user's profile
    ) );
}
add_action( 'bp_setup_nav', 'hpc_setup_nav' );

/**
 * The content of the "My Collections" tab.
 */
function hpc_collections_screen() {
    add_action( 'bp_template_content', 'hpc_collections_screen_content' );
    bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

function hpc_collections_screen_content() {
    $displayed_user_id = bp_displayed_user_id();
    if ( ! $displayed_user_id ) {
        return;
    }

    echo '<h2>האוספים שלי</h2>';

    // Get all collection terms for the displayed user using meta query
    $collections = get_terms( array(
        'taxonomy'   => 'collection',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key'     => 'hpc_user_id',
                'value'   => $displayed_user_id,
                'compare' => '=',
            ),
        ),
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );

    if ( is_wp_error( $collections ) || empty( $collections ) ) {
        echo '<div id="message" class="info"><p>עדיין לא יצרת אוספים. אפשר להתחיל בעמוד של כל פוסט שתאהב/י.</p></div>';
        return;
    }

    echo '<div class="hpc-collections-grid">';
 
     foreach ( $collections as $collection ) {
        $collection_link = get_term_link( $collection );

        echo '<div class="hpc-collection-item">'; // Start item
        
        echo '<div class="hpc-collection-item-main">'; // Main content area

            // Header
            if ( ! is_wp_error( $collection_link ) ) {
                echo '<h3><a href="' . esc_url( $collection_link ) . '">' . esc_html( $collection->name ) . '</a></h3>';
            } else {
                echo '<h3>' . esc_html( $collection->name ) . '</h3>';
            }

            // Query for posts in this collection
            $posts_in_collection = new WP_Query([
                'post_type'      => 'post',
                'posts_per_page' => 5,
                'tax_query'      => [
                    [
                        'taxonomy' => 'collection',
                        'field'    => 'term_id',
                        'terms'    => $collection->term_id,
                    ],
                ],
            ]);

            if ( $posts_in_collection->have_posts() ) {
                echo '<div class="hpc-collection-posts-preview">';
                while ( $posts_in_collection->have_posts() ) {
                    $posts_in_collection->the_post();
                    if ( has_post_thumbnail() ) {
                         echo '<a href="' . get_permalink() . '" title="' . esc_attr(get_the_title()) . '">';
                         the_post_thumbnail( 'thumbnail' );
                         echo '</a>';
                    }
                }
                echo '</div>'; // .hpc-collection-posts-preview
                 // Show "view all" only if there are more posts than the preview shows
                if ( $posts_in_collection->found_posts > 5 && ! is_wp_error( $collection_link ) ) {
                    echo '<a href="' . esc_url( $collection_link ) . '" class="hpc-view-all-link">הצג הכל (' . $posts_in_collection->found_posts . ')</a>';
                }
            } else {
                echo '<div class="hpc-empty-collection-wrapper"><p class="hpc-empty-collection-message">אוסף זה ריק.</p></div>';
            }
            wp_reset_postdata();

            // Only show editing UI if the logged-in user is viewing their own profile.
            if ( bp_is_my_profile() ) {
                // Add the description editor
                echo '<div class="hpc-collection-description-editor">';
                echo '<textarea class="hpc-collection-description-input" data-collection-id="' . esc_attr($collection->term_id) . '" placeholder="הוסף תיאור קצר לאוסף...">' . esc_textarea($collection->description) . '</textarea>';
                echo '<button class="hpc-save-description-button" data-collection-id="' . esc_attr($collection->term_id) . '">שמור תיאור</button>';
                echo '<span class="hpc-save-success-msg" style="display:none;">נשמר!</span>';
                echo '</div>'; // .hpc-collection-description-editor
            }


        echo '</div>'; // End .hpc-collection-item-main

        // Only show editing UI if the logged-in user is viewing their own profile.
        if ( bp_is_my_profile() ) {
            echo '<div class="hpc-collection-item-footer">'; // Footer for actions
                // Add Post to Collection UI
                echo '<div class="hpc-add-post-to-collection-ui">';
                echo '  <button class="hpc-open-search-button" data-collection-id="' . esc_attr($collection->term_id) . '">+ הוסף פוסטים</button>';
                echo '  <div class="hpc-search-area" id="hpc-search-area-'.esc_attr($collection->term_id).'" style="display: none;">';
                echo '      <div class="hpc-search-wrapper">';
                echo '          <input type="text" class="hpc-post-search-input" placeholder="חפש/י פוסטים לפי שם..." data-collection-id="' . esc_attr($collection->term_id) . '">';
                echo '      </div>';
                echo '      <div class="hpc-search-results"></div>';
                echo '  </div>';
                echo '</div>';
            echo '</div>'; // End .hpc-collection-item-footer
        }

        echo '</div>'; // End .hpc-collection-item
    }
 
      echo '</div>'; // .hpc-collections-grid
 } 

/**
 * Displays the list of collections a post belongs to on the single post page.
 */
function hpc_display_post_collections_list() {
    if ( !is_single() || !in_the_loop() ) {
        return;
    }

    $post_id = get_the_ID();
    
    // We get ALL collections this post is part of, regardless of owner, to show everyone.
    $collections = get_the_terms( $post_id, 'collection' );

    if ( empty( $collections ) || is_wp_error( $collections ) ) {
        return;
    }

    $collections_html = '<div class="hpc-post-collections-list-container">';
    $collections_html .= '<h3>' . esc_html__( 'מופיע באוספים הבאים:', 'homer-patuach-collections' ) . '</h3>';
    $collections_html .= '<ul class="hpc-post-collections-list">';

    $count = 0;
    foreach ( $collections as $collection ) {
        if ($count >= 4) break; // Limit to 4 collections
        $collection_link = get_term_link( $collection );
        $collections_html .= '<li><a href="' . esc_url( $collection_link ) . '">' . esc_html( $collection->name ) . ' <span class="collection-count">(' . $collection->count . ')</span></a></li>';
        $count++;
    }

    $collections_html .= '</ul></div>';
    
    echo $collections_html;
}