<?php
/**
 * Shortcodes for Homer Patuach Tips
 *
 * @package Homer_Patuach_Tips
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HPT_Shortcodes {

	public function register() {
		add_shortcode( 'hpt_approval_bell', array( $this, 'approval_bell' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_bell_styles' ) );
	}

	public function enqueue_bell_styles() {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		wp_enqueue_style( 'hpt-approval-bell', HPT_PLUGIN_URL . 'assets/css/approval-bell.css', array(), HPT_VERSION );
	}

	/**
	 * Shortcode [hpt_approval_bell] - פעמון אישור טיפים.
	 * מוצג רק לעורכים ואדמינים.
	 */
	public function approval_bell() {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_others_posts' ) ) {
			return '';
		}

		$counts   = wp_count_posts( 'os_tip' );
		$pending  = isset( $counts->pending ) ? (int) $counts->pending : 0;
		$url      = admin_url( 'edit.php?post_type=os_tip' . ( $pending > 0 ? '&post_status=pending' : '' ) );
		$has_cls  = $pending > 0 ? ' hpt-bell-has-pending' : '';
		$title    = $pending > 0
			? sprintf( /* translators: %d: pending count */ _n( '%d טיפ ממתין לאישור', '%d טיפים ממתינים לאישור', $pending, 'homer-patuach-tips' ), $pending )
			: __( 'ניהול טיפים', 'homer-patuach-tips' );

		ob_start();
		?>
		<a href="<?php echo esc_url( $url ); ?>" class="hpt-approval-bell<?php echo esc_attr( $has_cls ); ?>" title="<?php echo esc_attr( $title ); ?>">
			<svg class="hpt-bell-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
				<path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
			</svg>
			<?php if ( $pending > 0 ) : ?>
				<span class="hpt-pending-count"><?php echo esc_html( number_format_i18n( $pending ) ); ?></span>
			<?php endif; ?>
		</a>
		<?php
		return ob_get_clean();
	}
}
