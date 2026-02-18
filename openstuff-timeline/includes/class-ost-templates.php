<?php
/**
 * Templates for OpenStuff Timeline - Single & Archive
 *
 * @package OpenStuff_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OST_Templates {

	public function register() {
		add_filter( 'single_template', array( $this, 'single_timeline_template' ) );
		add_filter( 'archive_template', array( $this, 'archive_timeline_template' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_template_styles' ) );
	}

	/**
	 * Enqueue styles for single/archive timeline templates.
	 */
	public function enqueue_template_styles() {
		if ( ! is_singular( 'os_timeline' ) && ! is_post_type_archive( 'os_timeline' ) ) {
			return;
		}
		$css = OST_PLUGIN_DIR . 'assets/css/templates.css';
		if ( file_exists( $css ) ) {
			wp_enqueue_style(
				'ost-templates',
				OST_PLUGIN_URL . 'assets/css/templates.css',
				array(),
				OST_VERSION
			);
		}
	}

	/**
	 * Load single timeline template.
	 */
	public function single_timeline_template( $template ) {
		if ( is_singular( 'os_timeline' ) ) {
			$plugin_template = OST_PLUGIN_DIR . 'templates/single-os_timeline.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}
		return $template;
	}

	/**
	 * Load archive timeline template.
	 */
	public function archive_timeline_template( $template ) {
		if ( is_post_type_archive( 'os_timeline' ) ) {
			$plugin_template = OST_PLUGIN_DIR . 'templates/archive-os_timeline.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}
		return $template;
	}
}
