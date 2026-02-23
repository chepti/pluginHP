<?php
/**
 * הצגת חברי קבוצה בטבלה עם מיון לפי כותרות – למנהלי קבוצה
 *
 * @package Homer_Patuach_BP_Tweaks
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * בודק אם המשתמש מנהל/מודרטור של הקבוצה
 */
function hp_bp_tweaks_is_group_manager( $group_id = 0 ) {
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
    if ( function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( $user_id, $group_id ) ) {
        return true;
    }
    if ( function_exists( 'groups_is_user_mod' ) && groups_is_user_mod( $user_id, $group_id ) ) {
        return true;
    }
    if ( current_user_can( 'edit_others_posts' ) ) {
        return true;
    }
    return false;
}

/**
 * מציג את כותרת העמוד עם מתג טבלה/כרטיסים למנהלים (מחליף את הכותרת הרגילה)
 */
function hp_bp_tweaks_members_table_header_and_toggle() {
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
    $is_manager = hp_bp_tweaks_is_group_manager( $group->id );
    $base_url  = trailingslashit( bp_get_group_permalink( $group ) ) . 'members/';
    $is_table  = isset( $_GET['view'] ) && $_GET['view'] === 'table';

    $group_name = esc_html( $group->name );
    echo '<div class="hpg-group-members-header" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;">';
    echo '<h2 style="margin: 0; font-size: 1.5rem; color: #333;">' . $group_name . '</h2>';
    if ( $is_manager ) {
        if ( $is_table ) {
            echo '<a href="' . esc_url( $base_url ) . '" class="hpg-view-toggle-btn no-ajax">' . esc_html__( 'הצג ככרטיסים', 'homer-patuach-bp-tweaks' ) . '</a>';
        } else {
            echo '<a href="' . esc_url( add_query_arg( 'view', 'table', $base_url ) ) . '" class="hpg-view-toggle-btn no-ajax">' . esc_html__( 'הצג כטבלה', 'homer-patuach-bp-tweaks' ) . '</a>';
        }
    }
    echo '</div>';
}
remove_action( 'bp_before_group_members_content', 'hp_bp_tweaks_add_group_name_to_members_page', 5 );
add_action( 'bp_before_group_members_content', 'hp_bp_tweaks_members_table_header_and_toggle', 5 );

/**
 * מחליף את רשימת החברים בטבלה כשמנהל בוחר view=table
 */
