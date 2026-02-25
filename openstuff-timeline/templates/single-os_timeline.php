<?php
/**
 * Single Timeline Template
 * תבנית ציר זמן בודד - באנר + תצוגת ציר
 *
 * @package OpenStuff_Timeline
 */

get_header();

$timeline_id = get_the_ID();
?>

<?php if ( isset( $_GET['ost_debug'] ) ) : ?>
<div class="ost-debug-notice" style="background:#fff3cd;padding:8px 12px;margin:0 0 1rem;border:1px solid #ffc107;border-radius:4px;font-size:13px;">
	<strong>דרוג:</strong> תבנית OpenStuff v<?php echo esc_html( defined( 'OST_VERSION' ) ? OST_VERSION : '?' ); ?> נטענה.
	מחובר: <?php echo is_user_logged_in() ? 'כן' : 'לא'; ?> |
	edit_post: <?php echo current_user_can( 'edit_post', $timeline_id ) ? 'כן' : 'לא'; ?>
</div>
<?php endif; ?>
<main id="primary" class="site-main ost-single-timeline" dir="rtl" data-ost-version="<?php echo esc_attr( defined( 'OST_VERSION' ) ? OST_VERSION : '' ); ?>">
	<article id="post-<?php echo esc_attr( $timeline_id ); ?>" <?php post_class( 'ost-timeline-article' ); ?>>
		<?php if ( has_post_thumbnail() ) : ?>
			<header class="ost-timeline-banner">
				<?php the_post_thumbnail( 'large', array( 'class' => 'ost-timeline-banner-img' ) ); ?>
			</header>
		<?php endif; ?>

		<div class="ost-timeline-content">
			<?php if ( ! has_post_thumbnail() ) : ?>
				<header class="ost-timeline-header">
					<h1 class="ost-timeline-title"><?php the_title(); ?></h1>
				</header>
			<?php endif; ?>

			<?php if ( is_user_logged_in() && current_user_can( 'edit_post', $timeline_id ) ) : ?>
				<div class="ost-edit-timeline-bar">
					<a href="<?php echo esc_url( get_edit_post_link( $timeline_id, 'raw' ) ); ?>" class="ost-edit-timeline-btn">
						<span class="dashicons dashicons-edit" aria-hidden="true"></span>
						<?php esc_html_e( 'ערוך ציר', 'openstuff-timeline' ); ?>
					</a>
					<?php if ( ! current_user_can( 'publish_posts' ) ) : ?>
						<span class="ost-edit-hint"><?php esc_html_e( 'השינויים יישמרו כממתינים לאישור עורך', 'openstuff-timeline' ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php
			$has_pending = (int) get_post_meta( $timeline_id, 'ost_has_pending_changes', true ) > 0;
			if ( $has_pending && current_user_can( 'edit_others_posts' ) ) :
				$rest_url = rest_url( OST_REST_NAMESPACE . '/timeline/' . $timeline_id . '/approve-pending' );
				$nonce    = wp_create_nonce( 'wp_rest' );
				?>
				<div class="ost-pending-changes-bar" data-timeline-id="<?php echo esc_attr( $timeline_id ); ?>" data-rest-url="<?php echo esc_url( $rest_url ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
					<span class="ost-pending-changes-text"><?php esc_html_e( 'יש שינויים בציר שממתינים לאישור', 'openstuff-timeline' ); ?></span>
					<button type="button" class="ost-approve-pending-btn" aria-label="<?php esc_attr_e( 'אשר שינויים', 'openstuff-timeline' ); ?>">
						<?php esc_html_e( 'אשר שינויים', 'openstuff-timeline' ); ?>
					</button>
				</div>
			<?php endif; ?>

			<div class="ost-timeline-viewer-root" data-timeline-id="<?php echo esc_attr( $timeline_id ); ?>" dir="rtl"></div>
		</div>
	</article>
</main>

<?php
get_footer();
