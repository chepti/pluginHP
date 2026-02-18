<?php
/**
 * Plugin Name:       Homer Patuach - BuddyPress Tweaks
 * Plugin URI:        https://example.com/
 * Description:       Custom styles and functionality for BuddyPress pages with community badges system.
 * Version:           3.0.1
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

define( 'HP_BP_TWEAKS_VERSION', '3.0.1' );
define( 'HP_BP_TWEAKS_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'HP_BP_TWEAKS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Include badges system
if ( file_exists( HP_BP_TWEAKS_PLUGIN_DIR . 'includes/badges-system.php' ) ) {
    require_once HP_BP_TWEAKS_PLUGIN_DIR . 'includes/badges-system.php';
}

// Include admin functions
if ( file_exists( HP_BP_TWEAKS_PLUGIN_DIR . 'includes/admin-functions.php' ) ) {
    require_once HP_BP_TWEAKS_PLUGIN_DIR . 'includes/admin-functions.php';
}

// Include admin columns
if ( file_exists( HP_BP_TWEAKS_PLUGIN_DIR . 'includes/admin-columns.php' ) ) {
    require_once HP_BP_TWEAKS_PLUGIN_DIR . 'includes/admin-columns.php';
}

// Include group add members (הוספת חברים ישירות לקבוצה)
if ( file_exists( HP_BP_TWEAKS_PLUGIN_DIR . 'includes/group-add-members.php' ) ) {
    require_once HP_BP_TWEAKS_PLUGIN_DIR . 'includes/group-add-members.php';
}
/**
 * Enqueue custom stylesheet for the theme.
 */
