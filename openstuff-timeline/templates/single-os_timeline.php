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

<main id="primary" class="site-main ost-single-timeline" dir="rtl">
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

			<div class="ost-timeline-viewer-root" data-timeline-id="<?php echo esc_attr( $timeline_id ); ?>" dir="rtl"></div>
		</div>
	</article>
</main>

<?php
get_footer();
