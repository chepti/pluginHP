<?php
/**
 * Admin enhancements for OpenStuff Timeline
 *
 * @package OpenStuff_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OST_Admin {

	public function register() {
		add_filter( 'post_row_actions', array( $this, 'simplify_timeline_row_actions' ), 20, 2 );
		add_filter( 'register_post_type_args', array( $this, 'timeline_default_template' ), 10, 2 );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'force_block_editor_for_timeline' ), 999, 2 );
		add_filter( 'use_block_editor_for_post', array( $this, 'force_block_editor_for_timeline_post' ), 999, 2 );
		add_filter( 'rest_prepare_os_timeline', array( $this, 'inject_timeline_block_if_empty' ), 10, 3 );
	}

	/**
	 * Force block editor for os_timeline (override Classic Editor plugin).
	 */
	public function force_block_editor_for_timeline( $use_block_editor, $post_type ) {
		if ( 'os_timeline' === $post_type ) {
			return true;
		}
		return $use_block_editor;
	}

	/**
	 * Force block editor per-post (override Classic Editor plugin).
	 */
	public function force_block_editor_for_timeline_post( $use_block_editor, $post ) {
		if ( $post && 'os_timeline' === $post->post_type ) {
			return true;
		}
		return $use_block_editor;
	}

	/**
	 * Simplify row actions: single "עריכה" link to block editor, remove classic/block choice.
	 */
	public function simplify_timeline_row_actions( $actions, $post ) {
		if ( 'os_timeline' !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}
		$edit_url = get_edit_post_link( $post->ID, 'raw' );
		if ( ! $edit_url ) {
			return $actions;
		}
		unset( $actions['edit'] );
		unset( $actions['classic'] );
		unset( $actions['edit_as_classic'] );
		unset( $actions['edit_as_block'] );
		unset( $actions['edit_classic'] );
		unset( $actions['edit_block'] );
		$actions['edit'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $edit_url ),
			esc_html__( 'עריכת תוכן', 'openstuff-timeline' )
		);
		return $actions;
	}

	/**
	 * When os_timeline post has empty content, inject timeline editor block for REST API.
	 */
	public function inject_timeline_block_if_empty( $response, $post, $request ) {
		if ( ! $response ) {
			return $response;
		}
		$data = $response->get_data();
		if ( ! isset( $data['content'] ) ) {
			return $response;
		}
		$content = $post->post_content;
		if ( ! empty( trim( $content ) ) ) {
			return $response;
		}
		$block = '<!-- wp:ost/timeline-editor {"timelineId":0} /-->';
		$data['content']['raw'] = $block;
		if ( isset( $data['content']['rendered'] ) ) {
			$data['content']['rendered'] = '';
		}
		$response->set_data( $data );
		return $response;
	}

	/**
	 * Default block template for os_timeline - timeline editor block.
	 */
	public function timeline_default_template( $args, $post_type ) {
		if ( 'os_timeline' !== $post_type ) {
			return $args;
		}
		$args['template'] = array(
			array( 'ost/timeline-editor', array( 'timelineId' => 0 ) ),
		);
		return $args;
	}
}
