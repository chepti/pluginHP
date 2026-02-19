<?php
/**
 * REST API for OpenStuff Timeline
 *
 * @package OpenStuff_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OST_REST {

	public function register_routes() {
		add_action( 'rest_api_init', array( $this, 'register' ) );
	}

	public function register() {
		register_rest_route( OST_REST_NAMESPACE, '/timelines', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_timelines' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( OST_REST_NAMESPACE, '/timeline/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_timeline' ),
			'permission_callback' => '__return_true',
			'args'                => array( 'id' => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ) ),
		) );

		register_rest_route( OST_REST_NAMESPACE, '/pin', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'create_pin' ),
			'permission_callback' => function() { return is_user_logged_in(); },
			'args'                => array(
				'post_id'       => array( 'required' => true, 'type' => 'integer' ),
				'topic_id'      => array( 'required' => true, 'type' => 'integer' ),
				'lesson_label'  => array( 'type' => 'string' ),
			),
		) );

		register_rest_route( OST_REST_NAMESPACE, '/pin/(?P<id>\d+)/approve', array(
			'methods'             => 'PUT',
			'callback'            => array( $this, 'approve_pin' ),
			'permission_callback' => function() { return current_user_can( 'manage_options' ); },
			'args'                => array( 'id' => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ) ),
		) );

		register_rest_route( OST_REST_NAMESPACE, '/pin/(?P<id>\d+)/move', array(
			'methods'             => 'PUT',
			'callback'            => array( $this, 'update_pin' ),
			'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
			'args'                => array(
				'id'       => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
				'topic_id' => array( 'required' => true, 'type' => 'integer' ),
			),
		) );

		register_rest_route( OST_REST_NAMESPACE, '/pin/(?P<id>\d+)/unpin', array(
			'methods'             => 'DELETE',
			'callback'            => array( $this, 'delete_pin' ),
			'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
			'args'                => array( 'id' => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ) ),
		) );

		register_rest_route( OST_REST_NAMESPACE, '/topic/(?P<id>\d+)/reorder-pins', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'reorder_pins' ),
			'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
			'args'                => array(
				'id'       => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
				'pin_ids'  => array( 'required' => true, 'type' => 'array' ),
			),
		) );

		register_rest_route( OST_REST_NAMESPACE, '/categories', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_categories' ),
			'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
		) );

		register_rest_route( OST_REST_NAMESPACE, '/topic', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'create_topic' ),
			'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
			'args'                => array(
				'timeline_id' => array( 'required' => true, 'type' => 'integer' ),
				'title'       => array( 'required' => true, 'type' => 'string' ),
				'color'       => array( 'type' => 'string', 'default' => '#E8F4F8' ),
				'order'       => array( 'type' => 'integer', 'default' => 0 ),
			),
		) );

		register_rest_route( OST_REST_NAMESPACE, '/timeline/(?P<id>\d+)/reorder-topics', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'reorder_topics' ),
			'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
			'args'                => array(
				'id'         => array( 'validate_callback' => function( $v ) { return is_numeric( $v ); } ),
				'topic_ids'  => array( 'required' => true, 'type' => 'array' ),
			),
		) );

		register_rest_route( OST_REST_NAMESPACE, '/post/(?P<id>\d+)/remove-subject', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'remove_post_subject' ),
			'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
			'args'                => array(
				'id'         => array( 'required' => true, 'type' => 'integer' ),
				'subject_id' => array( 'required' => true, 'type' => 'integer' ),
			),
		) );

		register_rest_route( OST_REST_NAMESPACE, '/posts', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_posts' ),
			'permission_callback' => function() { return current_user_can( 'edit_posts' ); },
			'args'                => array(
				'timeline' => array( 'required' => true, 'type' => 'integer' ),
				'search'   => array( 'type' => 'string' ),
				'content_type' => array( 'type' => 'string' ),
			),
		) );
	}

	public function get_timelines( $request ) {
		$statuses = array( 'publish' );
		if ( current_user_can( 'edit_posts' ) ) {
			$statuses[] = 'draft';
		}
		$posts = get_posts( array(
			'post_type'      => 'os_timeline',
			'post_status'    => $statuses,
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		$items = array();
		foreach ( $posts as $p ) {
			$items[] = $this->format_timeline_brief( $p );
		}
		return rest_ensure_response( $items );
	}

	public function get_timeline( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== 'os_timeline' ) {
			return new WP_Error( 'not_found', __( 'ציר לא נמצא', 'openstuff-timeline' ), array( 'status' => 404 ) );
		}

		$topics = get_posts( array(
			'post_type'      => 'os_timeline_topic',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array( 'key' => 'ost_parent_timeline_id', 'value' => $id ),
			),
			'orderby'        => 'meta_value_num',
			'meta_key'       => 'ost_order',
			'order'          => 'ASC',
		) );

		$topic_ids = wp_list_pluck( $topics, 'ID' );
		$pins      = array();
		if ( ! empty( $topic_ids ) ) {
			$pin_posts = get_posts( array(
				'post_type'      => 'os_timeline_pin',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array( 'key' => 'ost_topic_id', 'value' => $topic_ids, 'compare' => 'IN' ),
				),
			) );
			foreach ( $pin_posts as $pin ) {
				$status = get_post_meta( $pin->ID, 'ost_status', true ) ?: 'pending';
				if ( $status === 'pending' && ! current_user_can( 'manage_options' ) ) {
					continue;
				}
				$pins[] = $this->format_pin( $pin );
			}
		}

		$formatted_topics = array();
		foreach ( $topics as $t ) {
			$formatted_topics[] = array(
				'id'       => $t->ID,
				'title'    => $t->post_title,
				'color'    => get_post_meta( $t->ID, 'ost_color', true ) ?: '#E8F4F8',
				'order'    => (int) get_post_meta( $t->ID, 'ost_order', true ),
				'pins'     => array_values( array_filter( $pins, function( $p ) use ( $t ) { return (int) $p['topic_id'] === (int) $t->ID; } ) ),
			);
		}

		usort( $formatted_topics, function( $a, $b ) { return $a['order'] - $b['order']; } );

		return rest_ensure_response( array(
			'id'            => $post->ID,
			'title'         => $post->post_title,
			'subject_id'    => (int) get_post_meta( $post->ID, 'ost_subject_id', true ),
			'grade_level_id'=> (int) get_post_meta( $post->ID, 'ost_grade_level_id', true ),
			'academic_year' => get_post_meta( $post->ID, 'ost_academic_year', true ),
			'topics'        => $formatted_topics,
		) );
	}

	public function create_pin( $request ) {
		$post_id  = (int) $request['post_id'];
		$topic_id = (int) $request['topic_id'];
		$label    = sanitize_text_field( $request->get_param( 'lesson_label' ) ?: '' );

		$topic = get_post( $topic_id );
		if ( ! $topic || $topic->post_type !== 'os_timeline_topic' ) {
			return new WP_Error( 'invalid_topic', __( 'נושא ציר לא תקין', 'openstuff-timeline' ), array( 'status' => 400 ) );
		}

		$timeline_id = (int) get_post_meta( $topic_id, 'ost_parent_timeline_id', true );
		$max_order   = 0;
		$existing    = get_posts( array(
			'post_type'   => 'os_timeline_pin',
			'post_status' => 'any',
			'posts_per_page' => -1,
			'meta_query'  => array( array( 'key' => 'ost_topic_id', 'value' => $topic_id ) ),
		) );
		foreach ( $existing as $e ) {
			$o = (int) get_post_meta( $e->ID, 'ost_position_order', true );
			if ( $o > $max_order ) $max_order = $o;
		}

		$id = wp_insert_post( array(
			'post_type'   => 'os_timeline_pin',
			'post_title'  => '',
			'post_status' => 'publish',
		) );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		update_post_meta( $id, 'ost_post_id', $post_id );
		update_post_meta( $id, 'ost_topic_id', $topic_id );
		update_post_meta( $id, 'ost_position_order', $max_order + 1 );
		update_post_meta( $id, 'ost_lesson_label', $label );
		update_post_meta( $id, 'ost_status', 'pending' );

		$pin = get_post( $id );
		return rest_ensure_response( $this->format_pin( $pin ) );
	}

	public function approve_pin( $request ) {
		$id   = (int) $request['id'];
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== 'os_timeline_pin' ) {
			return new WP_Error( 'not_found', __( 'נעיצה לא נמצאה', 'openstuff-timeline' ), array( 'status' => 404 ) );
		}
		update_post_meta( $id, 'ost_status', 'approved' );
		return rest_ensure_response( $this->format_pin( get_post( $id ) ) );
	}

	public function update_pin( $request ) {
		$id       = (int) $request['id'];
		$topic_id = (int) $request['topic_id'];
		$pin      = get_post( $id );
		if ( ! $pin || $pin->post_type !== 'os_timeline_pin' ) {
			return new WP_Error( 'not_found', __( 'נעיצה לא נמצאה', 'openstuff-timeline' ), array( 'status' => 404 ) );
		}
		$topic = get_post( $topic_id );
		if ( ! $topic || $topic->post_type !== 'os_timeline_topic' ) {
			return new WP_Error( 'invalid_topic', __( 'נושא ציר לא תקין', 'openstuff-timeline' ), array( 'status' => 400 ) );
		}
		update_post_meta( $id, 'ost_topic_id', $topic_id );
		return rest_ensure_response( $this->format_pin( get_post( $id ) ) );
	}

	public function delete_pin( $request ) {
		$id   = (int) $request['id'];
		$pin  = get_post( $id );
		if ( ! $pin || $pin->post_type !== 'os_timeline_pin' ) {
			return new WP_Error( 'not_found', __( 'נעיצה לא נמצאה', 'openstuff-timeline' ), array( 'status' => 404 ) );
		}
		wp_delete_post( $id, true );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function reorder_pins( $request ) {
		$topic_id = (int) $request['id'];
		$body     = $request->get_json_params();
		$pin_ids  = isset( $body['pin_ids'] ) ? $body['pin_ids'] : $request->get_param( 'pin_ids' );
		if ( ! is_array( $pin_ids ) ) {
			return new WP_Error( 'invalid', __( 'pin_ids חייב להיות מערך', 'openstuff-timeline' ), array( 'status' => 400 ) );
		}
		$topic = get_post( $topic_id );
		if ( ! $topic || $topic->post_type !== 'os_timeline_topic' ) {
			return new WP_Error( 'invalid_topic', __( 'נושא ציר לא תקין', 'openstuff-timeline' ), array( 'status' => 400 ) );
		}
		foreach ( $pin_ids as $order => $pin_id ) {
			$pid = (int) $pin_id;
			if ( $pid > 0 ) {
				$pin = get_post( $pid );
				if ( $pin && $pin->post_type === 'os_timeline_pin' ) {
					$tid = (int) get_post_meta( $pid, 'ost_topic_id', true );
					if ( $tid === $topic_id ) {
						update_post_meta( $pid, 'ost_position_order', $order );
					}
				}
			}
		}
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function get_categories( $request ) {
		$terms = get_terms( array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );
		if ( is_wp_error( $terms ) ) {
			return rest_ensure_response( array() );
		}
		$items = array();
		foreach ( $terms as $t ) {
			$items[] = array( 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug );
		}
		return rest_ensure_response( $items );
	}

	public function remove_post_subject( $request ) {
		$post_id   = (int) $request['id'];
		$subject_id = (int) $request['subject_id'];

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'post' ) {
			return new WP_Error( 'invalid_post', __( 'פוסט לא תקין', 'openstuff-timeline' ), array( 'status' => 400 ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'forbidden', __( 'אין הרשאה', 'openstuff-timeline' ), array( 'status' => 403 ) );
		}

		wp_remove_object_terms( $post_id, $subject_id, 'subject' );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function create_topic( $request ) {
		$timeline_id = (int) $request['timeline_id'];
		$timeline    = get_post( $timeline_id );
		if ( ! $timeline || $timeline->post_type !== 'os_timeline' ) {
			return new WP_Error( 'invalid_timeline', __( 'ציר לא תקין', 'openstuff-timeline' ), array( 'status' => 400 ) );
		}

		$title = sanitize_text_field( $request['title'] );
		$color = sanitize_hex_color( $request['color'] ) ?: '#E8F4F8';
		$order = (int) $request['order'];

		$max_order = 0;
		$existing  = get_posts( array(
			'post_type'   => 'os_timeline_topic',
			'post_status' => 'any',
			'posts_per_page' => -1,
			'meta_query'  => array( array( 'key' => 'ost_parent_timeline_id', 'value' => $timeline_id ) ),
		) );
		foreach ( $existing as $e ) {
			$o = (int) get_post_meta( $e->ID, 'ost_order', true );
			if ( $o >= $max_order ) $max_order = $o + 1;
		}
		if ( $order <= 0 ) {
			$order = $max_order;
		}

		$id = wp_insert_post( array(
			'post_type'   => 'os_timeline_topic',
			'post_title'  => $title,
			'post_status' => 'publish',
		) );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		update_post_meta( $id, 'ost_color', $color );
		update_post_meta( $id, 'ost_order', $order );
		update_post_meta( $id, 'ost_parent_timeline_id', $timeline_id );

		return rest_ensure_response( array(
			'id'            => $id,
			'title'         => $title,
			'color'         => $color,
			'order'         => $order,
			'parent_timeline_id' => $timeline_id,
			'pins'          => array(),
		) );
	}

	public function reorder_topics( $request ) {
		$timeline_id = (int) $request['id'];
		$body        = $request->get_json_params();
		$topic_ids   = isset( $body['topic_ids'] ) ? $body['topic_ids'] : $request->get_param( 'topic_ids' );
		if ( ! is_array( $topic_ids ) ) {
			return new WP_Error( 'invalid', __( 'topic_ids חייב להיות מערך', 'openstuff-timeline' ), array( 'status' => 400 ) );
		}
		$timeline = get_post( $timeline_id );
		if ( ! $timeline || $timeline->post_type !== 'os_timeline' ) {
			return new WP_Error( 'invalid_timeline', __( 'ציר לא תקין', 'openstuff-timeline' ), array( 'status' => 400 ) );
		}
		foreach ( $topic_ids as $order => $topic_id ) {
			$tid = (int) $topic_id;
			if ( $tid > 0 ) {
				update_post_meta( $tid, 'ost_order', $order );
			}
		}
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function get_posts( $request ) {
		$timeline_id = (int) $request['timeline'];
		$timeline    = get_post( $timeline_id );
		if ( ! $timeline || $timeline->post_type !== 'os_timeline' ) {
			return new WP_Error( 'invalid_timeline', __( 'ציר לא תקין', 'openstuff-timeline' ), array( 'status' => 400 ) );
		}

		$subject_id = (int) get_post_meta( $timeline_id, 'ost_subject_id', true );
		$grade_id   = (int) get_post_meta( $timeline_id, 'ost_grade_level_id', true );

		$topic_ids = array();
		$topics    = get_posts( array(
			'post_type'   => 'os_timeline_topic',
			'post_status' => 'any',
			'posts_per_page' => -1,
			'meta_query'  => array( array( 'key' => 'ost_parent_timeline_id', 'value' => $timeline_id ) ),
		) );
		foreach ( $topics as $t ) {
			$topic_ids[] = $t->ID;
		}
		$exclude_post_ids = array();
		if ( ! empty( $topic_ids ) ) {
			$pins = get_posts( array(
				'post_type'   => 'os_timeline_pin',
				'post_status' => 'any',
				'posts_per_page' => -1,
				'meta_query'  => array( array( 'key' => 'ost_topic_id', 'value' => $topic_ids, 'compare' => 'IN' ) ),
			) );
			foreach ( $pins as $p ) {
				$exclude_post_ids[] = (int) get_post_meta( $p->ID, 'ost_post_id', true );
			}
		}

		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'tax_query'      => array(
				'relation' => 'AND',
				array( 'taxonomy' => 'subject', 'field' => 'term_id', 'terms' => $subject_id ),
				array( 'taxonomy' => 'class', 'field' => 'term_id', 'terms' => $grade_id ),
			),
		);
		if ( ! empty( $exclude_post_ids ) ) {
			$args['post__not_in'] = array_unique( $exclude_post_ids );
		}

		if ( $request->get_param( 'search' ) ) {
			$args['s'] = sanitize_text_field( $request->get_param( 'search' ) );
		}
		if ( $request->get_param( 'content_type' ) ) {
			$ct = sanitize_text_field( $request->get_param( 'content_type' ) );
			$term = get_term_by( 'name', $ct, 'category' );
			if ( ! $term ) {
				$term = get_term_by( 'slug', $ct, 'category' );
			}
			if ( $term && ! is_wp_error( $term ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => 'category',
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				);
			}
		}

		$posts = get_posts( $args );
		$items = array();
		foreach ( $posts as $p ) {
			$items[] = $this->format_post_card( $p );
		}
		return rest_ensure_response( $items );
	}

	private function format_timeline_brief( $post ) {
		return array(
			'id'             => $post->ID,
			'title'          => $post->post_title,
			'subject_id'     => (int) get_post_meta( $post->ID, 'ost_subject_id', true ),
			'grade_level_id' => (int) get_post_meta( $post->ID, 'ost_grade_level_id', true ),
			'academic_year'  => get_post_meta( $post->ID, 'ost_academic_year', true ),
		);
	}

	private function format_pin( $post ) {
		$post_id = (int) get_post_meta( $post->ID, 'ost_post_id', true );
		$orig    = get_post( $post_id );
		$author  = '';
		$tags    = array();
		$credit  = '';
		if ( $orig ) {
			$author = get_the_author_meta( 'display_name', $orig->post_author );
			$terms  = wp_get_post_tags( $post_id );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$tags = wp_list_pluck( $terms, 'name' );
			}
			$credit = get_post_meta( $post_id, 'credit', true ) ?: get_post_meta( $post_id, '_credit', true ) ?: '';
		}
		return array(
			'id'             => $post->ID,
			'post_id'        => $post_id,
			'topic_id'       => (int) get_post_meta( $post->ID, 'ost_topic_id', true ),
			'position_order' => (int) get_post_meta( $post->ID, 'ost_position_order', true ),
			'lesson_label'   => get_post_meta( $post->ID, 'ost_lesson_label', true ),
			'status'         => get_post_meta( $post->ID, 'ost_status', true ) ?: 'pending',
			'title'          => $orig ? $orig->post_title : '',
			'thumbnail_url'  => $orig ? get_the_post_thumbnail_url( $post_id, 'thumbnail' ) : '',
			'content_type'   => $this->get_content_type_for_post( $post_id ),
			'url'            => $post_id ? get_permalink( $post_id ) : '',
			'author_name'    => $author,
			'tags'           => $tags,
			'credit'         => $credit,
		);
	}

	private function format_post_card( $post ) {
		return array(
			'id'             => $post->ID,
			'title'          => $post->post_title,
			'thumbnail_url'  => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ),
			'content_type'   => $this->get_content_type_for_post( $post->ID ),
			'url'            => get_permalink( $post->ID ),
		);
	}

	private function get_content_type_for_post( $post_id ) {
		$cats = get_the_category( $post_id );
		$map  = array(
			'פעילות'       => 'game',
			'מערך שיעור'   => 'worksheet',
			'כלי דיגיטלי'  => 'presentation',
			'תבנית'        => 'template',
			'סרטון'        => 'video',
			'מצגת'         => 'presentation',
			'דף עבודה'     => 'worksheet',
		);
		foreach ( $cats as $c ) {
			if ( isset( $map[ $c->name ] ) ) return $map[ $c->name ];
		}
		return 'default';
	}
}
