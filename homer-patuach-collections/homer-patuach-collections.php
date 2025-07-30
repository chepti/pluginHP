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
 * Add the creator's name to the collection archive page title.
 */
function hpc_add_creator_to_collection_archive_title( $title ) {
    if ( is_tax('collection') ) {
        $term = get_queried_object();
        if ($term && isset($term->term_id)) {
            $user_id = get_term_meta($term->term_id, 'hpc_user_id', true);
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

        echo '</div>'; // End .hpc-collection-item-main

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