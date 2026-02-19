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

	<?php
	$current_subject = isset( $_GET['subject_id'] ) ? absint( $_GET['subject_id'] ) : 0;
	$current_grade   = isset( $_GET['grade_id'] ) ? absint( $_GET['grade_id'] ) : 0;
	$subjects        = array();
	$grades          = array();
	if ( taxonomy_exists( 'subject' ) ) {
		$subjects = OST_Templates::get_terms_hierarchical( 'subject' );
	}
	if ( taxonomy_exists( 'class' ) ) {
		$grades = OST_Templates::get_terms_hierarchical( 'class' );
	}
	$has_filters = ! empty( $subjects ) || ! empty( $grades );
	?>

	<?php if ( $has_filters ) : ?>
	<form class="ost-archive-filters" method="get" role="search">
		<div class="ost-filter-row">
			<button type="submit" class="ost-filter-submit"><?php esc_html_e( 'סנן', 'openstuff-timeline' ); ?></button>
			<?php if ( ! empty( $grades ) ) : ?>
			<select id="ost-filter-grade" name="grade_id" class="ost-filter-select" aria-label="<?php esc_attr_e( 'כיתה', 'openstuff-timeline' ); ?>">
				<option value=""><?php esc_html_e( 'כל הכיתות', 'openstuff-timeline' ); ?></option>
				<?php foreach ( $grades as $item ) : ?>
					<?php
					$term  = $item['term'];
					$depth = $item['depth'];
					$pad   = str_repeat( '— ', $depth );
					?>
					<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $current_grade, $term->term_id ); ?>><?php echo esc_html( $pad . $term->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php endif; ?>
			<?php if ( ! empty( $subjects ) ) : ?>
			<select id="ost-filter-subject" name="subject_id" class="ost-filter-select" aria-label="<?php esc_attr_e( 'תחום דעת', 'openstuff-timeline' ); ?>">
				<option value=""><?php esc_html_e( 'כל התחומים', 'openstuff-timeline' ); ?></option>
				<?php foreach ( $subjects as $item ) : ?>
					<?php
					$term  = $item['term'];
					$depth = $item['depth'];
					$pad   = str_repeat( '— ', $depth );
					?>
					<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $current_subject, $term->term_id ); ?>><?php echo esc_html( $pad . $term->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php endif; ?>
		</div>
	</form>
	<script>
	(function(){
		var form = document.querySelector('.ost-archive-filters');
		if (form) {
			var selects = form.querySelectorAll('.ost-filter-select');
			selects.forEach(function(s){ s.addEventListener('change', function(){ form.submit(); }); });
		}
	})();
	</script>
	<?php endif; ?>

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
