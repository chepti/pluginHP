<?php
/**
 * Community Badges System
 * 
 * This file handles the badge system for community members.
 * Badges are awarded based on user activity and contributions.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Define all available badges with their criteria
 */
function hpg_get_badge_definitions() {
    return [
        'contributor' => [
            'name' => 'תורם',
            'emoji' => '🌱',
            'description' => 'פרסם לפחות 5 פוסטים',
            'threshold' => 5,
            'type' => 'posts',
            'manual' => false
        ],
        'super_contributor' => [
            'name' => 'תורם מוביל',
            'emoji' => '🌟',
            'description' => 'פרסם לפחות 10 פוסטים',
            'threshold' => 10,
            'type' => 'posts',
            'manual' => false
        ],
        'commenter' => [
            'name' => 'מגיב',
            'emoji' => '💬',
            'description' => 'כתב לפחות 5 תגובות',
            'threshold' => 5,
            'type' => 'comments_given',
            'manual' => false
        ],
        'super_commenter' => [
            'name' => 'מגיב מוביל',
            'emoji' => '🗣️',
            'description' => 'כתב לפחות 20 תגובות',
            'threshold' => 20,
            'type' => 'comments_given',
            'manual' => false
        ],
        'rater' => [
            'name' => 'מדרג',
            'emoji' => '⭐',
            'description' => 'נתן לפחות 5 דירוגי כוכבים לתגובות',
            'threshold' => 5,
            'type' => 'star_ratings_given',
            'manual' => false
        ],
        'super_rater' => [
            'name' => 'מדרג מוביל',
            'emoji' => '🌠',
            'description' => 'נתן לפחות 20 דירוגי כוכבים לתגובות',
            'threshold' => 20,
            'type' => 'star_ratings_given',
            'manual' => false
        ],
        'lover' => [
            'name' => 'אוהב',
            'emoji' => '❤️',
            'description' => 'נתן לפחות 10 לבבות לפוסטים',
            'threshold' => 10,
            'type' => 'heart_likes_given',
            'manual' => false
        ],
        'tagger' => [
            'name' => 'מתייג',
            'emoji' => '🏷️',
            'description' => 'הוסיף לפחות 5 תגיות לפוסטים',
            'threshold' => 5,
            'type' => 'tags_added',
            'manual' => false
        ],
        'founder' => [
            'name' => 'מייסד',
            'emoji' => '👑',
            'description' => 'חבר מייסד של הקהילה',
            'threshold' => 0,
            'type' => 'manual',
            'manual' => true
        ],
        'editor' => [
            'name' => 'עורך',
            'emoji' => '✏️',
            'description' => 'הרשאות עריכה',
            'threshold' => 0,
            'type' => 'role',
            'manual' => false,
            'required_capability' => 'edit_others_posts'
        ]
    ];
}

/**
 * Get user's total comments given (not received)
 */
function hpg_get_user_comments_given( $user_id ) {
    $count = get_user_meta( $user_id, 'hpg_total_comments_given', true );
    return $count ? (int) $count : 0;
}

/**
 * Get user's total star ratings given (for comments)
 */
function hpg_get_user_star_ratings_given( $user_id ) {
    $count = get_user_meta( $user_id, 'hpg_total_star_ratings_given', true );
    return $count ? (int) $count : 0;
}

/**
 * Get user's total heart likes given (for posts)
 */
function hpg_get_user_heart_likes_given( $user_id ) {
    $count = get_user_meta( $user_id, 'hpg_total_heart_likes_given', true );
    return $count ? (int) $count : 0;
}

/**
 * Check if user has earned a specific badge
 */
