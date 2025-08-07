<?php
/**
 * Plugin Name:       Homer Patuach - BuddyPress Tweaks
 * Plugin URI:        https://example.com/
 * Description:       Custom styles and functionality for BuddyPress pages.
 * Version:           2.3.0
 * Author:            chepti
 * Author URI:        https://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       homer-patuach-bp-tweaks
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'HP_BP_TWEAKS_VERSION', '2.3.0' );
define( 'HP_BP_TWEAKS_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );


/**
 * Enqueue custom stylesheet for the theme.
 */
function hp_bp_tweaks_enqueue_styles() {
    // Styles should be loaded globally for the user bar.
    wp_enqueue_style(
        'hp-bp-tweaks-styles', // handle
        HP_BP_TWEAKS_PLUGIN_DIR_URL . 'assets/css/style.css', // path
        [], // dependencies
        HP_BP_TWEAKS_VERSION // version
    );
}
add_action( 'wp_enqueue_scripts', 'hp_bp_tweaks_enqueue_styles' );

/**
 * Enqueue custom javascript for the theme.
 */
function hp_bp_tweaks_enqueue_scripts() {
    // Only load on BuddyPress pages, but we want the dropdown everywhere
    // if ( function_exists('is_buddypress') && is_buddypress() ) {
        wp_enqueue_script(
            'hp-bp-tweaks-main-js',
            HP_BP_TWEAKS_PLUGIN_DIR_URL . 'assets/js/main.js',
            ['jquery'], // dependency
            HP_BP_TWEAKS_VERSION,
            true // load in footer
        );

        // Localize script with data for AJAX
        wp_localize_script('hp-bp-tweaks-main-js', 'hp_bp_ajax_obj', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'report_nonce' => wp_create_nonce('hpg_report_nonce')
        ]);
    // }
}
add_action( 'wp_enqueue_scripts', 'hp_bp_tweaks_enqueue_scripts' );


/**
 * Translate specific strings in BuddyPress.
 */
function hp_bp_tweaks_translate_text( $translated_text, $text, $domain ) {
    if ( 'buddypress' === $domain ) {
        switch ( $text ) {
            case 'Member Activities':
                $translated_text = 'פעילויות';
                break;
            // Name is handled by JS for reliability
            // case 'Name':
            //     $translated_text = 'כינוי (יוצג באתר)';
            //     break;
            // Visibility text is handled by JS
            // case 'This field may be seen by':
            //     $translated_text = 'מי יוכל לראות שדה זה?';
            //     break;
            case 'Username':
                $translated_text = 'שם משתמש';
                break;
            case 'Email Address':
                $translated_text = 'כתובת אימייל';
                break;
            // Password prompt handled by JS
            // case 'Choose a Password':
            //      $translated_text = 'בחירת סיסמה';
            //     break;
            case '(required)':
                $translated_text = '(שדה חובה)';
                break;
            case 'Register':
                $translated_text = 'הרשמה';
                break;
            case 'Save Changes':
                $translated_text = 'שמירת שינויים';
                break;
        }
    }
    return $translated_text;
}
add_filter( 'gettext', 'hp_bp_tweaks_translate_text', 20, 3 );
add_filter( 'ngettext', 'hp_bp_tweaks_translate_text', 20, 3 );


/**
 * Hide admin bar for non-admin users.
 * This is the most reliable way to ensure the admin bar is hidden for everyone
 * who is not an administrator, including logged-out users.
 */
add_filter('show_admin_bar', function($show) {
    // If the user is logged in and is an administrator, show the bar.
    if ( is_user_logged_in() && current_user_can('manage_options') ) {
        return true;
    }
    // For everyone else (non-admins, logged-out users), hide the bar.
    return false;
}, 999);


/**
* Redirect non-admins to the homepage after login.
*/
function hp_bp_tweaks_login_redirect( $redirect_to, $request, $user ) {
   //is there a user to check?
   if ( isset( $user->roles ) && is_array( $user->roles ) ) {
       //check for admins
       if ( in_array( 'administrator', $user->roles ) ) {
           return $redirect_to; // Or admin_url()
       } else {
           return home_url();
       }
   }
   return $redirect_to;
}
add_filter( 'login_redirect', 'hp_bp_tweaks_login_redirect', 10, 3 );


/**
 * Add a floating button for logged-in users.
 */
function hp_bp_tweaks_add_floating_button() {
    // Show only for logged in users
    if ( ! is_user_logged_in() ) {
        return;
    }

    // Get the URL for the user's "My Posts" tab
    $my_posts_url = bp_loggedin_user_domain() . 'my-posts/';

    ?>
    <a href="<?php echo esc_url($my_posts_url); ?>" class="hp-bp-floating-button" title="הפוסטים שלי">
        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#FFFFFF"><path d="M0 0h24v24H0z" fill="none"/><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9h-4v4h-2v-4H9V9h4V5h2v4h4v2z"/></svg>
        <span>הפוסטים שלי</span>
    </a>
    <?php
}
add_action( 'wp_footer', 'hp_bp_tweaks_add_floating_button' );

/**
 * Adds a custom user bar to the top of the site.
 * This bar will only be displayed for logged-in users.
 */
