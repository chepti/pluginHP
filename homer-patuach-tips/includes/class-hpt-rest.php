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
		) );

		register_rest_route( 'hpt/v1', '/filter-options', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_filter_options' ),
			'permission_callback' => '__return_true',
		) );
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
