<?php
/**
 * Group Folders - תיקיות בתוך "פוסטים של הקבוצה"
 *
 * מנהל קבוצה יכול ליצור תיקיות. לכל תיקייה יש כפתור "+" שמוצג לחברי הקבוצה בלבד –
 * לחיצה פותחת את טופס ההגשה הרגיל, והפוסט נשמר עם קישור לתיקייה.
 * פוסטים שמוגשים דרך תיקייה מופיעים רק בפעמון בדיקת הפוסטים של הקבוצה, לא בפעמון האדמין הראשי.
 *
 * @package Homer_Patuach_BP_Tweaks
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * קבלת תיקיות של קבוצה.
 *
 * @param int $group_id
 * @return array
 */
function hpg_get_group_folders( $group_id ) {
    $key   = 'hpg_group_folders_' . (int) $group_id;
    $saved = get_option( $key, [] );
    return is_array( $saved ) ? $saved : [];
}

/**
 * יצירת תיקייה חדשה.
 *
 * @param int    $group_id
 * @param string $name
 * @param int    $created_by
 * @return array|WP_Error
 */
function hpg_create_group_folder( $group_id, $name, $created_by = 0 ) {
    $group_id = (int) $group_id;
    $name    = trim( sanitize_text_field( $name ) );
    if ( empty( $name ) ) {
        return new WP_Error( 'empty_name', __( 'שם התיקייה לא יכול להיות ריק.', 'homer-patuach-bp-tweaks' ) );
    }

    $folders = hpg_get_group_folders( $group_id );
    $slug    = sanitize_title( $name );
    $base    = $slug;
    $i       = 0;
    while ( wp_list_filter( $folders, [ 'slug' => $slug ] ) ) {
        $slug = $base . '-' . ( ++$i );
    }

    $id = uniqid( 'f', true );
    $folder = [
        'id'         => $id,
        'name'       => $name,
        'slug'       => $slug,
        'created_at' => current_time( 'mysql' ),
        'created_by' => $created_by ? (int) $created_by : get_current_user_id(),
    ];
    $folders[] = $folder;
    update_option( 'hpg_group_folders_' . $group_id, $folders );

    return $folder;
}

/**
 * מחיקת תיקייה.
 *
 * @param int    $group_id
 * @param string $folder_id
 * @return bool
 */
function hpg_delete_group_folder( $group_id, $folder_id ) {
    $folders = hpg_get_group_folders( $group_id );
    $before  = count( $folders );
    $folders = array_values( array_filter( $folders, function ( $f ) use ( $folder_id ) {
        return ( isset( $f['id'] ) ? $f['id'] : '' ) !== $folder_id;
    } ) );
    if ( count( $folders ) === $before ) {
        return false;
    }
    update_option( 'hpg_group_folders_' . (int) $group_id, $folders );
    return true;
}

/**
 * AJAX: יצירת תיקייה.
 */
function hpg_ajax_create_group_folder() {
    check_ajax_referer( 'hpg-group-folders-nonce', 'nonce' );

    $group_id = isset( $_POST['group_id'] ) ? (int) $_POST['group_id'] : 0;
    $name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

    if ( ! $group_id || ! $name ) {
        wp_send_json_error( [ 'message' => __( 'חסרים פרטים.', 'homer-patuach-bp-tweaks' ) ] );
    }

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( [ 'message' => __( 'יש להתחבר.', 'homer-patuach-bp-tweaks' ) ] );
    }

    $is_admin = false;
    if ( function_exists( 'groups_is_user_admin' ) ) {
        $is_admin = groups_is_user_admin( $user_id, $group_id );
    }
    if ( ! $is_admin && function_exists( 'groups_is_user_mod' ) ) {
        $is_admin = groups_is_user_mod( $user_id, $group_id );
    }
    if ( ! $is_admin && current_user_can( 'edit_others_posts' ) ) {
        $is_admin = true;
    }

    if ( ! $is_admin ) {
        wp_send_json_error( [ 'message' => __( 'אין הרשאה ליצור תיקיות.', 'homer-patuach-bp-tweaks' ) ] );
    }

    $folder = hpg_create_group_folder( $group_id, $name, $user_id );
    if ( is_wp_error( $folder ) ) {
        wp_send_json_error( [ 'message' => $folder->get_error_message() ] );
    }

    $is_member = function_exists( 'groups_is_user_member' ) && groups_is_user_member( $user_id, $group_id );
    wp_send_json_success( [
        'folder'    => $folder,
        'is_member' => $is_member,
    ] );
}
add_action( 'wp_ajax_hpg_create_group_folder', 'hpg_ajax_create_group_folder' );

/**
 * AJAX: מחיקת תיקייה.
 */
