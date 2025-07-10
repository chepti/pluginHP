<?php
/**
 * Plugin Name:       Homer Patuach Grid
 * Description:       Displays a filterable grid of posts.
 * Version:           1.3.0
 * Author:            chepti
 * Text Domain:       homer-patuach-grid
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'HPG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HPG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// 1. Register Custom Taxonomies
function hpg_register_taxonomies() {
    // 'Subject' Taxonomy
    $subject_labels = [
        'name'              => _x( 'Subjects', 'taxonomy general name', 'hpg' ),
        'singular_name'     => _x( 'Subject', 'taxonomy singular name', 'hpg' ),
        'search_items'      => __( 'Search Subjects', 'hpg' ),
        'all_items'         => __( 'All Subjects', 'hpg' ),
        'parent_item'       => __( 'Parent Subject', 'hpg' ),
        'parent_item_colon' => __( 'Parent Subject:', 'hpg' ),
        'edit_item'         => __( 'Edit Subject', 'hpg' ),
        'update_item'       => __( 'Update Subject', 'hpg' ),
        'add_new_item'      => __( 'Add New Subject', 'hpg' ),
        'new_item_name'     => __( 'New Subject Name', 'hpg' ),
        'menu_name'         => __( 'תחום דעת', 'hpg' ),
    ];
    $subject_args = [
        'hierarchical'      => true,
        'labels'            => $subject_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => [ 'slug' => 'subject' ],
    ];
    register_taxonomy( 'subject', [ 'post' ], $subject_args );

    // 'Class' Taxonomy
    $class_labels = [
        'name'              => _x( 'Classes', 'taxonomy general name', 'hpg' ),
        'singular_name'     => _x( 'Class', 'taxonomy singular name', 'hpg' ),
        'search_items'      => __( 'Search Classes', 'hpg' ),
        'all_items'         => __( 'All Classes', 'hpg' ),
        'parent_item'       => __( 'Parent Class', 'hpg' ),
        'parent_item_colon' => __( 'Parent Class:', 'hpg' ),
        'edit_item'         => __( 'Edit Class', 'hpg' ),
        'update_item'       => __( 'Update Class', 'hpg' ),
        'add_new_item'      => __( 'Add New Class', 'hpg' ),
        'new_item_name'     => __( 'New Class Name', 'hpg' ),
        'menu_name'         => __( 'כיתה', 'hpg' ),
    ];
    $class_args = [
        'hierarchical'      => true,
        'labels'            => $class_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => [ 'slug' => 'class' ],
    ];
    register_taxonomy( 'class', [ 'post' ], $class_args );
}
add_action( 'init', 'hpg_register_taxonomies' );

// 2. Enqueue Scripts and Styles
function hpg_load_scripts() {
    // FINAL-FINAL FIX: Corrected paths to include the 'assets' directory.
    wp_enqueue_style( 'hpg-style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', array(), '1.3.2' ); 
    wp_enqueue_script( 'hpg-ajax', plugin_dir_url( __FILE__ ) . 'assets/js/frontend-ajax.js', array('jquery'), '1.3.2', true );

    wp_localize_script( 'hpg-ajax', 'hpg_globals', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'hpg-nonce' )
    ) );
    
    // Enqueue submission script for all users so the login alert works.
    wp_enqueue_script('hpg-submission-script', plugin_dir_url(__FILE__) . 'assets/js/submission.js', array('jquery'), '1.0.3', true);

    // Enqueue submission assets (CSS, media uploader) only for logged-in users, as they are the only ones who see the popup.
    if (is_user_logged_in()) {
        // Corrected paths for submission assets
        wp_enqueue_style('hpg-submission-style', plugin_dir_url(__FILE__) . 'assets/css/submission.css', array(), '1.0.3');
        
        // Needed for media uploader
        wp_enqueue_media();
    }
}
add_action('wp_enqueue_scripts', 'hpg_load_scripts');


// 3. Post Metadata Functions (Likes & Views)
function hpg_get_post_likes( $post_id ) {
    $count = get_post_meta( $post_id, '_hpg_like_count', true );
    return $count ? (int) $count : 0;
}

function hpg_get_post_views( $post_id ) {
    $count = get_post_meta( $post_id, '_hpg_view_count', true );
    return $count ? (int) $count : 0;
}

function hpg_track_post_views( $post_id = null ) {
    if ( ! is_single() ) return;
    if ( empty ( $post_id ) ) {
        global $post;
        $post_id = $post->ID;
    }
    if ( current_user_can('manage_options') ) return; // Don't count for admins

    $count = hpg_get_post_views( $post_id );
    update_post_meta( $post_id, '_hpg_view_count', $count + 1 );
}
add_action( 'wp_head', 'hpg_track_post_views' );


// Function to get the average rating for a post
function hpg_get_post_average_rating( $post_id ) {
    global $wpdb;
    $query = $wpdb->prepare( "
        SELECT AVG(meta.meta_value) 
        FROM {$wpdb->commentmeta} meta
        INNER JOIN {$wpdb->comments} comments ON meta.comment_id = comments.comment_ID
        WHERE comments.comment_post_ID = %d AND meta.meta_key = 'hpg_rating' AND comments.comment_approved = '1'
    ", $post_id );
    $average_rating = $wpdb->get_var( $query );
    return $average_rating ? round($average_rating, 1) : 0;
}


// 4. AJAX Handlers
function hpg_like_post_ajax_handler() {
    check_ajax_referer( 'hpg-nonce', 'nonce' );

    if ( isset( $_POST['post_id'] ) ) {
        $post_id = intval( $_POST['post_id'] );
        $count = hpg_get_post_likes( $post_id );
        $new_count = $count + 1;
        update_post_meta( $post_id, '_hpg_like_count', $new_count );
        wp_send_json_success( [ 'new_count' => $new_count ] );
    } else {
        wp_send_json_error( 'Invalid post ID' );
    }
}
add_action( 'wp_ajax_hpg_like_post', 'hpg_like_post_ajax_handler' );
add_action( 'wp_ajax_nopriv_hpg_like_post', 'hpg_like_post_ajax_handler' );

/**
 * AJAX handler for filtering posts.
 */