function hpg_user_has_badge( $user_id, $badge_key ) {
    $definitions = hpg_get_badge_definitions();
    
    if ( ! isset( $definitions[$badge_key] ) ) {
        return false;
    }
    
    $badge = $definitions[$badge_key];
    
    // Manual badges
    if ( $badge['manual'] ) {
        $manual_badges = get_user_meta( $user_id, 'hpg_manual_badges', true );
        return is_array( $manual_badges ) && in_array( $badge_key, $manual_badges );
    }
    
    // Role-based badges
    if ( $badge['type'] === 'role' && isset( $badge['required_capability'] ) ) {
        return user_can( $user_id, $badge['required_capability'] );
    }
    
    // Activity-based badges
    $current_value = 0;
    
    switch ( $badge['type'] ) {
        case 'posts':
            $current_value = hpg_get_user_total_posts( $user_id );
            break;
        case 'comments_given':
            $current_value = hpg_get_user_comments_given( $user_id );
            break;
        case 'star_ratings_given':
            $current_value = hpg_get_user_star_ratings_given( $user_id );
            break;
        case 'heart_likes_given':
            $current_value = hpg_get_user_heart_likes_given( $user_id );
            break;
        case 'tags_added':
            $current_value = function_exists( 'hpg_get_user_total_tags_added' ) ? hpg_get_user_total_tags_added( $user_id ) : 0;
            break;
    }
    
    return $current_value >= $badge['threshold'];
}

/**
 * Get all badges earned by a user
 */
function hpg_get_user_badges( $user_id ) {
    $definitions = hpg_get_badge_definitions();
    $earned_badges = [];
    
    foreach ( $definitions as $key => $badge ) {
        if ( hpg_user_has_badge( $user_id, $key ) ) {
            $earned_badges[$key] = $badge;
        }
    }
    
    return $earned_badges;
}

/**
 * Get badge progress for a user
 */
function hpg_get_badge_progress( $user_id, $badge_key ) {
    $definitions = hpg_get_badge_definitions();
    
    if ( ! isset( $definitions[$badge_key] ) ) {
        return null;
    }
    
    $badge = $definitions[$badge_key];
    
    // Manual and role badges don't have progress
    if ( $badge['manual'] || $badge['type'] === 'role' ) {
        return null;
    }
    
    $current_value = 0;
    
    switch ( $badge['type'] ) {
        case 'posts':
            $current_value = hpg_get_user_total_posts( $user_id );
            break;
        case 'comments_given':
            $current_value = hpg_get_user_comments_given( $user_id );
            break;
        case 'star_ratings_given':
            $current_value = hpg_get_user_star_ratings_given( $user_id );
            break;
        case 'heart_likes_given':
            $current_value = hpg_get_user_heart_likes_given( $user_id );
            break;
        case 'tags_added':
            $current_value = function_exists( 'hpg_get_user_total_tags_added' ) ? hpg_get_user_total_tags_added( $user_id ) : 0;
            break;
    }
    
    $threshold = $badge['threshold'];
    $percentage = $threshold > 0 ? min( 100, ( $current_value / $threshold ) * 100 ) : 0;
    
    return [
        'current' => $current_value,
        'required' => $threshold,
        'percentage' => $percentage,
        'earned' => $current_value >= $threshold
    ];
}

/**
 * Display badges for a user (for their own community page)
 */