function hp_bp_tweaks_add_user_bar() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $user_id = get_current_user_id();
    $profile_url = bp_core_get_user_domain( $user_id );
    $my_posts_url = rtrim($profile_url, '/') . '/my-posts/';
    $friends_url = rtrim($profile_url, '/') . '/friends/';
    $profile_edit_url = rtrim($profile_url, '/') . '/profile/edit/';
    $logout_url = wp_logout_url( home_url() );
    ?>
    <div class="hp-bp-user-bar">
        <div class="hp-bp-user-bar-inner">
            <div class="hp-bp-user-menu">
                <button class="hp-bp-profile-trigger" aria-haspopup="true" aria-expanded="false">
                    <?php echo get_avatar( $user_id, 40 ); ?>
                </button>
                <div class="hp-bp-dropdown-menu" aria-hidden="true">
                    <a href="#" class="hpg-open-popup-button">הוסף פוסט</a>
                    <a href="<?php echo esc_url($my_posts_url); ?>">הפוסטים שלי</a>
                    <a href="<?php echo esc_url($profile_edit_url); ?>">הפרופיל שלי</a>
                    <a href="<?php echo esc_url($friends_url); ?>">חברים</a>
                    <a href="<?php echo esc_url($logout_url); ?>" class="logout-link">התנתקות</a>
                </div>
            </div>
        </div>
    </div>
    <?php
}
add_action( 'wp_body_open', 'hp_bp_tweaks_add_user_bar' );


/**
 * Modify BuddyPress navigation tabs.
 * Removes the default "Posts" tab.
 */
function hp_bp_tweaks_modify_bp_nav() {
    // Check if the function exists to avoid errors if BuddyPress is not active
    if ( function_exists( 'bp_core_remove_nav_item' ) ) {
        // The slug for the default blog posts tab is 'posts'
        bp_core_remove_nav_item( 'posts' );
    }
}
add_action( 'bp_setup_nav', 'hp_bp_tweaks_modify_bp_nav', 99 );


/**
 * Adds a custom "Bio" profile field if it doesn't exist.
 * Runs on bp_init hook to be more persistent.
 */
function hp_bp_tweaks_add_custom_profile_fields() {
    if ( ! function_exists('xprofile_insert_field') || ! function_exists('bp_xprofile_get_field_id_from_name') ) {
        return;
    }

    $base_field_group_id = 1; // Default BuddyPress 'Base' group

    // --- Field 1: Full Name (The one that will be displayed) ---
    $full_name_field = 'שם מלא';
    if ( ! bp_xprofile_get_field_id_from_name( $full_name_field ) ) {
        xprofile_insert_field(
            array(
                'field_group_id' => $base_field_group_id,
                'name'           => $full_name_field,
                'description'    => 'שם זה יוצג בפרופיל שלך.',
                'type'           => 'textbox',
                'can_delete'     => false,
                'is_required'    => true, // Make it required on the BP profile page as well
            )
        );
    }
    
    // --- Field 2: Bio ---
    $bio_field = 'קצת עליי';
    if ( ! bp_xprofile_get_field_id_from_name( $bio_field ) ) {
        xprofile_insert_field(
            array(
                'field_group_id' => $base_field_group_id,
                'name'           => $bio_field,
                'description'    => 'ספרו קצת על עצמכם, על תחומי העניין והמומחיות שלכם.',
                'type'           => 'textarea',
                'can_delete'     => false,
                'is_required'    => false,
            )
        );
    }
}
// add_action( 'bp_init', 'hp_bp_tweaks_add_custom_profile_fields', 5 );


/**
 * Hide the default BuddyPress 'Name' field (field_1) from profile edit and registration.
 */
function hp_bp_hide_default_name_field() {
    if ( ! function_exists('bp_xprofile_remove_field') ) {
        // A less ideal fallback for older BP versions or if the function is hooked weirdly
        if ( bp_is_user_profile_edit() || bp_is_register_page() ) {
            $GLOBALS['bp']->profile->fields[0]->option_buttons = ' style="display:none"';
        }
        return;
    }
    
    // The proper way: temporarily remove the field from the loop on edit/register pages.
    if ( bp_is_user_profile_edit() || bp_is_register_page() ) {
         bp_xprofile_remove_field( 1 );
    }
}
// add_action( 'bp_before_profile_loop_content', 'hp_bp_hide_default_name_field' );


// --- Custom Registration Form ---

/**
 * Handle the custom registration form submission.
 */
