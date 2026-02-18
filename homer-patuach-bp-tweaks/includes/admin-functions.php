<?php
/**
 * Recalculate comments given for all users (one-time script)
 * Access via: yoursite.com/wp-admin/?hpg_calc_comments=1
 */
function hpg_recalculate_comments_given() {
    if ( !isset($_GET['hpg_calc_comments']) || !current_user_can('manage_options') ) {
        return;
    }
    
    global $wpdb;
    $users = get_users();
    $updated = 0;
    
    foreach ($users as $user) {
        $comment_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments} 
             WHERE user_id = %d AND comment_approved = '1'",
            $user->ID
        ));
        
        update_user_meta($user->ID, 'hpg_total_comments_given', $comment_count);
        $updated++;
    }
    
    wp_die("Comments recalculated for $updated users!");
}
add_action('admin_init', 'hpg_recalculate_comments_given');

/**
 * Recalculate comments received for all users (one-time script)
 * Access via: yoursite.com/wp-admin/?hpg_calc_comments_received=1
 */
function hpg_recalculate_comments_received() {
    if ( !isset($_GET['hpg_calc_comments_received']) || !current_user_can('manage_options') ) {
        return;
    }

    global $wpdb;
    $users   = get_users();
    $updated = 0;

    foreach ( $users as $user ) {
        $comment_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) 
                 FROM {$wpdb->comments} c
                 INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
                 WHERE p.post_author = %d
                   AND p.post_type = 'post'
                   AND c.comment_approved = '1'",
                $user->ID
            )
        );

        update_user_meta( $user->ID, 'hpg_total_comments_received', (int) $comment_count );
        $updated++;
    }

    wp_die( 'Comments received recalculated for ' . $updated . ' users!' );
}
add_action( 'admin_init', 'hpg_recalculate_comments_received' );

/**
 * Recalculate heart likes given for all users (one-time script)
 * Access via: yoursite.com/wp-admin/?hpg_calc_likes=1
 */
function hpg_recalculate_heart_likes_given() {
    if ( !isset($_GET['hpg_calc_likes']) || !current_user_can('manage_options') ) {
        return;
    }
    
    global $wpdb;
    $users = get_users();
    $updated = 0;
    
    foreach ($users as $user) {
        // Count posts where this user's ID appears in the _hpg_likes meta
        $likes_count = 0;
        
        // Get all posts with likes
        $posts_with_likes = $wpdb->get_results(
            "SELECT post_id, meta_value 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = '_hpg_likes'"
        );
        
        foreach ($posts_with_likes as $post) {
            $likes_array = maybe_unserialize($post->meta_value);
            if (is_array($likes_array) && in_array($user->ID, $likes_array)) {
                $likes_count++;
            }
        }
        
        update_user_meta($user->ID, 'hpg_total_heart_likes_given', $likes_count);
        $updated++;
    }
    
    wp_die("Heart likes recalculated for $updated users!");
}
add_action('admin_init', 'hpg_recalculate_heart_likes_given');

/**
 * Add founder badge field to user profile (admin only)
 */
function hpg_add_founder_badge_field($user) {
    if (!current_user_can('manage_options')) return;
    
    $has_founder = hpg_user_has_badge($user->ID, 'founder');
    ?>
    <h3>באדג'ים מיוחדים</h3>
    <table class="form-table">
        <tr>
            <th><label>באדג' מייסד 👑</label></th>
            <td>
                <input type="checkbox" name="hpg_founder_badge" value="1" <?php checked($has_founder); ?>>
                <span class="description">סמן כדי להעניק באדג' מייסד למשתמש זה</span>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'hpg_add_founder_badge_field');
add_action('edit_user_profile', 'hpg_add_founder_badge_field');

/**
 * Save founder badge field
 */
function hpg_save_founder_badge_field($user_id) {
    if (!current_user_can('manage_options')) return;
    
    if (isset($_POST['hpg_founder_badge'])) {
        hpg_grant_manual_badge($user_id, 'founder');
    } else {
        hpg_revoke_manual_badge($user_id, 'founder');
    }
}
add_action('personal_options_update', 'hpg_save_founder_badge_field');
add_action('edit_user_profile_update', 'hpg_save_founder_badge_field');