function hpg_filter_posts_handler() {
    // Nonce verification
    if ( !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hpg-nonce') ) {
        wp_send_json_error(array('html' => '<p>Nonce verification failed.</p>'));
        return;
    }

    $args = [
        'post_type'      => 'post',
        'posts_per_page' => -1, // You might want to add pagination later
        'post_status'    => 'publish',
    ];

    $tax_query = [];
    if ( ! empty( $_POST['subject_filter'] ) ) {
        $tax_query[] = [
            'taxonomy' => 'subject',
            'field'    => 'term_id',
            'terms'    => intval( $_POST['subject_filter'] ),
        ];
    }
    if ( ! empty( $_POST['class_filter'] ) ) {
        $tax_query[] = [
            'taxonomy' => 'class',
            'field'    => 'term_id',
            'terms'    => intval( $_POST['class_filter'] ),
        ];
    }
    if ( count( $tax_query ) > 0 ) {
        $tax_query['relation'] = 'AND';
        $args['tax_query'] = $tax_query;
    }

    if ( ! empty( $_POST['search_filter'] ) ) {
        $args['s'] = sanitize_text_field( $_POST['search_filter'] );
    }

    $query = new WP_Query( $args );

    ob_start();
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            echo hpg_get_post_card_html( get_the_ID() );
        }
    } else {
        echo '<p class="hpg-no-results">' . __( 'No items found that match your criteria.', 'hpg' ) . '</p>';
    }
    wp_reset_postdata();

    $html = ob_get_clean();
    wp_send_json_success( [ 'html' => $html ] );
}
add_action( 'wp_ajax_hpg_filter_posts', 'hpg_filter_posts_handler' );
add_action( 'wp_ajax_nopriv_hpg_filter_posts', 'hpg_filter_posts_handler' );