function hp_bp_handle_custom_registration() {
    if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['hp_bp_register_nonce'] ) || ! wp_verify_nonce( $_POST['hp_bp_register_nonce'], 'hp_bp_custom_register' ) ) {
        return;
    }

    $errors = new WP_Error();

    // Get and sanitize form fields
    $user_login = sanitize_user( $_POST['user_login'] );
    $user_email = sanitize_email( $_POST['user_email'] );
    $user_pass = $_POST['user_pass'];
    $user_pass_confirm = $_POST['user_pass_confirm'];
    $full_name = sanitize_text_field( $_POST['full_name'] );
    $bio = sanitize_textarea_field( $_POST['bio'] );

    // --- Validation ---
    if ( empty($user_login) ) $errors->add( 'empty_username', '<strong>שגיאה</strong>: יש להזין שם משתמש.' );
    if ( ! validate_username( $user_login ) ) $errors->add( 'invalid_username', '<strong>שגיאה</strong>: שם המשתמש יכול להכיל רק אותיות באנגלית, מספרים, והתווים _ ו-.' );
    if ( username_exists( $user_login ) ) $errors->add( 'username_exists', '<strong>שגיאה</strong>: שם משתמש זה כבר תפוס.' );
    
    if ( empty($user_email) ) $errors->add( 'empty_email', '<strong>שגיאה</strong>: יש להזין כתובת אימייל.' );
    if ( ! is_email( $user_email ) ) $errors->add( 'invalid_email', '<strong>שגיאה</strong>: כתובת האימייל שהזנת אינה תקינה.' );
    if ( email_exists( $user_email ) ) $errors->add( 'email_exists', '<strong>שגיאה</strong>: כתובת אימייל זו כבר רשומה במערכת.' );

    if ( empty($user_pass) ) $errors->add( 'empty_password', '<strong>שגיאה</strong>: יש להזין סיסמה.' );
    if ( $user_pass !== $user_pass_confirm ) $errors->add( 'password_mismatch', '<strong>שגיאה</strong>: הסיסמאות אינן תואמות.' );
    
    if ( empty($full_name) ) $errors->add( 'empty_fullname', '<strong>שגיאה</strong>: יש להזין שם מלא.' );

    // Store errors in a transient to display after redirect
    if ( $errors->has_errors() ) {
        set_transient( 'hp_bp_registration_errors', $errors->get_error_messages(), 60 );
        wp_redirect( $_POST['_wp_http_referer'] );
        exit;
    }

    // --- Create User and Set Data ---
    $user_data = array(
        'user_login' => $user_login,
        'user_pass'  => $user_pass,
        'user_email' => $user_email,
        'role'       => 'contributor' // Set role to contributor on creation
    );
    $user_id = wp_insert_user( $user_data );

    if ( is_wp_error( $user_id ) ) {
        set_transient( 'hp_bp_registration_errors', $user_id->get_error_messages(), 60 );
        wp_redirect( $_POST['_wp_http_referer'] );
        exit;
    }

    // Update BuddyPress xProfile fields
    if ( function_exists('xprofile_set_field_data') ) {
        // Set 'Full Name' field data
        $full_name_field_id = bp_xprofile_get_field_id_from_name('שם מלא');
        if ($full_name_field_id) {
            xprofile_set_field_data( $full_name_field_id, $user_id, $full_name );
        }
        
        // Set 'Bio' field data
        $bio_field_id = bp_xprofile_get_field_id_from_name('קצת עליי');
        if ($bio_field_id) {
            xprofile_set_field_data( $bio_field_id, $user_id, $bio );
        }
    }

    // Log the user in
    wp_set_current_user( $user_id, $user_login );
    wp_set_auth_cookie( $user_id );
    do_action( 'wp_login', $user_login, get_user_by('id', $user_id) );

    // Redirect to their new profile
    wp_redirect( bp_core_get_user_domain( $user_id ) );
    exit;
}
// add_action( 'init', 'hp_bp_handle_custom_registration', 99 );


/**
 * Render the custom registration form via shortcode.
 * [hp_custom_register_form]
 */
function hp_bp_render_custom_registration_form() {
    if ( is_user_logged_in() ) {
        return '<p>אתה כבר מחובר למערכת.</p>';
    }

    ob_start();

    // Display errors if they exist
    if ( $errors = get_transient( 'hp_bp_registration_errors' ) ) {
        echo '<div class="hp-bp-reg-errors">';
        foreach ( $errors as $error ) {
            echo '<p>' . $error . '</p>';
        }
        echo '</div>';
        delete_transient( 'hp_bp_registration_errors' );
    }
    ?>
    <form id="hp-bp-custom-register-form" method="post">
        <div class="form-row">
            <label for="user_login">שם משתמש (באנגלית)</label>
            <input type="text" name="user_login" id="user_login" required>
            <p class="description">ישמש אותך להתחברות. יכול להכיל רק אותיות באנגלית ומספרים.</p>
        </div>

        <div class="form-row">
            <label for="full_name">שם מלא</label>
            <input type="text" name="full_name" id="full_name" required>
            <p class="description">השם שיוצג בפרופיל שלך ובאתר.</p>
        </div>

        <div class="form-row">
            <label for="user_email">כתובת אימייל</label>
            <input type="email" name="user_email" id="user_email" required>
        </div>

        <div class="form-row">
            <label for="user_pass">סיסמה</label>
            <input type="password" name="user_pass" id="user_pass" required>
        </div>

        <div class="form-row">
            <label for="user_pass_confirm">אימות סיסמה</label>
            <input type="password" name="user_pass_confirm" id="user_pass_confirm" required>
        </div>
        
        <?php
        // Only show Bio field if the xprofile function exists
        if ( function_exists('bp_xprofile_get_field_id_from_name') && bp_xprofile_get_field_id_from_name('קצת עליי') ) : ?>
        <div class="form-row">
            <label for="bio">קצת עליי</label>
            <textarea name="bio" id="bio" rows="5"></textarea>
            <p class="description">ספרו קצת על עצמכם, על תחומי העניין והמומחיות שלכם. שדה זה הוא אופציונלי.</p>
        </div>
        <?php endif; ?>

        <div class="form-row submit-row">
            <?php wp_nonce_field( 'hp_bp_custom_register', 'hp_bp_register_nonce' ); ?>
            <input type="submit" value="הרשמה ומתחילים!">
        </div>
        
        <div class="form-row login-link">
            <p>כבר יש לך חשבון? <a href="<?php echo esc_url( wp_login_url() ); ?>">להתחברות</a></p>
        </div>
    </form>
    <?php
    return ob_get_clean();
}
// add_shortcode( 'hp_custom_register_form', 'hp_bp_render_custom_registration_form' ); 


/**
 * Sets the user's nicename (slug) to their user ID upon registration.
 * This is a robust way to ensure unique, clean slugs without parsing emails.
 *
 * @param int $user_id The ID of the newly registered user.
 */
function hp_bp_tweaks_set_id_as_slug_on_register( $user_id ) {
    global $wpdb;

    // Directly update the user_nicename to match the user_id.
    // This is safe and avoids hook loops.
    $wpdb->update(
        $wpdb->users,
        array(
            'user_nicename' => $user_id, 
        ),
        array( 'ID' => $user_id ),
        array( '%s' ),
        array( '%d' )
    );

    // Clear caches to make the change visible immediately.
    clean_user_cache( $user_id ); 
    if ( function_exists('bp_core_clear_user_displayname_cache') ) {
        bp_core_clear_user_displayname_cache( $user_id );
    }
}
add_action( 'user_register', 'hp_bp_tweaks_set_id_as_slug_on_register', 99, 1 );


