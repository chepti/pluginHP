<?php
/**
 * Archive Timeline Template
 * תבנית ארכיון צירי זמן - רשימת כל הצירים שפורסמו
 *
 * @package OpenStuff_Timeline
 */

get_header();
?>

<main id="primary" class="site-main ost-archive-timeline" dir="rtl">
	<header class="ost-archive-header">
		<h1 class="ost-archive-title"><?php post_type_archive_title(); ?></h1>
	</header>

	<div class="ost-timelines-grid">
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();
				$thumb = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
				?>
				<article class="ost-timeline-card">
					<a href="<?php the_permalink(); ?>" class="ost-timeline-card-link">
						<?php if ( $thumb ) : ?>
							<div class="ost-timeline-card-thumb">
								<img src="<?php echo esc_url( $thumb ); ?>" alt="" loading="lazy" />
							</div>
						<?php else : ?>
							<div class="ost-timeline-card-thumb ost-timeline-card-placeholder">
								<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
							</div>
						<?php endif; ?>
						<h2 class="ost-timeline-card-title"><?php the_title(); ?></h2>
					</a>
				</article>
				<?php
			endwhile;
		else :
			?>
			<p class="ost-no-timelines"><?php esc_html_e( 'לא נמצאו צירי זמן שפורסמו.', 'openstuff-timeline' ); ?></p>
		<?php endif; ?>
	</div>

	<?php the_posts_pagination(); ?>
</main>

<?php
get_footer();