function hp_bp_tweaks_enqueue_styles() {
    // Styles should be loaded globally for the user bar.
    wp_enqueue_style(
        'hp-bp-tweaks-styles', // handle
        HP_BP_TWEAKS_PLUGIN_DIR_URL . 'assets/css/style.css', // path
        [], // dependencies
        HP_BP_TWEAKS_VERSION . '-' . time() // version with timestamp to force reload
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
            HP_BP_TWEAKS_VERSION . '-' . time(), // version with timestamp to force reload
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
 * Set custom email sender address for WordPress emails.
 */
function hp_bp_tweaks_set_email_from( $email ) {
    return 'Chep@chepti.com';
}
add_filter( 'wp_mail_from', 'hp_bp_tweaks_set_email_from' );

/**
 * Set custom email sender name for WordPress emails.
 */
function hp_bp_tweaks_set_email_from_name( $name ) {
    return 'Chep';
}
add_filter( 'wp_mail_from_name', 'hp_bp_tweaks_set_email_from_name' );

/**
 * מאפשר לעורכים (Editor) ולמנהלים (Administrator) ליצור קבוצות BuddyPress.
 * ברירת המחדל של BuddyPress יכולה להגביל יצירת קבוצות; פילטר זה מוודא שעורכים יכולים.
 */
function hp_bp_tweaks_allow_editors_create_groups( $can_create ) {
	if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' ) ) {
		return true;
	}
	return $can_create;
}
add_filter( 'bp_user_can_create_groups', 'hp_bp_tweaks_allow_editors_create_groups', 10, 1 );

/**
 * Translate specific strings in BuddyPress.
 */
function hp_bp_tweaks_translate_text( $translated_text, $text, $domain ) {
    if ( 'buddypress' === $domain ) {
        switch ( $text ) {
            // כותרות וניווט כלליים
            case 'Member Activities':
                $translated_text = 'פעילויות';
                break;
            case 'Home':
                $translated_text = 'פעילות';
                break;
            case 'Group Activities':
                $translated_text = 'פעילויות הקבוצה';
                break;
            case 'Group Administrators':
                $translated_text = 'מנהלי הקבוצה';
                break;
            case 'Manage':
                $translated_text = 'ניהול';
                break;
            case 'Invite':
                $translated_text = 'הזמנת חברים';
                break;
            case 'Members':
                $translated_text = 'חברים';
                break;
            case 'Joined':
                $translated_text = 'הצטרף';
                break;
            case 'Joined %s':
                $translated_text = 'הצטרף %s';
                break;
            case 'Add Friend':
                $translated_text = 'הוסף כחבר';
                break;
            case 'Friends':
                $translated_text = 'חברים';
                break;
            case 'Friend':
                $translated_text = 'חבר';
                break;
            case 'Membership List':
                $translated_text = 'רשימת חברים';
                break;
            case '%d Members':
                $translated_text = '%d חברים';
                break;
            case '%d Member':
                $translated_text = '%d חבר';
                break;
            case '%s Members':
                $translated_text = '%s חברים';
                break;
            case '%s Member':
                $translated_text = '%s חבר';
                break;

            // טקסטי מצב/זמן בפעילויות
            case 'Active %s':
                $translated_text = 'פעילה %s';
                break;
            case '%s ago':
                $translated_text = 'לפני %s';
                break;
            case '%d hours ago':
                $translated_text = 'לפני %d שעות';
                break;
            case '%d hour ago':
                $translated_text = 'לפני שעה';
                break;
            case '%d minutes ago':
                $translated_text = 'לפני %d דקות';
                break;
            case '%d minute ago':
                $translated_text = 'לפני דקה';
                break;
            case 'hour':
                $translated_text = 'שעה';
                break;
            case 'hours':
                $translated_text = 'שעות';
                break;
            case 'minute':
                $translated_text = 'דקה';
                break;
            case 'minutes':
                $translated_text = 'דקות';
                break;
            case 'day':
                $translated_text = 'יום';
                break;
            case 'days':
                $translated_text = 'ימים';
                break;
            case 'week':
                $translated_text = 'שבוע';
                break;
            case 'weeks':
                $translated_text = 'שבועות';
                break;
            case 'month':
                $translated_text = 'חודש';
                break;
            case 'months':
                $translated_text = 'חודשים';
                break;
            case 'year':
                $translated_text = 'שנה';
                break;
            case 'years':
                $translated_text = 'שנים';
                break;
            case '%d days ago':
                $translated_text = 'לפני %d ימים';
                break;
            case '%d day ago':
                $translated_text = 'לפני יום';
                break;
            case '%d weeks ago':
                $translated_text = 'לפני %d שבועות';
                break;
            case '%d week ago':
                $translated_text = 'לפני שבוע';
                break;
            case 'a week ago':
                $translated_text = 'לפני שבוע';
                break;
            case 'a day ago':
                $translated_text = 'לפני יום';
                break;

            // שדות טופס והרשמה
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

/**
 * Translate plural strings in BuddyPress (ngettext).
 */
function hp_bp_tweaks_translate_plural_text( $translated, $single, $plural, $number, $domain ) {
    if ( 'buddypress' === $domain ) {
        // "Member" / "Members"
        if ( $single === 'Member' && $plural === 'Members' ) {
            return $number === 1 ? 'חבר' : 'חברים';
        }
        // "hour" / "hours"
        if ( $single === 'hour' && $plural === 'hours' ) {
            return $number === 1 ? 'שעה' : 'שעות';
        }
        // "minute" / "minutes"
        if ( $single === 'minute' && $plural === 'minutes' ) {
            return $number === 1 ? 'דקה' : 'דקות';
        }
        // "day" / "days"
        if ( $single === 'day' && $plural === 'days' ) {
            return $number === 1 ? 'יום' : 'ימים';
        }
        // "week" / "weeks"
        if ( $single === 'week' && $plural === 'weeks' ) {
            return $number === 1 ? 'שבוע' : 'שבועות';
        }
        // "month" / "months"
        if ( $single === 'month' && $plural === 'months' ) {
            return $number === 1 ? 'חודש' : 'חודשים';
        }
        // "year" / "years"
        if ( $single === 'year' && $plural === 'years' ) {
            return $number === 1 ? 'שנה' : 'שנים';
        }
    }
    return $translated;
}
add_filter( 'ngettext', 'hp_bp_tweaks_translate_plural_text', 20, 5 );


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

    // This link will now trigger the popup from the other plugin
    ?>
    <a href="#" class="hp-bp-floating-button hpg-open-popup-button" title="הוספת פוסט חדש">
        <span class="hp-bp-floating-plus">+</span>
    </a>
    <?php
}
add_action( 'wp_footer', 'hp_bp_tweaks_add_floating_button' );

/**
 * חיפוש Astra במובייל: העברת בלוק החיפוש ל-body בפתיחה כדי שיצוף ברוחב מלא (יוצא מקונטיינר ההדר).
 */
function hp_bp_tweaks_astra_search_move_to_body() {
    ?>
    <script>
    (function() {
        function initAstraSearchFloat() {
            var wrap = document.querySelector('.ast-header-break-point .ast-search-menu-icon');
            if (!wrap) return;
            var inner = wrap.querySelector('.ast-search-menu-icon-inner');
            var input = wrap.querySelector('.search-field') || wrap.querySelector('input[type="search"]');
            if (!inner || !input) return;

            input.addEventListener('focus', function() {
                if (inner.parentNode === document.body) return;
                document.body.appendChild(inner);
                inner.classList.add('hp-search-floating');
                wrap.classList.add('hp-search-inner-floating');
            });

            input.addEventListener('blur', function() {
                setTimeout(function() {
                    if (inner.contains(document.activeElement)) return;
                    wrap.appendChild(inner);
                    inner.classList.remove('hp-search-floating');
                    wrap.classList.remove('hp-search-inner-floating');
                }, 300);
            });
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAstraSearchFloat);
        } else {
            initAstraSearchFloat();
        }
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'hp_bp_tweaks_astra_search_move_to_body', 25 );

/**
 * Renders the custom user menu HTML.
 * This is now used by the shortcode.
 */
function hp_bp_tweaks_get_user_bar_html() {
    ob_start();

    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $profile_url = bp_core_get_user_domain( $user_id );
        $my_posts_url = rtrim($profile_url, '/') . '/my-posts/';
        $collections_url = rtrim($profile_url, '/') . '/collections/';
        $friends_url = rtrim($profile_url, '/') . '/friends/';
        $profile_edit_url = rtrim($profile_url, '/') . '/profile/edit/';
        $logout_url = wp_logout_url( home_url() );
        ?>
        <div class="hp-bp-user-menu-container">
            <div class="hp-bp-user-menu">
                <button class="hp-bp-profile-trigger" aria-haspopup="true" aria-expanded="false">
                    <?php echo get_avatar( $user_id, 40 ); ?>
                </button>
                <div class="hp-bp-dropdown-menu" aria-hidden="true">
                    <a href="#" class="hpg-open-popup-button">הוסף פוסט</a>
                    <a href="<?php echo esc_url($my_posts_url); ?>">הפוסטים שלי</a>
                    <a href="<?php echo esc_url($profile_edit_url); ?>">הפרופיל שלי</a>
                    <a href="<?php echo esc_url($collections_url); ?>">האוספים שלי</a>
                    <a href="<?php echo esc_url($friends_url); ?>">חברים</a>
                    <a href="<?php echo esc_url($logout_url); ?>" class="logout-link">התנתקות</a>
                </div>
            </div>
        </div>
        <?php
    } else {
        // --- Logged-out User View ---
        ?>
        <div class="hp-bp-user-menu-container hp-bp-logged-out">
            <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="hp-bp-login-link" title="התחברות / הרשמה">
                 <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="38" height="38"><path fill-rule="evenodd" d="M18.685 19.097A9.723 9.723 0 0021.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 003.065 7.097A9.716 9.716 0 0012 21.75a9.716 9.716 0 006.685-2.653zm-12.54-1.285A7.718 7.718 0 0112 15.75a7.718 7.718 0 015.855 2.062A8.25 8.25 0 0112 20.25a8.25 8.25 0 01-5.855-2.438zM15.75 9a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" clip-rule="evenodd" /></svg>
            </a>
        </div>
        <?php
    }

    return ob_get_clean();
}

/**
 * Original function to add the user bar.
 * We are now disabling this to use a shortcode instead.
 */
function hp_bp_tweaks_add_user_bar() {
    echo hp_bp_tweaks_get_user_bar_html();
}
// add_action( 'wp_body_open', 'hp_bp_tweaks_add_user_bar' ); // Disabled in favor of shortcode

/**
 * Register the shortcode [hp_custom_user_menu]
 */
function hp_bp_tweaks_register_user_menu_shortcode() {
    return hp_bp_tweaks_get_user_bar_html();
}
add_shortcode( 'hp_custom_user_menu', 'hp_bp_tweaks_register_user_menu_shortcode' );


/**
 * תפריט מובייל – המבורגר + דרור (drawer) עם טקסט ברור.
 * שורטקוד: [hp_mobile_nav]
 * להצבה בכותרת לצד הלוגו; במובייל מוצג כפתור המבורגר שפותח דרור עם פריטי התפריט.
 */
function hp_bp_tweaks_mobile_nav_fallback( $args ) {
    $links = [];
    if ( function_exists( 'bp_get_members_directory_permalink' ) ) {
        $links[] = [ 'url' => bp_get_members_directory_permalink(), 'label' => 'קהילה' ];
    }
    if ( function_exists( 'bp_get_groups_directory_permalink' ) ) {
        $links[] = [ 'url' => bp_get_groups_directory_permalink(), 'label' => 'קבוצות' ];
    }
    $links[] = [ 'url' => home_url( '/' ), 'label' => 'אוספים' ];
    $links[] = [ 'url' => 'https://openstuff.co.il/clips/', 'label' => 'הקליפס' ];
    $links[] = [ 'url' => home_url( '/wp-admin/' ), 'label' => '?' ];
    echo '<ul class="hp-mobile-nav-menu" role="navigation" aria-label="תפריט ראשי">';
    foreach ( $links as $item ) {
        echo '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a></li>';
    }
    echo '</ul>';
}

function hp_bp_tweaks_mobile_nav_html() {
    ob_start();
    ?>
    <div class="hp-mobile-nav-wrap" id="hp-mobile-nav-wrap">
        <button type="button" class="hp-mobile-nav-toggle" aria-controls="hp-mobile-nav-drawer" aria-expanded="false" aria-label="פתח תפריט">
            <span class="hp-mobile-nav-icon" aria-hidden="true">
                <span></span><span></span><span></span>
            </span>
        </button>
        <div class="hp-mobile-nav-overlay" id="hp-mobile-nav-overlay" aria-hidden="true"></div>
        <div class="hp-mobile-nav-drawer" id="hp-mobile-nav-drawer" role="dialog" aria-label="תפריט ניווט" aria-modal="true" aria-hidden="true">
            <nav class="hp-mobile-nav-inner">
                <?php
                if ( has_nav_menu( 'primary' ) ) {
                    wp_nav_menu( [
                        'theme_location' => 'primary',
                        'menu_class'     => 'hp-mobile-nav-menu',
                        'container'      => false,
                        'fallback_cb'    => false,
                    ] );
                } elseif ( has_nav_menu( 'mobile' ) ) {
                    wp_nav_menu( [
                        'theme_location' => 'mobile',
                        'menu_class'     => 'hp-mobile-nav-menu',
                        'container'      => false,
                        'fallback_cb'    => false,
                    ] );
                } else {
                    hp_bp_tweaks_mobile_nav_fallback( [] );
                }
                ?>
            </nav>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function hp_bp_tweaks_register_mobile_nav_shortcode() {
    return hp_bp_tweaks_mobile_nav_html();
}
add_shortcode( 'hp_mobile_nav', 'hp_bp_tweaks_register_mobile_nav_shortcode' );


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
 * הוספת לשונית "פוסטים של הקבוצה" בעמודי קבוצה בבאדיפרס.
 * הלשונית מוצגת ראשונה בסרגל הניווט של הקבוצה ומציגה גריד פוסטים
 * בפורמט של דף הבית, כולל שורת יוצר בכל כרטיס.
 */
function hp_bp_tweaks_register_group_posts_tab() {
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return;
    }

    if ( ! function_exists( 'groups_get_current_group' ) ) {
        return;
    }

    $group = groups_get_current_group();
    if ( empty( $group ) || empty( $group->id ) ) {
        return;
    }

    $group_link = bp_get_group_permalink( $group );

    bp_core_new_subnav_item(
        [
            'name'            => __( 'פוסטים של הקבוצה', 'homer-patuach-bp-tweaks' ),
            'slug'            => 'group-posts',
            'parent_url'      => $group_link,
            'parent_slug'     => bp_get_current_group_slug(),
            'screen_function' => 'hp_bp_tweaks_group_posts_screen',
            'position'        => 5,
            'item_css_id'     => 'group-posts',
        ],
        'groups'
    );
}
add_action( 'bp_groups_setup_nav', 'hp_bp_tweaks_register_group_posts_tab', 5 );

/**
 * Callback למסך "פוסטים של הקבוצה".
 */
function hp_bp_tweaks_group_posts_screen() {
    add_action( 'bp_template_content', 'hp_bp_tweaks_group_posts_screen_content' );
    bp_core_load_template( apply_filters( 'bp_groups_template_group_home', 'groups/single/home' ) );
}

/**
 * התוכן בפועל של לשונית "פוסטים של הקבוצה".
 */
function hp_bp_tweaks_group_posts_screen_content() {
    $group = groups_get_current_group();
    $group_name = '';
    if ( $group && ! empty( $group->name ) ) {
        $group_name = esc_html( $group->name );
    }
    
    echo '<div class="hpg-group-posts-wrapper">';
    if ( $group_name ) {
        echo '<h2 class="hpg-group-posts-title">פוסטים של הקבוצה: ' . $group_name . '</h2>';
    } else {
        echo '<h2 class="hpg-group-posts-title">פוסטים של הקבוצה</h2>';
    }

    // הוסף כפתור פעמון למנחי הקבוצה
    if ( $group && ! empty( $group->id ) ) {
        $user_id = get_current_user_id();
        // בדוק אם המשתמש הוא מנחה או עורך או מנהל קבוצה
        $is_moderator = false;
        if ( function_exists( 'groups_is_user_mod' ) ) {
            $is_moderator = groups_is_user_mod( $user_id, $group->id );
        }
        if ( ! $is_moderator && function_exists( 'groups_is_user_admin' ) ) {
            $is_moderator = groups_is_user_admin( $user_id, $group->id );
        }
        // גם עורכים יכולים לראות
        if ( ! $is_moderator && current_user_can( 'edit_others_posts' ) ) {
            $is_moderator = true;
        }
        
        if ( $is_moderator ) {
            // ספירת פוסטים ממתינים בלבד (suppress_filters מונע דריסה ל־publish ב־pre_get_posts)
            $pending_count = hp_bp_tweaks_get_group_pending_posts_count( $group->id );
            $has_pending_class = $pending_count > 0 ? ' hpg-has-pending' : '';
            $title = $pending_count > 0
                ? sprintf( __( '%s פוסטים ממתינים לאישור – לחץ לפתיחה', 'homer-patuach-bp-tweaks' ), number_format_i18n( $pending_count ) )
                : __( 'בדיקת פוסטים – אין ממתינים כרגע; לחץ לפתיחה', 'homer-patuach-bp-tweaks' );

            $members_sort_url = trailingslashit( bp_get_group_permalink( $group ) ) . 'members/?members_order_by=post_count';
            echo '<div class="hpg-group-bell-wrapper" style="margin-bottom: 20px; text-align: left; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">';
            echo '<a href="#" class="hpg-group-approval-bell hpg-shortcode-bell' . esc_attr( $has_pending_class ) . '" data-group-id="' . esc_attr( $group->id ) . '" title="' . esc_attr( $title ) . '">';
            echo '<svg class="hpg-bell-svg" xmlns="http://www.w3.org/2000/svg" height="22px" viewBox="0 0 24 24" width="22px" fill="#ffffff"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.21 1.79-4 4-4s4 1.79 4 4v6z"/></svg>';
            echo '<span class="hpg-pending-count hpg-group-pending-count">' . esc_html( $pending_count ) . '</span>';
            echo '</a>';
            echo '<a href="' . esc_url( $members_sort_url ) . '" class="hpg-sort-by-posts-btn" title="' . esc_attr__( 'מיון החברים לפי כמות הפוסטים', 'homer-patuach-bp-tweaks' ) . '">';
            echo '<svg class="hpg-sort-posts-svg" xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 0 24 24" width="20px" fill="currentColor"><path d="M3 18h6v-2H3v2zM3 6v2h18V6H3zm0 7h12v-2H3v2z"/></svg>';
            echo '<span>' . esc_html__( 'מיון לפי פוסטים', 'homer-patuach-bp-tweaks' ) . '</span>';
            echo '</a>';
            echo '</div>';
        }
    }

    if ( function_exists( 'hpg_render_group_members_posts_grid' ) ) {
        echo hpg_render_group_members_posts_grid();
    } else {
        echo '<p>לא נמצא גריד הפוסטים (התוסף Homer Patuach Grid כבוי?).</p>';
    }

    echo '</div>';
}

/**
 * הוסף את שם הקבוצה גם בעמוד החברים.
 */
function hp_bp_tweaks_add_group_name_to_members_page() {
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return;
    }
    
    if ( ! function_exists( 'bp_is_current_action' ) || ! bp_is_current_action( 'members' ) ) {
        return;
    }
    
    $group = groups_get_current_group();
    if ( ! $group || empty( $group->name ) ) {
        return;
    }
    
    $group_name = esc_html( $group->name );
    echo '<div class="hpg-group-name-header" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px;">';
    echo '<h2 style="margin: 0; font-size: 1.5rem; color: #333;">' . $group_name . '</h2>';
    echo '</div>';
}
add_action( 'bp_before_group_members_content', 'hp_bp_tweaks_add_group_name_to_members_page', 5 );

/**
 * מזריק per_page גבוה ל־bp_ajax_querystring של group_members – גיבוי במקרה
 * שהטמפלייט/נובו משתמשים במחרוזת לפני bp_parse_args.
 */
function hp_bp_tweaks_ajax_querystring_group_members( $query_string, $object ) {
    if ( $object !== 'group_members' && $object !== 'group-members' ) {
        return $query_string;
    }
    $query_string = preg_replace( '/per_page=\d+/', 'per_page=999', $query_string );
    $query_string = preg_replace( '/mlpage=\d+/', 'mlpage=1', $query_string );
    if ( strpos( $query_string, 'per_page=' ) === false ) {
        $query_string .= ( $query_string !== '' ? '&' : '' ) . 'per_page=999';
    }
    return $query_string;
}
add_filter( 'bp_ajax_querystring', 'hp_bp_tweaks_ajax_querystring_group_members', 20, 2 );

/**
 * תיקון ניווט ומיון ברשימת חברי קבוצה – במיוחד בקבוצות שהצטרפו אליהן דרך קישור.
 * מוודא ש-group_id, page, type ו-search_terms תמיד מועברים נכון גם כש-bp_ajax_querystring
 * או ההקשר חסרים (למשל אחרי redirect מקישור הזמנה).
 */
function hp_bp_tweaks_fix_group_members_query_args( $args ) {
    // הצגת כל החברים בבת אחת (בלי פאגינציה)
    $args['per_page'] = 999;
    $args['page']     = 1;
    $args['max']      = 9999;

    // וודא group_id: אם ריק ויש קבוצה נוכחית, הזן
    if ( ( empty( $args['group_id'] ) || ! is_numeric( $args['group_id'] ) ) && function_exists( 'bp_get_current_group_id' ) ) {
        $gid = bp_get_current_group_id();
        if ( $gid ) {
            $args['group_id'] = (int) $gid;
        }
    }
    // וודא type (מיון): מ-members_order_by, type או orderby
    $orderby = isset( $_REQUEST['members_order_by'] ) ? $_REQUEST['members_order_by'] : ( isset( $_REQUEST['type'] ) ? $_REQUEST['type'] : ( isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : '' ) );
    if ( $orderby !== '' ) {
        $args['type'] = sanitize_text_field( $orderby );
    }
    // וודא search_terms: מ-members_search (טופס) או search-members
    $search = isset( $_REQUEST['members_search'] ) ? $_REQUEST['members_search'] : ( isset( $_REQUEST['search-members'] ) ? $_REQUEST['search-members'] : '' );
    if ( empty( $args['search_terms'] ) && $search !== '' ) {
        $args['search_terms'] = sanitize_text_field( $search );
    }
    return $args;
}
add_filter( 'bp_after_has_group_members_parse_args', 'hp_bp_tweaks_fix_group_members_query_args', 999 );

/**
 * מיון חברי קבוצה לפי כמות פוסטים – בעת type=post_count מעבירים user_ids ממוינים.
 */
function hp_bp_tweaks_group_members_sort_by_post_count( $query ) {
    if ( ! isset( $query->query_vars['type'] ) || $query->query_vars['type'] !== 'post_count' ) {
        return;
    }
    $group_id = isset( $query->query_vars['group_id'] ) ? (int) $query->query_vars['group_id'] : 0;
    if ( ! $group_id && function_exists( 'bp_get_current_group_id' ) ) {
        $group_id = (int) bp_get_current_group_id();
    }
    if ( ! $group_id || ! function_exists( 'groups_get_group_members' ) ) {
        return;
    }
    $members = groups_get_group_members(
        [
            'group_id' => $group_id,
            'per_page' => 9999,
            'page'     => 1,
        ]
    );
    if ( empty( $members['members'] ) ) {
        return;
    }
    $user_ids_with_posts = [];
    foreach ( $members['members'] as $m ) {
        $uid = isset( $m->user_id ) ? (int) $m->user_id : ( isset( $m->ID ) ? (int) $m->ID : 0 );
        if ( ! $uid ) {
            continue;
        }
        $count = function_exists( 'hpg_get_user_total_posts' ) ? hpg_get_user_total_posts( $uid ) : 0;
        $user_ids_with_posts[ $uid ] = $count;
    }
    arsort( $user_ids_with_posts, SORT_NUMERIC );
    $sorted_ids = array_keys( $user_ids_with_posts );
    if ( ! empty( $sorted_ids ) ) {
        $query->query_vars['user_ids'] = $sorted_ids;
    }
}
add_action( 'bp_pre_user_query_construct', 'hp_bp_tweaks_group_members_sort_by_post_count', 5 );

/**
 * Redirect כל כניסה לעמוד השורש של משתמש BuddyPress אל לשונית "הפוסטים שלי".
 * כך, קישורים סטנדרטיים לשם משתמש יגיעו ישירות ל- my-posts.
 */
function hp_bp_tweaks_redirect_member_root_to_my_posts() {
    if ( ! function_exists( 'bp_is_user' ) || ! bp_is_user() ) {
        return;
    }

    // אל תיגע אם כבר נמצאים ב-my-posts או בתת-עמוד אחר (חברים, הודעות וכו').
    if ( function_exists( 'bp_is_current_component' ) && bp_is_current_component( 'my-posts' ) ) {
        return;
    }

    if ( function_exists( 'bp_is_user_front' ) && bp_is_user_front() ) {
        $url = trailingslashit( rtrim( bp_displayed_user_domain(), '/' ) . '/my-posts' );
        bp_core_redirect( $url );
    }
}
add_action( 'bp_template_redirect', 'hp_bp_tweaks_redirect_member_root_to_my_posts', 9 );

/**
 * שנה את קישור ארכיון המחבר של וורדפרס אל עמוד ה-my-posts בבאדיפרס.
 */
function hp_bp_tweaks_author_link_to_my_posts( $link, $author_id = 0, $author_nicename = '' ) {
    if ( function_exists( 'bp_core_get_user_domain' ) && $author_id ) {
        return trailingslashit( rtrim( bp_core_get_user_domain( $author_id ), '/' ) . '/my-posts' );
    }
    return $link;
}
add_filter( 'author_link', 'hp_bp_tweaks_author_link_to_my_posts', 10, 3 );

/**
 * החלפת קישורי bp_core_get_userlink כך שיובילו ל-my-posts.
 * עובד גם כאשר מבוקש רק ה-URL וגם כאשר מוחזר HTML של עוגן.
 */
function hp_bp_tweaks_force_userlink_to_my_posts( $html_or_url, $user_id = 0 ) {
    if ( ! function_exists( 'bp_core_get_user_domain' ) ) {
        return $html_or_url;
    }

    if ( ! $user_id ) {
        $user_id = function_exists( 'bp_displayed_user_id' ) ? bp_displayed_user_id() : 0;
    }
    if ( ! $user_id ) {
        return $html_or_url;
    }

    $my_posts_url = trailingslashit( rtrim( bp_core_get_user_domain( $user_id ), '/' ) . '/my-posts' );

    // אם זה URL פשוט
    if ( strpos( $html_or_url, '<a' ) === false ) {
        return $my_posts_url;
    }

    // אם זה HTML של קישור, החלף את ה-href בלבד
    $updated = preg_replace( '/href=\"[^\"]*\"/i', 'href="' . esc_url( $my_posts_url ) . '"', $html_or_url );
    return $updated ? $updated : $html_or_url;
}
add_filter( 'bp_core_get_userlink', 'hp_bp_tweaks_force_userlink_to_my_posts', 10, 2 );


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
 * Hard exclude attachments at the SQL clauses level for any frontend search.
 */
function hp_bp_tweaks_exclude_attachments_in_clauses( $clauses, $query ) {
    if ( ! is_admin() && $query->is_search ) {
        global $wpdb;
        // Ensure the WHERE exists
        if ( empty( $clauses['where'] ) ) {
            $clauses['where'] = '';
        }
        // Add exclusion if not already present
        if ( strpos( $clauses['where'], "{$wpdb->posts}.post_type <> 'attachment'" ) === false ) {
            $clauses['where'] .= " AND {$wpdb->posts}.post_type <> 'attachment' ";
        }
        // Also restrict post_status to publish to avoid 'inherit'
        if ( strpos( $clauses['where'], "{$wpdb->posts}.post_status" ) === false ) {
            $clauses['where'] .= " AND {$wpdb->posts}.post_status = 'publish' ";
        }
        // DISTINCT to avoid duplicates if joins add rows
        $clauses['distinct'] = 'DISTINCT';
    }
    return $clauses;
}
add_filter( 'posts_clauses', 'hp_bp_tweaks_exclude_attachments_in_clauses', 9999, 2 );

/**
 * Final pass: remove attachments from the_posts array in case any slipped through.
 */
function hp_bp_tweaks_filter_the_posts( $posts, $query ) {
    if ( ! is_admin() && $query->is_search && ! empty( $posts ) ) {
        $posts = array_values( array_filter( $posts, function( $p ) {
            return isset( $p->post_type ) && $p->post_type !== 'attachment';
        } ) );
    }
    return $posts;
}
add_filter( 'the_posts', 'hp_bp_tweaks_filter_the_posts', 9999, 2 );

/**
 * Broad exclusion: remove attachments from any frontend list queries (search, archives, home, taxonomies).
 * Skips only when the query explicitly targets attachments or a singular attachment page.
 */
function hp_bp_tweaks_exclude_attachments_front_lists( $clauses, $query ) {
    if ( is_admin() ) {
        return $clauses;
    }

    // Skip if this query is explicitly for attachments or a singular object
    $pt = $query->get( 'post_type' );
    if ( ( is_string( $pt ) && $pt === 'attachment' ) || ( is_array( $pt ) && in_array( 'attachment', $pt, true ) ) || $query->is_singular ) {
        return $clauses;
    }

    // Apply on common listing contexts
    if ( $query->is_search || $query->is_home() || $query->is_archive() || $query->is_category() || $query->is_tag() || $query->is_tax() ) {
        global $wpdb;
        if ( empty( $clauses['where'] ) ) {
            $clauses['where'] = '';
        }
        if ( strpos( $clauses['where'], "{$wpdb->posts}.post_type <> 'attachment'" ) === false ) {
            $clauses['where'] .= " AND {$wpdb->posts}.post_type <> 'attachment' ";
        }
        // Also restrict to published content to avoid 'inherit' rows
        if ( strpos( $clauses['where'], "{$wpdb->posts}.post_status" ) === false ) {
            $clauses['where'] .= " AND {$wpdb->posts}.post_status = 'publish' ";
        }
        $clauses['distinct'] = 'DISTINCT';
    }

    return $clauses;
}
add_filter( 'posts_clauses', 'hp_bp_tweaks_exclude_attachments_front_lists', 9999, 2 );

/**
 * מסנן פוסטים שטרם אושרו (pending) כך שרק הבעלים, אדמינים ועורכים יראו אותם.
 * פוסטים pending לא יוצגו למשתמשים אחרים או למשתמשים לא מחוברים.
 */
/**
 * מוסיף pending posts ל-query רק בעמודי יוצרים, אם המשתמש מורשה לראות אותם.
 * בדף הבית, תוצאות חיפוש, וכו' - תמיד רק publish.
 */
function hp_bp_tweaks_add_pending_to_query( $query ) {
    // רק בפרונטאנד, לא באדמין
    if ( is_admin() ) {
        return;
    }

    // בדוק אם זה query של פוסטים
    $post_type = $query->get( 'post_type' );
    if ( empty( $post_type ) ) {
        $post_type = 'post'; // default
    }
    
    // רק עבור פוסטים רגילים
    if ( $post_type !== 'post' && ( ! is_array( $post_type ) || ! in_array( 'post', $post_type, true ) ) ) {
        return;
    }

    // בדוק אם זה עמוד יוצר (author page או BuddyPress member page)
    $is_author_page = false;
    $author_id = $query->get( 'author' );
    
    if ( ! empty( $author_id ) ) {
        // יש author_id ב-query - זה עמוד יוצר
        $is_author_page = true;
    } elseif ( is_author() ) {
        // זה author archive page
        $is_author_page = true;
    } elseif ( function_exists( 'bp_is_user' ) && bp_is_user() ) {
        // זה BuddyPress member page
        $is_author_page = true;
    }
    
    // אם זה לא עמוד יוצר, ודא שרק publish מוצג (לא pending, draft, trash)
    if ( ! $is_author_page ) {
        $post_status = $query->get( 'post_status' );
        if ( empty( $post_status ) ) {
            // אם אין post_status מוגדר, הגדר רק publish
            $query->set( 'post_status', 'publish' );
        } elseif ( is_array( $post_status ) ) {
            // הסר כל סטטוס שאינו publish
            $post_status = array_filter( $post_status, function( $status ) {
                return $status === 'publish';
            } );
            if ( empty( $post_status ) ) {
                $post_status = array( 'publish' );
            }
            $query->set( 'post_status', array_values( $post_status ) );
        } elseif ( $post_status !== 'publish' ) {
            // אם זה לא publish, שנה ל-publish
            $query->set( 'post_status', 'publish' );
        }
        return; // אל תמשיך - רק publish בדף הבית וכו'
    }
    
    // אם הגענו לכאן, זה עמוד יוצר - בדוק הרשאות
    $current_user_id = get_current_user_id();
    
    // אם אין משתמש מחובר, רק publish
    if ( $current_user_id === 0 ) {
        $post_status = $query->get( 'post_status' );
        if ( empty( $post_status ) || ( is_array( $post_status ) && ! in_array( 'publish', $post_status, true ) ) ) {
            $query->set( 'post_status', 'publish' );
        }
        return;
    }
    
    // בדוק אם המשתמש מורשה לראות pending posts בעמוד יוצר
    $can_view_pending = false;
    
    // אם המשתמש הוא אדמין או עורך, הוא יכול לראות pending של כל היוצרים
    if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' ) ) {
        $can_view_pending = true;
    }
    
    // אם המשתמש צופה בפרופיל שלו, הוא יכול לראות את ה-pending שלו
    $displayed_user_id = 0;
    
    if ( ! empty( $author_id ) ) {
        $displayed_user_id = (int) $author_id;
    } elseif ( is_author() ) {
        $displayed_user_id = get_queried_object_id();
    } elseif ( function_exists( 'bp_displayed_user_id' ) && bp_displayed_user_id() ) {
        $displayed_user_id = bp_displayed_user_id();
    }
    
    // רק אם המשתמש צופה בפרופיל שלו, תן לו לראות pending
    if ( $current_user_id > 0 && $current_user_id === $displayed_user_id ) {
        $can_view_pending = true;
    }
    
    // אם המשתמש מורשה, הוסף pending ל-post_status
    if ( $can_view_pending ) {
        $post_status = $query->get( 'post_status' );
        if ( empty( $post_status ) ) {
            // אם אין post_status מוגדר, הוסף publish ו-pending
            $post_status = array( 'publish', 'pending' );
        } elseif ( ! is_array( $post_status ) ) {
            // אם זה string, המר ל-array והוסף pending
            $post_status = array( $post_status );
            if ( ! in_array( 'pending', $post_status, true ) ) {
                $post_status[] = 'pending';
            }
        } else {
            // אם זה כבר array, הוסף pending אם הוא לא שם
            if ( ! in_array( 'pending', $post_status, true ) ) {
                $post_status[] = 'pending';
            }
        }
        
        $query->set( 'post_status', $post_status );
    } else {
        // אם המשתמש לא מורשה, רק publish
        $post_status = $query->get( 'post_status' );
        if ( empty( $post_status ) ) {
            $query->set( 'post_status', 'publish' );
        } elseif ( is_array( $post_status ) ) {
            // הסר pending מה-array
            $post_status = array_filter( $post_status, function( $status ) {
                return $status === 'publish';
            } );
            if ( empty( $post_status ) ) {
                $post_status = array( 'publish' );
            }
            $query->set( 'post_status', array_values( $post_status ) );
        } elseif ( $post_status !== 'publish' ) {
            $query->set( 'post_status', 'publish' );
        }
    }
}
add_action( 'pre_get_posts', 'hp_bp_tweaks_add_pending_to_query', 10, 1 );

/**
 * מסנן פוסטים pending לפי הרשאות.
 * גם מסנן draft, trash וכו' - רק publish מוצג למשתמשים רגילים.
 */
function hp_bp_tweaks_filter_pending_posts_by_permissions( $posts, $query ) {
    // רק בפרונטאנד, לא באדמין
    if ( is_admin() ) {
        return $posts;
    }

    // אם אין פוסטים, אין מה לסנן
    if ( empty( $posts ) ) {
        return $posts;
    }

    $current_user_id = get_current_user_id();
    $filtered_posts = array();
    
    // בדוק אם זה עמוד יוצר או פוסט בודד
    $is_author_page = false;
    $is_single_post = false;
    $author_id = 0;
    
    if ( is_author() ) {
        $is_author_page = true;
        $author_id = get_queried_object_id();
    } elseif ( function_exists( 'bp_is_user' ) && bp_is_user() ) {
        $is_author_page = true;
        if ( function_exists( 'bp_displayed_user_id' ) ) {
            $author_id = bp_displayed_user_id();
        }
    }
    
    // בדוק אם זה פוסט בודד - בדוק גם דרך query vars
    if ( ! $is_author_page ) {
        if ( is_singular( 'post' ) || 
             ( ! empty( $query->query_vars['p'] ) || ! empty( $query->query_vars['page_id'] ) || ! empty( $query->query_vars['name'] ) || isset( $_GET['p'] ) ) ) {
            $is_single_post = true;
        }
    }

    foreach ( $posts as $post ) {
        // אם הפוסט publish, תמיד הצג אותו
        if ( $post->post_status === 'publish' ) {
            $filtered_posts[] = $post;
            continue;
        }
        
        // אם הפוסט לא publish (pending, draft, trash, וכו'), בדוק הרשאות
        $post_author_id = (int) $post->post_author;
        $can_view = false;
        
        // בעמוד יוצר או בפוסט בודד - בדוק הרשאות
        if ( $is_author_page || $is_single_post ) {
            // 1. הבעלים של הפוסט תמיד יכול לראות
            if ( $current_user_id > 0 && $current_user_id === $post_author_id ) {
                $can_view = true;
            }
            // 2. אדמינים יכולים לראות (manage_options)
            elseif ( $current_user_id > 0 && current_user_can( 'manage_options' ) ) {
                $can_view = true;
            }
            // 3. עורכים יכולים לראות (edit_others_posts)
            elseif ( $current_user_id > 0 && current_user_can( 'edit_others_posts' ) ) {
                $can_view = true;
            }
        }
        
        // אם יש הרשאה, הוסף את הפוסט
        if ( $can_view ) {
            $filtered_posts[] = $post;
        }
        // אם אין הרשאה, הפוסט לא יוסף ל-$filtered_posts (יסונן החוצה)
    }

    return $filtered_posts;
}
add_filter( 'the_posts', 'hp_bp_tweaks_filter_pending_posts_by_permissions', 99999, 2 );


/**
 * מאפשר גישה לפוסט ב-pending/draft דרך template redirect.
 */
function hp_bp_tweaks_handle_pending_draft_post_access() {
    // רק בפרונטאנד, לא באדמין
    if ( is_admin() ) {
        return;
    }

    // אל תפריע לעמודי BuddyPress: פרופיל, הפוסטים שלי, חברים וכו'
    // הבדיקה הזו חייבת להיות לפני גישה ל-$wp_query – אחרת עלול להיזרק 404 לעמודי משתמש
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
    if ( strpos( $uri, '/friends/' ) !== false || strpos( $uri, '/members/' ) !== false ) {
        return;
    }
    if ( function_exists( 'bp_is_user' ) && bp_is_user() ) {
        return;
    }
    if ( function_exists( 'bp_is_members_component' ) && bp_is_members_component() ) {
        return;
    }
    
    global $wp_query, $post;
    
    // נסה לקבל את הפוסט מה-URL
    $post_id = 0;
    if ( isset( $_GET['p'] ) && is_numeric( $_GET['p'] ) ) {
        $post_id = (int) $_GET['p'];
    } elseif ( ! empty( $wp_query->query_vars['p'] ) ) {
        $post_id = (int) $wp_query->query_vars['p'];
    } elseif ( ! empty( $wp_query->query_vars['page_id'] ) ) {
        $post_id = (int) $wp_query->query_vars['page_id'];
    } elseif ( ! empty( $wp_query->query_vars['name'] ) ) {
        // נסה לקבל מה-permalink
        $post_obj = get_page_by_path( $wp_query->query_vars['name'], OBJECT, 'post' );
        if ( $post_obj ) {
            $post_id = $post_obj->ID;
        }
    }
    
    if ( empty( $post_id ) ) {
        return;
    }
    
    $post = get_post( $post_id );
    if ( empty( $post ) || $post->post_type !== 'post' ) {
        return;
    }
    
    // אם הפוסט publish, אין צורך לעשות כלום
    if ( $post->post_status === 'publish' ) {
        return;
    }
    
    $current_user_id = get_current_user_id();
    
    // אם אין משתמש מחובר, אל תאפשר גישה
    if ( $current_user_id === 0 ) {
        status_header( 404 );
        nocache_headers();
        return;
    }
    
    $post_author_id = (int) $post->post_author;
    $can_access = false;
    
    // 1. הבעלים של הפוסט יכול לראות
    if ( $current_user_id === $post_author_id ) {
        $can_access = true;
    }
    // 2. אדמינים יכולים לראות
    elseif ( current_user_can( 'manage_options' ) ) {
        $can_access = true;
    }
    // 3. עורכים יכולים לראות
    elseif ( current_user_can( 'edit_others_posts' ) ) {
        $can_access = true;
    }
    
    // אם המשתמש לא מורשה, החזר 404
    if ( ! $can_access ) {
        status_header( 404 );
        nocache_headers();
        return;
    }
    
    // אם המשתמש מורשה, ודא שהפוסט נטען נכון
    // WordPress בדרך כלל לא טוען pending/draft בפרונטאנד, אז נטען אותו ידנית
    if ( $post->post_status !== 'publish' ) {
        // ודא שהפוסט נטען ב-query
        if ( empty( $wp_query->posts ) || empty( $wp_query->posts[0] ) || $wp_query->posts[0]->ID !== $post->ID ) {
            // הוסף את הפוסט ל-query
            $wp_query->posts = array( $post );
            $wp_query->post_count = 1;
            $wp_query->is_singular = true;
            $wp_query->is_single = true;
            $wp_query->queried_object = $post;
            $wp_query->queried_object_id = $post->ID;
            $wp_query->found_posts = 1;
            $wp_query->max_num_pages = 1;
            
            // ודא שה-post global נטען
            $GLOBALS['post'] = $post;
            
            // טען את הפוסט מחדש עם כל המטא-דאטה
            $post = get_post( $post->ID, OBJECT );
            if ( $post ) {
                $wp_query->posts[0] = $post;
                $wp_query->queried_object = $post;
                $GLOBALS['post'] = $post;
                setup_postdata( $post );
            }
        }
        
        // הוסף הודעה שהפוסט לא פורסם
        add_action( 'wp_head', 'hp_bp_tweaks_add_pending_draft_notice' );
    }
}
add_action( 'template_redirect', 'hp_bp_tweaks_handle_pending_draft_post_access', 5 );

/**
 * מוסיף פוסט pending/draft ל-results אם הוא לא נמצא ב-query.
 */
function hp_bp_tweaks_add_pending_draft_to_posts( $posts, $query ) {
    // רק בפרונטאנד, לא באדמין
    if ( is_admin() ) {
        return $posts;
    }
    
    // בדוק אם זה main query או query של פוסט בודד
    $is_single = false;
    if ( $query->is_main_query() ) {
        // נסה לזהות אם זה פוסט בודד
        if ( isset( $_GET['p'] ) || ! empty( $query->query_vars['p'] ) || ! empty( $query->query_vars['page_id'] ) || ! empty( $query->query_vars['name'] ) ) {
            $is_single = true;
        }
    }
    
    if ( ! $is_single ) {
        return $posts;
    }
    
    // נסה לקבל את הפוסט מה-URL
    $post_id = 0;
    if ( isset( $_GET['p'] ) && is_numeric( $_GET['p'] ) ) {
        $post_id = (int) $_GET['p'];
    } elseif ( ! empty( $query->query_vars['p'] ) ) {
        $post_id = (int) $query->query_vars['p'];
    } elseif ( ! empty( $query->query_vars['page_id'] ) ) {
        $post_id = (int) $query->query_vars['page_id'];
    } elseif ( ! empty( $query->query_vars['name'] ) ) {
        // נסה לקבל מה-permalink
        $post_obj = get_page_by_path( $query->query_vars['name'], OBJECT, 'post' );
        if ( $post_obj ) {
            $post_id = $post_obj->ID;
        }
    }
    
    if ( empty( $post_id ) ) {
        return $posts;
    }
    
    $post = get_post( $post_id );
    if ( empty( $post ) || $post->post_type !== 'post' ) {
        return $posts;
    }
    
    // אם הפוסט publish, אין צורך לעשות כלום
    if ( $post->post_status === 'publish' ) {
        return $posts;
    }
    
    $current_user_id = get_current_user_id();
    if ( $current_user_id === 0 ) {
        return $posts;
    }
    
    $post_author_id = (int) $post->post_author;
    $can_access = false;
    
    // 1. הבעלים של הפוסט יכול לראות
    if ( $current_user_id === $post_author_id ) {
        $can_access = true;
    }
    // 2. אדמינים יכולים לראות
    elseif ( current_user_can( 'manage_options' ) ) {
        $can_access = true;
    }
    // 3. עורכים יכולים לראות
    elseif ( current_user_can( 'edit_others_posts' ) ) {
        $can_access = true;
    }
    
    // אם המשתמש מורשה, הוסף את הפוסט
    if ( $can_access ) {
        // אם אין פוסטים או שהפוסט הראשון לא זה, הוסף את הפוסט
        if ( empty( $posts ) || ( ! empty( $posts[0] ) && $posts[0]->ID !== $post->ID ) ) {
            // ודא שהפוסט נטען עם כל המטא-דאטה
            $post = get_post( $post_id, OBJECT );
            if ( $post ) {
                $posts = array( $post );
            }
        }
    }
    
    return $posts;
}
add_filter( 'the_posts', 'hp_bp_tweaks_add_pending_draft_to_posts', 5, 2 );

/**
 * מוסיף pending/draft ל-query אם המשתמש מורשה (רק בפוסטים בודדים).
 */
function hp_bp_tweaks_include_pending_draft_in_single_query( $query ) {
    // רק בפרונטאנד, לא באדמין
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }
    
    // רק בפוסטים בודדים
    if ( ! $query->is_singular( 'post' ) ) {
        return;
    }
    
    $current_user_id = get_current_user_id();
    if ( $current_user_id === 0 ) {
        return;
    }
    
    // נסה לקבל את ה-post ID מה-query
    $post_id = 0;
    if ( isset( $_GET['p'] ) && is_numeric( $_GET['p'] ) ) {
        $post_id = (int) $_GET['p'];
    } elseif ( $query->get( 'p' ) ) {
        $post_id = (int) $query->get( 'p' );
    } elseif ( $query->get( 'page_id' ) ) {
        $post_id = (int) $query->get( 'page_id' );
    } elseif ( $query->get( 'name' ) ) {
        // נסה לקבל מה-permalink
        $post_obj = get_page_by_path( $query->get( 'name' ), OBJECT, 'post' );
        if ( $post_obj ) {
            $post_id = $post_obj->ID;
        }
    }
    
    // בדוק אם המשתמש מורשה
    $can_access = false;
    
    // אם יש post_id, בדוק אם המשתמש הוא יוצר הפוסט
    if ( $post_id > 0 ) {
        $post = get_post( $post_id );
        if ( $post && $post->post_type === 'post' ) {
            $post_author_id = (int) $post->post_author;
            if ( $current_user_id === $post_author_id ) {
                $can_access = true;
            }
        }
    }
    
    // אדמינים ועורכים יכולים לראות
    if ( ! $can_access && ( current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' ) ) ) {
        $can_access = true;
    }
    
    // אם המשתמש מורשה, הוסף pending ו-draft ל-post_status
    if ( $can_access ) {
        $post_status = $query->get( 'post_status' );
        if ( empty( $post_status ) ) {
            $post_status = array( 'publish', 'pending', 'draft' );
        } elseif ( ! is_array( $post_status ) ) {
            $post_status = array( $post_status );
            if ( ! in_array( 'pending', $post_status, true ) ) {
                $post_status[] = 'pending';
            }
            if ( ! in_array( 'draft', $post_status, true ) ) {
                $post_status[] = 'draft';
            }
        } else {
            if ( ! in_array( 'pending', $post_status, true ) ) {
                $post_status[] = 'pending';
            }
            if ( ! in_array( 'draft', $post_status, true ) ) {
                $post_status[] = 'draft';
            }
        }
        $query->set( 'post_status', $post_status );
    }
}
add_action( 'pre_get_posts', 'hp_bp_tweaks_include_pending_draft_in_single_query', 10, 1 );

/**
 * מוסיף הודעה בפוסט pending/draft.
 */
function hp_bp_tweaks_add_pending_draft_notice() {
    global $post;
    if ( empty( $post ) ) {
        return;
    }
    
    $status_label = '';
    if ( $post->post_status === 'pending' ) {
        $status_label = 'ממתין לאישור';
    } elseif ( $post->post_status === 'draft' ) {
        $status_label = 'טיוטה';
    }
    
    if ( ! empty( $status_label ) ) {
        echo '<style>
            .hp-post-status-notice {
                background: #fff3cd;
                border: 2px solid #ffc107;
                border-radius: 5px;
                padding: 15px;
                margin: 20px 0;
                text-align: center;
                font-weight: bold;
                color: #856404;
            }
        </style>';
        echo '<div class="hp-post-status-notice">⚠️ פוסט זה נמצא במצב: ' . esc_html( $status_label ) . '</div>';
    }
}

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
                // AND a grouped OR block so existing constraints (like post_type=post) remain effective
                $where .= $wpdb->prepare(
                    " OR (({$wpdb->users}.display_name LIKE %s) OR ({$wpdb->postmeta}.meta_value LIKE %s)) ",
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

function hpg_get_user_total_tags_added( $user_id ) {
    $count = get_user_meta( $user_id, 'hpg_total_tags_added', true );
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
    
    // Get additional stats
    $total_comments_given = function_exists( 'hpg_get_user_comments_given' ) ? hpg_get_user_comments_given( $user_id ) : 0;
    $total_tags_added = hpg_get_user_total_tags_added( $user_id );
    ?>
    <div class="hpg-user-stats-container">
        <div class="hpg-stats-row">
            <div class="hpg-stat-item">
                <span class="hpg-stat-value"><?php echo number_format_i18n( $total_posts ); ?></span>
                <span class="hpg-stat-label">📝 פוסטים</span>
            </div>
            <div class="hpg-stat-item">
                <span class="hpg-stat-value"><?php echo number_format_i18n( $total_views ); ?></span>
                <span class="hpg-stat-label">👁️ צפיות</span>
            </div>
            <div class="hpg-stat-item">
                <span class="hpg-stat-value"><?php echo number_format_i18n( $total_comments ); ?></span>
                <span class="hpg-stat-label">💬 תגובות</span>
            </div>
            <div class="hpg-stat-item">
                <span class="hpg-stat-value"><?php echo number_format_i18n( $total_likes ); ?></span>
                <span class="hpg-stat-label">❤ לבבות</span>
            </div>
            <div class="hpg-stat-item">
                <span class="hpg-stat-value"><?php echo number_format_i18n( $total_comments_given ); ?></span>
                <span class="hpg-stat-label">💬 הגיב</span>
            </div>
            <div class="hpg-stat-item">
                <span class="hpg-stat-value"><?php echo number_format_i18n( $total_tags_added ); ?></span>
                <span class="hpg-stat-label">🏷️ תייג</span>
            </div>
        </div>
    </div>

    <?php
}
add_action( 'bp_after_member_header', 'hpg_display_user_reputation_stats' );

/**
 * Display badges on the cover image (top of profile banner)
 */
function hpg_display_badges_on_cover() {
    if ( ! bp_is_user() ) {
        return;
    }

    $user_id = bp_displayed_user_id();
    if ( ! $user_id ) {
        return;
    }

    // Display earned badges
    if ( function_exists( 'hpg_display_earned_badges' ) ) {
        $badges_html = hpg_display_earned_badges( $user_id );
        if ( ! empty( $badges_html ) ) {
            echo '<div class="hpg-cover-badges-wrapper">' . $badges_html . '</div>';
        }
    }
}
// Display badges inside the cover image container - try multiple hooks to find the right one
add_action( 'bp_after_cover_image_settings', 'hpg_display_badges_on_cover', 5 );
// Fallback: also try bp_before_member_header_meta in case the first doesn't work
add_action( 'bp_before_member_header_meta', 'hpg_display_badges_on_cover', 5 );

/**
 * מציג את הביו (xProfile: "קצת עליי") באזור הכותרת של פרופיל המשתמש.
 * נטען בתוך ה־header כך שהוא תמיד גלוי.
 */
function hpg_display_user_bio_in_header() {
    if ( ! function_exists( 'bp_is_user' ) || ! bp_is_user() ) {
        return;
    }

    $user_id = function_exists( 'bp_displayed_user_id' ) ? bp_displayed_user_id() : 0;
    if ( ! $user_id ) {
        return;
    }

    $bio_text = hpg_get_user_bio_raw( $user_id );

    if ( empty( $bio_text ) ) {
        return;
    }

    $bio_html = hpg_format_bio_html( $bio_text );
    ?>
    <div class="hpg-profile-bio"><?php echo $bio_html; ?></div>
    <?php
}
// ממוקם בתוך #item-header-content לפני ה־meta, כך שזה באמת בבאנר
add_action( 'bp_before_member_header_meta', 'hpg_display_user_bio_in_header' );

/**
 * מוסיף target="_blank" ו-rel="noopener nofollow ugc" לכל תגית <a> ב־HTML נתון.
 */
function hpg_add_target_blank_to_links( $html ) {
    return preg_replace_callback( '/<a\s[^>]*href=\"[^\"]+\"[^>]*>/i', function( $matches ) {
        $tag = $matches[0];
        // דאג ל-target
        if ( stripos( $tag, 'target=' ) === false ) {
            $tag = rtrim( $tag, '>' ) . ' target="_blank">';
        } else {
            $tag = preg_replace( '/target=\"[^\"]*\"/i', 'target="_blank"', $tag );
        }
        // דאג ל-rel בטוח
        if ( stripos( $tag, 'rel=' ) === false ) {
            $tag = rtrim( $tag, '>' ) . ' rel="noopener nofollow ugc">';
        }
        return $tag;
    }, $html );
}

/**
 * מחזיר טקסט ביו גולמי לפי סדר עדיפויות: xProfile("קצת עליי"/"ביו") ואז WP user description.
 */
function hpg_get_user_bio_raw_OLD( $user_id ) {
    $text = '';
    if ( function_exists( 'bp_xprofile_get_field_id_from_name' ) ) {
        foreach ( array( 'קצת עליי', 'ביו' ) as $name ) {
            $fid = bp_xprofile_get_field_id_from_name( $name );
            if ( $fid ) {
                $text = xprofile_get_field_data( $fid, $user_id );
                if ( ! empty( $text ) ) break;
            }
        }
    }
    if ( empty( $text ) ) {
        $text = get_user_meta( $user_id, 'description', true );
    }
    return (string) $text;
}

/**
 * מעצב טקסט ביו ל־HTML בטוח עם קישורים וירידות שורה.
 */
function hpg_format_bio_html( $bio_text ) {
    $bio_text = (string) $bio_text;
    $has_html = strpos( $bio_text, '<' ) !== false;
    if ( $has_html ) {
        $allowed_tags = [
            'a' => [ 'href' => true, 'title' => true, 'target' => true, 'rel' => true ],
            'p' => [], 'br' => [], 'strong' => [], 'em' => [], 'b' => [], 'i' => [],
            'ul' => [], 'ol' => [], 'li' => [], 'span' => [ 'style' => true ],
        ];
        $bio_html = wpautop( wp_kses( $bio_text, $allowed_tags ) );
    } else {
        $bio_html = wpautop( make_clickable( esc_html( $bio_text ) ) );
    }
    return hpg_add_target_blank_to_links( $bio_html );
}

/**
 * החלת העיצוב והקישורים גם על תצוגת השדה בעמוד "פרופיל" ובכל מקום שבו BuddyPress מציג את הערך.
 */
function hpg_filter_xprofile_bio_output( $value, $type = '' ) {
    if ( function_exists( 'bp_get_the_profile_field_name' ) ) {
        $name = bp_get_the_profile_field_name();
        if ( in_array( $name, array( 'קצת עליי', 'ביו' ), true ) ) {
            return hpg_format_bio_html( $value );
        }
    }
    return $value;
}
add_filter( 'bp_get_the_profile_field_value', 'hpg_filter_xprofile_bio_output', 20, 2 );

/**
 * הפעלת עורך עשיר (TinyMCE) לשדה xProfile "קצת עליי" בממשק העריכה הקדמי.
 */
function hpg_enable_richtext_for_bio_field( $enabled, $field ) {
    // נפתח עורך עשיר לכל שדה מסוג טקסט מרובה שורות (textarea) בעריכה הקדמית
    if ( is_object( $field ) ) {
        // BuddyPress מחזיר סוג כשם 'textarea' לשדה מרובה שורות
        if ( isset( $field->type ) && 'textarea' === $field->type ) {
            return true;
        }
        // וגם לפי שם נפוץ לביו
        if ( isset( $field->name ) && in_array( $field->name, array( 'קצת עליי', 'ביו' ), true ) ) {
            return true;
        }
    }
    return $enabled;
}
add_filter( 'bp_xprofile_is_richtext_enabled_for_field', 'hpg_enable_richtext_for_bio_field', 10, 2 );

/**
 * הזרקת תרגומים/שינויים טקסטואליים במסך עריכת פרופיל קדמי.
 */
function hpg_localize_profile_edit_texts() {
    if ( function_exists( 'bp_is_user_profile_edit' ) && bp_is_user_profile_edit() ) {
        ?>
        <script>
        (function(){
            try {
                var base = document.querySelector('#buddypress .bp-user #profile-edit-form .bp-profile-group .bp-heading');
                if (base && base.textContent && /Base/i.test(base.textContent)) { base.textContent = 'פרופיל'; }
                var headings = document.querySelectorAll('#buddypress #profile-edit-form h2');
                headings.forEach(function(h){ if (/Editing\s+"Base"\s+Profile\s+Group/i.test(h.textContent)) h.textContent = 'עריכת פרופיל'; });
                var hints = document.querySelectorAll('#buddypress #profile-edit-form .field-visibility-settings-toggle, #buddypress #profile-edit-form .field-visibility-settings-notoggle');
                hints.forEach(function(el){ el.style.display='none'; });
            } catch(e){}
        })();
        </script>
        <?php
    }
}
add_action( 'wp_footer', 'hpg_localize_profile_edit_texts', 20 );

/**
 * מציג באדג'ים, ספירת פוסטים וספירת תגובות ברשימת חברי קבוצה – בתחתית הכרטיס.
 */
function hpg_show_badges_in_group_members_list() {
    if ( ! function_exists( 'bp_get_member_user_id' ) ) {
        return;
    }

    $user_id = (int) bp_get_member_user_id();
    if ( ! $user_id ) {
        return;
    }

    $posts = function_exists( 'hpg_get_user_total_posts' ) ? hpg_get_user_total_posts( $user_id ) : 0;
    $comments = function_exists( 'hpg_get_user_comments_given' ) ? hpg_get_user_comments_given( $user_id ) : 0;
    $badges_html = function_exists( 'hpg_display_earned_badges' ) ? hpg_display_earned_badges( $user_id ) : '';

    $has_badges = ! empty( $badges_html );

    echo '<div class="hpg-member-badges-wrapper hpg-member-footer">';
    echo '<div class="hpg-member-badges hpg-badges-compact-inline">' . ( $has_badges ? $badges_html : '' ) . '</div>';
    echo '<div class="hpg-member-stats-inline">';
    echo '<span class="hpg-stat-posts">' . sprintf( esc_html( _n( '%s פוסט', '%s פוסטים', $posts, 'homer-patuach-bp-tweaks' ) ), number_format_i18n( $posts ) ) . '</span>';
    echo '<span class="hpg-stat-sep"> · </span>';
    echo '<span class="hpg-stat-comments">' . sprintf( esc_html( _n( '%s תגובה', '%s תגובות', $comments, 'homer-patuach-bp-tweaks' ) ), number_format_i18n( $comments ) ) . '</span>';
    echo '</div>';
    echo '</div>';
}
// priority מאוד גבוה כדי שירוץ אחרי כל התוכן (כולל הכפתור)
add_action( 'bp_group_members_list_item', 'hpg_show_badges_in_group_members_list', 999 );

/**
 * משנה קישורי שמות חברים בקבוצה כך שיובילו ל"הפוסטים שלי" במקום "פעילות".
 */
function hp_bp_tweaks_fix_group_member_name_link( $html, $user_id ) {
    if ( ! function_exists( 'bp_core_get_user_domain' ) || ! $user_id ) {
        return $html;
    }

    // רק ברשימת חברי קבוצה
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return $html;
    }

    if ( ! function_exists( 'bp_is_current_action' ) || ! bp_is_current_action( 'members' ) ) {
        return $html;
    }

    $my_posts_url = trailingslashit( rtrim( bp_core_get_user_domain( $user_id ), '/' ) . '/my-posts' );

    // החלף href בקישור
    if ( strpos( $html, '<a' ) !== false ) {
        $html = preg_replace( '/href=["\'][^"\']*["\']/i', 'href="' . esc_url( $my_posts_url ) . '"', $html );
    } else {
        $html = '<a href="' . esc_url( $my_posts_url ) . '">' . $html . '</a>';
    }

    return $html;
}
add_filter( 'bp_get_member_name', 'hp_bp_tweaks_fix_group_member_name_link', 10, 2 );

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

/**
 * מחזיר טקסט ביו גולמי לפי סדר עדיפויות: xProfile("קצת עליי"/"ביו") ואז WP user description.
 * גרסה משופרת עם חיפוש חכם יותר.
 */
function hpg_get_user_bio_raw( $user_id ) {
    $text = '';
    
    // 1. Try specific names via BP API
    if ( function_exists( 'bp_xprofile_get_field_id_from_name' ) ) {
        // Expanded list of potential field names
        $field_names = array( 'קצת עליי', 'ביו', 'Bio', 'About', 'About Me', 'תיאור', 'Description' );
        foreach ( $field_names as $name ) {
            $fid = bp_xprofile_get_field_id_from_name( $name );
            if ( $fid ) {
                $text = xprofile_get_field_data( $fid, $user_id );
                if ( ! empty( $text ) ) return (string) $text;
            }
        }
        
        // 2. Fallback: Search in Base group (ID 1) for any textarea/textbox that looks like a bio
        // This helps if there are encoding issues with the Hebrew name lookup or slight variations
        global $wpdb;
        $bp = buddypress();
        if ( isset( $bp->profile->table_name_fields ) ) {
            $table = $bp->profile->table_name_fields;
            // Search for fields in group 1 that might be the bio
            $sql = "SELECT id FROM {$table} WHERE group_id = 1 AND type IN ('textarea', 'textbox') AND (name LIKE '%Bio%' OR name LIKE '%About%' OR name LIKE '%קצת עליי%' OR name LIKE '%תיאור%')";
            $results = $wpdb->get_results( $sql );
            
            if ( $results ) {
                foreach ( $results as $field ) {
                    $text = xprofile_get_field_data( $field->id, $user_id );
                    if ( ! empty( $text ) ) return (string) $text;
                }
            }
        }
    }
    
    // 3. Fallback to WP description
    if ( empty( $text ) ) {
        $text = get_user_meta( $user_id, 'description', true );
    }
    
    return (string) $text;
}

/**
 * =================================================================
 * GROUP PENDING POSTS BELL - כפתור פעמון לאישור פוסטים של קבוצה
 * =================================================================
 */

/**
 * ספירת פוסטים ממתינים של חברי קבוצה מסוימת.
 */
function hp_bp_tweaks_get_group_pending_posts_count( $group_id ) {
    if ( ! function_exists( 'groups_get_group_members' ) ) {
        return 0;
    }

    $members = groups_get_group_members(
        [
            'group_id'  => $group_id,
            'per_page'  => 999,
            'page'      => 1,
        ]
    );

    if ( empty( $members['members'] ) ) {
        return 0;
    }

    // BP_Groups_Member has user_id (לא ID)
    $author_ids = array();
    foreach ( $members['members'] as $m ) {
        $uid = isset( $m->user_id ) ? $m->user_id : ( isset( $m->ID ) ? $m->ID : 0 );
        if ( $uid ) {
            $author_ids[] = (int) $uid;
        }
    }
    $author_ids = array_unique( array_filter( $author_ids ) );
    if ( empty( $author_ids ) ) {
        return 0;
    }

    // שאילתת $wpdb ישירה – בטוחה מפילטרים ומהירה יותר
    // מבטיחה שאנחנו סופרים רק pending, לא publish או סטטוסים אחרים
    global $wpdb;
    $placeholders = implode( ',', array_fill( 0, count( $author_ids ), '%d' ) );
    $count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts}
        WHERE post_type = 'post'
        AND post_status = 'pending'
        AND post_author IN ($placeholders)",
        $author_ids
    ) );

    return (int) $count;
}

