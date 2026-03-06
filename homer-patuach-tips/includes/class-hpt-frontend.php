<?php
/**
 * Frontend: floating bubble, tips display
 *
 * @package Homer_Patuach_Tips
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HPT_Frontend {

	public function register() {
		add_action( 'wp_footer', array( $this, 'output_bubble' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function should_show_bubble() {
		$pages = get_option( 'hpt_bubble_pages', 'all' );
		if ( $pages === 'all' ) {
			return true;
		}
		if ( $pages === 'single' ) {
			return is_singular( 'post' );
		}
		if ( $pages === 'single_archive' ) {
			return is_singular( 'post' ) || is_home() || is_archive();
		}
		return true;
	}

	public function enqueue_assets() {
		if ( ! $this->should_show_bubble() ) {
			return;
		}

		wp_enqueue_style( 'hpt-bubble', HPT_PLUGIN_URL . 'assets/css/bubble.css', array(), HPT_VERSION );
		wp_enqueue_script( 'hpt-bubble', HPT_PLUGIN_URL . 'assets/js/bubble.js', array( 'jquery' ), HPT_VERSION, true );
		wp_localize_script( 'hpt-bubble', 'hptBubble', array(
			'restUrl' => rest_url( 'hpt/v1/' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		) );
	}

	public function output_bubble() {
		if ( ! $this->should_show_bubble() ) {
			return;
		}

		$position = get_option( 'hpt_bubble_position', 'bottom-left' );
		$position_class = 'hpt-bubble--' . sanitize_html_class( $position );
		?>
		<div id="hpt-tips-bubble" class="hpt-bubble <?php echo esc_attr( $position_class ); ?>" dir="rtl" aria-label="<?php esc_attr_e( 'טיפים', 'homer-patuach-tips' ); ?>">
			<button type="button" class="hpt-bubble-trigger" aria-expanded="false" aria-controls="hpt-bubble-panel">
				<span class="hpt-bubble-icon" aria-hidden="true">💡</span>
			</button>
			<div id="hpt-bubble-panel" class="hpt-bubble-panel" hidden>
				<div class="hpt-bubble-panel-inner">
					<button type="button" class="hpt-bubble-close" aria-label="<?php esc_attr_e( 'סגור', 'homer-patuach-tips' ); ?>">&times;</button>
					<div class="hpt-tip-content-area">
						<div class="hpt-tip-loading"><?php esc_html_e( 'טוען טיפ...', 'homer-patuach-tips' ); ?></div>
						<div class="hpt-tip-display" style="display:none;">
							<div class="hpt-tip-media"></div>
							<div class="hpt-tip-body"></div>
							<div class="hpt-tip-credit"></div>
						</div>
						<div class="hpt-tip-empty" style="display:none;"><?php esc_html_e( 'לא נמצאו טיפים.', 'homer-patuach-tips' ); ?></div>
					</div>
					<div class="hpt-bubble-nav">
						<button type="button" class="hpt-nav-prev" aria-label="<?php esc_attr_e( 'טיפ קודם', 'homer-patuach-tips' ); ?>">‹</button>
						<button type="button" class="hpt-nav-next" aria-label="<?php esc_attr_e( 'טיפ הבא', 'homer-patuach-tips' ); ?>">›</button>
					</div>
					<button type="button" class="hpt-filter-toggle"><?php esc_html_e( 'סינון', 'homer-patuach-tips' ); ?></button>
					<div class="hpt-filter-chips" style="display:none;"></div>
				</div>
			</div>
		</div>
		<?php
	}
}