/**
 * Overrides the BuddyPress mention name in profile headers.
 * Replaces the default "@username" (e.g., "@24") with the user's full display name.
 * This targets the specific function used in many themes for the profile header name.
 *
 * @param string $mention_name The original mention name (e.g., "@24").
 * @return string The modified display name.
 */
function hp_bp_tweaks_replace_mention_name_with_display_name( $mention_name ) {
    // Only run on member profile pages.
    if ( ! bp_is_user() ) {
        return $mention_name;
    }

    // Get the user ID for the profile being displayed.
    $displayed_user_id = bp_displayed_user_id();

    if ( $displayed_user_id ) {
        // Fetch the user's "real" display name.
        $real_display_name = bp_core_get_user_displayname( $displayed_user_id );

        // If the real display name is not empty, use it.
        if ( ! empty( $real_display_name ) ) {
            return $real_display_name;
        }
    }

    // Fallback to the original mention name if something goes wrong.
    return $mention_name;
}
add_filter( 'bp_get_displayed_user_mentionname', 'hp_bp_tweaks_replace_mention_name_with_display_name', 10, 1 ); 

/**
 * Always exclude attachments from default WordPress search results.
 * This ensures media files (e.g., featured images) do not appear as separate results.
 */
function hp_bp_tweaks_exclude_attachments_from_search( $query ) {
    if ( ! is_admin() && $query->is_search ) {
        $post_type = $query->get('post_type');
        if ( empty($post_type) || 'any' === $post_type ) {
            $query->set( 'post_type', array( 'post', 'page' ) );
        } elseif ( is_string($post_type) ) {
            if ( 'attachment' === $post_type ) {
                $query->set( 'post_type', array( 'post', 'page' ) );
            }
        } elseif ( is_array($post_type) ) {
            $filtered = array_values( array_diff( $post_type, array('attachment') ) );
            if ( empty($filtered) ) {
                $filtered = array( 'post', 'page' );
            }
            $query->set( 'post_type', $filtered );
        }

        // Only published content
        $query->set( 'post_status', 'publish' );

        // SQL-level safety net: exclude attachments regardless of post_type mutations by other hooks
        $where_filter = function( $where ) {
            global $wpdb;
            return $where . " AND {$wpdb->posts}.post_type <> 'attachment' ";
        };
        add_filter( 'posts_where', $where_filter, 999 );
        add_action( 'posts_selection', function() use ( $where_filter ) {
            remove_filter( 'posts_where', $where_filter, 999 );
        } );
    }
    return $query;
}
add_action( 'pre_get_posts', 'hp_bp_tweaks_exclude_attachments_from_search', 999 );

/**
 * Final safety net: remove attachments from search results just before rendering.
 */
function hp_bp_tweaks_strip_attachments_from_results( $posts, $query ) {
    if ( ! is_admin() && $query->is_search && ! empty( $posts ) ) {
        $filtered = [];
        foreach ( $posts as $p ) {
            if ( isset($p->post_type) && $p->post_type === 'attachment' ) {
                continue; // skip attachments
            }
            $filtered[] = $p;
        }
        return $filtered;
    }
    return $posts;
}
add_filter( 'posts_results', 'hp_bp_tweaks_strip_attachments_from_results', 9999, 2 );

/**
 * Extends the default WordPress search to include user display names and all post meta fields,
 * while excluding attachments from the results.
 */
function hp_bp_tweaks_expand_search_to_users_and_meta( $query ) {
    // Get our plugin settings
    $options = get_option('hp_bp_tweaks_settings');
    $is_search_enabled = isset($options['enable_user_search']) && $options['enable_user_search'];

    // Ensure this is a main search query on the frontend and not in the admin area
    if ( $is_search_enabled && $query->is_search && $query->is_main_query() && ! is_admin() ) {
        
        // Exclude attachments from search results by only including 'post' and 'page'.
        $query->set('post_type', ['post', 'page']);

        $search_term = $query->get( 's' );

        if ( ! empty( $search_term ) ) {
            
            $join_filter = function( $join ) {
                global $wpdb;
                // Join users table to search by author display name
                $join .= " LEFT JOIN {$wpdb->users} ON {$wpdb->posts}.post_author = {$wpdb->users}.ID ";
                // Join postmeta table to search by custom fields
                $join .= " LEFT JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id ";
                return $join;
            };
            
            $where_filter = function( $where ) use ( $search_term ) {
                global $wpdb;
                // Add author display name and post meta to the search
                $where .= $wpdb->prepare(
                    " OR ({$wpdb->users}.display_name LIKE %s) OR ({$wpdb->postmeta}.meta_value LIKE %s) ",
                    '%' . $wpdb->esc_like( $search_term ) . '%',
                    '%' . $wpdb->esc_like( $search_term ) . '%'
                );
                return $where;
            };

            // Add a DISTINCT clause to avoid duplicate results
            $distinct_filter = function( $distinct ) {
                return 'DISTINCT';
            };

            add_filter( 'posts_join', $join_filter );
            add_filter( 'posts_where', $where_filter );
            add_filter( 'posts_distinct', $distinct_filter );
            
            // Remove the filters after the main query has run
            add_action('posts_selection', function() use ($join_filter, $where_filter, $distinct_filter) {
                remove_filter('posts_join', $join_filter);
                remove_filter('posts_where', $where_filter);
                remove_filter('posts_distinct', $distinct_filter);
            });
        }
    }
    return $query;
}
add_action( 'pre_get_posts', 'hp_bp_tweaks_expand_search_to_users_and_meta', 99 ); 