/**
 * AJAX handler לקבלת פוסטים ממתינים של חברי קבוצה.
 */
function hp_bp_tweaks_get_group_pending_posts_ajax() {
    check_ajax_referer( 'hpg-group-bell-nonce', 'nonce' );

    if ( ! isset( $_POST['group_id'] ) ) {
        wp_send_json_error( 'Missing group ID' );
    }

    $group_id = intval( $_POST['group_id'] );
    $user_id = get_current_user_id();

    // בדוק הרשאות - מנחה קבוצה או עורך
    $is_moderator = false;
    if ( function_exists( 'groups_is_user_mod' ) ) {
        $is_moderator = groups_is_user_mod( $user_id, $group_id );
    }
    if ( ! $is_moderator && function_exists( 'groups_is_user_admin' ) ) {
        $is_moderator = groups_is_user_admin( $user_id, $group_id );
    }
    if ( ! $is_moderator && current_user_can( 'edit_others_posts' ) ) {
        $is_moderator = true;
    }

    if ( ! $is_moderator ) {
        wp_send_json_error( 'Unauthorized' );
    }

    // קבל את רשימת החברים
    if ( ! function_exists( 'groups_get_group_members' ) ) {
        wp_send_json_error( 'BuddyPress groups not available' );
    }

    $members = groups_get_group_members(
        [
            'group_id'  => $group_id,
            'per_page'  => 999,
            'page'      => 1,
        ]
    );

    if ( empty( $members['members'] ) ) {
        wp_send_json_success( [] );
    }

    // BP_Groups_Member has user_id (לא ID)
    $author_ids = array();
    foreach ( $members['members'] as $m ) {
        $uid = isset( $m->user_id ) ? $m->user_id : ( isset( $m->ID ) ? $m->ID : 0 );
        if ( $uid ) {
            $author_ids[] = (int) $uid;
        }
    }
    $author_ids = array_unique( array_filter( $author_ids ) );
    if ( empty( $author_ids ) ) {
        wp_send_json_success( [] );
    }

    // שאילתה לפוסטים ממתינים (רק post, רק pending). suppress_filters – עקביות עם ספירה.
    $pending_posts_query = new WP_Query([
        'post_type'         => 'post',
        'post_status'       => 'pending',
        'posts_per_page'    => -1,
        'author__in'        => $author_ids,
        'suppress_filters'  => true,
    ]);

    $posts_data = [];
    if ( $pending_posts_query->have_posts() ) {
        while ( $pending_posts_query->have_posts() ) {
            $pending_posts_query->the_post();
            $post_id = get_the_ID();
            
            // השתמש בפונקציה הקיימת ליצירת HTML הכרטיס + הוסף כפתור שליחת מייל
            if ( function_exists( 'hpg_get_pending_post_card_html' ) ) {
                $card_html = hpg_get_pending_post_card_html( $post_id );
                $email_btn = '<button class="hpg-button hpg-button-email-to-author" data-post-id="' . esc_attr( $post_id ) . '" title="שליחת מייל לתורם">שליחת מייל</button>';
                // הזרקה לפני כפתור מחק
                $card_html = str_replace( '<button class="hpg-button hpg-button-delete"', $email_btn . '<button class="hpg-button hpg-button-delete"', $card_html );
                $posts_data[] = [
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'intro' => wpautop( make_clickable( get_field( 'post_intro', $post_id ) ) ),
                    'author_name' => get_the_author(),
                    'preview_link' => get_preview_post_link( $post_id ),
                    'thumbnail' => get_the_post_thumbnail_url( $post_id, 'thumbnail' ),
                    'html' => $card_html,
                ];
            }
        }
    }
    wp_reset_postdata();

    wp_send_json_success( $posts_data );
}
add_action( 'wp_ajax_hp_bp_tweaks_get_group_pending_posts', 'hp_bp_tweaks_get_group_pending_posts_ajax' );

