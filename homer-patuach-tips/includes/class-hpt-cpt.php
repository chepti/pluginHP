<?php
/**
 * Custom Post Type for Tips
 *
 * @package Homer_Patuach_Tips
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HPT_CPT {

	public function register() {
		add_action( 'init', array( $this, 'register_tip_cpt' ) );
		add_action( 'init', array( $this, 'register_tip_tag_taxonomy' ), 11 );
		add_filter( 'map_meta_cap', array( $this, 'allow_logged_in_to_edit_tips' ), 10, 4 );
	}

	/**
	 * כל משתמש מחובר יכול ליצור ולערוך טיפים.
	 */
	public function allow_logged_in_to_edit_tips( $caps, $cap, $user_id, $args ) {
		$tip_caps = array( 'edit_tip', 'edit_tips', 'edit_others_tips', 'publish_tips', 'read_private_tips', 'delete_tip', 'delete_tips' );
		if ( ! in_array( $cap, $tip_caps, true ) ) {
			return $caps;
		}
		if ( in_array( $cap, array( 'edit_tip', 'delete_tip' ), true ) && ! empty( $args[0] ) ) {
			$post = get_post( $args[0] );
			if ( $post && $post->post_type === 'os_tip' ) {
				if ( $user_id && user_can( $user_id, 'read' ) ) {
					return array( 'read' );
				}
			}
		}
		if ( $user_id && user_can( $user_id, 'read' ) ) {
			return array( 'read' );
		}
		return $caps;
	}

	public function register_tip_cpt() {
		$labels = array(
			'name'               => __( 'טיפים', 'homer-patuach-tips' ),
			'singular_name'      => __( 'טיפ', 'homer-patuach-tips' ),
			'menu_name'          => __( 'טיפים', 'homer-patuach-tips' ),
			'add_new'            => __( 'הוסף טיפ', 'homer-patuach-tips' ),
			'add_new_item'       => __( 'הוסף טיפ חדש', 'homer-patuach-tips' ),
			'edit_item'          => __( 'ערוך טיפ', 'homer-patuach-tips' ),
			'new_item'           => __( 'טיפ חדש', 'homer-patuach-tips' ),
			'view_item'          => __( 'צפה בטיפ', 'homer-patuach-tips' ),
			'search_items'       => __( 'חפש טיפים', 'homer-patuach-tips' ),
			'not_found'          => __( 'לא נמצאו טיפים', 'homer-patuach-tips' ),
			'not_found_in_trash' => __( 'לא נמצאו טיפים בפח', 'homer-patuach-tips' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'rest_base'           => 'tips',
		'capability_type'     => array( 'tip', 'tips' ),
		'map_meta_cap'       => true,
			'hierarchical'        => false,
			'menu_icon'           => 'dashicons-lightbulb',
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
			'has_archive'         => false,
			'rewrite'             => array( 'slug' => 'tip', 'with_front' => false ),
		);

		register_post_type( 'os_tip', $args );

		$meta_auth = function( $allowed, $meta_key, $post_id ) {
			$post = get_post( $post_id );
			return $post && $post->post_type === 'os_tip' && current_user_can( 'edit_post', $post_id );
		};

		register_post_meta( 'os_tip', 'hpt_credit', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'auth_callback' => $meta_auth,
		) );
		register_post_meta( 'os_tip', 'hpt_credit_user_id', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'integer',
			'auth_callback' => $meta_auth,
		) );
		register_post_meta( 'os_tip', 'hpt_has_media_type', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'default'       => 'emoji',
			'auth_callback' => $meta_auth,
		) );
		register_post_meta( 'os_tip', 'hpt_image_id', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'integer',
			'auth_callback' => $meta_auth,
		) );
		register_post_meta( 'os_tip', 'hpt_emoji', array(
			'show_in_rest'  => true,
			'single'        => true,
			'type'          => 'string',
			'auth_callback' => $meta_auth,
		) );
	}

	public function register_tip_tag_taxonomy() {
		if ( ! taxonomy_exists( 'tip_tag' ) ) {
			$labels = array(
				'name'              => _x( 'תגיות טיפ', 'taxonomy general name', 'homer-patuach-tips' ),
				'singular_name'     => _x( 'תגית טיפ', 'taxonomy singular name', 'homer-patuach-tips' ),
				'search_items'      => __( 'חפש תגיות', 'homer-patuach-tips' ),
				'all_items'         => __( 'כל התגיות', 'homer-patuach-tips' ),
				'edit_item'         => __( 'ערוך תגית', 'homer-patuach-tips' ),
				'update_item'       => __( 'עדכן תגית', 'homer-patuach-tips' ),
				'add_new_item'      => __( 'הוסף תגית חדשה', 'homer-patuach-tips' ),
				'new_item_name'     => __( 'שם תגית חדשה', 'homer-patuach-tips' ),
				'menu_name'         => __( 'תגיות טיפ', 'homer-patuach-tips' ),
			);

			$args = array(
				'hierarchical'      => false,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'tip-tag', 'with_front' => false ),
				'show_in_rest'      => true,
			);

			register_taxonomy( 'tip_tag', array( 'os_tip' ), $args );
		}

		if ( taxonomy_exists( 'subject' ) ) {
			register_taxonomy_for_object_type( 'subject', 'os_tip' );
		}
		if ( taxonomy_exists( 'class' ) ) {
			register_taxonomy_for_object_type( 'class', 'os_tip' );
		}
	}
}