function hpg_ajax_delete_group_folder() {
    check_ajax_referer( 'hpg-group-folders-nonce', 'nonce' );

    $group_id   = isset( $_POST['group_id'] ) ? (int) $_POST['group_id'] : 0;
    $folder_id  = isset( $_POST['folder_id'] ) ? sanitize_text_field( wp_unslash( $_POST['folder_id'] ) ) : '';

    if ( ! $group_id || ! $folder_id ) {
        wp_send_json_error( [ 'message' => __( 'חסרים פרטים.', 'homer-patuach-bp-tweaks' ) ] );
    }

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( [ 'message' => __( 'יש להתחבר.', 'homer-patuach-bp-tweaks' ) ] );
    }

    $is_admin = false;
    if ( function_exists( 'groups_is_user_admin' ) ) {
        $is_admin = groups_is_user_admin( $user_id, $group_id );
    }
    if ( ! $is_admin && function_exists( 'groups_is_user_mod' ) ) {
        $is_admin = groups_is_user_mod( $user_id, $group_id );
    }
    if ( ! $is_admin && current_user_can( 'edit_others_posts' ) ) {
        $is_admin = true;
    }

    if ( ! $is_admin ) {
        wp_send_json_error( [ 'message' => __( 'אין הרשאה.', 'homer-patuach-bp-tweaks' ) ] );
    }

    if ( ! hpg_delete_group_folder( $group_id, $folder_id ) ) {
        wp_send_json_error( [ 'message' => __( 'התיקייה לא נמצאה.', 'homer-patuach-bp-tweaks' ) ] );
    }

    wp_send_json_success();
}
add_action( 'wp_ajax_hpg_delete_group_folder', 'hpg_ajax_delete_group_folder' );

/**
 * Meta key for storing folder ID on posts submitted via a group folder.
 */
define( 'HPG_GROUP_FOLDER_META_KEY', '_hpg_group_folder_id' );

/**
 * שמירת folder_id כ־post meta בעת יצירת פוסט (אם הטופס שולח hpg_group_folder_id).
 */
function hpg_save_group_folder_on_post( $post_id, $post, $update ) {
	if ( $update ) {
		return;
	}
	if ( ! isset( $_POST['hpg_group_folder_id'] ) || $_POST['hpg_group_folder_id'] === '' ) {
		return;
	}
	$folder_id = sanitize_text_field( wp_unslash( $_POST['hpg_group_folder_id'] ) );
	if ( $folder_id ) {
		update_post_meta( $post_id, HPG_GROUP_FOLDER_META_KEY, $folder_id );
	}
}
add_action( 'save_post_post', 'hpg_save_group_folder_on_post', 15, 3 );

/**
 * סינון פוסטים לפי תיקייה – הוק ל־pre_get_posts.
 * רץ כשמשתמש מסנן לפי תיקייה (hpg_folder ב־URL) בעמוד פוסטים של הקבוצה.
 */
function hpg_filter_group_posts_by_folder( $query ) {
	if ( ! function_exists( 'bp_is_current_action' ) || ! bp_is_current_action( 'group-posts' ) ) {
		return;
	}
	$folder = isset( $_GET['hpg_folder'] ) ? sanitize_text_field( wp_unslash( $_GET['hpg_folder'] ) ) : '';
	if ( empty( $folder ) ) {
		return;
	}
	$post_type = $query->get( 'post_type' );
	if ( $post_type !== 'post' && ( ! is_array( $post_type ) || ! in_array( 'post', $post_type, true ) ) ) {
		return;
	}
	$meta_query = $query->get( 'meta_query' );
	if ( ! is_array( $meta_query ) ) {
		$meta_query = [];
	}
	$meta_query[] = [
		'key'   => HPG_GROUP_FOLDER_META_KEY,
		'value' => $folder,
	];
	$query->set( 'meta_query', $meta_query );
}
add_action( 'pre_get_posts', 'hpg_filter_group_posts_by_folder', 999 );

/**
 * פילטר ל־query args – לשימוש על ידי גריד הפוסטים (אם משתמש ב־apply_filters).
 *
 * @param array $args
 * @param int   $group_id
 * @return array
 */
function hpg_add_folder_filter_to_group_posts_args( $args, $group_id = 0 ) {
	$folder = isset( $_GET['hpg_folder'] ) ? sanitize_text_field( wp_unslash( $_GET['hpg_folder'] ) ) : '';
	if ( empty( $folder ) ) {
		return $args;
	}
	$meta_query = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : [];
	$meta_query[] = [
		'key'   => HPG_GROUP_FOLDER_META_KEY,
		'value' => $folder,
	];
	$args['meta_query'] = $meta_query;
	return $args;
}
add_filter( 'hpg_group_posts_query_args', 'hpg_add_folder_filter_to_group_posts_args', 10, 2 );
