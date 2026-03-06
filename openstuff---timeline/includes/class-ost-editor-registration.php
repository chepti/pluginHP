<?php
/**
 * הרשמת מורים לעריכת צירים - טופס, שמירה, אדמין
 *
 * @package OpenStuff_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OST_Editor_Registration {

	const OPTION_KEY = 'ost_editor_registrations';

	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_grant_editor' ) );
		add_action( 'wp_ajax_ost_submit_editor_registration', array( $this, 'ajax_submit_registration' ) );
		add_action( 'wp_ajax_nopriv_ost_submit_editor_registration', array( $this, 'ajax_submit_registration' ) );
		add_action( 'save_post_os_timeline', array( $this, 'track_timeline_edit' ), 10, 2 );
	}

	/**
	 * מעקב עריכות ציר - לבאדג' עורך על (ספירת צירים ייחודיים שערך)
	 */
	public function track_timeline_edit( $post_id, $post ) {
		if ( ! $post || $post->post_status === 'auto-draft' ) {
			return;
		}
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}
		$edited = get_user_meta( $user_id, 'ost_timelines_edited_ids', true );
		if ( ! is_array( $edited ) ) {
			$edited = array();
		}
		if ( ! in_array( $post_id, $edited, true ) ) {
			$edited[] = $post_id;
			update_user_meta( $user_id, 'ost_timelines_edited_ids', $edited );
			update_user_meta( $user_id, 'ost_timelines_edited_count', count( $edited ) );
		}
	}

	/** רשימת הרשמות מטבלת options */
	public static function get_registrations() {
		$data = get_option( self::OPTION_KEY, array() );
		return is_array( $data ) ? $data : array();
	}

	private static function save_registrations( $list ) {
		update_option( self::OPTION_KEY, $list );
	}

	/**
	 * REST - שליחת הרשמה (לשימוש עתידי)
	 */
	public function register_rest_routes() {
		register_rest_route( OST_REST_NAMESPACE, '/editor-registration', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_submit_registration' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'name'         => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
				'email'        => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_email' ),
				'study_groups' => array( 'required' => true, 'type' => 'array' ),
			),
		) );
	}

	public function rest_submit_registration( $request ) {
		return $this->do_submit_registration(
			$request->get_param( 'name' ),
			$request->get_param( 'email' ),
			$request->get_param( 'study_groups' )
		);
	}

	/**
	 * AJAX - שליחת הרשמה
	 */
	public function ajax_submit_registration() {
		check_ajax_referer( 'ost_editor_registration', 'nonce' );
		$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$groups_raw = isset( $_POST['study_groups'] ) ? wp_unslash( $_POST['study_groups'] ) : '';
		$study_groups = array();
		if ( is_string( $groups_raw ) ) {
			$study_groups = json_decode( $groups_raw, true );
		}
		if ( ! is_array( $study_groups ) ) {
			$study_groups = array();
		}

		$result = $this->do_submit_registration( $name, $email, $study_groups );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( array( 'message' => __( 'ההרשמה נשלחה בהצלחה! ניצור איתך קשר בהקדם.', 'openstuff-timeline' ) ) );
	}

	private function do_submit_registration( $name, $email, $study_groups ) {
		if ( empty( $name ) || empty( $email ) ) {
			return new WP_Error( 'missing', __( 'נא למלא שם ומייל.', 'openstuff-timeline' ) );
		}
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'כתובת המייל אינה תקינה.', 'openstuff-timeline' ) );
		}
		$valid_groups = array();
		foreach ( $study_groups as $g ) {
			$grade_id   = isset( $g['grade_id'] ) ? absint( $g['grade_id'] ) : 0;
			$subject_id = isset( $g['subject_id'] ) ? absint( $g['subject_id'] ) : 0;
			if ( $grade_id && $subject_id ) {
				$valid_groups[] = array( 'grade_id' => $grade_id, 'subject_id' => $subject_id );
			}
		}
		if ( empty( $valid_groups ) ) {
			return new WP_Error( 'no_groups', __( 'נא לבחור לפחות קבוצת לימוד אחת (כיתה + תחום דעת).', 'openstuff-timeline' ) );
		}

		$user = get_user_by( 'email', $email );
		$user_id = $user ? $user->ID : 0;

		$list = self::get_registrations();
		foreach ( $list as $r ) {
			if ( $r['email'] === $email && $this->groups_match( $r['study_groups'], $valid_groups ) ) {
				return new WP_Error( 'duplicate', __( 'כבר נרשמת עם קבוצות לימוד אלו. ניצור איתך קשר בהקדם.', 'openstuff-timeline' ) );
			}
		}

		$id = 'ost_' . time() . '_' . wp_rand( 1000, 9999 );
		$item = array(
			'id'           => $id,
			'name'         => $name,
			'email'        => $email,
			'user_id'      => $user_id,
			'status'       => 'pending',
			'study_groups'  => $valid_groups,
			'date'         => current_time( 'mysql' ),
		);
		$list[] = $item;
		self::save_registrations( $list );

		return $id;
	}

	private function groups_match( $a, $b ) {
		if ( count( $a ) !== count( $b ) ) {
			return false;
		}
		foreach ( $a as $ga ) {
			$found = false;
			foreach ( $b as $gb ) {
				if ( (int) $ga['grade_id'] === (int) $gb['grade_id'] && (int) $ga['subject_id'] === (int) $gb['subject_id'] ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * תפריט אדמין - טבלת הרשמות
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=os_timeline',
			__( 'הרשמות מורים', 'openstuff-timeline' ),
			__( 'הרשמות מורים', 'openstuff-timeline' ),
			'manage_options',
			'ost-editor-registrations',
			array( $this, 'render_admin_page' )
		);
	}

	public function render_admin_page() {
		$registrations = array_reverse( self::get_registrations() );

		wp_enqueue_style( 'ost-admin-registrations', OST_PLUGIN_URL . 'assets/css/admin-registrations.css', array(), OST_VERSION );
		?>
		<div class="wrap ost-editor-registrations-wrap" dir="rtl" style="text-align: right;">
			<h1><?php esc_html_e( 'הרשמות מורים לעריכת צירים', 'openstuff-timeline' ); ?></h1>
			<p class="ost-admin-desc"><?php esc_html_e( 'מורים שנרשמו דרך דף ארכיון הצירים. לחץ "הענק עורך" כדי לתת הרשאת עורך ולעדכן סטטוס.', 'openstuff-timeline' ); ?></p>

			<table class="wp-list-table widefat fixed striped ost-registrations-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'שם', 'openstuff-timeline' ); ?></th>
						<th><?php esc_html_e( 'מייל', 'openstuff-timeline' ); ?></th>
						<th><?php esc_html_e( 'קבוצות לימוד', 'openstuff-timeline' ); ?></th>
						<th><?php esc_html_e( 'סטטוס', 'openstuff-timeline' ); ?></th>
						<th><?php esc_html_e( 'פעולות', 'openstuff-timeline' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $registrations ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'אין הרשמות.', 'openstuff-timeline' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $registrations as $r ) :
							$email   = $r['email'] ?? '';
							$user_id = (int) ( $r['user_id'] ?? 0 );
							$status  = $r['status'] ?? 'pending';
							$groups  = $r['study_groups'] ?? array();
							$groups_labels = array();
							foreach ( $groups as $g ) {
								$grade   = get_term( (int) ( $g['grade_id'] ?? 0 ), 'class' );
								$subject = get_term( (int) ( $g['subject_id'] ?? 0 ), 'subject' );
								$gl = ( $grade && ! is_wp_error( $grade ) ? $grade->name : '?' ) . ' + ' . ( $subject && ! is_wp_error( $subject ) ? $subject->name : '?' );
								$groups_labels[] = $gl;
							}
							$has_editor = $user_id && user_can( $user_id, 'edit_others_posts' );
							$reg_id = $r['id'] ?? '';
						?>
							<tr>
								<td><strong><?php echo esc_html( $r['name'] ?? '' ); ?></strong></td>
								<td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td>
								<td><?php echo esc_html( implode( ' | ', $groups_labels ) ); ?></td>
								<td>
									<?php
									if ( $status === 'approved' ) {
										echo '<span class="ost-status approved">' . esc_html__( 'אושר', 'openstuff-timeline' ) . '</span>';
									} elseif ( $has_editor ) {
										echo '<span class="ost-status editor">' . esc_html__( 'עורך', 'openstuff-timeline' ) . '</span>';
									} else {
										echo '<span class="ost-status pending">' . esc_html__( 'ממתין', 'openstuff-timeline' ) . '</span>';
									}
									?>
								</td>
								<td>
									<?php if ( ! $has_editor && $user_id ) : ?>
										<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'ost_grant_editor' => $reg_id, 'page' => 'ost-editor-registrations' ), admin_url( 'edit.php?post_type=os_timeline' ) ), 'ost_grant_' . $reg_id ) ); ?>" class="button button-primary"><?php esc_html_e( 'הענק עורך', 'openstuff-timeline' ); ?></a>
									<?php elseif ( ! $has_editor && ! $user_id ) : ?>
										<span class="ost-no-user" title="<?php esc_attr_e( 'המשתמש לא רשום באתר – הזמן אותו להירשם ואז הענק הרשאה', 'openstuff-timeline' ); ?>"><?php esc_html_e( 'לא רשום באתר', 'openstuff-timeline' ); ?></span>
									<?php else : ?>
										<span class="ost-has-editor">✓</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function handle_grant_editor() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$reg_id = isset( $_GET['ost_grant_editor'] ) ? sanitize_text_field( wp_unslash( $_GET['ost_grant_editor'] ) ) : '';
		if ( ! $reg_id || ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'ost_grant_' . $reg_id ) ) {
			return;
		}
		$list = self::get_registrations();
		$found = null;
		foreach ( $list as $i => $r ) {
			if ( ( $r['id'] ?? '' ) === $reg_id ) {
				$found = $i;
				break;
			}
		}
		if ( $found === null ) {
			wp_redirect( add_query_arg( 'ost_error', 'not_found', wp_get_referer() ) );
			exit;
		}
		$user_id = (int) ( $list[ $found ]['user_id'] ?? 0 );
		if ( ! $user_id ) {
			wp_redirect( add_query_arg( 'ost_error', 'no_user', wp_get_referer() ) );
			exit;
		}
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			wp_redirect( add_query_arg( 'ost_error', 'no_user', wp_get_referer() ) );
			exit;
		}
		$user->set_role( 'editor' );
		$list[ $found ]['status'] = 'approved';
		self::save_registrations( $list );

		if ( function_exists( 'hpg_grant_manual_badge' ) ) {
			hpg_grant_manual_badge( $user_id, 'timeline_editor' );
		}

		wp_redirect( remove_query_arg( array( 'ost_grant_editor', '_wpnonce' ), wp_get_referer() ) );
		exit;
	}

	/**
	 * ספירת צירים ייחודיים שערך משתמש (לבאדג' עורך על)
	 */
	public static function get_user_timelines_edited_count( $user_id ) {
		$count = get_user_meta( $user_id, 'ost_timelines_edited_count', true );
		return $count ? (int) $count : 0;
	}
}
