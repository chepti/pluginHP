<?php
/**
 * REST API for Tips
 *
 * @package Homer_Patuach_Tips
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HPT_REST {

	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route( 'hpt/v1', '/tips', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_tips' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'random'     => array( 'type' => 'boolean', 'default' => false ),
					'subject_id' => array( 'type' => 'integer', 'default' => 0 ),
					'grade_id'   => array( 'type' => 'integer', 'default' => 0 ),
					'tag_ids'    => array(
						'type'    => 'array',
						'default' => array(),
						'items'   => array( 'type' => 'integer' ),
					),
					'offset'     => array( 'type' => 'integer', 'default' => 0 ),
					'per_page'   => array( 'type' => 'integer', 'default' => 10 ),
				),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_tip' ),
				'permission_callback' => function() { return is_user_logged_in(); },
				'args'                => array(
					'content'    => array( 'type' => 'string', 'required' => true ),
					'credit'     => array( 'type' => 'string' ),
					'media_type' => array( 'type' => 'string', 'default' => 'emoji' ),
					'emoji'      => array( 'type' => 'string' ),
					'image_id'   => array( 'type' => 'integer', 'default' => 0 ),
					'subject_id' => array( 'type' => 'integer', 'default' => 0 ),
					'grade_id'   => array( 'type' => 'integer', 'default' => 0 ),
					'tags'       => array( 'type' => 'string' ),
				),
			),
		) );

		register_rest_route( 'hpt/v1', '/filter-options', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_filter_options' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function create_tip( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_body_params();
		}
		$content    = isset( $params['content'] ) ? sanitize_textarea_field( $params['content'] ) : '';
		$credit     = isset( $params['credit'] ) ? sanitize_text_field( $params['credit'] ) : '';
		$media_type = isset( $params['media_type'] ) && in_array( $params['media_type'], array( 'image', 'emoji' ), true ) ? $params['media_type'] : 'emoji';
		$emoji      = isset( $params['emoji'] ) ? sanitize_text_field( $params['emoji'] ) : '';
		$image_id   = isset( $params['image_id'] ) ? (int) $params['image_id'] : 0;
		$subject_id = isset( $params['subject_id'] ) ? (int) $params['subject_id'] : 0;
		$grade_id   = isset( $params['grade_id'] ) ? (int) $params['grade_id'] : 0;
		$tags_str   = isset( $params['tags'] ) ? sanitize_text_field( $params['tags'] ) : '';

		if ( empty( $content ) ) {
			return new WP_Error( 'missing_content', __( 'תוכן הטיפ חובה', 'homer-patuach-tips' ), array( 'status' => 400 ) );
		}

		$user_id = get_current_user_id();
		$post_id = wp_insert_post( array(
			'post_type'   => 'os_tip',
			'post_title'  => wp_trim_words( $content, 5 ),
			'post_content' => $content,
			'post_status' => 'pending',
			'post_author' => $user_id,
		) );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, 'hpt_credit', $credit );
		update_post_meta( $post_id, 'hpt_credit_user_id', $credit ? 0 : $user_id );
		update_post_meta( $post_id, 'hpt_has_media_type', $media_type );
		update_post_meta( $post_id, 'hpt_emoji', $media_type === 'emoji' ? $emoji : '' );
		update_post_meta( $post_id, 'hpt_image_id', $media_type === 'image' ? $image_id : 0 );

		if ( $subject_id > 0 && taxonomy_exists( 'subject' ) ) {
			wp_set_object_terms( $post_id, array( $subject_id ), 'subject' );
		}
		if ( $grade_id > 0 && taxonomy_exists( 'class' ) ) {
			wp_set_object_terms( $post_id, array( $grade_id ), 'class' );
		}
		if ( ! empty( $tags_str ) && taxonomy_exists( 'tip_tag' ) ) {
			$tags = array_map( 'trim', explode( ',', $tags_str ) );
			$tags = array_filter( $tags );
			if ( ! empty( $tags ) ) {
				wp_set_object_terms( $post_id, $tags, 'tip_tag' );
			}
		}

		return rest_ensure_response( array( 'id' => $post_id, 'message' => __( 'הטיפ נשלח לאישור', 'homer-patuach-tips' ) ) );
	}

	public function get_tips( $request ) {
		$random   = $request->get_param( 'random' );
		$subject  = $request->get_param( 'subject_id' );
		$grade    = $request->get_param( 'grade_id' );
		$tag_ids  = $request->get_param( 'tag_ids' );
		if ( is_string( $tag_ids ) ) {
			$tag_ids = array_filter( array_map( 'intval', explode( ',', $tag_ids ) ) );
		}
		$tag_ids  = array_values( (array) $tag_ids );
		$offset   = $request->get_param( 'offset' );
		$per_page = min( 50, max( 1, $request->get_param( 'per_page' ) ) );

		$args = array(
			'post_type'      => 'os_tip',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'offset'         => $offset,
		);

		$tax_query = array();
		if ( $subject > 0 && taxonomy_exists( 'subject' ) ) {
			$tax_query[] = array(
				'taxonomy' => 'subject',
				'field'    => 'term_id',
				'terms'    => $subject,
			);
		}
		if ( $grade > 0 && taxonomy_exists( 'class' ) ) {
			$tax_query[] = array(
				'taxonomy' => 'class',
				'field'    => 'term_id',
				'terms'    => $grade,
			);
		}
		if ( ! empty( $tag_ids ) && taxonomy_exists( 'tip_tag' ) ) {
			$tax_query[] = array(
				'taxonomy' => 'tip_tag',
				'field'    => 'term_id',
				'terms'    => array_map( 'intval', $tag_ids ),
			);
		}
		if ( ! empty( $tax_query ) ) {
			$tax_query['relation'] = 'AND';
			$args['tax_query']     = $tax_query;
		}

		if ( $random ) {
			$args['orderby'] = 'rand';
			$args['offset']  = 0;
		}

		$query = new WP_Query( $args );
		$tips  = array();

		foreach ( $query->posts as $post ) {
			$tips[] = $this->format_tip( $post );
		}

		return rest_ensure_response( array(
			'tips'   => $tips,
			'total'  => $query->found_posts,
		) );
	}

	public function get_filter_options( $request ) {
		$subjects = array();
		$grades   = array();
		$tags     = array();

		if ( taxonomy_exists( 'subject' ) ) {
			$terms = get_terms( array( 'taxonomy' => 'subject', 'hide_empty' => true ) );
			foreach ( (array) $terms as $t ) {
				if ( ! is_wp_error( $t ) ) {
					$subjects[] = array( 'id' => $t->term_id, 'name' => $t->name );
				}
			}
		}
		if ( taxonomy_exists( 'class' ) ) {
			$terms = get_terms( array( 'taxonomy' => 'class', 'hide_empty' => true ) );
			foreach ( (array) $terms as $t ) {
				if ( ! is_wp_error( $t ) ) {
					$grades[] = array( 'id' => $t->term_id, 'name' => $t->name );
				}
			}
		}
		if ( taxonomy_exists( 'tip_tag' ) ) {
			$terms = get_terms( array( 'taxonomy' => 'tip_tag', 'hide_empty' => true, 'number' => 20 ) );
			foreach ( (array) $terms as $t ) {
				if ( ! is_wp_error( $t ) ) {
					$tags[] = array( 'id' => $t->term_id, 'name' => $t->name );
				}
			}
		}

		return rest_ensure_response( array(
			'subjects' => $subjects,
			'grades'   => $grades,
			'tags'     => $tags,
		) );
	}

	private function format_tip( $post ) {
		$media_type = get_post_meta( $post->ID, 'hpt_has_media_type', true ) ?: 'emoji';
		$credit     = get_post_meta( $post->ID, 'hpt_credit', true );
		$credit_uid = (int) get_post_meta( $post->ID, 'hpt_credit_user_id', true );
		$emoji      = get_post_meta( $post->ID, 'hpt_emoji', true );
		$image_id   = (int) get_post_meta( $post->ID, 'hpt_image_id', true );

		if ( ! $credit && $credit_uid ) {
			$user = get_userdata( $credit_uid );
			$credit = $user ? $user->display_name : '';
		}
		if ( ! $credit && $post->post_author ) {
			$credit = get_the_author_meta( 'display_name', $post->post_author );
		}

		$image_url = '';
		if ( $media_type === 'image' && $image_id ) {
			$img = wp_get_attachment_image_src( $image_id, 'medium' );
			$image_url = $img ? $img[0] : '';
		}

		$subject = '';
		$grade   = '';
		$subjects = wp_get_object_terms( $post->ID, 'subject' );
		$grades   = wp_get_object_terms( $post->ID, 'class' );
		if ( ! is_wp_error( $subjects ) && ! empty( $subjects ) ) {
			$subject = $subjects[0]->name;
		}
		if ( ! is_wp_error( $grades ) && ! empty( $grades ) ) {
			$grade = $grades[0]->name;
		}

		$tag_names = array();
		$tags = wp_get_object_terms( $post->ID, 'tip_tag' );
		if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
			$tag_names = wp_list_pluck( $tags, 'name' );
		}

		return array(
			'id'        => $post->ID,
			'content'   => apply_filters( 'the_content', $post->post_content ),
			'credit'    => $credit,
			'emoji'     => $media_type === 'emoji' ? $emoji : '',
			'image_url' => $image_url,
			'subject'   => $subject,
			'grade'     => $grade,
			'tags'      => $tag_names,
		);
	}
}
