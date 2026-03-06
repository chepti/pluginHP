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

		wp_enqueue_style( 'hpt-google-fonts', 'https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap', array(), null );
		wp_enqueue_style( 'hpt-bubble', HPT_PLUGIN_URL . 'assets/css/bubble.css', array( 'hpt-google-fonts' ), HPT_VERSION );
		wp_enqueue_script( 'hpt-bubble', HPT_PLUGIN_URL . 'assets/js/bubble.js', array( 'jquery' ), HPT_VERSION, true );
		if ( is_user_logged_in() ) {
			wp_enqueue_media();
		}
		wp_localize_script( 'hpt-bubble', 'hptBubble', array(
			'restUrl'   => rest_url( 'hpt/v1/' ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'loggedIn'  => is_user_logged_in(),
		) );
	}

	public function output_bubble() {
		if ( ! $this->should_show_bubble() ) {
			return;
		}

		$position = get_option( 'hpt_bubble_position', 'bottom-left' );
		$position_class = 'hpt-bubble--' . sanitize_html_class( $position );
		$is_logged_in = is_user_logged_in();
		?>
		<div id="hpt-tips-bubble" class="hpt-bubble <?php echo esc_attr( $position_class ); ?>" dir="rtl" aria-label="<?php esc_attr_e( 'טיפים', 'homer-patuach-tips' ); ?>">
			<button type="button" class="hpt-bubble-trigger" aria-expanded="false" aria-controls="hpt-bubble-panel">
				<span class="hpt-bubble-icon" aria-hidden="true">💡</span>
			</button>
		</div>

		<div id="hpt-bubble-overlay" class="hpt-bubble-overlay" hidden aria-hidden="true">
			<div id="hpt-bubble-panel" class="hpt-bubble-panel">
				<div class="hpt-bubble-panel-inner">
					<button type="button" class="hpt-bubble-close" aria-label="<?php esc_attr_e( 'סגור', 'homer-patuach-tips' ); ?>">×</button>
					<div class="hpt-tip-content-area">
						<div class="hpt-tip-content-wrapper">
							<button type="button" class="hpt-nav-prev" aria-label="<?php esc_attr_e( 'טיפ קודם', 'homer-patuach-tips' ); ?>">‹</button>
							<div class="hpt-tip-inner">
								<div class="hpt-tip-loading"><?php esc_html_e( 'טוען טיפ...', 'homer-patuach-tips' ); ?></div>
								<div class="hpt-tip-display" style="display:none;">
									<div class="hpt-tip-media"></div>
									<div class="hpt-tip-body"></div>
									<div class="hpt-tip-footer">
										<div class="hpt-tip-credit"></div>
										<button type="button" class="hpt-tip-like" aria-label="<?php esc_attr_e( 'לייק', 'homer-patuach-tips' ); ?>"><span class="hpt-like-icon">♡</span> <span class="hpt-like-count">0</span></button>
									</div>
								</div>
								<div class="hpt-tip-empty" style="display:none;"><?php esc_html_e( 'לא נמצאו טיפים.', 'homer-patuach-tips' ); ?></div>
							</div>
							<button type="button" class="hpt-nav-next" aria-label="<?php esc_attr_e( 'טיפ הבא', 'homer-patuach-tips' ); ?>">›</button>
						</div>
					</div>
					<div class="hpt-bubble-actions">
						<?php if ( $is_logged_in ) : ?>
						<button type="button" class="hpt-add-tip-btn"><?php esc_html_e( 'הוספת טיפ', 'homer-patuach-tips' ); ?></button>
						<?php endif; ?>
						<button type="button" class="hpt-filter-toggle"><?php esc_html_e( 'סינון', 'homer-patuach-tips' ); ?></button>
					</div>
					<div class="hpt-filter-chips" style="display:none;"></div>
					<button type="button" class="hpt-filter-clear" style="display:none;"><?php esc_html_e( 'ניקוי סינון', 'homer-patuach-tips' ); ?></button>
				</div>
			</div>
		</div>

		<?php if ( $is_logged_in ) : ?>
		<div id="hpt-add-tip-modal" class="hpt-modal-overlay" hidden aria-hidden="true">
			<div class="hpt-modal">
				<button type="button" class="hpt-modal-close" aria-label="<?php esc_attr_e( 'סגור', 'homer-patuach-tips' ); ?>">×</button>
				<h3 class="hpt-modal-title"><?php esc_html_e( 'הוספת טיפ חדש', 'homer-patuach-tips' ); ?></h3>
				<form id="hpt-add-tip-form" class="hpt-add-tip-form">
					<p>
						<label for="hpt-form-content"><?php esc_html_e( 'תוכן הטיפ', 'homer-patuach-tips' ); ?> *</label>
						<div class="hpt-form-format-bar">
							<button type="button" class="hpt-format-btn" data-cmd="bold" title="<?php esc_attr_e( 'מודגש', 'homer-patuach-tips' ); ?>"><b>B</b></button>
							<button type="button" class="hpt-format-btn" data-cmd="createLink" title="<?php esc_attr_e( 'קישור', 'homer-patuach-tips' ); ?>">🔗</button>
						</div>
						<div id="hpt-form-content" class="hpt-form-content-editable" contenteditable="true" role="textbox" aria-label="<?php esc_attr_e( 'תוכן הטיפ', 'homer-patuach-tips' ); ?>" data-placeholder="<?php esc_attr_e( 'כתוב את הטיפ כאן...', 'homer-patuach-tips' ); ?>"></div>
					</p>
					<p>
						<label for="hpt-form-credit"><?php esc_html_e( 'קרדיט', 'homer-patuach-tips' ); ?></label>
						<input type="text" id="hpt-form-credit" name="credit" placeholder="<?php esc_attr_e( 'ברירת מחדל: שם המשתמש', 'homer-patuach-tips' ); ?>">
					</p>
					<p class="hpt-form-symbol-row">
						<label><?php esc_html_e( 'סמל', 'homer-patuach-tips' ); ?></label>
						<span class="hpt-form-symbol-controls">
							<input type="hidden" id="hpt-form-emoji" name="emoji" value="">
							<input type="hidden" id="hpt-form-image-id" name="image_id" value="0">
							<button type="button" class="hpt-form-emoji-pick"><?php esc_html_e( 'אימוג\'י', 'homer-patuach-tips' ); ?></button>
							<button type="button" class="hpt-form-upload-image"><?php esc_html_e( 'תמונה מהמחשב', 'homer-patuach-tips' ); ?></button>
							<span class="hpt-form-symbol-preview"></span>
						</span>
					</p>
					<p class="hpt-form-row-inline">
						<span class="hpt-form-field">
							<select id="hpt-form-subject" name="subject_id"><option value=""><?php esc_html_e( 'תחום דעת', 'homer-patuach-tips' ); ?></option></select>
						</span>
						<span class="hpt-form-field">
							<select id="hpt-form-grade" name="grade_id"><option value=""><?php esc_html_e( 'שכבת גיל', 'homer-patuach-tips' ); ?></option></select>
						</span>
					</p>
					<p>
						<label for="hpt-form-tags"><?php esc_html_e( 'תגיות', 'homer-patuach-tips' ); ?></label>
						<input type="text" id="hpt-form-tags" name="tags" placeholder="<?php esc_attr_e( 'תגית1, תגית2...', 'homer-patuach-tips' ); ?>">
					</p>
					<p class="hpt-form-submit-wrap">
						<button type="submit" class="hpt-form-submit"><?php esc_html_e( 'שלח לאישור', 'homer-patuach-tips' ); ?></button>
					</p>
					<p class="hpt-form-message" style="display:none;"></p>
				</form>
				<input type="file" id="hpt-form-file-input" accept="image/*" style="display:none;">
			</div>
		</div>
		<?php endif; ?>
		<?php
	}
}