/**
 * טעינת scripts ו-styles לכפתור הפעמון של הקבוצה.
 */
function hp_bp_tweaks_enqueue_group_bell_assets() {
    // רק בעמודי קבוצה
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return;
    }

    $group = groups_get_current_group();
    if ( empty( $group ) || empty( $group->id ) ) {
        return;
    }

    $user_id = get_current_user_id();
    
    // בדוק אם המשתמש הוא מנחה או עורך
    $is_moderator = false;
    if ( function_exists( 'groups_is_user_mod' ) ) {
        $is_moderator = groups_is_user_mod( $user_id, $group->id );
    }
    if ( ! $is_moderator && function_exists( 'groups_is_user_admin' ) ) {
        $is_moderator = groups_is_user_admin( $user_id, $group->id );
    }
    if ( ! $is_moderator && current_user_can( 'edit_others_posts' ) ) {
        $is_moderator = true;
    }

    if ( ! $is_moderator ) {
        return;
    }

    // טען את ה-CSS של הפעמון (מהתוסף הראשי)
    if ( defined( 'HPG_PLUGIN_URL' ) ) {
        wp_enqueue_style(
            'hpg-admin-bell-style',
            HPG_PLUGIN_URL . 'assets/css/admin-bell.css',
            ['dashicons'],
            HP_BP_TWEAKS_VERSION
        );
    }

    // טען את ה-JS של הפעמון של הקבוצה
    wp_enqueue_script(
        'hp-bp-tweaks-group-bell-script',
        HP_BP_TWEAKS_PLUGIN_DIR_URL . 'assets/js/group-bell.js',
        ['jquery'],
        HP_BP_TWEAKS_VERSION,
        true
    );

    // העבר נתונים ל-JS (group_name לכותרת החלונית)
    wp_localize_script(
        'hp-bp-tweaks-group-bell-script',
        'hp_bp_tweaks_group_bell_globals',
        [
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'hpg-group-bell-nonce' ),
            'admin_nonce' => wp_create_nonce( 'hpg-admin-bell-nonce' ), // Nonce לפונקציות האישור הקיימות
            'group_id'    => $group->id,
            'group_name'  => ! empty( $group->name ) ? $group->name : '',
        ]
    );
}
add_action( 'wp_enqueue_scripts', 'hp_bp_tweaks_enqueue_group_bell_assets' );