/**
 * =================================================================
 * AJAX HANDLER FOR CONTENT REPORTING
 * =================================================================
 */

// 1. Register the AJAX action for logged-in users
add_action('wp_ajax_hpg_handle_report_submission', 'hpg_handle_report_submission_callback');

// 2. The callback function
function hpg_handle_report_submission_callback() {
    // Security check
    check_ajax_referer('hpg_report_nonce', 'security');

    // Basic validation
    if ( !isset($_POST['post_id']) || !isset($_POST['reason']) || empty($_POST['reason']) ) {
        wp_send_json_error(['message' => 'שדות חובה חסרים.']);
        return;
    }

    $post_id = intval($_POST['post_id']);
    $reason_code = sanitize_text_field($_POST['reason']);
    $details = isset($_POST['details']) ? sanitize_textarea_field($_POST['details']) : '';
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $post = get_post($post_id);

    // Ensure the post exists and user is logged in
    if ( !$post || !$user_id ) {
        wp_send_json_error(['message' => 'הבקשה אינה תקינה.']);
        return;
    }

    // --- Core Logic ---

    // 1. Translate reason code to a readable string
    $reasons = [
        'broken_link' => 'קישור שבור',
        'content_error' => 'שגיאה בתוכן',
        'offensive_content' => 'תוכן פוגעני'
    ];
    $reason_text = isset($reasons[$reason_code]) ? $reasons[$reason_code] : 'לא צוינה סיבה';

    // 2. Save report details as post meta
    update_post_meta($post_id, '_hpg_report_info', [
        'reporter_id' => $user_id,
        'reporter_name' => $user->display_name,
        'reason_code' => $reason_code,
        'reason_text' => $reason_text,
        'details' => $details,
        'report_time' => current_time('mysql')
    ]);

    // 3. Change post status to 'pending'
    // Temporarily grant the user permission to edit this specific post
    // to allow changing the status to 'pending'.
    $author_id = $post->post_author;
    if ($user_id != $author_id) { // Only do this if the reporter is not the author
        add_filter('user_has_cap', 'hpg_allow_pending_transition', 10, 3);
    }
    
    $post_update_args = [
        'ID' => $post_id,
        'post_status' => 'pending'
    ];
    wp_update_post($post_update_args);

    // Immediately remove the filter to restore original permissions
    if ($user_id != $author_id) {
        remove_filter('user_has_cap', 'hpg_allow_pending_transition', 10);
    }

    // 4. Send an email notification
    $admin_email = get_option('admin_email');
    $subject = '[הומר פתוח] דיווח על תוכן: ' . $post->post_title;
    $edit_link = get_edit_post_link($post_id, 'raw');

    $message = "שלום מנהל,\n\n";
    $message .= "התקבל דיווח על הפוסט \"" . $post->post_title . "\".\n";
    $message .= "הפוסט הועבר למצב 'ממתין לאישור' לבדיקתך.\n\n";
    $message .= "פרטי הדיווח:\n";
    $message .= "------------------------\n";
    $message .= "מדווח: " . $user->display_name . " (ID: " . $user_id . ")\n";
    $message .= "סיבה: " . $reason_text . "\n";
    if (!empty($details)) {
        $message .= "פרטים נוספים: " . $details . "\n";
    }
    $message .= "------------------------\n\n";
    $message .= "תוכל לערוך את הפוסט ולבדוק את הדיווח כאן:\n";
    $message .= $edit_link . "\n\n";
    $message .= "תודה,\nצוות האתר";

    wp_mail($admin_email, $subject, $message);

    // Return a success message
    wp_send_json_success(['message' => 'הדיווח התקבל, תודה! אנו נטפל בו בהקדם.']);
}

/**
 * A temporary capabilities filter to allow a user to change a post's status to pending.
 */
function hpg_allow_pending_transition($allcaps, $caps, $args) {
    // Check if the user is trying to edit a post
    if (isset($args[0]) && $args[0] == 'edit_post' && isset($args[2])) {
        // Grant the capability for this specific action
        $allcaps['edit_post'] = true;
    }
    return $allcaps;
}

/**
 * =================================================================
 * ADMIN-SIDE REPORT DISPLAY
 * =================================================================
 */

// 1. Add a meta box to the post editor screen
function hpg_register_report_info_meta_box() {
    add_meta_box(
        'hpg_report_info_box', // ID
        'פרטי דיווח על תוכן', // Title
        'hpg_report_info_meta_box_callback', // Callback
        'post', // Post type
        'side', // Context (normal, side, advanced)
        'high' // Priority
    );
}
add_action('add_meta_boxes', 'hpg_register_report_info_meta_box');

// 2. The callback function to render the meta box content
function hpg_report_info_meta_box_callback($post) {
    $report_info = get_post_meta($post->ID, '_hpg_report_info', true);

    if (empty($report_info)) {
        echo '<p>אין דיווחים על פוסט זה.</p>';
        return;
    }

    // Security nonce
    wp_nonce_field('hpg_clear_report_action', 'hpg_clear_report_nonce');

    echo '<div style="padding: 10px; line-height: 1.6;">';
    echo '<strong>מדווח:</strong> ' . esc_html($report_info['reporter_name']) . ' (ID: ' . esc_html($report_info['reporter_id']) . ')<br>';
    echo '<strong>תאריך:</strong> ' . date('j.n.Y H:i', strtotime($report_info['report_time'])) . '<br>';
    echo '<strong>סיבה:</strong> ' . esc_html($report_info['reason_text']) . '<br>';

    if (!empty($report_info['details'])) {
        echo '<strong>פרטים:</strong><div style="background: #f9f9f9; border: 1px solid #eee; padding: 5px; margin-top: 5px;">' . nl2br(esc_html($report_info['details'])) . '</div>';
    }
    echo '</div>';

    echo '<hr style="margin: 10px 0;">';

    // Add a button to clear the report info
    echo '<p>לאחר הטיפול בפוסט (אישור מחדש או מחיקה), ניתן לנקות את הדיווח:</p>';
    echo '<button type="submit" name="hpg_clear_report" class="button">ניקוי דיווח</button>';
}

