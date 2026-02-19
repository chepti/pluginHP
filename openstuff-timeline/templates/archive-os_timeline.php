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
		<button type="button" class="ost-editor-register-btn" id="ost-open-editor-form" aria-expanded="false" aria-controls="ost-editor-registration-form">
			<?php esc_html_e( 'רוצה לערוך ציר? הירשם כאן', 'openstuff-timeline' ); ?>
		</button>
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
				$tid   = get_the_ID();
				$thumb = get_the_post_thumbnail_url( $tid, 'medium' );
				$views = (int) get_post_meta( $tid, '_hpg_view_count', true );
				$likes = (int) get_post_meta( $tid, '_hpg_like_count', true );
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
					<div class="ost-timeline-card-footer">
						<span class="ost-card-meta" title="צפיות"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 10c-2.48 0-4.5-2.02-4.5-4.5S9.52 5.5 12 5.5s4.5 2.02 4.5 4.5-2.02 4.5-4.5 4.5z"/></svg> <?php echo esc_html( number_format_i18n( $views ) ); ?></span>
						<span class="ost-card-meta" title="לייקים"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg> <?php echo esc_html( number_format_i18n( $likes ) ); ?></span>
					</div>
				</article>
				<?php
			endwhile;
		else :
			?>
			<p class="ost-no-timelines"><?php esc_html_e( 'לא נמצאו צירי זמן שפורסמו.', 'openstuff-timeline' ); ?></p>
		<?php endif; ?>
	</div>

	<?php the_posts_pagination(); ?>

	<?php
	// טופס הרשמת מורים לעריכת צירים
	$form_subjects = array();
	$form_grades   = array();
	if ( taxonomy_exists( 'subject' ) ) {
		$form_subjects = OST_Templates::get_terms_hierarchical( 'subject' );
	}
	if ( taxonomy_exists( 'class' ) ) {
		$form_grades = OST_Templates::get_terms_hierarchical( 'class' );
	}
	?>
	<div id="ost-editor-registration-overlay" class="ost-editor-form-overlay" aria-hidden="true">
		<div class="ost-editor-form-modal" role="dialog" aria-labelledby="ost-editor-form-title" aria-modal="true">
			<button type="button" class="ost-editor-form-close" id="ost-close-editor-form" aria-label="<?php esc_attr_e( 'סגור', 'openstuff-timeline' ); ?>">&times;</button>
			<h2 id="ost-editor-form-title" class="ost-editor-form-title"><?php esc_html_e( 'הרשמה לעריכת צירי זמן', 'openstuff-timeline' ); ?></h2>
			<p class="ost-editor-form-desc"><?php esc_html_e( 'מורים שמוכנים לעזור בבניית צירים – מלאו את הפרטים ונעניק לכם הרשאת עורך לציר הזמן שלכם.', 'openstuff-timeline' ); ?></p>

			<form id="ost-editor-registration-form" class="ost-editor-registration-form">
				<?php wp_nonce_field( 'ost_editor_registration', 'ost_registration_nonce' ); ?>
				<div class="ost-form-field">
					<label for="ost-reg-name"><?php esc_html_e( 'שם מלא', 'openstuff-timeline' ); ?> <span class="required">*</span></label>
					<input type="text" id="ost-reg-name" name="name" required placeholder="<?php esc_attr_e( 'השם שלך', 'openstuff-timeline' ); ?>" />
				</div>
				<div class="ost-form-field">
					<label for="ost-reg-email"><?php esc_html_e( 'מייל גוגל', 'openstuff-timeline' ); ?> <span class="required">*</span></label>
					<input type="email" id="ost-reg-email" name="email" required placeholder="example@gmail.com" />
					<span class="ost-field-hint"><?php esc_html_e( 'אם אתה רשום באתר עם מייל זה – מצוין!', 'openstuff-timeline' ); ?></span>
				</div>

				<div id="ost-study-groups-container">
					<div class="ost-study-group-block" data-index="0">
						<h3 class="ost-group-block-title"><?php esc_html_e( 'קבוצת לימוד 1', 'openstuff-timeline' ); ?></h3>
						<div class="ost-form-field">
							<label><?php esc_html_e( 'כיתה', 'openstuff-timeline' ); ?> <span class="required">*</span></label>
							<div class="ost-pill-buttons ost-grade-pills" data-field="grade_id">
								<?php foreach ( $form_grades as $item ) : $t = $item['term']; ?>
									<button type="button" class="ost-pill" data-value="<?php echo esc_attr( $t->term_id ); ?>"><?php echo esc_html( $t->name ); ?></button>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="ost-form-field">
							<label><?php esc_html_e( 'תחום דעת', 'openstuff-timeline' ); ?> <span class="required">*</span></label>
							<div class="ost-pill-buttons ost-subject-pills" data-field="subject_id">
								<?php foreach ( $form_subjects as $item ) : $t = $item['term']; ?>
									<button type="button" class="ost-pill" data-value="<?php echo esc_attr( $t->term_id ); ?>"><?php echo esc_html( $t->name ); ?></button>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>
				<?php if ( empty( $form_grades ) || empty( $form_subjects ) ) : ?>
				<p class="ost-form-no-terms"><?php esc_html_e( 'לא הוגדרו כיתות או תחומי דעת במערכת. פנה למנהל.', 'openstuff-timeline' ); ?></p>
				<?php endif; ?>
				<button type="button" class="ost-add-group-btn" id="ost-add-study-group"><?php esc_html_e( '+ הוסף קבוצת לימוד', 'openstuff-timeline' ); ?></button>

				<div id="ost-form-message" class="ost-form-message" role="alert" aria-live="polite"></div>
				<button type="submit" class="ost-form-submit"><?php esc_html_e( 'שליחת ההרשמה', 'openstuff-timeline' ); ?></button>
			</form>
		</div>
	</div>
	<script>
	(function() {
		var overlay = document.getElementById('ost-editor-registration-overlay');
		var openBtn = document.getElementById('ost-open-editor-form');
		var closeBtn = document.getElementById('ost-close-editor-form');
		var form = document.getElementById('ost-editor-registration-form');
		var addGroupBtn = document.getElementById('ost-add-study-group');
		var container = document.getElementById('ost-study-groups-container');
		var groupIndex = 1;

		function openForm() {
			overlay.classList.add('ost-open');
			overlay.setAttribute('aria-hidden', 'false');
			openBtn.setAttribute('aria-expanded', 'true');
			document.body.style.overflow = 'hidden';
		}
		function closeForm() {
			overlay.classList.remove('ost-open');
			overlay.setAttribute('aria-hidden', 'true');
			openBtn.setAttribute('aria-expanded', 'false');
			document.body.style.overflow = '';
		}

		if (openBtn) openBtn.addEventListener('click', openForm);
		if (closeBtn) closeBtn.addEventListener('click', closeForm);
		overlay.addEventListener('click', function(e) { if (e.target === overlay) closeForm(); });
		document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeForm(); });

		// Pill selection - dataset uses camelCase (grade_id -> gradeId)
		function getBlockDataKey(field) { return field === 'grade_id' ? 'gradeId' : 'subjectId'; }
		document.querySelectorAll('.ost-pill-buttons').forEach(function(btns) {
			btns.addEventListener('click', function(e) {
				var pill = e.target.closest('.ost-pill');
				if (!pill) return;
				var field = btns.dataset.field;
				var block = btns.closest('.ost-study-group-block');
				btns.querySelectorAll('.ost-pill').forEach(function(p) { p.classList.remove('selected'); });
				pill.classList.add('selected');
				if (block) block.dataset[getBlockDataKey(field)] = pill.dataset.value;
			});
		});

		// Add group
		var gradeHtml = <?php echo wp_json_encode( array_map( function( $item ) { $t = $item['term']; return array( 'id' => $t->term_id, 'name' => $t->name ); }, $form_grades ) ); ?>;
		var subjectHtml = <?php echo wp_json_encode( array_map( function( $item ) { $t = $item['term']; return array( 'id' => $t->term_id, 'name' => $t->name ); }, $form_subjects ) ); ?>;
		if (addGroupBtn && container) {
			addGroupBtn.addEventListener('click', function() {
				groupIndex++;
				var block = document.createElement('div');
				block.className = 'ost-study-group-block';
				block.dataset.index = groupIndex;
				block.innerHTML = '<h3 class="ost-group-block-title"><?php echo esc_js( __( 'קבוצת לימוד', 'openstuff-timeline' ) ); ?> ' + groupIndex + '</h3>' +
					'<div class="ost-form-field"><label><?php echo esc_js( __( 'כיתה', 'openstuff-timeline' ) ); ?> <span class="required">*</span></label>' +
					'<div class="ost-pill-buttons ost-grade-pills" data-field="grade_id">' +
					gradeHtml.map(function(g){ return '<button type="button" class="ost-pill" data-value="'+g.id+'">'+g.name+'</button>'; }).join('') +
					'</div></div>' +
					'<div class="ost-form-field"><label><?php echo esc_js( __( 'תחום דעת', 'openstuff-timeline' ) ); ?> <span class="required">*</span></label>' +
					'<div class="ost-pill-buttons ost-subject-pills" data-field="subject_id">' +
					subjectHtml.map(function(s){ return '<button type="button" class="ost-pill" data-value="'+s.id+'">'+s.name+'</button>'; }).join('') +
					'</div></div>';
				block.querySelectorAll('.ost-pill-buttons').forEach(function(btns) {
					btns.addEventListener('click', function(e) {
						var pill = e.target.closest('.ost-pill');
						if (!pill) return;
						var field = btns.dataset.field;
						var blk = btns.closest('.ost-study-group-block');
						btns.querySelectorAll('.ost-pill').forEach(function(p) { p.classList.remove('selected'); });
						pill.classList.add('selected');
						if (blk) blk.dataset[getBlockDataKey(field)] = pill.dataset.value;
					});
				});
				container.appendChild(block);
			});
		}

		// Submit
		if (form) {
			form.addEventListener('submit', function(e) {
				e.preventDefault();
				var msgEl = document.getElementById('ost-form-message');
				var name = document.getElementById('ost-reg-name').value.trim();
				var email = document.getElementById('ost-reg-email').value.trim();
				var groups = [];
				document.querySelectorAll('.ost-study-group-block').forEach(function(block) {
					var gid = block.dataset.gradeId || (block.querySelector('.ost-grade-pills .ost-pill.selected') && block.querySelector('.ost-grade-pills .ost-pill.selected').dataset.value);
					var sid = block.dataset.subjectId || (block.querySelector('.ost-subject-pills .ost-pill.selected') && block.querySelector('.ost-subject-pills .ost-pill.selected').dataset.value);
					if (gid && sid) groups.push({ grade_id: parseInt(gid,10), subject_id: parseInt(sid,10) });
				});
				if (!name || !email) {
					msgEl.textContent = '<?php echo esc_js( __( 'נא למלא שם ומייל.', 'openstuff-timeline' ) ); ?>';
					msgEl.className = 'ost-form-message ost-error';
					return;
				}
				if (groups.length === 0) {
					msgEl.textContent = '<?php echo esc_js( __( 'נא לבחור לפחות כיתה ותחום דעת אחת.', 'openstuff-timeline' ) ); ?>';
					msgEl.className = 'ost-form-message ost-error';
					return;
				}
				msgEl.textContent = '<?php echo esc_js( __( 'שולח...', 'openstuff-timeline' ) ); ?>';
				msgEl.className = 'ost-form-message ost-loading';
				var fd = new FormData();
				fd.append('action', 'ost_submit_editor_registration');
				fd.append('nonce', document.querySelector('[name="ost_registration_nonce"]').value);
				fd.append('name', name);
				fd.append('email', email);
				fd.append('study_groups', JSON.stringify(groups));
				fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
					.then(function(r) { return r.json(); })
					.then(function(res) {
						if (res.success) {
							msgEl.textContent = res.data.message || '<?php echo esc_js( __( 'ההרשמה נשלחה בהצלחה!', 'openstuff-timeline' ) ); ?>';
							msgEl.className = 'ost-form-message ost-success';
							setTimeout(closeForm, 2000);
						} else {
							msgEl.textContent = (res.data && res.data.message) || '<?php echo esc_js( __( 'שגיאה. נסה שוב.', 'openstuff-timeline' ) ); ?>';
							msgEl.className = 'ost-form-message ost-error';
						}
					})
					.catch(function() {
						msgEl.textContent = '<?php echo esc_js( __( 'שגיאת רשת. נסה שוב.', 'openstuff-timeline' ) ); ?>';
						msgEl.className = 'ost-form-message ost-error';
					});
			});
		}
	})();
	</script>
</main>

<?php
get_footer();
