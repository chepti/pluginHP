<?php
/**
 * עריכת צירים על ידי כל משתמש מחובר – שמירה כממתין לאישור
 *
 * @package OpenStuff_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OST_Contributor_Editing {

	public function register() {
		add_filter( 'map_meta_cap', array( $this, 'map_timeline_edit_cap' ), 10, 4 );
		add_filter( 'rest_pre_insert_os_timeline', array( $this, 'store_pending_instead_of_publish' ), 10, 2 );
		add_action( 'rest_after_insert_os_timeline', array( $this, 'after_timeline_save_store_pending' ), 10, 3 );
		add_filter( 'rest_pre_insert_os_timeline_topic', array( $this, 'allow_topic_for_contributors' ), 10, 2 );
		add_filter( 'rest_pre_insert_os_timeline_pin', array( $this, 'allow_pin_for_contributors' ), 10, 2 );
	}

	/**
	 * מאפשר לכל משתמש מחובר לערוך ציר זמן – edit_post יוחזר read אם אין publish
	 */
	public function map_timeline_edit_cap( $caps, $cap, $user_id, $args ) {
		if ( ! in_array( $cap, array( 'edit_post', 'read_post', 'delete_post' ), true ) ) {
			return $caps;
		}
		if ( empty( $args[0] ) ) {
			return $caps;
		}
		$post = get_post( $args[0] );
		if ( ! $post || $post->post_type !== 'os_timeline' ) {
			return $caps;
		}
		if ( ! $user_id ) {
			return $caps;
		}
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return $caps;
		}
		/* עורכים ומנהלים – ללא שינוי */
		if ( user_can( $user_id, 'edit_others_posts' ) ) {
			return $caps;
		}
		/* משתמש מחובר – יכול לערוך (יקבל pending בשמירה) */
		if ( $cap === 'edit_post' || $cap === 'read_post' ) {
			return array( 'exist' );
		}
		if ( $cap === 'delete_post' ) {
			return array( 'do_not_allow' );
		}
		return $caps;
	}

	/**
	 * שמירת ציר – משתמש ללא publish_posts: לא משנה סטטוס, שומר שינויים כממתינים.
	 * הציר נשאר מפורסם בגרסה הקודמת; השינויים נשמרים ב-meta.
	 */
	public function store_pending_instead_of_publish( $prepared_post, $request ) {
		if ( current_user_can( 'publish_posts' ) || current_user_can( 'manage_options' ) ) {
			return $prepared_post;
		}
		$id = (int) $request->get_param( 'id' );
		if ( $id ) {
			$post = get_post( $id );
			if ( $post && $post->post_type === 'os_timeline' ) {
				$prepared_post->post_status = 'publish';
				$prepared_post->post_content = $post->post_content;
			}
		} else {
			$prepared_post->post_status = 'pending';
		}
		return $prepared_post;
	}

	/**
	 * אחרי שמירה – אם משתמש תורם, שמור את התוכן החדש ב-meta כממתין.
	 */
	public function after_timeline_save_store_pending( $post, $request, $creating ) {
		if ( $creating || current_user_can( 'publish_posts' ) || current_user_can( 'manage_options' ) ) {
			return;
		}
		$content = $request->get_param( 'content' );
		if ( is_array( $content ) && isset( $content['raw'] ) ) {
			$content = $content['raw'];
		}
		if ( is_string( $content ) ) {
			update_post_meta( $post->ID, 'ost_pending_content', $content );
			update_post_meta( $post->ID, 'ost_has_pending_changes', 1 );
			update_post_meta( $post->ID, 'ost_pending_author_id', get_current_user_id() );
		}
	}

	/**
	 * נושאי ציר – מאפשר יצירה/עדכון למשתמשים מחוברים (REST בודק edit_post על הציר)
	 */
	public function allow_topic_for_contributors( $prepared_post, $request ) {
		return $prepared_post;
	}

	public function allow_pin_for_contributors( $prepared_post, $request ) {
		return $prepared_post;
	}

	/**
	 * האם משתמש יכול לערוך ציר (גם כממתין לאישור)
	 */
	public static function user_can_edit_timeline( $user_id, $post_id ) {
		if ( ! $user_id ) {
			return false;
		}
		return user_can( $user_id, 'edit_post', $post_id );
	}

	/**
	 * permission_callback ל-REST – משתמש מחובר שיכול לערוך את הציר
	 *
	 * @param WP_REST_Request $request
	 * @param int|null        $timeline_id ציר – אם null, מפיק מהבקשה
	 * @return bool
	 */
	public static function rest_can_edit_timeline( $request, $timeline_id = null ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( current_user_can( 'edit_others_posts' ) ) {
			return true;
		}
		$tid = $timeline_id;
		if ( $tid === null ) {
			$tid = (int) $request->get_param( 'timeline_id' );
			if ( ! $tid ) {
				$tid = (int) $request->get_param( 'timeline' );
			}
			if ( ! $tid ) {
				$pid = (int) $request->get_param( 'id' );
				if ( $pid ) {
					$post = get_post( $pid );
					if ( $post ) {
						if ( $post->post_type === 'os_timeline' ) {
							$tid = $pid;
						} elseif ( $post->post_type === 'os_timeline_topic' ) {
							$tid = (int) get_post_meta( $pid, 'ost_parent_timeline_id', true );
						} elseif ( $post->post_type === 'os_timeline_pin' ) {
							$topic_id = (int) get_post_meta( $pid, 'ost_topic_id', true );
							$topic = get_post( $topic_id );
							if ( $topic && $topic->post_type === 'os_timeline_topic' ) {
								$tid = (int) get_post_meta( $topic_id, 'ost_parent_timeline_id', true );
							}
						}
					}
				}
			}
			if ( ! $tid ) {
				$topic_id = (int) $request->get_param( 'topic_id' );
				if ( $topic_id ) {
					$topic = get_post( $topic_id );
					if ( $topic && $topic->post_type === 'os_timeline_topic' ) {
						$tid = (int) get_post_meta( $topic_id, 'ost_parent_timeline_id', true );
					}
				}
			}
		}
		return $tid > 0 && current_user_can( 'edit_post', $tid );
	}
}