// 5. Shortcode
function hpg_render_grid_shortcode() {
    ob_start();
    ?>
    <div id="hpg-container">
        <form id="hpg-filters">
            <div class="hpg-filter-item hpg-search-item">
                <input type="search" name="search_filter" id="search_filter" placeholder="חפשו את כל מה שצריך..."/>
            </div>
            <div class="hpg-filter-item">
                <?php
                wp_dropdown_categories([
                    'taxonomy'        => 'class',
                    'name'            => 'class_filter',
                    'id'              => 'class_filter',
                    'show_option_all' => 'כל הכיתות',
                    'hierarchical'    => true,
                    'value_field'     => 'term_id',
                ]);
                ?>
            </div>
            <div class="hpg-filter-item">
                 <?php
                wp_dropdown_categories([
                    'taxonomy'        => 'subject',
                    'name'            => 'subject_filter',
                    'id'              => 'subject_filter',
                    'show_option_all' => 'כל התחומים',
                    'hierarchical'    => true,
                    'value_field'     => 'term_id',
                ]);
                ?>
            </div>
             <div class="hpg-filter-item">
                <button type="button" id="hpg-clear-filters">נקה סינון</button>
            </div>
        </form>

        <div id="hpg-results-container">
            <div id="hpg-loader" style="display: none;"><div class="spinner"></div></div>
            <?php
            $initial_query = new WP_Query([
                'post_type' => 'post',
                'posts_per_page' => -1,
                'post_status' => 'publish',
            ]);
            if ( $initial_query->have_posts() ) {
                while ( $initial_query->have_posts() ) {
                    $initial_query->the_post();
                    echo hpg_get_post_card_html( get_the_ID() );
                }
            } else {
                echo '<p class="hpg-no-results">' . __( 'No items found.', 'hpg' ) . '</p>';
            }
            wp_reset_postdata();
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'homer_patuach_grid', 'hpg_render_grid_shortcode' );


// 6. Helper function to render a single post card
function hpg_get_post_card_html( $post_id ) {
    $post_url = get_permalink( $post_id );
    $thumbnail_url = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'medium_large' ) : HPG_PLUGIN_URL . 'assets/placeholder.png'; // Make sure you have a placeholder image
    $title = get_the_title( $post_id );
    $date = get_the_date( 'j בM Y', $post_id );
    $average_rating = hpg_get_post_average_rating( $post_id );

    // Get subject term
    $subjects = get_the_terms( $post_id, 'subject' );
    $subject_name = '';
    if ( ! empty( $subjects ) && ! is_wp_error( $subjects ) ) {
        $subject_name = $subjects[0]->name;
    }

    $likes = hpg_get_post_likes( $post_id );
    $views = hpg_get_post_views( $post_id );
    $comments = get_comments_number( $post_id );

    ob_start();
    ?>
    <div class="hpg-card">
        <a href="<?php echo esc_url( $post_url ); ?>" class="hpg-card-link-wrapper">
            <div class="hpg-card-image" style="background-image: url('<?php echo esc_url( $thumbnail_url ); ?>');">
            </div>
             <?php if ( $average_rating > 0 ) : ?>
                <div class="hpg-card-rating">
                    <span class="rating-value"><?php echo esc_html( $average_rating ); ?></span>
                    <span class="star-icon">★</span>
                </div>
            <?php endif; ?>
            <div class="hpg-card-content">
                 <div class="hpg-card-meta-top">
                    <span class="hpg-card-date"><?php echo esc_html( $date ); ?></span>
                    <?php if ( $subject_name ) : ?>
                        <span class="hpg-card-subject-tag-content"><?php echo esc_html( $subject_name ); ?></span>
                    <?php endif; ?>
                </div>
                <h3 class="hpg-card-title"><?php echo esc_html( $title ); ?></h3>
            </div>
        </a>
         <div class="hpg-card-footer">
            <span class="hpg-meta-item hpg-like-btn" data-post-id="<?php echo esc_attr( $post_id ); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                <span class="hpg-like-count"><?php echo esc_html( $likes ); ?></span>
            </span>
            <span class="hpg-meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>
                <?php echo esc_html( $comments ); ?>
            </span>
             <span class="hpg-meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5zm0 10c-2.48 0-4.5-2.02-4.5-4.5S9.52 5.5 12 5.5s4.5 2.02 4.5 4.5-2.02 4.5-4.5 4.5zm0-7c-1.38 0-2.5 1.12-2.5 2.5s1.12 2.5 2.5 2.5 2.5-1.12 2.5-2.5-1.12-2.5-2.5-2.5z"/></svg>
                <?php echo esc_html( $views ); ?>
            </span>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Add a placeholder image if needed
