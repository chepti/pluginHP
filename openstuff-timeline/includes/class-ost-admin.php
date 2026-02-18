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
		add_filter( 'post_row_actions', array( $this, 'add_content_edit_row_action' ), 10, 2 );
		add_filter( 'register_post_type_args', array( $this, 'timeline_default_template' ), 10, 2 );
	}

	/**
	 * Add "עריכת תוכן" row action in timeline list.
	 */
	public function add_content_edit_row_action( $actions, $post ) {
		if ( 'os_timeline' !== $post->post_type ) {
			return $actions;
		}
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}
		$edit_url = get_edit_post_link( $post->ID, 'raw' );
		if ( $edit_url ) {
			$actions['edit_content'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $edit_url ),
				esc_html__( 'עריכת תוכן', 'openstuff-timeline' )
			);
		}
		return $actions;
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