/**
 * =================================================================
 * GROUP INVITE LINK SYSTEM - קבוצות עם קישור ייחודי
 * =================================================================
 */

/**
 * יוצר או מחזיר קישור ייחודי לקבוצה.
 */
function hp_bp_tweaks_get_group_invite_link( $group_id ) {
    // ודא שיש group_id תקין
    if ( empty( $group_id ) || ! is_numeric( $group_id ) ) {
        return '';
    }
    
    // בדוק אם הפונקציות של BuddyPress קיימות
    if ( ! function_exists( 'groups_get_groupmeta' ) || ! function_exists( 'groups_update_groupmeta' ) ) {
        return '';
    }
    
    try {
        $invite_token = groups_get_groupmeta( $group_id, 'hp_invite_link_token', true );
        
        if ( empty( $invite_token ) ) {
            // צור טוקן חדש
            $invite_token = wp_generate_password( 32, false );
            groups_update_groupmeta( $group_id, 'hp_invite_link_token', $invite_token );
        }
        
        // בדוק אם הפונקציות קיימות לפני השימוש
        if ( ! function_exists( 'groups_get_group' ) || ! function_exists( 'bp_get_group_permalink' ) ) {
            return '';
        }
        
        $group = groups_get_group( $group_id );
        if ( empty( $group ) || empty( $group->id ) ) {
            return '';
        }
        
        $group_permalink = bp_get_group_permalink( $group );
        if ( empty( $group_permalink ) ) {
            return '';
        }
        
        $invite_url = add_query_arg( 'invite_token', $invite_token, $group_permalink );
        
        return $invite_url;
    } catch ( Exception $e ) {
        // אם יש שגיאה, החזר מחרוזת ריקה
        return '';
    }
}

/**
 * בודק אם קבוצה היא מסוג "קישור ייחודי".
 */
function hp_bp_tweaks_is_invite_link_group( $group_id ) {
    $invite_only = groups_get_groupmeta( $group_id, 'hp_invite_link_only', true );
    return ! empty( $invite_only );
}

/**
 * מוסיף שדה בהגדרות הקבוצה לבחירת סוג "קישור ייחודי".
 * עובד גם ביצירת קבוצה חדשה וגם בעריכה.
 */