function hpg_plugin_activation() {
    $upload_dir = wp_upload_dir();
    $assets_dir = HPG_PLUGIN_DIR . 'assets';
    if ( ! file_exists( $assets_dir ) ) {
        wp_mkdir_p( $assets_dir );
    }
    // You should manually add a 'placeholder.png' to an 'assets' folder in your plugin directory.
}
register_activation_hook( __FILE__, 'hpg_plugin_activation' );


// 7. Related Posts Function
function hpg_render_related_posts( $post_id ) {
    $terms = get_the_terms( $post_id, 'subject' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return;
    }

    $term_ids = wp_list_pluck( $terms, 'term_id' );

    $related_args = [
        'post_type'      => 'post',
        'posts_per_page' => 3,
        'post__not_in'   => [ $post_id ],
        'tax_query'      => [
            [
                'taxonomy' => 'subject',
                'field'    => 'term_id',
                'terms'    => $term_ids,
            ],
        ],
    ];

    $related_query = new WP_Query( $related_args );

    if ( $related_query->have_posts() ) {
        echo '<div class="hpg-related-posts-container">';
        echo '<h2 class="related-posts-title">אולי יעניין אותך גם...</h2>';
        echo '<div class="hpg-results-grid-wrapper">';
        while ( $related_query->have_posts() ) {
            $related_query->the_post();
            // We use the same function from our plugin to keep the design consistent
            echo hpg_get_post_card_html( get_the_ID() );
        }
        echo '</div>';
        echo '</div>';
    }
    wp_reset_postdata();
}
?> 

<?php
/**
 * Renders the "Add Post" button via a shortcode.
 * The button will always be visible, but the popup it triggers is only loaded for logged-in users.
 */
function hpg_add_post_button_shortcode() {
    ob_start();
    ?>
    <a href="#" id="hpg-open-popup-button" class="hpg-button">הוספת פוסט חדש</a>
    <?php
    return ob_get_clean();
}
add_shortcode('hpg_add_post_button', 'hpg_add_post_button_shortcode');

// ---- FRONTEND POST SUBMISSION ----

/**
 * Renders the submission form directly on the page via a shortcode.
 * DEBUGGING STEP: Removed popup logic to isolate submission issue.
 */
