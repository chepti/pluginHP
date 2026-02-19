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
		add_action( 'pre_get_posts', array( $this, 'filter_archive_by_subject_grade' ) );
	}

	/**
	 * Filter archive timelines by subject_id and grade_level_id from URL.
	 */
	public function filter_archive_by_subject_grade( $query ) {
		if ( ! $query->is_main_query() || ! $query->is_post_type_archive( 'os_timeline' ) ) {
			return;
		}
		$subject_id = isset( $_GET['subject_id'] ) ? absint( $_GET['subject_id'] ) : 0;
		$grade_id   = isset( $_GET['grade_id'] ) ? absint( $_GET['grade_id'] ) : 0;
		if ( ! $subject_id && ! $grade_id ) {
			return;
		}
		$meta_query = array( 'relation' => 'AND' );
		if ( $subject_id ) {
			$meta_query[] = array(
				'key'   => 'ost_subject_id',
				'value' => $subject_id,
			);
		}
		if ( $grade_id ) {
			$meta_query[] = array(
				'key'   => 'ost_grade_level_id',
				'value' => $grade_id,
			);
		}
		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Enqueue styles for single/archive timeline templates.
	 */
	public function enqueue_template_styles() {
		if ( ! is_singular( 'os_timeline' ) && ! is_post_type_archive( 'os_timeline' ) ) {
			return;
		}
		wp_enqueue_style(
			'ost-google-fonts',
			'https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600&display=swap',
			array(),
			null
		);
		$css = OST_PLUGIN_DIR . 'assets/css/templates.css';
		if ( file_exists( $css ) ) {
			wp_enqueue_style(
				'ost-templates',
				OST_PLUGIN_URL . 'assets/css/templates.css',
				array( 'ost-google-fonts' ),
				OST_VERSION
			);
		}
	}

	/**
	 * Get terms in hierarchical order for select options.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return array Flat array of terms with depth for indentation.
	 */
	public static function get_terms_hierarchical( $taxonomy ) {
		$all = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );
		if ( is_wp_error( $all ) || empty( $all ) ) {
			return array();
		}
		$by_parent = array();
		foreach ( $all as $t ) {
			$pid = (int) $t->parent;
			if ( ! isset( $by_parent[ $pid ] ) ) {
				$by_parent[ $pid ] = array();
			}
			$by_parent[ $pid ][] = $t;
		}
		$out = array();
		$walk = function( $parent_id, $depth ) use ( &$walk, $by_parent, &$out ) {
			if ( ! isset( $by_parent[ $parent_id ] ) ) {
				return;
			}
			foreach ( $by_parent[ $parent_id ] as $t ) {
				$out[] = array( 'term' => $t, 'depth' => $depth );
				$walk( $t->term_id, $depth + 1 );
			}
		};
		$walk( 0, 0 );
		return $out;
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