function hp_bp_tweaks_add_invite_link_group_setting() {
    // מניעת כפילות - אם כבר הוספנו, אל תוסיף שוב
    static $already_added = false;
    if ( $already_added ) {
        return;
    }
    
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return;
    }
    
    // בדוק אם אנחנו ביצירת קבוצה חדשה או בעריכת הגדרות
    $is_creating = function_exists( 'bp_is_group_create' ) && bp_is_group_create();
    $is_editing = function_exists( 'bp_is_group_admin_screen' ) && ( bp_is_group_admin_screen( 'group-settings' ) || bp_is_group_admin_screen( 'settings' ) );
    
    if ( ! $is_creating && ! $is_editing ) {
        return;
    }
    
    $already_added = true;
    
    // אם זה עריכה, בדוק הרשאות
    if ( $is_editing ) {
        $group = groups_get_current_group();
        if ( empty( $group ) || empty( $group->id ) ) {
            return;
        }
        
        // רק מנהלי קבוצה יכולים לראות את זה
        if ( ! function_exists( 'groups_is_user_admin' ) || ! groups_is_user_admin( get_current_user_id(), $group->id ) ) {
            return;
        }
        
        $is_invite_link_only = hp_bp_tweaks_is_invite_link_group( $group->id );
        $invite_link = hp_bp_tweaks_get_group_invite_link( $group->id );
    } else {
        // ביצירת קבוצה חדשה, בדוק אם יש ערך ב-POST
        $is_invite_link_only = isset( $_POST['hp_invite_link_only'] ) && $_POST['hp_invite_link_only'] == '1';
        $invite_link = '';
    }
    
    ?>
    <div class="bp-widget" style="margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
        <h4>הצטרפות דרך קישור ייחודי</h4>
        <p>כאשר מופעל, רק מי שמקבל את הקישור הייחודי יכול להצטרף לקבוצה. אנשים אחרים יוכלו לראות את הקבוצה אבל לא להצטרף.</p>
        
        <div class="checkbox">
            <label>
                <input type="checkbox" name="hp_invite_link_only" value="1" <?php checked( $is_invite_link_only, 1 ); ?> />
                הפעל הצטרפות דרך קישור ייחודי בלבד
            </label>
        </div>
        
        <?php if ( $is_invite_link_only && ! empty( $invite_link ) ) : ?>
            <div style="margin-top: 15px; padding: 15px; background: #f0f0f0; border-radius: 5px;">
                <p><strong>קישור ייחודי להצטרפות:</strong></p>
                <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                    <input type="text" id="hp-invite-link-input" value="<?php echo esc_url( $invite_link ); ?>" readonly style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 3px;" />
                    <button type="button" id="hp-copy-invite-link" class="button" style="white-space: nowrap;">העתק קישור</button>
                </div>
                <p style="margin-top: 10px; font-size: 12px; color: #666;">שלח את הקישור הזה לאנשים שאתה רוצה שיצטרפו לקבוצה.</p>
            </div>
        <?php endif; ?>
        
        <script>
        jQuery(document).ready(function($) {
            $('#hp-copy-invite-link').on('click', function() {
                var input = document.getElementById('hp-invite-link-input');
                if (input) {
                    input.select();
                    input.setSelectionRange(0, 99999); // For mobile devices
                    document.execCommand('copy');
                    
                    var button = $(this);
                    var originalText = button.text();
                    button.text('הועתק!');
                    setTimeout(function() {
                        button.text(originalText);
                    }, 2000);
                }
            });
        });
        </script>
    </div>
    <?php
}
// הוסף את האופציה - רק hooks ספציפיים כדי למנוע כפילות
add_action( 'bp_after_group_settings_creation_step', 'hp_bp_tweaks_add_invite_link_group_setting', 20 );
add_action( 'bp_after_group_settings_admin', 'hp_bp_tweaks_add_invite_link_group_setting', 20 );

/**
 * מוסיף את האופציה באמצעות JavaScript אם ה-hooks לא עובדים.
 * כבוי כרגע כדי למנוע כפילות.
 */
function hp_bp_tweaks_add_invite_link_setting_js() {
    // כבוי זמנית כדי למנוע כפילות
    return;
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return;
    }
    
    if ( ! function_exists( 'bp_is_group_admin_screen' ) ) {
        return;
    }
    
    // בדוק אם אנחנו ביצירת קבוצה חדשה או בעריכת הגדרות
    $is_creating = bp_is_group_create();
    $is_editing = function_exists( 'bp_is_group_admin_screen' ) && ( bp_is_group_admin_screen( 'group-settings' ) || bp_is_group_admin_screen( 'settings' ) );
    
    if ( ! $is_creating && ! $is_editing ) {
        return;
    }
    
    // אם זה עריכה, בדוק הרשאות
    if ( $is_editing ) {
        $group = groups_get_current_group();
        if ( empty( $group ) || empty( $group->id ) ) {
            return;
        }
        
        // רק מנהלי קבוצה
        if ( ! function_exists( 'groups_is_user_admin' ) || ! groups_is_user_admin( get_current_user_id(), $group->id ) ) {
            return;
        }
        
        $is_invite_link_only = hp_bp_tweaks_is_invite_link_group( $group->id );
        $invite_link = hp_bp_tweaks_get_group_invite_link( $group->id );
    } else {
        // ביצירת קבוצה חדשה
        $is_invite_link_only = isset( $_POST['hp_invite_link_only'] ) && $_POST['hp_invite_link_only'] == '1';
        $invite_link = '';
    }
    
    $checked = $is_invite_link_only ? 'checked' : '';
    
    // צור את ה-HTML ב-PHP
    $invite_link_html = '<div class="bp-widget" style="margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">';
    $invite_link_html .= '<h4>הצטרפות דרך קישור ייחודי</h4>';
    $invite_link_html .= '<p>כאשר מופעל, רק מי שמקבל את הקישור הייחודי יכול להצטרף לקבוצה. אנשים אחרים יוכלו לראות את הקבוצה אבל לא להצטרף.</p>';
    $invite_link_html .= '<div class="checkbox">';
    $invite_link_html .= '<label>';
    $invite_link_html .= '<input type="checkbox" name="hp_invite_link_only" value="1" ' . $checked . ' />';
    $invite_link_html .= ' הפעל הצטרפות דרך קישור ייחודי בלבד';
    $invite_link_html .= '</label>';
    $invite_link_html .= '</div>';
    
    if ( $is_invite_link_only ) {
        $invite_link_html .= '<div style="margin-top: 15px; padding: 15px; background: #f0f0f0; border-radius: 5px;">';
        $invite_link_html .= '<p><strong>קישור ייחודי להצטרפות:</strong></p>';
        $invite_link_html .= '<div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">';
        $invite_link_html .= '<input type="text" id="hp-invite-link-input" value="' . esc_attr( $invite_link ) . '" readonly style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 3px;" />';
        $invite_link_html .= '<button type="button" id="hp-copy-invite-link" class="button" style="white-space: nowrap;">העתק קישור</button>';
        $invite_link_html .= '</div>';
        $invite_link_html .= '<p style="margin-top: 10px; font-size: 12px; color: #666;">שלח את הקישור הזה לאנשים שאתה רוצה שיצטרפו לקבוצה.</p>';
        $invite_link_html .= '</div>';
    }
    
    $invite_link_html .= '</div>';
    $invite_link_html_escaped = esc_js( $invite_link_html );
    ?>
    <script>
    jQuery(document).ready(function($) {
        // חכה שהטופס יטען
        setTimeout(function() {
            // חפש את הטופס של הגדרות פרטיות - גם ביצירה חדשה וגם בעריכה
            var $privacySection = $('form#group-settings-form, form.group-settings-form, form#create-group-form, form.create-group-form').find('h4, h3, .bp-widget h4').filter(function() {
                var text = $(this).text();
                return text.indexOf('פרטיות') !== -1 || text.indexOf('Privacy') !== -1 || text.indexOf('הגדרות פרטיות') !== -1 || text.indexOf('Select Group Settings') !== -1;
            }).closest('.bp-widget, div').first();
            
            // אם לא מצאנו, נסה למצוא את הטופס עצמו
            if ($privacySection.length === 0) {
                $privacySection = $('form#group-settings-form, form.group-settings-form, form#create-group-form, form.create-group-form, .group-settings-form').first();
            }
            
            // אם עדיין לא מצאנו, נסה למצוא את כל הטופסים
            if ($privacySection.length === 0) {
                $privacySection = $('form').has('input[name*="group-status"], input[name*="privacy"], input[value="public"], input[value="private"], input[value="hidden"]').first();
            }
            
            // אם עדיין לא מצאנו, נסה למצוא לפי תוכן
            if ($privacySection.length === 0) {
                $privacySection = $('form').filter(function() {
                    return $(this).html().indexOf('קבוצה ציבורית') !== -1 || $(this).html().indexOf('Public') !== -1;
                }).first();
            }
            
            // אם מצאנו משהו, הוסף את האופציה
            if ($privacySection.length > 0) {
                var inviteLinkHtml = <?php echo json_encode( $invite_link_html ); ?>;
                
                $privacySection.after(inviteLinkHtml);
                
                // הוסף את הפונקציונליות של העתקה
                $(document).on('click', '#hp-copy-invite-link', function() {
                    var input = document.getElementById('hp-invite-link-input');
                    if (input) {
                        input.select();
                        input.setSelectionRange(0, 99999);
                        document.execCommand('copy');
                        
                        var button = $(this);
                        var originalText = button.text();
                        button.text('הועתק!');
                        setTimeout(function() {
                            button.text(originalText);
                        }, 2000);
                    }
                });
            } else {
                // אם לא מצאנו, נסה להוסיף בסוף הטופס - גם ביצירה חדשה וגם בעריכה
                var $form = $('form#group-settings-form, form.group-settings-form, form#create-group-form, form.create-group-form, .group-settings-form').first();
                if ($form.length > 0) {
                    var inviteLinkHtml = <?php echo json_encode( $invite_link_html ); ?>;
                    
                    // הוסף לפני כפתור השמירה
                    var $submitButton = $form.find('input[type="submit"], button[type="submit"]').first();
                    if ($submitButton.length > 0) {
                        $submitButton.before(inviteLinkHtml);
                    } else {
                        $form.append(inviteLinkHtml);
                    }
                    
                    // הוסף את הפונקציונליות של העתקה
                    $(document).on('click', '#hp-copy-invite-link', function() {
                        var input = document.getElementById('hp-invite-link-input');
                        if (input) {
                            input.select();
                            input.setSelectionRange(0, 99999);
                            document.execCommand('copy');
                            
                            var button = $(this);
                            var originalText = button.text();
                            button.text('הועתק!');
                            setTimeout(function() {
                                button.text(originalText);
                            }, 2000);
                        }
                    });
                }
            }
        }, 500);
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'hp_bp_tweaks_add_invite_link_setting_js' );

/**
 * שומר את ההגדרה של "קישור ייחודי" בעת שמירת הגדרות הקבוצה.
 * רק לעריכה, לא ליצירה חדשה.
 */
function hp_bp_tweaks_save_invite_link_group_setting( $group_id ) {
    // מניעת כפילות - אם כבר שמרנו, אל תשמור שוב
    static $already_saved = array();
    if ( isset( $already_saved[ $group_id ] ) ) {
        return;
    }
    
    // ודא שיש group_id תקין
    if ( empty( $group_id ) || ! is_numeric( $group_id ) ) {
        return;
    }
    
    // בדוק אם הקבוצה קיימת
    if ( ! function_exists( 'groups_get_group' ) ) {
        return;
    }
    
    $group = groups_get_group( $group_id );
    if ( empty( $group ) || empty( $group->id ) ) {
        // הקבוצה עדיין לא קיימת - אל תנסה לשמור
        return;
    }
    
    // בדוק אם זו יצירה חדשה - אם כן, אל תטפל כאן (יש פונקציה נפרדת)
    if ( function_exists( 'bp_is_group_create' ) && bp_is_group_create() ) {
        return;
    }
    
    // בדוק הרשאות רק אם זו עריכה (לא יצירה חדשה)
    // ביצירה חדשה, המשתמש הוא היוצר אז הוא אוטומטית מנהל
    if ( function_exists( 'groups_is_user_admin' ) ) {
        // בדוק הרשאות רק אם זו עריכה של קבוצה קיימת
        // ביצירה חדשה, המשתמש הוא היוצר אז הוא אוטומטית מנהל
        $current_user_id = get_current_user_id();
        if ( $current_user_id > 0 ) {
            // אם המשתמש הוא היוצר, תן לו לשמור
            if ( $group->creator_id != $current_user_id ) {
                // אם הוא לא היוצר, בדוק אם הוא מנהל
                if ( ! groups_is_user_admin( $current_user_id, $group_id ) ) {
                    return;
                }
            }
        } else {
            // משתמש לא מחובר - אל תאפשר שמירה
            return;
        }
    }
    
    // שמור את ההגדרה
    if ( isset( $_POST['hp_invite_link_only'] ) && $_POST['hp_invite_link_only'] == '1' ) {
        groups_update_groupmeta( $group_id, 'hp_invite_link_only', '1' );
        // ודא שיש טוקן - רק אם הקבוצה קיימת
        if ( $group && ! empty( $group->id ) ) {
            hp_bp_tweaks_get_group_invite_link( $group_id );
        }
    } else {
        // אם לא נשלח, מחק את ההגדרה (רק אם זו עריכה)
        if ( isset( $_POST['hp_invite_link_only'] ) ) {
            groups_delete_groupmeta( $group_id, 'hp_invite_link_only' );
        }
    }
    
    // סמן שכבר שמרנו
    $already_saved[ $group_id ] = true;
}
// שמירה בעריכה
add_action( 'groups_group_settings_edited', 'hp_bp_tweaks_save_invite_link_group_setting', 10, 1 );

// שמירה ביצירה חדשה - רק אחרי שהקבוצה נוצרה במלואה
add_action( 'groups_created_group', 'hp_bp_tweaks_save_invite_link_on_group_creation', 99, 1 );

/**
 * שומר את ההגדרה של "קישור ייחודי" ביצירת קבוצה חדשה.
 */
function hp_bp_tweaks_save_invite_link_on_group_creation( $group_id ) {
    // ודא שיש group_id תקין
    if ( empty( $group_id ) || ! is_numeric( $group_id ) ) {
        return;
    }
    
    // בדוק אם יש ערך ב-POST
    if ( ! isset( $_POST['hp_invite_link_only'] ) || $_POST['hp_invite_link_only'] != '1' ) {
        return;
    }
    
    // בדוק אם הפונקציות קיימות
    if ( ! function_exists( 'groups_get_group' ) || ! function_exists( 'groups_update_groupmeta' ) ) {
        return;
    }
    
    // נסה לקבל את הקבוצה - אם היא עדיין לא קיימת, פשוט תחזור
    $group = groups_get_group( $group_id );
    if ( empty( $group ) || empty( $group->id ) ) {
        // הקבוצה עדיין לא קיימת - אל תעשה כלום
        // המשתמש יוכל לשמור את זה אחר כך בעריכה
        return;
    }
    
    // שמור את ההגדרה
    groups_update_groupmeta( $group_id, 'hp_invite_link_only', '1' );
    
    // ודא שיש טוקן - רק אם הקבוצה קיימת
    hp_bp_tweaks_get_group_invite_link( $group_id );
}

/**
 * מסתיר את כפתור ההצטרפות הרגיל עבור קבוצות עם קישור ייחודי.
 * עובד גם בעמוד הקבוצה הבודדת וגם בעמוד רשימת כל הקבוצות.
 */
function hp_bp_tweaks_hide_join_button_for_invite_link_groups( $button ) {
    // אם הכפתור כבר ריק, אל תעשה כלום
    if ( empty( $button ) ) {
        return $button;
    }
    
    $group_id = 0;
    
    // נסה לקבל את ה-group_id - קודם נסה בעמוד הקבוצה הבודדת
    if ( function_exists( 'bp_get_current_group_id' ) ) {
        $group_id = bp_get_current_group_id();
    }
    
    // אם לא מצאנו, נסה בעמוד הרשימה (בתוך הלולאה)
    if ( ! $group_id && function_exists( 'bp_get_group_id' ) ) {
        $group_id = bp_get_group_id();
    }
    
    // אם עדיין לא מצאנו, נסה דרך groups_get_current_group
    if ( ! $group_id && function_exists( 'groups_get_current_group' ) ) {
        $group = groups_get_current_group();
        if ( ! empty( $group ) && ! empty( $group->id ) ) {
            $group_id = $group->id;
        }
    }
    
    // אם לא מצאנו group_id, החזר את הכפתור כמו שהוא
    if ( ! $group_id ) {
        return $button;
    }
    
    // רק אם זו קבוצה עם קישור ייחודי
    if ( ! hp_bp_tweaks_is_invite_link_group( $group_id ) ) {
        return $button;
    }
    
    // אם המשתמש כבר חבר, תן לו לראות את הכפתור הרגיל
    if ( function_exists( 'groups_is_user_member' ) && groups_is_user_member( get_current_user_id(), $group_id ) ) {
        return $button;
    }
    
    // אם המשתמש הוא מנהל קבוצה, תן לו לראות את הכפתור הרגיל
    if ( function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( get_current_user_id(), $group_id ) ) {
        return $button;
    }
    
    // הסתר את הכפתור הרגיל רק עבור קבוצות עם קישור ייחודי
    return '';
}
add_filter( 'bp_get_group_join_button', 'hp_bp_tweaks_hide_join_button_for_invite_link_groups', 10, 1 );

/**
 * מוסיף JavaScript שיסתיר כפתורי הצטרפות בעמוד רשימת הקבוצות.
 */
function hp_bp_tweaks_hide_join_buttons_in_directory_js() {
    // רק בעמוד רשימת הקבוצות
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return;
    }
    
    // רק אם זה לא עמוד קבוצה בודדת
    if ( function_exists( 'bp_is_single_item' ) && bp_is_single_item() ) {
        return;
    }
    
    // קבל את כל ה-group IDs ו-slugs בעמוד דרך ה-global template
    $group_data = array();
    global $groups_template;
    if ( ! empty( $groups_template ) && ! empty( $groups_template->groups ) ) {
        foreach ( $groups_template->groups as $group ) {
            if ( ! empty( $group->id ) ) {
                $group_data[] = array(
                    'id' => $group->id,
                    'slug' => ! empty( $group->slug ) ? $group->slug : '',
                );
            }
        }
    }
    
    // אם אין קבוצות, אל תעשה כלום
    if ( empty( $group_data ) ) {
        return;
    }
    
    // בדוק אילו קבוצות הן עם קישור ייחודי
    $invite_link_groups = array();
    $user_id = get_current_user_id();
    foreach ( $group_data as $group_info ) {
        $group_id = $group_info['id'];
        if ( hp_bp_tweaks_is_invite_link_group( $group_id ) ) {
            // בדוק אם המשתמש לא חבר ולא מנהל
            $is_member = function_exists( 'groups_is_user_member' ) && groups_is_user_member( $user_id, $group_id );
            $is_admin = function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( $user_id, $group_id );
            
            if ( ! $is_member && ! $is_admin ) {
                $invite_link_groups[] = array(
                    'id' => $group_id,
                    'slug' => $group_info['slug'],
                );
            }
        }
    }
    
    // אם אין קבוצות עם קישור ייחודי, אל תעשה כלום
    if ( empty( $invite_link_groups ) ) {
        return;
    }
    
    $groups_json = wp_json_encode( $invite_link_groups );
    
    ?>
    <script>
    (function($) {
        function hideInviteLinkGroupButtons() {
            var inviteLinkGroups = <?php echo $groups_json; ?>;
            
            // אם אין קבוצות עם קישור ייחודי, אל תעשה כלום
            if ( ! inviteLinkGroups || inviteLinkGroups.length === 0 ) {
                return;
            }
            
            // עבור כל קבוצה עם קישור ייחודי
            inviteLinkGroups.forEach(function(groupInfo) {
                var groupId = groupInfo.id;
                var groupSlug = groupInfo.slug;
                
                // הסתר כפתורי הצטרפות לפי data-group-id (אם יש)
                $('[data-group-id="' + groupId + '"]').closest('.generic-button, .button, a').hide();
                $('.generic-button[data-group-id="' + groupId + '"], .button[data-group-id="' + groupId + '"], a[data-group-id="' + groupId + '"]').hide();
                
                // הסתר לפי slug ב-URL - רק כפתורי הצטרפות של BuddyPress
                if ( groupSlug ) {
                    // מצא את כל ה-items של הקבוצה
                    $('a[href*="/groups/' + groupSlug + '/"]').each(function() {
                        var $groupLink = $(this);
                        var $item = $groupLink.closest('.group-item, .item-list li, .groups-list li, .group-list-item, li, .item');
                        
                        if ( $item.length > 0 ) {
                            // מצא כפתורי הצטרפות ספציפיים של BuddyPress
                            // BuddyPress משתמש ב-class "generic-button" עם קישור שמכיל "join"
                            $item.find('.generic-button a, .button a, a.button').each(function() {
                                var $btn = $(this);
                                var href = $btn.attr('href') || '';
                                var btnText = $btn.text().trim();
                                
                                // רק אם זה כפתור הצטרפות (מכיל "join" ב-URL או טקסט "הצטרף")
                                if ( href.indexOf('join') !== -1 || btnText.indexOf('הצטרף') !== -1 || btnText.indexOf('Join') !== -1 ) {
                                    // הסתר את כל ה-container של הכפתור
                                    $btn.closest('.generic-button, .button').hide();
                                }
                            });
                        }
                    });
                }
            });
            
            // גם נסתיר דרך ה-URL של הקבוצה - רק כפתורי הצטרפות של BuddyPress
            $('.group-item, .item-list li, .groups-list li, .group-list-item, .item').each(function() {
                var $item = $(this);
                var $groupLink = $item.find('a[href*="/groups/"]').first();
                
                if ( $groupLink.length > 0 ) {
                    var groupUrl = $groupLink.attr('href');
                    if ( groupUrl ) {
                        inviteLinkGroups.forEach(function(groupInfo) {
                            var groupSlug = groupInfo.slug;
                            if ( groupSlug && groupUrl.indexOf('/groups/' + groupSlug + '/') !== -1 ) {
                                // מצא כפתורי הצטרפות ספציפיים של BuddyPress
                                $item.find('.generic-button a, .button a, a.button').each(function() {
                                    var $btn = $(this);
                                    var href = $btn.attr('href') || '';
                                    var btnText = $btn.text().trim();
                                    
                                    // רק אם זה כפתור הצטרפות (מכיל "join" ב-URL או טקסט "הצטרף")
                                    if ( href.indexOf('join') !== -1 || btnText.indexOf('הצטרף') !== -1 || btnText.indexOf('Join') !== -1 ) {
                                        // הסתר את כל ה-container של הכפתור
                                        $btn.closest('.generic-button, .button').hide();
                                    }
                                });
                            }
                        });
                    }
                }
            });
        }
        
        $(document).ready(function() {
            hideInviteLinkGroupButtons();
            
            // גם אחרי AJAX (אם יש)
            $(document).ajaxComplete(function() {
                setTimeout(hideInviteLinkGroupButtons, 100);
            });
            
            // גם אחרי שינוי דינמי של התוכן
            if ( typeof MutationObserver !== 'undefined' ) {
                var observer = new MutationObserver(function(mutations) {
                    setTimeout(hideInviteLinkGroupButtons, 100);
                });
                observer.observe(document.body, { childList: true, subtree: true });
            }
        });
    })(jQuery);
    </script>
    <?php
}
add_action( 'wp_footer', 'hp_bp_tweaks_hide_join_buttons_in_directory_js' );

