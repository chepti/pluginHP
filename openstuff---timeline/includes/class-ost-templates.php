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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_header_badge_styles' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_archive_by_subject_grade' ) );
		add_action( 'pre_get_posts', array( $this, 'include_pending_timelines_for_editors' ), 15 );
		add_action( 'ost_editor_header_badges', array( $this, 'render_pending_timelines_badge' ) );
		// Astra theme – הוסף מדבקה ליד אייקוני העורך
		add_action( 'astra_header_right', array( $this, 'render_pending_timelines_badge' ), 5 );
		// Fallback – מדבקה בפינה (אם התבנית לא משתמשת ב-astra_header_right)
		add_action( 'wp_body_open', array( $this, 'render_pending_timelines_badge_fixed' ), 5 );
		add_action( 'wp_footer', array( $this, 'maybe_print_approve_pending_script' ) );
		add_action( 'wp_footer', array( $this, 'maybe_render_badge_fallback' ), 1 );
		add_shortcode( 'ost_pending_timelines_badge', array( $this, 'shortcode_pending_badge' ) );
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
	 * כולל צירים ממתינים לאישור בארכיון – עבור עורכים ומנהלים.
	 */
	public function include_pending_timelines_for_editors( $query ) {
		if ( ! $query->is_main_query() || ! $query->is_post_type_archive( 'os_timeline' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		$req_status = isset( $_GET['post_status'] ) ? sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) : '';
		if ( 'pending' === $req_status ) {
			$query->set( 'post_status', 'pending' );
			return;
		}
		$status = $query->get( 'post_status' );
		if ( empty( $status ) ) {
			$query->set( 'post_status', array( 'publish', 'pending' ) );
		} elseif ( is_array( $status ) && ! in_array( 'pending', $status, true ) ) {
			$status[] = 'pending';
			$query->set( 'post_status', $status );
		} elseif ( is_string( $status ) && 'publish' === $status ) {
			$query->set( 'post_status', array( 'publish', 'pending' ) );
		}
	}

	/** @var bool האם המדבקה כבר הוצגה (למניעת כפילות) */
	private static $badge_rendered = false;

	/**
	 * מדבקה – צירים ממתינים לאישור. מוצגת בתפריט עורכים (ליד עיגול הבדיקה).
	 */
	public function render_pending_timelines_badge() {
		echo $this->get_pending_badge_html( false );
	}

	/**
	 * מדבקה בפינה – fallback כשאין astra_header_right.
	 */
	public function render_pending_timelines_badge_fixed() {
		echo $this->get_pending_badge_html( true );
	}

	/**
	 * שורטקוד [ost_pending_timelines_badge] – הוספה ידנית בווידג'ט/עמוד.
	 * לא משתמש בדגל כפילות – המשתמש בוחר היכן להציג.
	 */
	public function shortcode_pending_badge() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return '';
		}
		$count = ost_get_pending_timelines_count();
		$archive_url = get_post_type_archive_link( 'os_timeline' );
		if ( ! $archive_url ) {
			return '';
		}
		$archive_url = add_query_arg( 'post_status', 'pending', $archive_url );
		$title = $count > 0
			? sprintf( __( '%s צירים ממתינים לאישור – לחץ לפתיחה', 'openstuff-timeline' ), number_format_i18n( $count ) )
			: __( 'צירים ממתינים – אין כרגע; לחץ לפתיחה', 'openstuff-timeline' );
		$has_pending = $count > 0 ? ' ost-has-pending' : '';
		ob_start();
		?>
		<span class="ost-pending-badge-wrapper">
			<a href="<?php echo esc_url( $archive_url ); ?>" class="ost-pending-timelines-badge<?php echo esc_attr( $has_pending ); ?>" title="<?php echo esc_attr( $title ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
				<svg class="ost-badge-svg" xmlns="http://www.w3.org/2000/svg" height="22" viewBox="0 0 24 24" width="22" fill="currentColor" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
				<?php if ( $count > 0 ) : ?>
					<span class="ost-pending-count"><?php echo esc_html( $count ); ?></span>
				<?php endif; ?>
			</a>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * HTML של מדבקת צירים ממתינים.
	 *
	 * @param bool $fixed האם להציג בפינה (position: fixed).
	 * @return string
	 */
	private function get_pending_badge_html( $fixed = false ) {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return '';
		}
		if ( self::$badge_rendered ) {
			return '';
		}
		$count = ost_get_pending_timelines_count();
		$archive_url = get_post_type_archive_link( 'os_timeline' );
		if ( ! $archive_url ) {
			return '';
		}
		$archive_url = add_query_arg( 'post_status', 'pending', $archive_url );
		$title = $count > 0
			? sprintf( __( '%s צירים ממתינים לאישור – לחץ לפתיחה', 'openstuff-timeline' ), number_format_i18n( $count ) )
			: __( 'צירים ממתינים – אין כרגע; לחץ לפתיחה', 'openstuff-timeline' );
		$has_pending = $count > 0 ? ' ost-has-pending' : '';
		$wrapper_class = $fixed ? ' ost-pending-badge-fixed' : '';
		self::$badge_rendered = true;
		ob_start();
		?>
		<span class="ost-pending-badge-wrapper<?php echo esc_attr( $wrapper_class ); ?>">
			<a href="<?php echo esc_url( $archive_url ); ?>" class="ost-pending-timelines-badge<?php echo esc_attr( $has_pending ); ?>" title="<?php echo esc_attr( $title ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
				<svg class="ost-badge-svg" xmlns="http://www.w3.org/2000/svg" height="22" viewBox="0 0 24 24" width="22" fill="currentColor" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
				<?php if ( $count > 0 ) : ?>
					<span class="ost-pending-count"><?php echo esc_html( $count ); ?></span>
				<?php endif; ?>
			</a>
		</span>
		<?php
		return ob_get_clean();
	}

	/**
	 * Fallback – אם המדבקה עדיין לא הוצגה (תבנית ללא wp_body_open/astra_header_right).
	 */
	public function maybe_render_badge_fallback() {
		if ( self::$badge_rendered ) {
			return;
		}
		echo $this->get_pending_badge_html( true );
	}

	/**
	 * סקריפט לאישור שינויים ממתינים – בעמוד ציר בודד.
	 */
	public function maybe_print_approve_pending_script() {
		if ( ! is_singular( 'os_timeline' ) ) {
			return;
		}
		$post = get_queried_object();
		if ( ! $post || ! isset( $post->ID ) ) {
			return;
		}
		$has_pending = (int) get_post_meta( $post->ID, 'ost_has_pending_changes', true ) > 0;
		if ( ! $has_pending || ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		?>
		<script>
		(function() {
			var btn = document.querySelector('.ost-approve-pending-btn');
			if (!btn) return;
			var bar = btn.closest('.ost-pending-changes-bar');
			if (!bar) return;
			var url = bar.getAttribute('data-rest-url');
			var nonce = bar.getAttribute('data-nonce');
			if (!url || !nonce) return;
			btn.addEventListener('click', function() {
				btn.disabled = true;
				btn.textContent = '<?php echo esc_js( __( 'מאשר...', 'openstuff-timeline' ) ); ?>';
				fetch(url, {
					method: 'POST',
					headers: { 'X-WP-Nonce': nonce, 'Content-Type': 'application/json' }
				}).then(function(r) {
					if (r.ok) {
						bar.style.display = 'none';
						location.reload();
					} else {
						btn.disabled = false;
						btn.textContent = '<?php echo esc_js( __( 'אשר שינויים', 'openstuff-timeline' ) ); ?>';
					}
				}).catch(function() {
					btn.disabled = false;
					btn.textContent = '<?php echo esc_js( __( 'אשר שינויים', 'openstuff-timeline' ) ); ?>';
				});
			});
		})();
		</script>
		<?php
	}

	/**
	 * עיצוב מדבקת צירים ממתינים – לעורכים בלבד.
	 */
	public function enqueue_header_badge_styles() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		$css = OST_PLUGIN_DIR . 'assets/css/header-badge.css';
		if ( file_exists( $css ) ) {
			wp_enqueue_style(
				'ost-header-badge',
				OST_PLUGIN_URL . 'assets/css/header-badge.css',
				array(),
				OST_VERSION
			);
		}
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
