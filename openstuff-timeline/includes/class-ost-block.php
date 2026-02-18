<?php
/**
 * Gutenberg Block registration for OpenStuff Timeline
 *
 * @package OpenStuff_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OST_Block {

	public function register() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	public function register_block() {
		$block_json = OST_PLUGIN_DIR . 'build/block/block.json';
		if ( ! file_exists( $block_json ) ) {
			return;
		}
		register_block_type( OST_PLUGIN_DIR . 'build/block', array(
			'render_callback' => array( $this, 'render_editor_block' ),
		) );

		$viewer_json = OST_PLUGIN_DIR . 'build/frontend/block.json';
		if ( file_exists( $viewer_json ) ) {
			register_block_type( OST_PLUGIN_DIR . 'build/frontend', array(
				'render_callback' => array( $this, 'render_viewer_block' ),
			) );
		}
	}

	public function enqueue_editor_assets() {
		$asset = OST_PLUGIN_DIR . 'build/block/index.asset.php';
		if ( ! file_exists( $asset ) ) {
			return;
		}
		$data = include $asset;
		wp_enqueue_script(
			'ost-block-editor',
			OST_PLUGIN_URL . 'build/block/index.js',
			$data['dependencies'],
			$data['version'] ?? OST_VERSION
		);
		wp_enqueue_style(
			'ost-block-editor',
			OST_PLUGIN_URL . 'build/block/index.css',
			array(),
			$data['version'] ?? OST_VERSION
		);
		wp_localize_script( 'ost-block-editor', 'ostData', array(
			'restUrl'   => rest_url( OST_REST_NAMESPACE ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'isAdmin'   => current_user_can( 'manage_options' ),
		) );
	}

	public function enqueue_frontend_assets() {
		global $post;
		$has_timeline = $post && ( has_block( 'ost/timeline-viewer', $post ) || has_block( 'ost/timeline-editor', $post ) );
		$is_single_timeline = $post && is_singular( 'os_timeline' );
		if ( ! $has_timeline && ! $is_single_timeline ) {
			return;
		}
		$asset = OST_PLUGIN_DIR . 'build/frontend/viewer.asset.php';
		if ( ! file_exists( $asset ) ) {
			return;
		}
		$data = include $asset;
		wp_enqueue_script(
			'ost-timeline-viewer',
			OST_PLUGIN_URL . 'build/frontend/viewer.js',
			$data['dependencies'] ?? array( 'wp-element' ),
			$data['version'] ?? OST_VERSION
		);
		$viewer_css = OST_PLUGIN_DIR . 'build/frontend/viewer.css';
		if ( file_exists( $viewer_css ) ) {
			wp_enqueue_style(
				'ost-timeline-viewer-style',
				OST_PLUGIN_URL . 'build/frontend/viewer.css',
				array(),
				$data['version'] ?? OST_VERSION
			);
		}
		wp_localize_script( 'ost-timeline-viewer', 'ostData', array(
			'restUrl' => rest_url( OST_REST_NAMESPACE ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'isAdmin' => current_user_can( 'manage_options' ),
		) );
	}

	public function render_editor_block( $attrs, $content, $block ) {
		$id = isset( $attrs['timelineId'] ) ? (int) $attrs['timelineId'] : 0;
		if ( ! $id ) {
			return '<p>' . esc_html__( 'בחר ציר זמן להצגה', 'openstuff-timeline' ) . '</p>';
		}
		return '<div class="ost-timeline-viewer-root" data-timeline-id="' . esc_attr( $id ) . '" dir="rtl"></div>';
	}

	public function render_viewer_block( $attrs, $content, $block ) {
		$id = isset( $attrs['timelineId'] ) ? (int) $attrs['timelineId'] : 0;
		if ( ! $id ) {
			return '<p>' . esc_html__( 'בחר ציר זמן להצגה', 'openstuff-timeline' ) . '</p>';
		}
		return '<div class="ost-timeline-viewer-root" data-timeline-id="' . esc_attr( $id ) . '" dir="rtl"></div>';
	}
}
