<?php
/**
 * Plugin Name:       Homer Patuach - BuddyPress Tweaks
 * Plugin URI:        https://example.com/
 * Description:       Custom styles and functionality for BuddyPress pages.
 * Version:           1.4.0
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

define( 'HP_BP_TWEAKS_VERSION', '1.0.0' );
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
 */
function hp_bp_tweaks_hide_admin_bar() {
    if ( ! current_user_can( 'manage_options' ) && is_user_logged_in() ) {
        add_filter('show_admin_bar', '__return_false');
    }
}
add_action( 'init', 'hp_bp_tweaks_hide_admin_bar' );


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
 */
function hp_bp_tweaks_add_user_bar() {
    ?>
    <div class="hp-bp-user-bar">
        <div class="hp-bp-user-bar-inner">
            <?php if ( is_user_logged_in() ) :
                $user_id = get_current_user_id();
                $profile_url = bp_core_get_user_domain( $user_id );
                $my_posts_url = rtrim($profile_url, '/') . '/my-posts/';
                $friends_url = rtrim($profile_url, '/') . '/friends/';
                $profile_edit_url = rtrim($profile_url, '/') . '/profile/edit/';
                $logout_url = wp_logout_url( home_url() );
            ?>
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
            <?php else :
                $login_url = wp_login_url( get_permalink() );
                $register_url = wp_registration_url();
            ?>
                 <div class="hp-bp-user-links">
                    <a href="<?php echo esc_url($login_url); ?>">התחברות</a>
                    <a href="<?php echo esc_url($register_url); ?>" class="button-register">הרשמה</a>
                </div>
            <?php endif; ?>
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
    $user_id = wp_create_user( $user_login, $user_pass, $user_email );

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
 * Customize user data upon registration.
 * Runs AFTER the user is created to ensure all social login data is present.
 * - Sets user_nicename (slug) from the email address prefix.
 * - Sets display_name and nickname from full name or the generated nicename.
 */
function hp_bp_tweaks_customize_user_after_creation( $user_id ) {
    // Get user data object
    $user = get_userdata( $user_id );
    if ( ! $user ) {
        return;
    }

    $email = $user->user_email;

    // We only proceed if we have an email to work with
    if ( empty( $email ) || strpos( $email, '@' ) === false ) {
        return;
    }

    $email_parts = explode( '@', $email );
    // Use sanitize_user to be safer and closer to WP standards for usernames. Then sanitize_title for URL slug.
    $new_nicename_base = sanitize_title( sanitize_user( $email_parts[0], true ) );

    // Ensure the nicename is unique. If the current user already has this nicename, we're good.
    // Otherwise, we find a new one.
    global $wpdb;
    $original_nicename = $new_nicename_base;
    $new_nicename = $original_nicename;
    $i = 2;
    // Check if the nicename is already taken by ANOTHER user
    $existing_user_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_nicename = %s", $new_nicename ) );
    while ( $existing_user_id && $existing_user_id != $user_id ) {
        $new_nicename = $original_nicename . '-' . $i;
        $i++;
        $existing_user_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_nicename = %s", $new_nicename ) );
    }

    // --- Data to update ---
    $user_data_to_update = array(
        'ID' => $user_id,
        'user_nicename' => $new_nicename,
    );

    // --- Set a better display name ---
    $new_display_name = '';

    // Priority 1: Use first name and last name if they exist in user meta (common with social logins)
    $first_name = get_user_meta( $user_id, 'first_name', true );
    $last_name = get_user_meta( $user_id, 'last_name', true );

    if ( ! empty( trim( $first_name ) ) ) {
        $new_display_name = trim( $first_name );
        if ( ! empty( trim( $last_name ) ) ) {
            $new_display_name .= ' ' . trim( $last_name );
        }
    }

    // Priority 2: In BuddyPress, check the "Full Name" xProfile field.
    if ( empty($new_display_name) && function_exists('xprofile_get_field_data') ) {
        // As per previous code, we assume the field is named "שם מלא".
        $full_name_field_id = bp_xprofile_get_field_id_from_name('שם מלא');
        if ( $full_name_field_id ) {
             $bp_full_name = xprofile_get_field_data( $full_name_field_id, $user_id );
             if ( ! empty( trim( $bp_full_name ) ) ) {
                $new_display_name = trim( $bp_full_name );
             }
        }
    }
    
    // Priority 3: Fallback to creating a readable name from the new nicename.
    if ( empty( $new_display_name ) ) {
        $new_display_name = ucwords( str_replace( ['-', '_'], ' ', $new_nicename ) );
    }
    
    // Update nickname and display_name in the data array.
    if ( ! empty( trim( $new_display_name ) ) ) {
        $user_data_to_update['nickname'] = trim($new_display_name);
        $user_data_to_update['display_name'] = trim($new_display_name);

        // --- ALSO update BuddyPress xProfile "Full Name" field to ensure sync ---
        if ( function_exists('xprofile_set_field_data') ) {
            // As per previous code, we assume the field is named "שם מלא".
            $full_name_field_id = bp_xprofile_get_field_id_from_name('שם מלא');
            if ( $full_name_field_id ) {
                xprofile_set_field_data( $full_name_field_id, $user_id, trim($new_display_name) );
            }
        }
    }

    // Only update if we have something to change to avoid loops.
    if ( count($user_data_to_update) > 1 ) {
        wp_update_user( $user_data_to_update );
    }
}
add_action( 'user_register', 'hp_bp_tweaks_customize_user_after_creation', 20, 1 ); 