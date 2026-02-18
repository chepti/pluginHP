<?php
/**
 * הוספת חברים ישירות לקבוצה – חיפוש משתמש בודד או ייבוא רשימת אימיילים
 *
 * @package Homer_Patuach_BP_Tweaks
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * בודק אם למשתמש הנוכחי יש הרשאה להוסיף חברים לקבוצה (מנהל או מודרטור)
 */
function hp_bp_tweaks_can_add_group_members( $group_id = 0 ) {
    if ( ! $group_id && function_exists( 'bp_get_current_group_id' ) ) {
        $group_id = bp_get_current_group_id();
    }
    if ( ! $group_id ) {
        return false;
    }
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return false;
    }
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }
    if ( function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( $user_id, $group_id ) ) {
        return true;
    }
    if ( function_exists( 'groups_is_user_mod' ) && groups_is_user_mod( $user_id, $group_id ) ) {
        return true;
    }
    return false;
}

/**
 * מציג את טופס הוספת חברים בעמוד ניהול החברים
 */
function hp_bp_tweaks_render_add_members_box() {
    if ( ! function_exists( 'bp_get_current_group_id' ) || ! bp_get_current_group_id() ) {
        return;
    }
    $group_id = bp_get_current_group_id();
    if ( ! hp_bp_tweaks_can_add_group_members( $group_id ) ) {
        return;
    }
    $nonce = wp_create_nonce( 'hp_add_group_members' );
    ?>
    <div class="hp-add-members-box bp-widget" id="hp-add-members-box">
        <h4 class="hp-add-members-title">הוסף חברים לקבוצה</h4>
        <p class="hp-add-members-desc">הוסף משתמש רשום בודד או ייבא רשימת אימיילים (כתובת אחת בשורה).</p>

        <div class="hp-add-members-tabs">
            <button type="button" class="hp-add-members-tab active" data-tab="single">הוסף חבר בודד</button>
            <button type="button" class="hp-add-members-tab" data-tab="import">ייבוא רשימת אימיילים</button>
        </div>

        <div class="hp-add-members-tab-content active" data-tab-content="single">
            <div class="hp-add-single-member">
                <label for="hp-member-search">חפש משתמש (שם משתמש או אימייל):</label>
                <input type="text" id="hp-member-search" class="hp-member-search-input" placeholder="הקלד כדי לחפש..." autocomplete="off" />
                <div id="hp-member-search-results" class="hp-member-search-results"></div>
                <div id="hp-member-search-status" class="hp-member-search-status"></div>
            </div>
        </div>

        <div class="hp-add-members-tab-content" data-tab-content="import">
            <div class="hp-import-emails">
                <label for="hp-emails-list">רשימת אימיילים (כתובת אחת בשורה):</label>
                <textarea id="hp-emails-list" class="hp-emails-textarea" rows="6" placeholder="user1@example.com&#10;user2@example.com&#10;user3@example.com"></textarea>
                <button type="button" id="hp-import-emails-btn" class="button button-primary">יבא והוסף לקבוצה</button>
                <div id="hp-import-status" class="hp-import-status"></div>
            </div>
        </div>

        <input type="hidden" id="hp-add-members-group-id" value="<?php echo (int) $group_id; ?>" />
        <input type="hidden" id="hp-add-members-nonce" value="<?php echo esc_attr( $nonce ); ?>" />
    </div>
    <?php
}
add_action( 'bp_before_group_manage_members_content', 'hp_bp_tweaks_render_add_members_box', 5 );

/**
 * AJAX: חיפוש משתמשים לפי שם משתמש או אימייל
 */
function hp_bp_tweaks_ajax_search_users() {
    check_ajax_referer( 'hp_add_group_members', 'nonce' );
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
    if ( ! $group_id || ! hp_bp_tweaks_can_add_group_members( $group_id ) ) {
        wp_send_json_error( [ 'message' => 'אין הרשאה.' ] );
    }
    if ( strlen( $search ) < 2 ) {
        wp_send_json_error( [ 'message' => 'הקלד לפחות 2 תווים.' ] );
    }
    global $wpdb;
    $like = '%' . $wpdb->esc_like( $search ) . '%';
    $users = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, user_login, user_email, display_name 
             FROM {$wpdb->users} 
             WHERE user_login LIKE %s OR user_email LIKE %s OR display_name LIKE %s 
             ORDER BY display_name ASC 
             LIMIT 20",
            $like,
            $like,
            $like
        )
    );
    $results = [];
    foreach ( (array) $users as $u ) {
        $is_member = false;
        if ( function_exists( 'groups_is_user_member' ) ) {
            $is_member = groups_is_user_member( (int) $u->ID, $group_id );
        }
        $results[] = [
            'id'         => (int) $u->ID,
            'login'      => $u->user_login,
            'email'      => $u->user_email,
            'name'       => $u->display_name,
            'is_member'  => $is_member,
        ];
    }
    wp_send_json_success( [ 'users' => $results ] );
}
add_action( 'wp_ajax_hp_add_group_members_search', 'hp_bp_tweaks_ajax_search_users' );