/**
 * מוסיף הודעה במקום כפתור ההצטרפות עבור קבוצות עם קישור ייחודי.
 */
function hp_bp_tweaks_add_invite_link_message() {
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return;
    }
    
    if ( ! function_exists( 'bp_get_current_group_id' ) ) {
        return;
    }
    
    $group_id = bp_get_current_group_id();
    if ( ! $group_id ) {
        return;
    }
    
    if ( ! hp_bp_tweaks_is_invite_link_group( $group_id ) ) {
        return;
    }
    
    // אם המשתמש כבר חבר, אל תציג הודעה
    if ( function_exists( 'groups_is_user_member' ) && groups_is_user_member( get_current_user_id(), $group_id ) ) {
        return;
    }
    
    // אם המשתמש הוא מנהל קבוצה, אל תציג הודעה
    if ( function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( get_current_user_id(), $group_id ) ) {
        return;
    }
    
    // בדוק אם יש token ב-URL
    $has_token = isset( $_GET['invite_token'] ) && ! empty( $_GET['invite_token'] );
    $token = $has_token ? sanitize_text_field( $_GET['invite_token'] ) : '';
    
    // אם יש token, בדוק אם הוא תקין
    if ( $has_token && strlen( $token ) >= 20 ) {
        $saved_token = groups_get_groupmeta( $group_id, 'hp_invite_link_token', true );
        if ( $token === $saved_token ) {
            // Token תקין - אל תציג את ההודעה הכללית, הטיפול ייעשה בפונקציה hp_bp_tweaks_handle_invite_link_join
            return;
        }
    }
    
    // הצג הודעה
    ?>
    <div class="hp-invite-link-message" style="padding: 15px; margin: 15px 0; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; text-align: center;">
        <p style="margin: 0; color: #856404;">
            <strong>הצטרפות לקבוצה זו אפשרית רק דרך קישור ייחודי.</strong><br>
            אם קיבלת קישור להצטרפות, לחץ עליו כדי להצטרף.
        </p>
    </div>
    <?php
}
add_action( 'bp_before_group_header_meta', 'hp_bp_tweaks_add_invite_link_message', 20 );

/**
 * מטפל בהצטרפות דרך קישור ייחודי דרך template redirect.
 */
function hp_bp_tweaks_handle_invite_link_join() {
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return;
    }
    
    $group = groups_get_current_group();
    if ( empty( $group ) || empty( $group->id ) ) {
        return;
    }
    
    // בדוק אם יש query parameter של invite_token
    if ( ! isset( $_GET['invite_token'] ) || empty( $_GET['invite_token'] ) ) {
        return;
    }
    
    $token = sanitize_text_field( $_GET['invite_token'] );
    if ( strlen( $token ) < 20 ) {
        return; // טוקן צריך להיות ארוך
    }
    
    // בדוק אם הקבוצה היא מסוג "קישור ייחודי"
    if ( ! hp_bp_tweaks_is_invite_link_group( $group->id ) ) {
        return;
    }
    
    // בדוק אם הטוקן תואם
    $saved_token = groups_get_groupmeta( $group->id, 'hp_invite_link_token', true );
    if ( $token !== $saved_token ) {
        // הטוקן לא תואם - הצג הודעת שגיאה
        add_action( 'bp_template_content', 'hp_bp_tweaks_invite_link_error_screen' );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/home' ) );
        return;
    }
    
    // אם יש POST request עם הצטרפות, נטפל בהצטרפות
    if ( isset( $_POST['hp_join_group'] ) && wp_verify_nonce( $_POST['hp_join_group_nonce'], 'hp_join_group_' . $group->id ) ) {
        // בדוק אם המשתמש מחובר
        if ( ! is_user_logged_in() ) {
            $login_url = wp_login_url( add_query_arg( 'invite_token', $token, bp_get_group_permalink( $group ) ) );
            wp_redirect( $login_url );
            exit;
        }
        
        // בדוק אם המשתמש כבר חבר
        if ( function_exists( 'groups_is_user_member' ) && groups_is_user_member( get_current_user_id(), $group->id ) ) {
            bp_core_add_message( 'אתה כבר חבר בקבוצה זו.', 'error' );
            bp_core_redirect( bp_get_group_permalink( $group ) );
            exit;
        }
        
        // הצטרף לקבוצה
        if ( function_exists( 'groups_join_group' ) ) {
            $joined = groups_join_group( $group->id );
            
            if ( $joined ) {
                bp_core_add_message( 'הצטרפת בהצלחה לקבוצה!', 'success' );
            } else {
                bp_core_add_message( 'אירעה שגיאה בהצטרפות לקבוצה.', 'error' );
            }
            
            bp_core_redirect( bp_get_group_permalink( $group ) );
            exit;
        }
    }
    
    // הסר את ההודעה הכללית כי יש לנו token תקין
    remove_action( 'bp_before_group_header_meta', 'hp_bp_tweaks_add_invite_link_message', 20 );
    
    // אם המשתמש לא מחובר, הצג מסך התחברות
    if ( ! is_user_logged_in() ) {
        remove_all_actions( 'bp_template_content' );
        add_action( 'bp_template_content', 'hp_bp_tweaks_invite_link_login_screen', 999 );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/home' ) );
        return;
    }
    
    // בדוק אם המשתמש כבר חבר
    if ( function_exists( 'groups_is_user_member' ) && groups_is_user_member( get_current_user_id(), $group->id ) ) {
        remove_all_actions( 'bp_template_content' );
        add_action( 'bp_template_content', 'hp_bp_tweaks_invite_link_already_member_screen', 999 );
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/home' ) );
        return;
    }
    
    // הצג טופס הצטרפות - רק דרך JavaScript (ה-JavaScript יטופל בזה)
    // אל תוסיף hooks נוספים כאן כדי למנוע כפילות
}
add_action( 'bp_template_redirect', 'hp_bp_tweaks_handle_invite_link_join', 3 );

/**
 * מוסיף את טופס ההצטרפות דרך JavaScript.
 */
function hp_bp_tweaks_add_invite_link_join_form_js() {
    static $already_added = false;
    
    // מניעת כפילות - אם כבר הוספנו את ה-script, אל תוסיף שוב
    if ( $already_added ) {
        return;
    }
    
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return;
    }
    
    // בדוק אם יש token ב-URL
    if ( ! isset( $_GET['invite_token'] ) || empty( $_GET['invite_token'] ) ) {
        return;
    }
    
    $group = groups_get_current_group();
    if ( empty( $group ) || empty( $group->id ) ) {
        return;
    }
    
    // בדוק אם הקבוצה היא מסוג "קישור ייחודי"
    if ( ! hp_bp_tweaks_is_invite_link_group( $group->id ) ) {
        return;
    }
    
    // בדוק אם הטוקן תואם
    $token = sanitize_text_field( $_GET['invite_token'] );
    $saved_token = groups_get_groupmeta( $group->id, 'hp_invite_link_token', true );
    
    if ( $token !== $saved_token || strlen( $token ) < 20 ) {
        return;
    }
    
    // אם המשתמש כבר חבר, אל תציג טופס
    if ( function_exists( 'groups_is_user_member' ) && groups_is_user_member( get_current_user_id(), $group->id ) ) {
        return;
    }
    
    // אם המשתמש לא מחובר, אל תציג טופס (יש מסך התחברות)
    if ( ! is_user_logged_in() ) {
        return;
    }
    
    // סמן שכבר הוספנו
    $already_added = true;
    
    $group_name = ! empty( $group->name ) ? esc_js( $group->name ) : 'הקבוצה';
    $form_action = esc_js( add_query_arg( 'invite_token', $token, bp_get_group_permalink( $group ) ) );
    $nonce = wp_create_nonce( 'hp_join_group_' . $group->id );
    
    ?>
    <script>
    (function($) {
        // מניעת כפילות - בדוק אם כבר יש טופס או אם כבר הוספנו
        if ( typeof window.hpInviteFormAdded !== 'undefined' && window.hpInviteFormAdded === true ) {
            return;
        }
        
        if ( $('.hp-invite-link-join-form').length > 0 ) {
            return;
        }
        
        // סמן שכבר הוספנו
        window.hpInviteFormAdded = true;
        
        // חכה שהדף יטען
        $(document).ready(function() {
            setTimeout(function() {
                // בדוק שוב אם כבר יש טופס (למקרה שהוסף בינתיים)
                if ( $('.hp-invite-link-join-form').length > 0 ) {
                    return;
                }
                
                // צור את טופס ההצטרפות
                var joinFormHtml = '<div class="hp-invite-link-join-form" style="max-width: 600px; margin: 40px auto; padding: 30px; background: #fff; border: 2px solid #4a90e2; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">' +
                    '<h2 style="margin-top: 0; color: #2c5aa0; text-align: center;">הצטרפות לקבוצה: <?php echo $group_name; ?></h2>' +
                    '<div style="text-align: center; margin-bottom: 25px;">' +
                    '<p style="font-size: 16px; color: #555; margin-bottom: 10px;">✅ קיבלת קישור ייחודי להצטרפות לקבוצה זו.</p>' +
                    '<p style="font-size: 14px; color: #666;">לחץ על הכפתור למטה כדי להצטרף לקבוצה.</p>' +
                    '</div>' +
                    '<form method="post" action="<?php echo $form_action; ?>" style="text-align: center;">' +
                    '<input type="hidden" name="hp_join_group_nonce" value="<?php echo esc_attr( $nonce ); ?>" />' +
                    '<button type="submit" name="hp_join_group" class="button button-primary" style="padding: 15px 40px; font-size: 18px; font-weight: bold; border-radius: 5px; background: #4a90e2; border: none; color: #fff; cursor: pointer;">הצטרף לקבוצה</button>' +
                    '</form>' +
                    '</div>';
                
                // נסה למצוא איפה להוסיף - רק במקום אחד
                var $contentArea = $('#buddypress .item-body, .groups.group-single .item-body, #buddypress #item-body').first();
                if ( $contentArea.length > 0 ) {
                    // הסר את כל התוכן הקיים והוסף את הטופס
                    $contentArea.html(joinFormHtml);
                } else {
                    // אם לא מצאנו, הוסף בתחילת body
                    $('body').prepend(joinFormHtml);
                }
            }, 300);
        });
    })(jQuery);
    </script>
    <?php
}
add_action( 'wp_footer', 'hp_bp_tweaks_add_invite_link_join_form_js' );

