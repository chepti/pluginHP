<?php
/**
 * Custom Post Types for OpenStuff Timeline
 *
 * @package OpenStuff_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OST_CPT {

	public function register() {
		add_action( 'init', array( $this, 'register_timeline_cpt' ) );
		add_action( 'init', array( $this, 'register_topic_cpt' ) );
		add_action( 'init', array( $this, 'register_pin_cpt' ) );
		add_action( 'init', array( $this, 'maybe_auto_create_timelines' ), 20 );
		add_action( 'init', array( $this, 'maybe_move_timelines_to_draft' ), 25 );
	}

	/**
	 * מיגרציה חד-פעמית: צירים בלי תמונה ראשית → טיוטה, עם תמונה ראשית → פורסם.
	 * לא תרוץ שוב אחרי הביצוע.
	 */
	public function maybe_move_timelines_to_draft() {
		if ( get_option( 'ost_timeline_status_migration_done', false ) ) {
			return;
		}
		$posts = get_posts( array(
			'post_type'      => 'os_timeline',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		) );
		foreach ( $posts as $post ) {
			$thumb_id = (int) get_post_thumbnail_id( $post->ID );
			$status   = $thumb_id > 0 ? 'publish' : 'draft';
			if ( $post->post_status !== $status ) {
				wp_update_post( array( 'ID' => $post->ID, 'post_status' => $status ) );
			}
		}
		update_option( 'ost_timeline_status_migration_done', true );
	}

	public function register_timeline_cpt() {
		$labels = array(
			'name'               => __( 'צירי זמן', 'openstuff-timeline' ),
			'singular_name'      => __( 'ציר זמן', 'openstuff-timeline' ),
			'menu_name'          => __( 'צירי זמן', 'openstuff-timeline' ),
			'add_new'            => __( 'הוסף ציר', 'openstuff-timeline' ),
			'add_new_item'       => __( 'הוסף ציר חדש', 'openstuff-timeline' ),
			'edit_item'          => __( 'ערוך ציר', 'openstuff-timeline' ),
			'new_item'           => __( 'ציר חדש', 'openstuff-timeline' ),
			'view_item'          => __( 'צפה בציר', 'openstuff-timeline' ),
			'search_items'       => __( 'חפש צירים', 'openstuff-timeline' ),
			'not_found'          => __( 'לא נמצאו צירים', 'openstuff-timeline' ),
			'not_found_in_trash' => __( 'לא נמצאו צירים בפח', 'openstuff-timeline' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'rest_base'            => 'os_timelines',
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'menu_icon'           => 'dashicons-calendar-alt',
			'supports'            => array( 'title', 'thumbnail', 'editor' ),
			'has_archive'         => true,
			'rewrite'             => array( 'slug' => 'timelines' ),
		);

		register_post_type( 'os_timeline', $args );

		register_post_meta( 'os_timeline', 'ost_subject_id', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'integer',
			'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
		) );
		register_post_meta( 'os_timeline', 'ost_grade_level_id', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'integer',
			'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
		) );
		register_post_meta( 'os_timeline', 'ost_academic_year', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
		) );
	}

	public function register_topic_cpt() {
		$labels = array(
			'name'               => __( 'נושאי ציר', 'openstuff-timeline' ),
			'singular_name'      => __( 'נושא ציר', 'openstuff-timeline' ),
			'menu_name'          => __( 'נושאי ציר', 'openstuff-timeline' ),
			'add_new'            => __( 'הוסף נושא', 'openstuff-timeline' ),
			'add_new_item'       => __( 'הוסף נושא חדש', 'openstuff-timeline' ),
			'edit_item'          => __( 'ערוך נושא', 'openstuff-timeline' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'              => false,
			'show_in_rest'         => true,
			'rest_base'            => 'os_timeline_topics',
			'capability_type'      => 'post',
			'hierarchical'         => false,
			'supports'             => array( 'title' ),
		);

		register_post_type( 'os_timeline_topic', $args );

		register_post_meta( 'os_timeline_topic', 'ost_color', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'default'       => '#E8F4F8',
			'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
		) );
		register_post_meta( 'os_timeline_topic', 'ost_order', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'integer',
			'default'       => 0,
			'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
		) );
		register_post_meta( 'os_timeline_topic', 'ost_parent_timeline_id', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'integer',
			'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
		) );
	}

	public function register_pin_cpt() {
		$labels = array(
			'name'               => __( 'נעיצות ציר', 'openstuff-timeline' ),
			'singular_name'      => __( 'נעיצה', 'openstuff-timeline' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'              => false,
			'show_in_rest'         => true,
			'rest_base'            => 'os_timeline_pins',
			'capability_type'      => 'post',
			'hierarchical'         => false,
			'supports'             => array(),
		);

		register_post_type( 'os_timeline_pin', $args );

		register_post_meta( 'os_timeline_pin', 'ost_post_id', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'integer',
			'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
		) );
		register_post_meta( 'os_timeline_pin', 'ost_topic_id', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'integer',
			'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
		) );
		register_post_meta( 'os_timeline_pin', 'ost_position_order', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'integer',
			'default'       => 0,
			'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
		) );
		register_post_meta( 'os_timeline_pin', 'ost_lesson_label', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
		) );
		register_post_meta( 'os_timeline_pin', 'ost_status', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'default'       => 'pending',
			'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
		) );
	}

	/**
	 * Auto-create one os_timeline per unique subject+grade from existing posts.
	 */
	public function maybe_auto_create_timelines() {
		if ( get_option( 'ost_timelines_auto_created', false ) ) {
			return;
		}

		$combos = $this->get_unique_subject_grade_combos();
		foreach ( $combos as $combo ) {
			$this->ensure_timeline_exists( $combo['subject_id'], $combo['grade_id'] );
		}
		update_option( 'ost_timelines_auto_created', true );
	}

	private function get_unique_subject_grade_combos() {
		global $wpdb;
		$subject_tax = 'subject';
		$class_tax   = 'class';
		$tt_subject  = $wpdb->get_var( $wpdb->prepare(
			"SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s LIMIT 1",
			$subject_tax
		) );
		$tt_class = $wpdb->get_var( $wpdb->prepare(
			"SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s LIMIT 1",
			$class_tax
		) );
		if ( ! $tt_subject || ! $tt_class ) {
			return array();
		}

		$combos = array();
		$posts  = get_posts( array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		foreach ( $posts as $post_id ) {
			$subjects = wp_get_object_terms( $post_id, 'subject' );
			$grades   = wp_get_object_terms( $post_id, 'class' );
			if ( empty( $subjects ) || is_wp_error( $subjects ) || empty( $grades ) || is_wp_error( $grades ) ) {
				continue;
			}
			foreach ( $subjects as $s ) {
				foreach ( $grades as $g ) {
					$key = $s->term_id . '_' . $g->term_id;
					if ( ! isset( $combos[ $key ] ) ) {
						$combos[ $key ] = array( 'subject_id' => $s->term_id, 'grade_id' => $g->term_id );
					}
				}
			}
		}
		return array_values( $combos );
	}

	private function ensure_timeline_exists( $subject_id, $grade_id ) {
		$existing = get_posts( array(
			'post_type'      => 'os_timeline',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array( 'key' => 'ost_subject_id', 'value' => $subject_id ),
				array( 'key' => 'ost_grade_level_id', 'value' => $grade_id ),
			),
		) );
		if ( ! empty( $existing ) ) {
			return $existing[0]->ID;
		}

		$subject = get_term( $subject_id, 'subject' );
		$grade   = get_term( $grade_id, 'class' );
		$title   = sprintf(
			/* translators: 1: subject name, 2: grade name */
			__( '%1$s - %2$s', 'openstuff-timeline' ),
			$subject && ! is_wp_error( $subject ) ? $subject->name : $subject_id,
			$grade && ! is_wp_error( $grade ) ? $grade->name : $grade_id
		);

		$id = wp_insert_post( array(
			'post_type'   => 'os_timeline',
			'post_title'  => $title,
			'post_status' => 'draft',
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, 'ost_subject_id', $subject_id );
			update_post_meta( $id, 'ost_grade_level_id', $grade_id );
			update_post_meta( $id, 'ost_academic_year', date( 'Y' ) );
		}
		return $id;
	}
}