// 3. Handle clearing the report info when post is saved/updated
function hpg_clear_report_on_save($post_id) {
    // Check if our button was clicked and the nonce is valid
    if ( !isset($_POST['hpg_clear_report']) || !isset($_POST['hpg_clear_report_nonce']) ) {
        return;
    }
    if ( !wp_verify_nonce($_POST['hpg_clear_report_nonce'], 'hpg_clear_report_action') ) {
        return;
    }
    // Don't save on autosave
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
        return;
    }
    // Check user permissions
    if ( isset($_POST['post_type']) && 'post' == $_POST['post_type'] ) {
        if ( !current_user_can('edit_post', $post_id) ) {
            return;
        }
    }

    // All good, clear the meta field
    delete_post_meta($post_id, '_hpg_report_info');
}
add_action('save_post', 'hpg_clear_report_on_save'); 

/**
 * =================================================================
 * USER REPUTATION & STATISTICS
 * =================================================================
 */

// --- 1. Helper functions to get user stats ---

function hpg_get_user_total_likes( $user_id ) {
    $count = get_user_meta( $user_id, 'hpg_total_likes_received', true );
    return $count ? (int) $count : 0;
}

function hpg_get_user_total_comments( $user_id ) {
    $count = get_user_meta( $user_id, 'hpg_total_comments_received', true );
    return $count ? (int) $count : 0;
}

function hpg_get_user_total_posts( $user_id ) {
    $count = get_user_meta( $user_id, 'hpg_total_posts_created', true );
    return $count ? (int) $count : 0;
}

function hpg_get_user_total_views( $user_id ) {
    $count = get_user_meta( $user_id, 'hpg_total_views_received', true );
    return $count ? (int) $count : 0;
}

// --- 2. Hooks to track user stats ---

/**
 * Recalculate total likes for an author whenever their post's like count changes.
 * This is the most robust method to ensure accuracy.
 */