/**
 * מציג את הקישור הייחודי בעמוד "הזמנת חברים".
 */
function hp_bp_tweaks_display_invite_link_on_invite_page() {
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return;
    }
    
    // רק בעמוד הזמנות
    if ( ! function_exists( 'bp_is_group_admin_screen' ) || ! bp_is_group_admin_screen( 'group-invites' ) ) {
        return;
    }
    
    $group = groups_get_current_group();
    if ( empty( $group ) || empty( $group->id ) ) {
        return;
    }
    
    // רק אם זו קבוצה עם קישור ייחודי
    if ( ! hp_bp_tweaks_is_invite_link_group( $group->id ) ) {
        return;
    }
    
    // רק מנהלי קבוצה יכולים לראות את זה
    if ( ! function_exists( 'groups_is_user_admin' ) || ! groups_is_user_admin( get_current_user_id(), $group->id ) ) {
        return;
    }
    
    $invite_link = hp_bp_tweaks_get_group_invite_link( $group->id );
    if ( empty( $invite_link ) ) {
        return;
    }
    
    ?>
    <div class="hp-invite-link-display-box" style="margin-bottom: 30px; padding: 20px; background: #f0f8ff; border: 2px solid #4a90e2; border-radius: 8px;">
        <h3 style="margin-top: 0; color: #2c5aa0;">🔗 קישור ייחודי להצטרפות לקבוצה</h3>
        <p style="margin-bottom: 15px; color: #555;">שלח את הקישור הזה לאנשים שאתה רוצה שיצטרפו לקבוצה. רק מי שמקבל את הקישור יכול להצטרף.</p>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="hp-invite-link-display-input" value="<?php echo esc_url( $invite_link ); ?>" readonly style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: monospace; font-size: 14px; background: #fff;" />
            <button type="button" id="hp-copy-invite-link-display" class="button button-primary" style="white-space: nowrap; padding: 10px 20px;">העתק קישור</button>
        </div>
        <p style="margin-top: 10px; font-size: 12px; color: #666; margin-bottom: 0;">לחץ על "העתק קישור" ואז שלח את הקישור לאנשים שאתה רוצה שיצטרפו.</p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#hp-copy-invite-link-display').on('click', function() {
            var input = document.getElementById('hp-invite-link-display-input');
            if (input) {
                input.select();
                input.setSelectionRange(0, 99999);
                document.execCommand('copy');
                
                var button = $(this);
                var originalText = button.text();
                button.text('הועתק!');
                button.css('background', '#28a745');
                setTimeout(function() {
                    button.text(originalText);
                    button.css('background', '');
                }, 2000);
            }
        });
    });
    </script>
    <?php
}
add_action( 'bp_before_group_invites_content', 'hp_bp_tweaks_display_invite_link_on_invite_page', 5 );

/**
 * מציג את הקישור הייחודי בעמוד "הגדרות" תמיד (אם זו קבוצה עם קישור ייחודי).
 */
function hp_bp_tweaks_display_invite_link_on_settings_page() {
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return;
    }
    
    // רק בעמוד הגדרות
    if ( ! function_exists( 'bp_is_group_admin_screen' ) || ! bp_is_group_admin_screen( 'group-settings' ) ) {
        return;
    }
    
    $group = groups_get_current_group();
    if ( empty( $group ) || empty( $group->id ) ) {
        return;
    }
    
    // רק אם זו קבוצה עם קישור ייחודי
    if ( ! hp_bp_tweaks_is_invite_link_group( $group->id ) ) {
        return;
    }
    
    // רק מנהלי קבוצה יכולים לראות את זה
    if ( ! function_exists( 'groups_is_user_admin' ) || ! groups_is_user_admin( get_current_user_id(), $group->id ) ) {
        return;
    }
    
    $invite_link = hp_bp_tweaks_get_group_invite_link( $group->id );
    if ( empty( $invite_link ) ) {
        return;
    }
    
    ?>
    <div class="hp-invite-link-display-box" style="margin-bottom: 30px; padding: 20px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 8px;">
        <h3 style="margin-top: 0; color: #856404;">🔗 קישור ייחודי להצטרפות לקבוצה</h3>
        <p style="margin-bottom: 15px; color: #555;">הקבוצה מוגדרת כקבוצה עם קישור ייחודי. שלח את הקישור הזה לאנשים שאתה רוצה שיצטרפו לקבוצה.</p>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" id="hp-invite-link-settings-input" value="<?php echo esc_url( $invite_link ); ?>" readonly style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: monospace; font-size: 14px; background: #fff;" />
            <button type="button" id="hp-copy-invite-link-settings" class="button button-primary" style="white-space: nowrap; padding: 10px 20px;">העתק קישור</button>
        </div>
        <p style="margin-top: 10px; font-size: 12px; color: #666; margin-bottom: 0;">לחץ על "העתק קישור" ואז שלח את הקישור לאנשים שאתה רוצה שיצטרפו.</p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#hp-copy-invite-link-settings').on('click', function() {
            var input = document.getElementById('hp-invite-link-settings-input');
            if (input) {
                input.select();
                input.setSelectionRange(0, 99999);
                document.execCommand('copy');
                
                var button = $(this);
                var originalText = button.text();
                button.text('הועתק!');
                button.css('background', '#28a745');
                setTimeout(function() {
                    button.text(originalText);
                    button.css('background', '');
                }, 2000);
            }
        });
    });
    </script>
    <?php
}
add_action( 'bp_before_group_settings_admin', 'hp_bp_tweaks_display_invite_link_on_settings_page', 5 );

/**
 * מסך הצטרפות דרך קישור ייחודי.
 */
function hp_bp_tweaks_invite_link_join_screen() {
    $group = groups_get_current_group();
    if ( empty( $group ) || empty( $group->id ) ) {
        return;
    }
    
    $token = isset( $_GET['invite_token'] ) ? sanitize_text_field( $_GET['invite_token'] ) : '';
    $form_action = add_query_arg( 'invite_token', $token, bp_get_group_permalink( $group ) );
    $group_name = ! empty( $group->name ) ? esc_html( $group->name ) : 'הקבוצה';
    
    ?>
    <div class="hp-invite-link-join-form" style="max-width: 600px; margin: 40px auto; padding: 30px; background: #fff; border: 2px solid #4a90e2; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #2c5aa0; text-align: center;">הצטרפות לקבוצה: <?php echo $group_name; ?></h2>
        <div style="text-align: center; margin-bottom: 25px;">
            <p style="font-size: 16px; color: #555; margin-bottom: 10px;">✅ קיבלת קישור ייחודי להצטרפות לקבוצה זו.</p>
            <p style="font-size: 14px; color: #666;">לחץ על הכפתור למטה כדי להצטרף לקבוצה.</p>
        </div>
        <form method="post" action="<?php echo esc_url( $form_action ); ?>" style="text-align: center;">
            <?php wp_nonce_field( 'hp_join_group_' . $group->id, 'hp_join_group_nonce' ); ?>
            <button type="submit" name="hp_join_group" class="button button-primary" style="padding: 15px 40px; font-size: 18px; font-weight: bold; border-radius: 5px; background: #4a90e2; border: none; color: #fff; cursor: pointer;">
                הצטרף לקבוצה
            </button>
        </form>
    </div>
    <?php
}

/**
 * מסך שגיאה - קישור לא תקין.
 */
function hp_bp_tweaks_invite_link_error_screen() {
    $group = groups_get_current_group();
    if ( empty( $group ) || empty( $group->id ) ) {
        return;
    }
    
    echo '<div class="hp-invite-link-error">';
    echo '<p>קישור לא תקין או פג תוקף. אנא בדוק את הקישור שקיבלת.</p>';
    echo '<p><a href="' . esc_url( bp_get_group_permalink( $group ) ) . '" class="button">חזרה לעמוד הקבוצה</a></p>';
    echo '</div>';
}

/**
 * מסך התחברות - משתמש לא מחובר.
 */
function hp_bp_tweaks_invite_link_login_screen() {
    $group = groups_get_current_group();
    if ( empty( $group ) || empty( $group->id ) ) {
        return;
    }
    
    $token = isset( $_GET['invite_token'] ) ? sanitize_text_field( $_GET['invite_token'] ) : '';
    $login_url = wp_login_url( add_query_arg( 'invite_token', $token, bp_get_group_permalink( $group ) ) );
    
    echo '<div class="hp-invite-link-login">';
    echo '<p>עליך להתחבר כדי להצטרף לקבוצה.</p>';
    echo '<p><a href="' . esc_url( $login_url ) . '" class="button">התחברות</a></p>';
    echo '</div>';
}

/**
 * מסך - משתמש כבר חבר.
 */
function hp_bp_tweaks_invite_link_already_member_screen() {
    $group = groups_get_current_group();
    if ( empty( $group ) || empty( $group->id ) ) {
        return;
    }
    
    echo '<div class="hp-invite-link-already-member">';
    echo '<p>אתה כבר חבר בקבוצה זו.</p>';
    echo '<p><a href="' . esc_url( bp_get_group_permalink( $group ) ) . '" class="button">חזרה לעמוד הקבוצה</a></p>';
    echo '</div>';
}

/**
 * =================================================================
 * GROUP AVATAR WITH COLOR - תמונות ראשיות עגולות עם צבע
 * =================================================================
 */

/**
 * Generate a consistent color from a string (group name) using hash
 * Returns a hex color that's always the same for the same input
 */
function hp_bp_tweaks_get_group_color( $name ) {
    if ( empty( $name ) ) {
        return '#f0f0f0';
    }
    // Hash the name to get a consistent number
    $hash = crc32( $name );
    // Use absolute value and modulo to get a hue (0-360)
    $hue = abs( $hash ) % 360;
    // Use medium saturation and lightness for readable colors
    $saturation = 60 + ( abs( $hash ) % 20 ); // 60-80%
    $lightness = 75 + ( abs( $hash ) % 15 ); // 75-90% (light backgrounds)
    
    // Convert HSL to RGB
    $h = $hue / 360;
    $s = $saturation / 100;
    $l = $lightness / 100;
    
    $c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
    $x = $c * ( 1 - abs( fmod( $h * 6, 2 ) - 1 ) );
    $m = $l - $c / 2;
    
    if ( $h < 1/6 ) {
        $r = $c; $g = $x; $b = 0;
    } elseif ( $h < 2/6 ) {
        $r = $x; $g = $c; $b = 0;
    } elseif ( $h < 3/6 ) {
        $r = 0; $g = $c; $b = $x;
    } elseif ( $h < 4/6 ) {
        $r = 0; $g = $x; $b = $c;
    } elseif ( $h < 5/6 ) {
        $r = $x; $g = 0; $b = $c;
    } else {
        $r = $c; $g = 0; $b = $x;
    }
    
    $r = round( ( $r + $m ) * 255 );
    $g = round( ( $g + $m ) * 255 );
    $b = round( ( $b + $m ) * 255 );
    
    return sprintf( '#%02x%02x%02x', $r, $g, $b );
}

/**
 * Filter group avatar output to add color wrapper
 * This wraps the avatar in a div with the group's color
 * Uses bp_core_fetch_avatar filter which is the correct filter for BuddyPress avatars
 */
function hp_bp_tweaks_filter_group_avatar( $avatar, $args ) {
    // רק ברשימת הקבוצות (לא בעמוד קבוצה בודדת)
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return $avatar;
    }
    
    // רק אם זה לא עמוד קבוצה בודדת
    if ( function_exists( 'bp_is_single_item' ) && bp_is_single_item() ) {
        return $avatar;
    }
    
    // בדוק אם זה avatar של קבוצה (object = group)
    if ( empty( $args['object'] ) || $args['object'] !== 'group' ) {
        return $avatar;
    }
    
    // קבל את group_id
    $group_id = ! empty( $args['item_id'] ) ? $args['item_id'] : 0;
    if ( ! $group_id ) {
        return $avatar;
    }
    
    // קבל את שם הקבוצה
    $group = groups_get_group( $group_id );
    if ( empty( $group ) || empty( $group->name ) ) {
        return $avatar;
    }
    
    // קבל את הצבע של הקבוצה
    $group_color = hp_bp_tweaks_get_group_color( $group->name );
    
    // עטוף את התמונה ב-div עם צבע
    $wrapped_avatar = '<div class="hp-group-avatar-wrapper" style="--group-color: ' . esc_attr( $group_color ) . ';" data-group-name="' . esc_attr( $group->name ) . '">' . $avatar . '</div>';
    
    return $wrapped_avatar;
}
add_filter( 'bp_core_fetch_avatar', 'hp_bp_tweaks_filter_group_avatar', 10, 2 );

/**
 * Filter group permalink to point to group-posts tab instead of activity
 * This changes the default group link in the groups directory to go to group-posts
 * Uses a more targeted approach - only affects links in the groups directory list
 */
function hp_bp_tweaks_filter_group_permalink( $permalink, $group ) {
    // רק ברשימת הקבוצות (לא בעמוד קבוצה בודדת)
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return $permalink;
    }
    
    // רק אם זה לא עמוד קבוצה בודדת
    if ( function_exists( 'bp_is_single_item' ) && bp_is_single_item() ) {
        return $permalink;
    }
    
    // רק אם זה לא כבר group-posts או members או טאב אחר
    if ( strpos( $permalink, '/group-posts' ) !== false ||
         strpos( $permalink, '/members' ) !== false ||
         strpos( $permalink, '/admin' ) !== false ||
         strpos( $permalink, '/settings' ) !== false ||
         strpos( $permalink, '/send-invites' ) !== false ) {
        return $permalink;
    }
    
    // שנה את הקישור ל-group-posts (אם קיים) או members (אם לא)
    // קודם ננסה group-posts
    $group_posts_url = trailingslashit( $permalink ) . 'group-posts/';
    
    // תמיד נשתמש ב-group-posts כי אנחנו יוצרים את הלשונית
    return $group_posts_url;
}
add_filter( 'bp_get_group_permalink', 'hp_bp_tweaks_filter_group_permalink', 10, 2 ); 