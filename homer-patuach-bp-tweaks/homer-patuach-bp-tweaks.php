<?php
/**
 * Plugin Name:       Homer Patuach - BuddyPress Tweaks
 * Plugin URI:        https://example.com/
 * Description:       Custom styles and functionality for BuddyPress pages.
 * Version:           1.2.0
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
function hp_bp_tweaks_add_bio_profile_field() {
    if ( function_exists( 'xprofile_insert_field' ) && function_exists('bp_xprofile_get_field_id_from_name') ) {
        $field_name = 'קצת עליי';
        // Check if the field already exists to avoid duplicates
        if ( ! bp_xprofile_get_field_id_from_name( $field_name ) ) {
            xprofile_insert_field(
                array(
                    'field_group_id' => 1, // Base field group
                    'name'           => $field_name,
                    'description'    => 'ספרו קצת על עצמכם, על תחומי העניין והמומחיות שלכם.',
                    'type'           => 'textarea',
                    'can_delete'     => false,
                    'is_required'    => false,
                )
            );
        }
    }
}
add_action( 'bp_init', 'hp_bp_tweaks_add_bio_profile_field', 5 ); 