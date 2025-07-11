<?php
/**
 * Plugin Name:       Homer Patuach - Collections
 * Plugin URI:        https://homerpatuach.com/
 * Description:       Allows users to create and manage collections of posts.
 * Version:           1.2.0
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
        'rewrite'           => array( 'slug' => 'collection' ),
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
add_filter( 'the_content', 'hpc_add_collections_ui_to_content' );

function hpc_add_collections_ui_to_content( $content ) {
    // Only add the button to the main post content on single post pages
    if ( is_single() && 'post' === get_post_type() && in_the_loop() && is_main_query() ) {
        // We need to capture the output of our function
        ob_start();
        hpc_add_collections_ui();
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

            $data[] = [
                'id' => $collection->term_id,
                'name' => esc_html( $collection->name ),
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
    check_ajax_referer( 'hpc_collections_nonce', 'nonce' );

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( ['message' => 'User not logged in.'] );
    }

    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    $collection_id = isset( $_POST['collection_id'] ) ? intval( $_POST['collection_id'] ) : 0;

    if ( ! $post_id || ! $collection_id ) {
        wp_send_json_error( ['message' => 'Invalid data provided.'] );
    }

    // Verify the collection belongs to the user by checking term meta
    $collection_user_id = get_term_meta( $collection_id, 'hpc_user_id', true );
    if ( (int)$collection_user_id !== $user_id ) {
        wp_send_json_error( ['message' => 'Invalid collection ownership.'] );
    }
    
    // Verify the post is a 'post'
    if ( get_post_type( $post_id ) !== 'post' ) {
         wp_send_json_error( ['message' => 'Invalid post.' ] );
    }

    // Check if the term is already associated with the post
    if ( has_term( $collection_id, 'collection', $post_id ) ) {
        // If yes, remove it
        $result = wp_remove_object_terms( $post_id, $collection_id, 'collection' );
        $action = 'removed';
    } else {
        // If no, add it (append)
        $result = wp_set_post_terms( $post_id, $collection_id, 'collection', true );
        $action = 'added';
    }

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( ['message' => $result->get_error_message()] );
    } else {
        wp_send_json_success( ['message' => 'Collection updated!', 'action' => $action] );
    }
}
add_action( 'wp_ajax_hpc_add_post_to_collection', 'hpc_add_post_to_collection' );


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
 * Display the collections a post belongs to on the single post page.
 */
function hpc_display_post_collections( $content ) {
    // Only on single post pages, and not in feeds or other loops.
    if ( is_single() && 'post' === get_post_type() && in_the_loop() && is_main_query() ) {
        $post_id = get_the_ID();
        $collections = wp_get_post_terms( $post_id, 'collection' );

        $user_collections = [];
        if ( ! empty( $collections ) && ! is_wp_error( $collections ) ) {
            foreach($collections as $collection) {
                // We only want to show collections that are user-created (have our meta key)
                if ( get_term_meta( $collection->term_id, 'hpc_user_id', true ) ) {
                    $user_collections[] = $collection;
                }
            }
        }

        if ( ! empty($user_collections) ) {
            $output = '<div class="hpc-post-collections-list">';
            $output .= '<h4>נמצא באוספים:</h4>';
            $output .= '<ul>';
            foreach ( $user_collections as $collection ) {
                 $collection_link = get_term_link( $collection, 'collection' );
                 if ( ! is_wp_error( $collection_link ) ) {
                     $output .= '<li><a href="' . esc_url( $collection_link ) . '">' . esc_html( $collection->name ) . '</a></li>';
                 }
            }
            $output .= '</ul>';
            $output .= '</div>';

            $content .= $output;
        }
    }
    return $content;
}
add_filter( 'the_content', 'hpc_display_post_collections' );


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
        // Link to the collection page
        $collection_link = get_term_link( $collection );

        echo '<div class="hpc-collection-item">';
        
        if ( ! is_wp_error( $collection_link ) ) {
            echo '<h3><a href="' . esc_url( $collection_link ) . '">' . esc_html( $collection->name ) . '</a></h3>';
        } else {
            echo '<h3>' . esc_html( $collection->name ) . '</h3>';
        }


        // Query for posts in this collection
        $args = array(
            'post_type'      => 'post',
            'posts_per_page' => 5,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'collection',
                    'field'    => 'term_id',
                    'terms'    => $collection->term_id,
                ),
            ),
        );

        $posts_in_collection = new WP_Query( $args );

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
            echo '<p class="hpc-empty-collection-message">אוסף זה ריק. אפשר להוסיף פוסטים דרך עמוד הפוסט.</p>';
        }
        wp_reset_postdata();

        echo '</div>'; // .hpc-collection-posts-preview

        // Add Post to Collection UI
        echo '<div class="hpc-add-post-to-collection-ui">';
        echo '  <button class="hpc-open-search-button">+ הוסף פוסטים</button>';
        echo '  <div class="hpc-search-area" style="display: none;">';
        echo '      <div class="hpc-search-wrapper">';
        echo '          <input type="text" class="hpc-post-search-input" placeholder="חפש/י פוסטים לפי שם..." data-collection-id="' . esc_attr($collection->term_id) . '">';
        echo '      </div>';
        echo '      <div class="hpc-search-results"></div>';
        echo '  </div>';
        echo '</div>';


        echo '</div>'; // .hpc-collection-item
    }

    echo '</div>'; // .hpc-collections-grid
} 