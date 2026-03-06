<?php
/**
 * Admin: meta boxes, settings, emoji picker
 *
 * @package Homer_Patuach_Tips
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HPT_Admin {

	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_os_tip', array( $this, 'save_meta' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_menu', array( $this, 'add_pending_badge' ), 99 );
	}

	public function add_pending_badge() {
		global $menu;
		$pending = (int) wp_count_posts( 'os_tip' )->pending;
		if ( $pending < 1 || ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}
		foreach ( (array) $menu as $i => $item ) {
			if ( isset( $item[2] ) && $item[2] === 'edit.php?post_type=os_tip' ) {
				$menu[ $i ][0] .= ' <span class="awaiting-mod count-' . esc_attr( $pending ) . '"><span class="pending-count">' . number_format_i18n( $pending ) . '</span></span>';
				break;
			}
		}
	}

	public function add_meta_boxes() {
		add_meta_box(
			'hpt_tip_details',
			__( 'פרטי הטיפ', 'homer-patuach-tips' ),
			array( $this, 'render_tip_details_meta_box' ),
			'os_tip',
			'normal',
			'high'
		);
	}

	public function render_tip_details_meta_box( $post ) {
		wp_nonce_field( 'hpt_save_tip_details', 'hpt_tip_details_nonce' );

		$credit         = get_post_meta( $post->ID, 'hpt_credit', true );
		$credit_user_id = (int) get_post_meta( $post->ID, 'hpt_credit_user_id', true );
		$media_type     = get_post_meta( $post->ID, 'hpt_has_media_type', true ) ?: 'emoji';
		$image_id       = (int) get_post_meta( $post->ID, 'hpt_image_id', true );
		$emoji          = get_post_meta( $post->ID, 'hpt_emoji', true );

		$author_id = $credit_user_id ?: $post->post_author ?: get_current_user_id();
		$default_credit = $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';
		$credit_display = $credit ?: $default_credit;
		?>
		<div class="hpt-meta-box" dir="rtl" style="text-align: right;">
			<p>
				<label for="hpt_credit"><?php esc_html_e( 'קרדיט', 'homer-patuach-tips' ); ?></label><br>
				<input type="text" id="hpt_credit" name="hpt_credit" value="<?php echo esc_attr( $credit_display ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'ברירת מחדל: שם המשתמש', 'homer-patuach-tips' ); ?>">
				<input type="hidden" id="hpt_credit_user_id" name="hpt_credit_user_id" value="<?php echo esc_attr( $credit_user_id ?: $post->post_author ?: get_current_user_id() ); ?>">
			</p>

			<p>
				<label><?php esc_html_e( 'תמונה או אימוג\'י', 'homer-patuach-tips' ); ?></label><br>
				<label><input type="radio" name="hpt_has_media_type" value="emoji" <?php checked( $media_type, 'emoji' ); ?>><?php esc_html_e( 'אימוג\'י', 'homer-patuach-tips' ); ?></label>
				&nbsp;
				<label><input type="radio" name="hpt_has_media_type" value="image" <?php checked( $media_type, 'image' ); ?>><?php esc_html_e( 'תמונה', 'homer-patuach-tips' ); ?></label>
			</p>

			<p class="hpt-emoji-field" style="<?php echo $media_type === 'image' ? 'display:none;' : ''; ?>">
				<label for="hpt_emoji"><?php esc_html_e( 'אימוג\'י', 'homer-patuach-tips' ); ?></label><br>
				<input type="text" id="hpt_emoji" name="hpt_emoji" value="<?php echo esc_attr( $emoji ); ?>" class="hpt-emoji-input" maxlength="4" placeholder="💡">
				<button type="button" class="button hpt-emoji-picker-trigger"><?php esc_html_e( 'בחר אימוג\'י', 'homer-patuach-tips' ); ?></button>
			</p>

			<p class="hpt-image-field" style="<?php echo $media_type === 'emoji' ? 'display:none;' : ''; ?>">
				<label><?php esc_html_e( 'תמונה', 'homer-patuach-tips' ); ?></label><br>
				<div class="hpt-image-preview">
					<?php if ( $image_id ) : ?>
						<?php echo wp_get_attachment_image( $image_id, 'thumbnail' ); ?>
					<?php endif; ?>
				</div>
				<input type="hidden" id="hpt_image_id" name="hpt_image_id" value="<?php echo esc_attr( $image_id ); ?>">
				<button type="button" class="button hpt-upload-image"><?php esc_html_e( 'בחר תמונה', 'homer-patuach-tips' ); ?></button>
				<button type="button" class="button hpt-remove-image" style="<?php echo $image_id ? '' : 'display:none;'; ?>"><?php esc_html_e( 'הסר', 'homer-patuach-tips' ); ?></button>
			</p>
		</div>
		<?php
	}

	public function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['hpt_tip_details_nonce'] ) || ! wp_verify_nonce( $_POST['hpt_tip_details_nonce'], 'hpt_save_tip_details' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$credit = isset( $_POST['hpt_credit'] ) ? sanitize_text_field( $_POST['hpt_credit'] ) : '';
		$credit_user_id = isset( $_POST['hpt_credit_user_id'] ) ? (int) $_POST['hpt_credit_user_id'] : 0;
		if ( $credit ) {
			$credit_user_id = 0;
		}
		$media_type = isset( $_POST['hpt_has_media_type'] ) && in_array( $_POST['hpt_has_media_type'], array( 'image', 'emoji' ), true ) ? $_POST['hpt_has_media_type'] : 'emoji';
		$image_id = isset( $_POST['hpt_image_id'] ) ? (int) $_POST['hpt_image_id'] : 0;
		$emoji = isset( $_POST['hpt_emoji'] ) ? sanitize_text_field( $_POST['hpt_emoji'] ) : '';

		update_post_meta( $post_id, 'hpt_credit', $credit );
		update_post_meta( $post_id, 'hpt_credit_user_id', $credit_user_id );
		update_post_meta( $post_id, 'hpt_has_media_type', $media_type );
		update_post_meta( $post_id, 'hpt_image_id', $image_id );
		update_post_meta( $post_id, 'hpt_emoji', $emoji );
	}

	public function add_settings_page() {
		add_submenu_page(
			'edit.php?post_type=os_tip',
			__( 'הגדרות טיפים', 'homer-patuach-tips' ),
			__( 'הגדרות', 'homer-patuach-tips' ),
			'manage_options',
			'hpt-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'hpt_settings', 'hpt_bubble_position', array(
			'type'              => 'string',
			'default'           => 'bottom-left',
			'sanitize_callback' => 'sanitize_text_field',
		) );
		register_setting( 'hpt_settings', 'hpt_bubble_pages', array(
			'type'              => 'string',
			'default'           => 'all',
			'sanitize_callback' => 'sanitize_text_field',
		) );
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$position = get_option( 'hpt_bubble_position', 'bottom-left' );
		$pages    = get_option( 'hpt_bubble_pages', 'all' );
		?>
		<div class="wrap" dir="rtl" style="text-align: right;">
			<h1><?php esc_html_e( 'הגדרות בועת הטיפים', 'homer-patuach-tips' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'hpt_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'מיקום הבועה', 'homer-patuach-tips' ); ?></th>
						<td>
							<select name="hpt_bubble_position">
								<option value="bottom-left" <?php selected( $position, 'bottom-left' ); ?>><?php esc_html_e( 'שמאל תחתון', 'homer-patuach-tips' ); ?></option>
								<option value="bottom-right" <?php selected( $position, 'bottom-right' ); ?>><?php esc_html_e( 'ימין תחתון', 'homer-patuach-tips' ); ?></option>
								<option value="top-left" <?php selected( $position, 'top-left' ); ?>><?php esc_html_e( 'שמאל עליון', 'homer-patuach-tips' ); ?></option>
								<option value="top-right" <?php selected( $position, 'top-right' ); ?>><?php esc_html_e( 'ימין עליון', 'homer-patuach-tips' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'הצגה בעמודים', 'homer-patuach-tips' ); ?></th>
						<td>
							<select name="hpt_bubble_pages">
								<option value="all" <?php selected( $pages, 'all' ); ?>><?php esc_html_e( 'בכל העמודים', 'homer-patuach-tips' ); ?></option>
								<option value="single" <?php selected( $pages, 'single' ); ?>><?php esc_html_e( 'רק בעמודי פוסט', 'homer-patuach-tips' ); ?></option>
								<option value="single_archive" <?php selected( $pages, 'single_archive' ); ?>><?php esc_html_e( 'עמודי פוסט + ארכיון', 'homer-patuach-tips' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function enqueue_assets( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== 'os_tip' ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'hpt-admin', HPT_PLUGIN_URL . 'assets/css/admin.css', array(), HPT_VERSION );
		wp_enqueue_script( 'hpt-admin', HPT_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), HPT_VERSION, true );
		wp_localize_script( 'hpt-admin', 'hptAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'hpt_admin' ),
		) );
	}
}