function hpg_recalculate_user_likes( $meta_id, $object_id, $meta_key, $_meta_value ) {
    // Only proceed if the meta key is for likes.
    if ( '_hpg_like_count' !== $meta_key ) {
        return;
    }

    $post_id = $object_id;
    $post = get_post( $post_id );

    // Ensure we're working with a valid post.
    if ( ! $post || 'post' !== get_post_type($post) ) {
        return;
    }

    $author_id = $post->post_author;

    // Perform a direct DB query to sum up all likes for the author's published posts.
    global $wpdb;
    $total_likes = $wpdb->get_var( $wpdb->prepare( "
        SELECT SUM(CAST(m.meta_value AS SIGNED)) 
        FROM {$wpdb->postmeta} m
        INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
        WHERE p.post_author = %d 
        AND p.post_type = 'post' 
        AND p.post_status = 'publish' 
        AND m.meta_key = '_hpg_like_count'
    ", $author_id ) );

    update_user_meta( $author_id, 'hpg_total_likes_received', (int)$total_likes );
}
// Hook into both adding and updating post meta for likes.
add_action( 'updated_post_meta', 'hpg_recalculate_user_likes', 10, 4 );
add_action( 'added_post_meta', 'hpg_recalculate_user_likes', 10, 4 );


/**
 * Recalculate total views for an author whenever their post's view count changes.
 */
function hpg_recalculate_user_views( $meta_id, $object_id, $meta_key, $_meta_value ) {
    // Only proceed if the meta key is for views.
    if ( '_hpg_view_count' !== $meta_key ) {
        return;
    }

    $post_id = $object_id;
    $post = get_post( $post_id );

    // Ensure we're working with a valid post.
    if ( ! $post || 'post' !== get_post_type($post) ) {
        return;
    }

    $author_id = $post->post_author;

    // Perform a direct DB query to sum up all views for the author's published posts.
    global $wpdb;
    $total_views = $wpdb->get_var( $wpdb->prepare( "
        SELECT SUM(CAST(m.meta_value AS SIGNED)) 
        FROM {$wpdb->postmeta} m
        INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
        WHERE p.post_author = %d 
        AND p.post_type = 'post' 
        AND p.post_status = 'publish' 
        AND m.meta_key = '_hpg_view_count'
    ", $author_id ) );

    update_user_meta( $author_id, 'hpg_total_views_received', (int)$total_views );
}
// Hook into both adding and updating post meta for views.
add_action( 'updated_post_meta', 'hpg_recalculate_user_views', 10, 4 );
add_action( 'added_post_meta', 'hpg_recalculate_user_views', 10, 4 );


/**
 * Track comments when comment status changes (e.g., approved, trashed).
 */
function hpg_track_user_comments_on_status_change( $new_status, $old_status, $comment ) {
    $post = get_post( $comment->comment_post_ID );
    if ( !$post || 'post' !== get_post_type($post) ) return;

    $author_id = $post->post_author;

    // Don't count comments from the author on their own posts.
    if ( $comment->user_id && $comment->user_id == $author_id ) {
        return;
    }

    $current_comments = hpg_get_user_total_comments( $author_id );

    // Comment is approved
    if ( 'approved' === $new_status && 'approved' !== $old_status ) {
        update_user_meta( $author_id, 'hpg_total_comments_received', $current_comments + 1 );
    } 
    // Comment is un-approved
    elseif ( 'approved' === $old_status && 'approved' !== $new_status ) {
        if ($current_comments > 0) {
            update_user_meta( $author_id, 'hpg_total_comments_received', $current_comments - 1 );
        }
    }
}
add_action( 'transition_comment_status', 'hpg_track_user_comments_on_status_change', 10, 3 );


/**
 * Track new posts when they are published.
 */
function hpg_track_user_posts_on_publish( $new_status, $old_status, $post ) {
    if ( 'post' !== $post->post_type ) {
        return;
    }

    $author_id = $post->post_author;

    if ( 'publish' === $new_status && 'publish' !== $old_status ) {
        $current_posts = hpg_get_user_total_posts( $author_id );
        update_user_meta( $author_id, 'hpg_total_posts_created', $current_posts + 1 );
    }
    elseif ( 'publish' === $old_status && 'publish' !== $new_status ) {
        $current_posts = hpg_get_user_total_posts( $author_id );
        if ( $current_posts > 0 ) {
            update_user_meta( $author_id, 'hpg_total_posts_created', $current_posts - 1 );
        }
    }
}
add_action( 'transition_post_status', 'hpg_track_user_posts_on_publish', 10, 3 );


// --- 3. Display stats on BuddyPress profile ---

/**
 * Display the reputation stats on the member's profile header.
 */
function hpg_display_user_reputation_stats() {
    if ( ! bp_is_user() ) {
        return;
    }

    $user_id = bp_displayed_user_id();
    if ( ! $user_id ) {
        return;
    }

    $total_likes = hpg_get_user_total_likes( $user_id );
    $total_comments = hpg_get_user_total_comments( $user_id );
    $total_posts = hpg_get_user_total_posts( $user_id );
    $total_views = hpg_get_user_total_views( $user_id );
    ?>
    <div class="hpg-user-stats-container">
        <div class="hpg-stat-item">
            <span class="hpg-stat-value"><?php echo number_format_i18n( $total_views ); ?></span>
            <span class="hpg-stat-label">👁️ צפיות</span>
        </div>
        <div class="hpg-stat-item">
            <span class="hpg-stat-value"><?php echo number_format_i18n( $total_likes ); ?></span>
            <span class="hpg-stat-label">❤ לבבות</span>
        </div>
        <div class="hpg-stat-item">
            <span class="hpg-stat-value"><?php echo number_format_i18n( $total_comments ); ?></span>
            <span class="hpg-stat-label">💬 תגובות</span>
        </div>
        <div class="hpg-stat-item">
            <span class="hpg-stat-value"><?php echo number_format_i18n( $total_posts ); ?></span>
            <span class="hpg-stat-label">📝 פוסטים</span>
        </div>
    </div>
    <?php
}
add_action( 'bp_after_member_header', 'hpg_display_user_reputation_stats' );

/**
 * =================================================================
 * ADD USER STATS TO ADMIN USERS LIST
 * =================================================================
 */

// 1. Add custom columns to the users list
function hpg_add_user_stats_columns( $columns ) {
    $columns['hpg_total_views'] = '👁️ סה"כ צפיות';
    $columns['hpg_total_likes'] = '❤ סה"כ לבבות';
    $columns['hpg_total_comments'] = '💬 סה"כ תגובות';
    return $columns;
}
add_filter( 'manage_users_columns', 'hpg_add_user_stats_columns' );

// 2. Populate the custom columns with data
function hpg_show_user_stats_in_columns( $value, $column_name, $user_id ) {
    switch ( $column_name ) {
        case 'hpg_total_views':
            return number_format_i18n( hpg_get_user_total_views( $user_id ) );
        case 'hpg_total_likes':
            return number_format_i18n( hpg_get_user_total_likes( $user_id ) );
        case 'hpg_total_comments':
            return number_format_i18n( hpg_get_user_total_comments( $user_id ) );
        default:
    }
    return $value;
}
add_filter( 'manage_users_custom_column', 'hpg_show_user_stats_in_columns', 10, 3 );

// 3. Make the new columns sortable
function hpg_make_user_stats_columns_sortable( $columns ) {
    $columns['hpg_total_views'] = 'hpg_total_views';
    $columns['hpg_total_likes'] = 'hpg_total_likes';
    $columns['hpg_total_comments'] = 'hpg_total_comments';
    return $columns;
}
add_filter( 'manage_users_sortable_columns', 'hpg_make_user_stats_columns_sortable' );

// 4. Handle the sorting logic
function hpg_user_stats_column_sorting( $query ) {
    if ( ! is_admin() || 'users' !== $query->get('query_id') ) {
        return;
    }

    $orderby = $query->get( 'orderby' );

    switch ( $orderby ) {
        case 'hpg_total_views':
            $query->set( 'meta_key', 'hpg_total_views_received' );
            $query->set( 'orderby', 'meta_value_num' );
            break;
        case 'hpg_total_likes':
            $query->set( 'meta_key', 'hpg_total_likes_received' );
            $query->set( 'orderby', 'meta_value_num' );
            break;
        case 'hpg_total_comments':
            $query->set( 'meta_key', 'hpg_total_comments_received' );
            $query->set( 'orderby', 'meta_value_num' );
            break;
        default:
            break;
    }
}
add_action( 'pre_get_users', 'hpg_user_stats_column_sorting' );


/**
 * =================================================================
 * ONE-TIME RECALCULATION SCRIPT
 * =================================================================
 */

/**
 * Handle the one-time recalculation of all user stats.
 * Triggered by visiting the admin dashboard with a specific query parameter.
 * e.g., /wp-admin/?hpg_recalculate_stats=true
 */
function hpg_recalculate_all_user_stats() {
    if ( ! isset( $_GET['hpg_recalculate_stats'] ) || $_GET['hpg_recalculate_stats'] !== 'true' || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;

    // Reset all stats to 0 first to prevent duplicates.
    $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ('hpg_total_likes_received', 'hpg_total_comments_received', 'hpg_total_posts_created', 'hpg_total_views_received')" );

    // Recalculate total published posts for each user.
    $post_counts = $wpdb->get_results( "
        SELECT post_author, COUNT(ID) as count 
        FROM {$wpdb->posts} 
        WHERE post_type = 'post' AND post_status = 'publish' 
        GROUP BY post_author
    " );
    foreach ( $post_counts as $row ) {
        update_user_meta( $row->post_author, 'hpg_total_posts_created', $row->count );
    }

    // Recalculate total likes for each user.
    $like_counts = $wpdb->get_results( "
        SELECT p.post_author, SUM(CAST(m.meta_value AS SIGNED)) as count
        FROM {$wpdb->postmeta} m
        INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
        WHERE p.post_type = 'post' AND p.post_status = 'publish' AND m.meta_key = '_hpg_like_count'
        GROUP BY p.post_author
    " );
    foreach ( $like_counts as $row ) {
        update_user_meta( $row->post_author, 'hpg_total_likes_received', $row->count );
    }

    // Recalculate total views for each user.
    $view_counts = $wpdb->get_results( "
        SELECT p.post_author, SUM(CAST(m.meta_value AS SIGNED)) as count
        FROM {$wpdb->postmeta} m
        INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID
        WHERE p.post_type = 'post' AND p.post_status = 'publish' AND m.meta_key = '_hpg_view_count'
        GROUP BY p.post_author
    " );
    foreach ( $view_counts as $row ) {
        update_user_meta( $row->post_author, 'hpg_total_views_received', $row->count );
    }

    // Recalculate total comments received for each user.
    $comment_counts = $wpdb->get_results( "
        SELECT p.post_author, COUNT(c.comment_ID) as count
        FROM {$wpdb->comments} c
        INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
        WHERE p.post_type = 'post' AND p.post_status = 'publish' AND c.comment_approved = '1' AND c.user_id != p.post_author
        GROUP BY p.post_author
    " );
    foreach ( $comment_counts as $row ) {
        update_user_meta( $row->post_author, 'hpg_total_comments_received', $row->count );
    }

    // Display a success message in the admin panel.
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible"><p>סטטיסטיקות המשתמשים חושבו מחדש בהצלחה.</p></div>';
    });
}
add_action( 'admin_init', 'hpg_recalculate_all_user_stats' ); 

/**
 * =================================================================
 * PLUGIN SETTINGS PAGE
 * =================================================================
 */

// 1. Add settings page to the admin menu
function hp_bp_tweaks_add_settings_page() {
    add_options_page(
        'Homer Patuach Tweaks Settings', // Page Title
        'Homer Patuach Tweaks',          // Menu Title
        'manage_options',                // Capability
        'hp-bp-tweaks-settings',         // Menu Slug
        'hp_bp_tweaks_render_settings_page' // Callback function
    );
}
add_action('admin_menu', 'hp_bp_tweaks_add_settings_page');

// 2. Register settings
function hp_bp_tweaks_register_settings() {
    register_setting(
        'hp_bp_tweaks_options_group', // Option group
        'hp_bp_tweaks_settings',      // Option name
        'hp_bp_tweaks_sanitize_settings' // Sanitize callback
    );

    add_settings_section(
        'hp_bp_tweaks_search_section', // ID
        'הגדרות חיפוש', // Title
        null, // Callback
        'hp-bp-tweaks-settings' // Page
    );

    add_settings_field(
        'enable_user_search', // ID
        'הרחבת חיפוש לשמות משתמשים', // Title
        'hp_bp_tweaks_enable_user_search_callback', // Callback
        'hp-bp-tweaks-settings', // Page
        'hp_bp_tweaks_search_section' // Section
    );
}
add_action('admin_init', 'hp_bp_tweaks_register_settings');

// 3. Render the form fields
function hp_bp_tweaks_enable_user_search_callback() {
    $options = get_option('hp_bp_tweaks_settings');
    $checked = isset($options['enable_user_search']) && $options['enable_user_search'] ? 'checked' : '';
    echo '<input type="checkbox" id="enable_user_search" name="hp_bp_tweaks_settings[enable_user_search]" value="1" ' . $checked . ' />';
    echo '<label for="enable_user_search">הפעל כדי לכלול בתוצאות החיפוש גם שמות מחברים ואת כל השדות המותאמים (כמו קרדיט וכו\').</label>';
}


// 4. Sanitize the settings
function hp_bp_tweaks_sanitize_settings($input) {
    $new_input = [];
    if (isset($input['enable_user_search'])) {
        $new_input['enable_user_search'] = absint($input['enable_user_search']);
    }
    return $new_input;
}

// 5. Render the settings page content
function hp_bp_tweaks_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>הגדרות עבור Homer Patuach Tweaks</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('hp_bp_tweaks_options_group');
                do_settings_sections('hp-bp-tweaks-settings');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Add a "Settings" link to the plugin's action links on the plugins page.
 *
 * @param array  $links An array of plugin action links.
 * @return array An array of modified plugin action links.
 */
function hp_bp_tweaks_add_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=hp-bp-tweaks-settings">' . __('הגדרות') . '</a>';
    array_unshift($links, $settings_link); // Add to the beginning of the links array
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'hp_bp_tweaks_add_settings_link'); 