/**
 * AJAX: הוספת משתמש בודד לקבוצה
 */
function hp_bp_tweaks_ajax_add_single_member() {
    check_ajax_referer( 'hp_add_group_members', 'nonce' );
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    $user_id  = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
    if ( ! $group_id || ! $user_id || ! hp_bp_tweaks_can_add_group_members( $group_id ) ) {
        wp_send_json_error( [ 'message' => 'אין הרשאה.' ] );
    }
    if ( ! get_userdata( $user_id ) ) {
        wp_send_json_error( [ 'message' => 'משתמש לא נמצא.' ] );
    }
    if ( function_exists( 'groups_is_user_member' ) && groups_is_user_member( $user_id, $group_id ) ) {
        wp_send_json_error( [ 'message' => 'המשתמש כבר חבר בקבוצה.' ] );
    }
    if ( ! function_exists( 'groups_join_group' ) ) {
        wp_send_json_error( [ 'message' => 'פונקציית BuddyPress לא זמינה.' ] );
    }
    $joined = groups_join_group( $group_id, $user_id );
    if ( $joined ) {
        if ( function_exists( 'bp_update_user_last_activity' ) ) {
            bp_update_user_last_activity( $user_id );
        }
        $display_name = get_userdata( $user_id ) ? get_userdata( $user_id )->display_name : '';
        wp_send_json_success( [ 'message' => 'נוסף בהצלחה: ' . esc_html( $display_name ) ] );
    }
    wp_send_json_error( [ 'message' => 'שגיאה בהוספה.' ] );
}
add_action( 'wp_ajax_hp_add_group_members_add_one', 'hp_bp_tweaks_ajax_add_single_member' );

/**
 * AJAX: ייבוא רשימת אימיילים והוספת כל המשתמשים הרשומים לקבוצה
 */
function hp_bp_tweaks_ajax_import_emails() {
    check_ajax_referer( 'hp_add_group_members', 'nonce' );
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    $emails   = isset( $_POST['emails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['emails'] ) ) : '';
    if ( ! $group_id || ! hp_bp_tweaks_can_add_group_members( $group_id ) ) {
        wp_send_json_error( [ 'message' => 'אין הרשאה.' ] );
    }
    $lines = array_filter( array_map( 'trim', explode( "\n", $emails ) ) );
    $emails_list = array_filter( array_unique( $lines ), 'is_email' );
    if ( empty( $emails_list ) ) {
        wp_send_json_error( [ 'message' => 'לא נמצאו אימיילים תקינים.' ] );
    }
    $added   = 0;
    $skipped = 0;
    $not_found = 0;
    foreach ( $emails_list as $email ) {
        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            $not_found++;
            continue;
        }
        $user_id = (int) $user->ID;
        if ( function_exists( 'groups_is_user_member' ) && groups_is_user_member( $user_id, $group_id ) ) {
            $skipped++;
            continue;
        }
        if ( function_exists( 'groups_join_group' ) ) {
            $joined = groups_join_group( $group_id, $user_id );
            if ( $joined ) {
                $added++;
                if ( function_exists( 'bp_update_user_last_activity' ) ) {
                    bp_update_user_last_activity( $user_id );
                }
            }
        }
    }
    $message = sprintf(
        'נוספו: %d | דולגו (כבר בקבוצה): %d | לא נמצאו במערכת: %d',
        $added,
        $skipped,
        $not_found
    );
    wp_send_json_success( [
        'message'   => $message,
        'added'     => $added,
        'skipped'   => $skipped,
        'not_found' => $not_found,
    ] );
}
add_action( 'wp_ajax_hp_add_group_members_import', 'hp_bp_tweaks_ajax_import_emails' );

/**
 * טוען את ה-JS להוספת חברים
 */
function hp_bp_tweaks_enqueue_add_members_script() {
    if ( ! function_exists( 'bp_is_group_admin_screen' ) || ! bp_is_group_admin_screen( 'manage-members' ) ) {
        return;
    }
    if ( ! hp_bp_tweaks_can_add_group_members() ) {
        return;
    }
    wp_enqueue_script(
        'hp-add-group-members',
        HP_BP_TWEAKS_PLUGIN_DIR_URL . 'assets/js/group-add-members.js',
        [ 'jquery' ],
        HP_BP_TWEAKS_VERSION . '-' . time(),
        true
    );
    wp_localize_script( 'hp-add-group-members', 'hpAddMembers', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'hp_add_group_members' ),
    ] );
}
add_action( 'bp_enqueue_scripts', 'hp_bp_tweaks_enqueue_add_members_script', 20 );