function hp_bp_tweaks_render_members_table() {
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return;
    }
    if ( ! function_exists( 'bp_is_current_action' ) || ! bp_is_current_action( 'members' ) ) {
        return;
    }
    if ( ! isset( $_GET['view'] ) || $_GET['view'] !== 'table' ) {
        return;
    }
    $group = groups_get_current_group();
    if ( ! $group || empty( $group->id ) ) {
        return;
    }
    if ( ! hp_bp_tweaks_is_group_manager( $group->id ) ) {
        return;
    }
    if ( ! function_exists( 'groups_get_group_members' ) ) {
        return;
    }

    $members = groups_get_group_members(
        [
            'group_id' => $group->id,
            'per_page' => 9999,
            'page'     => 1,
        ]
    );
    if ( empty( $members['members'] ) ) {
        echo '<div class="hpg-members-table-wrapper"><p>' . esc_html__( 'אין חברים להצגה.', 'homer-patuach-bp-tweaks' ) . '</p></div>';
        return;
    }

    $rows = [];
    foreach ( $members['members'] as $m ) {
        $uid = isset( $m->user_id ) ? (int) $m->user_id : ( isset( $m->ID ) ? (int) $m->ID : 0 );
        if ( ! $uid ) {
            continue;
        }
        $user = get_userdata( $uid );
        $name = $user ? $user->display_name : '';
        $my_posts_url = function_exists( 'bp_core_get_user_domain' ) ? trailingslashit( rtrim( bp_core_get_user_domain( $uid ), '/' ) . '/my-posts' ) : get_author_posts_url( $uid );
        $posts = function_exists( 'hpg_get_user_total_posts' ) ? hpg_get_user_total_posts( $uid ) : 0;
        $comments = function_exists( 'hpg_get_user_comments_given' ) ? hpg_get_user_comments_given( $uid ) : 0;
        $date_joined = isset( $m->date_modified ) ? $m->date_modified : '';
        if ( $date_joined ) {
            $date_joined = bp_core_time_since( $date_joined );
        }
        $rows[] = [
            'uid'     => $uid,
            'name'    => $name,
            'url'     => $my_posts_url,
            'posts'   => $posts,
            'comments'=> $comments,
            'joined'  => $date_joined,
        ];
    }

    echo '<div class="hpg-members-table-wrapper hpg-members-table-mode" data-rows="' . esc_attr( wp_json_encode( $rows ) ) . '">';
    echo '<table class="hpg-members-table" dir="rtl">';
    echo '<thead><tr>';
    echo '<th class="hpg-sortable" data-sort="name">' . esc_html__( 'שם', 'homer-patuach-bp-tweaks' ) . ' <span class="hpg-sort-icon"></span></th>';
    echo '<th class="hpg-sortable" data-sort="joined">' . esc_html__( 'הצטרף', 'homer-patuach-bp-tweaks' ) . ' <span class="hpg-sort-icon"></span></th>';
    echo '<th class="hpg-sortable" data-sort="posts">' . esc_html__( 'פוסטים', 'homer-patuach-bp-tweaks' ) . ' <span class="hpg-sort-icon"></span></th>';
    echo '<th class="hpg-sortable" data-sort="comments">' . esc_html__( 'תגובות', 'homer-patuach-bp-tweaks' ) . ' <span class="hpg-sort-icon"></span></th>';
    echo '</tr></thead><tbody></tbody></table>';
    echo '</div>';
}
add_action( 'bp_after_group_members_content', 'hp_bp_tweaks_render_members_table', 5 );

/**
 * מסתיר את רשימת הכרטיסים כשמציגים טבלה
 */
function hp_bp_tweaks_hide_default_members_when_table() {
    if ( ! isset( $_GET['view'] ) || $_GET['view'] !== 'table' ) {
        return;
    }
    if ( ! function_exists( 'bp_is_groups_component' ) || ! bp_is_groups_component() ) {
        return;
    }
    if ( ! function_exists( 'bp_is_current_action' ) || ! bp_is_current_action( 'members' ) ) {
        return;
    }
    $group = groups_get_current_group();
    if ( ! $group || ! hp_bp_tweaks_is_group_manager( $group->id ) ) {
        return;
    }
    echo '<style>.hpg-members-table-mode ~ .item-list.members-group-list, .hpg-members-table-mode ~ #group-members-list, .hpg-members-table-mode ~ [data-bp-list="group-members"], body.hpg-table-view .item-list.members-group-list, body.hpg-table-view #group-members-list, body.hpg-table-view [data-bp-list="group-members"] { display: none !important; }</style>';
    echo '<script>document.body.classList.add("hpg-table-view");</script>';
}
add_action( 'bp_after_group_members_content', 'hp_bp_tweaks_hide_default_members_when_table', 1 );

/**
 * שורטקוד לבדיקת מנוע התבניות: [hp_bp_template_pack]
 * להציג בעמוד כלשהו (למשל דף בדיקה) – מראה איזה מנוע תבניות BuddyPress פעיל (nouveau או legacy).
 */
function hp_bp_tweaks_template_pack_shortcode() {
    if ( ! function_exists( 'bp_get_theme_package_id' ) ) {
        return '<p>BuddyPress לא פעיל.</p>';
    }
    $pack = bp_get_theme_package_id();
    return '<p><strong>מנוע תבניות BuddyPress:</strong> ' . esc_html( $pack ) . '</p>';
}
add_shortcode( 'hp_bp_template_pack', 'hp_bp_tweaks_template_pack_shortcode' );