function hpg_render_submission_form_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>יש להתחבר כדי להעלות תוכן.</p>';
    }

    ob_start();
    ?>
    <div id="hpg-direct-form-wrapper">
        <form id="hpg-submission-form" method="post" enctype="multipart/form-data">
            
            <?php
            // Display errors if they are passed in the URL
            if (isset($_GET['submission_errors'])) {
                $error_json = urldecode($_GET['submission_errors']);
                $errors = json_decode($error_json);
                if (!empty($errors) && is_array($errors)) {
                    echo '<div class="hpg-form-errors">';
                    echo '<strong>אופס! נראה שיש כמה שגיאות:</strong>';
                    echo '<ul>';
                    foreach ($errors as $error) {
                        echo '<li>' . esc_html($error) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
            }
            ?>

            <h2>העלאת תוכן חדש</h2>

            <p>
                <label for="hpg_post_title">כותרת*</label>
                <input type="text" id="hpg_post_title" name="hpg_post_title" placeholder="כותרת קצרה וקליטה" required>
            </p>

            <p>
                <label for="hpg_post_intro">פתיח / תקציר*</label>
                <textarea id="hpg_post_intro" name="hpg_post_intro" rows="4" placeholder="כמה מילים שיתארו את התוכן (יוצג בכרטיסיה)" required></textarea>
            </p>

            <div class="hpg-form-row">
                <p class="hpg-form-col">
                    <label for="hpg_content_link">קישור לתוכן*</label>
                    <input type="url" id="hpg_content_link" name="hpg_content_link" placeholder="https://example.com/my-lesson" required>
                </p>
                <p class="hpg-form-col">
                    <label for="hpg_credit">קרדיט</label>
                    <input type="text" id="hpg_credit" name="hpg_credit" placeholder="למי לתת את הקרדיט?">
                </p>
            </div>
             <div class="hpg-form-row">
                <p class="hpg-form-col">
                    <label for="hpg_platform">פלטפורמה</label>
                    <input type="text" id="hpg_platform" name="hpg_platform" placeholder="לדוגמה: יוטיוב, קהילת המורים וכו'">
                </p>
                <p class="hpg-form-col">
                    <label for="hpg_post_tags">תגיות (מופרדות בפסיק)</label>
                    <input type="text" id="hpg_post_tags" name="hpg_post_tags" placeholder="לדוגמה: שפה, חשבון, כיתה א'">
                </p>
            </div>

            <?php
            if (!function_exists('hpg_render_taxonomy_chips')) {
                function hpg_render_taxonomy_chips($taxonomy_slug, $label, $is_multiselect = true) {
                    $terms = get_terms(['taxonomy' => $taxonomy_slug, 'hide_empty' => false]);
                    if (empty($terms) || is_wp_error($terms)) return;

                    echo '<div class="hpg-chip-group">';
                    echo '<label>' . esc_html($label) . '*</label>';
                    echo '<div class="hpg-chips-container">';
                    foreach ($terms as $term) {
                        $input_type = $is_multiselect ? 'checkbox' : 'radio';
                        $name = $is_multiselect ? "hpg_{$taxonomy_slug}_tax[]" : "hpg_{$taxonomy_slug}_tax";
                        echo '<div class="hpg-chip">';
                        echo '<input type="' . $input_type . '" id="term-' . esc_attr($term->term_id) . '" name="' . $name . '" value="' . esc_attr($term->term_id) . '" required>';
                        echo '<label for="term-' . esc_attr($term->term_id) . '">' . esc_html($term->name) . '</label>';
                        echo '</div>';
                    }
                    echo '</div></div>';
                }
            }

            hpg_render_taxonomy_chips('class', 'כיתה');
            hpg_render_taxonomy_chips('subject', 'תחום דעת');
            hpg_render_taxonomy_chips('category', 'קטגוריה'); 
            ?>

            <p>
                <label for="hpg_featured_image">תמונה ראשית*</label>
                <input type="file" id="hpg_featured_image" name="hpg_featured_image" accept="image/*" required>
                <small>או הדביקו קישור לתמונה בשדה הבא:</small>
                <input type="url" id="hpg_image_url" name="hpg_image_url" placeholder="https://example.com/image.jpg">
            </p>

            <?php wp_nonce_field('hpg_new_post_action', 'hpg_new_post_nonce'); ?>
            <input type="hidden" name="action" value="hpg_submit_post">

            <p>
                <button type="button" id="hpg-form-submit-button" class="hpg-button" onclick="this.form.submit();">שליחת התוכן לבדיקה</button>
            </p>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('hpg_submission_form', 'hpg_render_submission_form_shortcode');


/**
 * Handles the frontend post submission with new fields.
 */
function hpg_handle_post_submission() {
    if (isset($_POST['action']) && $_POST['action'] === 'hpg_submit_post' && isset($_POST['hpg_new_post_nonce'])) {

        if (!wp_verify_nonce($_POST['hpg_new_post_nonce'], 'hpg_new_post_action')) {
            wp_die('Security check failed.');
        }

        // Check if ACF is active
        if (!function_exists('update_field')) {
            wp_die('ACF Pro is required for this feature to work.');
        }

        $errors = [];

        // --- Sanitize and Validate Fields ---
        $title = sanitize_text_field($_POST['hpg_post_title']);
        $intro = sanitize_textarea_field($_POST['hpg_post_intro']);
        $content_link = esc_url_raw($_POST['hpg_content_link']);
        $credit = sanitize_text_field($_POST['hpg_credit']);
        $platform = sanitize_text_field($_POST['hpg_platform']);
        $tags = sanitize_text_field($_POST['hpg_post_tags']);
        
        $class_ids = isset($_POST['hpg_class_tax']) ? array_map('intval', $_POST['hpg_class_tax']) : [];
        $subject_ids = isset($_POST['hpg_subject_tax']) ? array_map('intval', $_POST['hpg_subject_tax']) : [];
        $category_ids = isset($_POST['hpg_category_tax']) ? array_map('intval', $_POST['hpg_category_tax']) : [];
        
        $image_url = esc_url_raw($_POST['hpg_image_url']);

        if (empty($title)) $errors['hpg_post_title'] = 'כותרת היא שדה חובה.';
        if (empty($intro)) $errors['hpg_post_intro'] = 'פתיח הוא שדה חובה.';
        if (empty($content_link) || !filter_var($content_link, FILTER_VALIDATE_URL)) $errors['hpg_content_link'] = 'קישור לתוכן אינו תקין.';
        if (empty($class_ids)) $errors['hpg_class_tax'] = 'יש לבחור לפחות כיתה אחת.';
        if (empty($subject_ids)) $errors['hpg_subject_tax'] = 'יש לבחור לפחות תחום דעת אחד.';
        if (empty($category_ids)) $errors['hpg_category_tax'] = 'יש לבחור לפחות קטגוריה אחת.';
        if (empty($_FILES['hpg_featured_image']['name']) && empty($image_url)) $errors['hpg_featured_image'] = 'יש להעלות תמונה ראשית או להדביק קישור.';
        
        if (!empty($errors)) {
            // Instead of wp_die, redirect back to the form with errors
            $referer = wp_get_referer();
            if (!$referer) {
                // Fallback to home page if referer is not available
                $referer = home_url('/');
            }
            // Add error messages as a query parameter.
            $redirect_url = add_query_arg('submission_errors', urlencode(json_encode($errors)), $referer);
            // Redirect back and add a hash to focus the user on the popup form area
            wp_redirect($redirect_url . '#hpg-popup-overlay');
            exit;
        }

        // --- Create Post ---
        $post_data = array(
            'post_title'    => $title,
            'post_content'  => $intro, // Use the main content for the intro
            'post_status'   => 'pending',
            'post_author'   => get_current_user_id(),
            'post_type'     => 'post',
        );

        $post_id = wp_insert_post($post_data, true); // Pass true to get WP_Error on failure

        if (is_wp_error($post_id)) {
            wp_die('An error occurred while creating the post: ' . $post_id->get_error_message());
        }

        // --- Set Taxonomies (still useful for native WP queries) ---
        wp_set_object_terms($post_id, $class_ids, 'class');
        wp_set_object_terms($post_id, $subject_ids, 'subject');
        wp_set_object_terms($post_id, $category_ids, 'category');

        // Handle Tags
        if (!empty($tags)) {
            wp_set_post_tags($post_id, $tags, false);
        }

        // --- Save data to ACF Fields using their names ---
        update_field('post_intro', $intro, $post_id);
        update_field('post_link', $content_link, $post_id);
        update_field('post_credit', $credit, $post_id);
        update_field('post_platform', $platform, $post_id);

        // For taxonomies, ACF can also store them. Assuming your ACF fields have these names.
        // update_field('post_class', $class_ids, $post_id);
        // update_field('post_subject', $subject_ids, $post_id);
        // update_field('post_category', $category_ids, $post_id);


        // --- Handle Featured Image ---
        if (!function_exists('media_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
        }

        $attachment_id = 0;
        if (isset($_FILES['hpg_featured_image']) && $_FILES['hpg_featured_image']['size'] > 0) {
            $attachment_id = media_handle_upload('hpg_featured_image', $post_id);
        } 
        elseif (!empty($image_url)) {
            // Complex: Sideloading the image from URL
            // This can be added later if needed. For now, we recommend manual upload.
        }

        if ($attachment_id && !is_wp_error($attachment_id)) {
            // Set both the native WP thumbnail AND the ACF field if you have one
            set_post_thumbnail($post_id, $attachment_id);
            // update_field('post_file', $attachment_id, $post_id); // Assuming 'post_file' is your ACF image/file field name
        }

        wp_redirect(home_url('?post_submitted=true'));
        exit;
    }
}
add_action('init', 'hpg_handle_post_submission');

/**
 * Adds the submission popup modal to the site footer.
 * This ensures the popup is available on any page where the trigger button might be.
 */
function hpg_add_popup_to_footer() {
    // Only show for logged-in users, as they are the only ones who can submit.
    if (!is_user_logged_in()) {
        return;
    }
    ?>
    <div id="hpg-popup-overlay" class="hpg-popup-hidden">
        <div class="hpg-popup-container">
             <div id="hpg-popup-content">
                <button id="hpg-close-popup-button" class="hpg-popup-close-btn" aria-label="סגירה">&times;</button>
                <?php echo hpg_render_submission_form_shortcode(); ?>
            </div>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'hpg_add_popup_to_footer');

/**
 * =================================================================
 * BUDDYPRESS INTEGRATION: Display user's posts on their profile.
 * =================================================================
 */

/**
 * Add a new "My Posts" tab to the user's profile.
 */
function hpg_setup_profile_nav() {
    // Check if BuddyPress is active
    if (!function_exists('buddypress')) {
        return;
    }

    global $bp;

    bp_core_new_nav_item( array(
        'name' => 'הפוסטים שלי',
        'slug' => 'my-posts',
        'screen_function' => 'hpg_my_posts_screen',
        'position' => 1,
        'parent_url'      => bp_loggedin_user_domain() . 'my-posts/',
        'default_subnav_slug' => 'my-posts'
    ) );
}
add_action( 'bp_setup_nav', 'hpg_setup_profile_nav' );

/**
 * The content of the "My Posts" tab.
 */
function hpg_my_posts_screen() {
    add_action( 'bp_template_content', 'hpg_my_posts_screen_content' );
    bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
}

/**
 * Renders the list of posts for the user.
 */
function hpg_my_posts_screen_content() {
    $user_id = bp_displayed_user_id();
    
    $args = array(
        'author' => $user_id,
        'post_type' => 'post',
        'post_status' => array('publish', 'pending', 'draft'), // Show all statuses
        'posts_per_page' => -1, // Show all posts
    );

    $user_posts = new WP_Query($args);

    if ($user_posts->have_posts()) :
        echo '<h3>כל הפוסטים שהעלית:</h3>';
        
        // Use the same container as the main grid for consistent styling
        echo '<div id="hpg-results-container" class="hpg-profile-posts">';

        while ($user_posts->have_posts()) : $user_posts->the_post();
            // Reuse the post card HTML function from our plugin
            if (function_exists('hpg_get_post_card_html')) {
                echo hpg_get_post_card_html(get_the_ID());
            }
        endwhile;

        echo '</div>'; // End #hpg-results-container
        wp_reset_postdata();
    else :
        echo '<p>עדיין לא העלית פוסטים.</p>';
    endif;
} 

/**
 * =================================================================
 * SINGLE POST VIEW: Add a responsive author box.
 * =================================================================
 */

/**
 * Wraps the post content and adds the author box on single post pages.
 */
function hpg_add_author_box_wrapper($content) {
    // Only on single post pages, not in loops or other pages.
    if (is_single() && in_the_loop() && is_main_query()) {
        $author_box_html = hpg_get_author_box_html();
        // Wrap content and author box for responsive layout
        return '<div class="hpg-single-post-wrapper">' . $author_box_html . '<div class="hpg-post-main-content">' . $content . '</div></div>';
    }
    return $content;
}
add_filter('the_content', 'hpg_add_author_box_wrapper');

/**
 * Generates the HTML for the author box.
 */
function hpg_get_author_box_html() {
    $author_id = get_the_author_meta('ID');
    
    // Using BuddyPress avatar if available, otherwise fallback to standard avatar
    if (function_exists('bp_core_fetch_avatar')) {
        $author_avatar = bp_core_fetch_avatar(array('item_id' => $author_id, 'type' => 'full', 'html' => false));
    } else {
        $author_avatar = get_avatar_url($author_id, ['size' => 150]);
    }

    $author_name = get_the_author();
    $author_description = get_the_author_meta('description');
    
    ob_start();
    ?>
    <aside class="hpg-author-box">
        <div class="hpg-author-avatar">
            <img src="<?php echo esc_url($author_avatar); ?>" alt="<?php echo esc_attr($author_name); ?>">
        </div>
        <h4 class="hpg-author-name"><?php echo esc_html($author_name); ?></h4>
        <?php if ($author_description): ?>
            <p class="hpg-author-description"><?php echo esc_html($author_description); ?></p>
        <?php endif; ?>
        
        <?php if (function_exists('bp_follow_get_add_follow_button')) : ?>
             <div class="hpg-author-follow-button">
                <?php echo bp_follow_get_add_follow_button(array('leader_id' => $author_id, 'follower_id' => get_current_user_id())); ?>
            </div>
        <?php endif; ?>
    </aside>
    <?php
    return ob_get_clean();
} 