function hpg_display_user_badges_with_progress( $user_id ) {
    $definitions = hpg_get_badge_definitions();
    $badges_to_show = [];
    
    // Smart filtering: show earned badges + next achievable ones
    foreach ( $definitions as $key => $badge ) {
        $has_badge = hpg_user_has_badge( $user_id, $key );
        
        if ( $has_badge ) {
            // Always show earned badges
            $badges_to_show[$key] = $badge;
        } else {
            // For unearned badges, check if they're the "next" in their category
            $progress = hpg_get_badge_progress( $user_id, $key );
            
            if ( $progress !== null && $progress['current'] > 0 ) {
                // Show if user has started progress
                $badges_to_show[$key] = $badge;
            } elseif ( $badge['manual'] || $badge['type'] === 'role' ) {
                // Don't show manual or role badges unless earned
                continue;
            } elseif ( $progress !== null && $progress['current'] === 0 ) {
                // For "super" badges, only show if the basic one is earned
                if ( strpos($key, 'super_') === 0 ) {
                    $basic_key = str_replace('super_', '', $key);
                    if ( !hpg_user_has_badge( $user_id, $basic_key ) ) {
                        continue; // Don't show super badge if basic not earned
                    }
                }
                $badges_to_show[$key] = $badge;
            }
        }
    }
    
    ob_start();
    ?>
    <div class="hpg-badges-section hpg-badges-compact">
        <h3>הבאדג'ים שלי</h3>
        <div class="hpg-badges-grid">
            <?php foreach ( $badges_to_show as $key => $badge ) : 
                $has_badge = hpg_user_has_badge( $user_id, $key );
                $progress = hpg_get_badge_progress( $user_id, $key );
                $badge_class = $has_badge ? 'hpg-badge-earned' : 'hpg-badge-locked';
            ?>
                <div class="hpg-badge-item <?php echo esc_attr( $badge_class ); ?>" 
                     title="<?php echo esc_attr( $badge['description'] ); ?>">
                    <div class="hpg-badge-icon">
                        <?php echo $badge['emoji']; ?>
                    </div>
                    <div class="hpg-badge-name"><?php echo esc_html( $badge['name'] ); ?></div>
                    
                    <?php if ( ! $has_badge && $progress !== null ) : ?>
                        <div class="hpg-badge-progress">
                            <div class="hpg-progress-bar">
                                <div class="hpg-progress-fill" style="width: <?php echo esc_attr( $progress['percentage'] ); ?>%"></div>
                            </div>
                            <div class="hpg-progress-text">
                                <?php echo esc_html( $progress['current'] ); ?> / <?php echo esc_html( $progress['required'] ); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Display earned badges only (for profile pages and contributor cards)
 * Shows only the highest tier badge in each category
 */
function hpg_display_earned_badges( $user_id, $limit = 0 ) {
    $earned_badges = hpg_get_user_badges( $user_id );
    
    if ( empty( $earned_badges ) ) {
        return '';
    }
    
    // Filter to show only highest tier in each category
    $badge_tiers = [
        'posts' => ['super_contributor', 'contributor'],
        'comments_given' => ['super_commenter', 'commenter'],
        'star_ratings_given' => ['super_rater', 'rater'],
    ];
    
    $filtered_badges = [];
    
    foreach ( $earned_badges as $key => $badge ) {
        $is_lower_tier = false;
        
        // Check if this is a lower tier badge
        foreach ( $badge_tiers as $category => $tiers ) {
            if ( $badge['type'] === $category ) {
                // If this badge is in the tier list
                $badge_position = array_search( $key, $tiers );
                if ( $badge_position !== false ) {
                    // Check if user has a higher tier badge
                    for ( $i = 0; $i < $badge_position; $i++ ) {
                        if ( isset( $earned_badges[$tiers[$i]] ) ) {
                            $is_lower_tier = true;
                            break 2;
                        }
                    }
                }
            }
        }
        
        if ( !$is_lower_tier ) {
            $filtered_badges[$key] = $badge;
        }
    }
    
    // Limit badges if specified
    if ( $limit > 0 ) {
        $filtered_badges = array_slice( $filtered_badges, 0, $limit, true );
    }
    
    ob_start();
    ?>
    <div class="hpg-badges-list hpg-badges-profile">
        <?php foreach ( $filtered_badges as $key => $badge ) : ?>
            <div class="hpg-badge-circle" title="<?php echo esc_attr( $badge['description'] ); ?>">
                <span class="hpg-badge-emoji"><?php echo $badge['emoji']; ?></span>
                <span class="hpg-badge-label"><?php echo esc_html( $badge['name'] ); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Track comments given by users
 */
function hpg_track_comments_given( $comment_id, $comment_approved, $commentdata ) {
    if ( $comment_approved === 1 || $comment_approved === 'approve' ) {
        $user_id = $commentdata['user_id'];
        
        if ( $user_id ) {
            $current_count = hpg_get_user_comments_given( $user_id );
            update_user_meta( $user_id, 'hpg_total_comments_given', $current_count + 1 );
        }
    }
}
add_action( 'comment_post', 'hpg_track_comments_given', 10, 3 );

/**
 * Track when comments are approved
 */
function hpg_track_comment_approval( $new_status, $old_status, $comment ) {
    if ( $new_status === 'approved' && $old_status !== 'approved' ) {
        $user_id = $comment->user_id;
        
        if ( $user_id ) {
            $current_count = hpg_get_user_comments_given( $user_id );
            update_user_meta( $user_id, 'hpg_total_comments_given', $current_count + 1 );
        }
    } elseif ( $old_status === 'approved' && $new_status !== 'approved' ) {
        $user_id = $comment->user_id;
        
        if ( $user_id ) {
            $current_count = hpg_get_user_comments_given( $user_id );
            if ( $current_count > 0 ) {
                update_user_meta( $user_id, 'hpg_total_comments_given', $current_count - 1 );
            }
        }
    }
}
add_action( 'transition_comment_status', 'hpg_track_comment_approval', 10, 3 );

/**
 * Track heart likes given (for posts)
 * This hooks into the like system from homer-patuach-grid
 */
function hpg_track_heart_like_given( $post_id, $user_id ) {
    $current_count = hpg_get_user_heart_likes_given( $user_id );
    update_user_meta( $user_id, 'hpg_total_heart_likes_given', $current_count + 1 );
}
add_action( 'hpg_user_liked_post', 'hpg_track_heart_like_given', 10, 2 );

/**
 * Track heart like removal
 */
function hpg_track_heart_like_removed( $post_id, $user_id ) {
    $current_count = hpg_get_user_heart_likes_given( $user_id );
    if ( $current_count > 0 ) {
        update_user_meta( $user_id, 'hpg_total_heart_likes_given', $current_count - 1 );
    }
}
add_action( 'hpg_user_unliked_post', 'hpg_track_heart_like_removed', 10, 2 );

/**
 * Track star ratings given (for comments)
 * TODO: Hook this to your comment rating system when implemented
 */
function hpg_track_star_rating_given( $comment_id, $user_id ) {
    $current_count = hpg_get_user_star_ratings_given( $user_id );
    update_user_meta( $user_id, 'hpg_total_star_ratings_given', $current_count + 1 );
}
// add_action( 'your_comment_rating_action', 'hpg_track_star_rating_given', 10, 2 );

/**
 * Track star rating removal
 */
function hpg_track_star_rating_removed( $comment_id, $user_id ) {
    $current_count = hpg_get_user_star_ratings_given( $user_id );
    if ( $current_count > 0 ) {
        update_user_meta( $user_id, 'hpg_total_star_ratings_given', $current_count - 1 );
    }
}
// add_action( 'your_comment_rating_removal_action', 'hpg_track_star_rating_removed', 10, 2 );

/**
 * Admin function to grant manual badges
 */
function hpg_grant_manual_badge( $user_id, $badge_key ) {
    $definitions = hpg_get_badge_definitions();
    
    if ( ! isset( $definitions[$badge_key] ) || ! $definitions[$badge_key]['manual'] ) {
        return false;
    }
    
    $manual_badges = get_user_meta( $user_id, 'hpg_manual_badges', true );
    if ( ! is_array( $manual_badges ) ) {
        $manual_badges = [];
    }
    
    if ( ! in_array( $badge_key, $manual_badges ) ) {
        $manual_badges[] = $badge_key;
        update_user_meta( $user_id, 'hpg_manual_badges', $manual_badges );
        return true;
    }
    
    return false;
}

/**
 * Admin function to revoke manual badges
 */
function hpg_revoke_manual_badge( $user_id, $badge_key ) {
    $manual_badges = get_user_meta( $user_id, 'hpg_manual_badges', true );
    
    if ( is_array( $manual_badges ) ) {
        $key_index = array_search( $badge_key, $manual_badges );
        if ( $key_index !== false ) {
            unset( $manual_badges[$key_index] );
            update_user_meta( $user_id, 'hpg_manual_badges', array_values( $manual_badges ) );
            return true;
        }
    }
    
    return false;
}
