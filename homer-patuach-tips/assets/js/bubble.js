(function($) {
	'use strict';

	var tips = [];
	var currentIndex = 0;
	var filters = { subject_id: 0, grade_id: 0, tag_ids: [] };

	function init() {
		var $bubble = $('#hpt-tips-bubble');
		var $trigger = $bubble.find('.hpt-bubble-trigger');
		var $overlay = $('#hpt-bubble-overlay');
		var $close = $overlay.find('.hpt-bubble-close');
		var $filterToggle = $overlay.find('.hpt-filter-toggle');
		var $filterChips = $overlay.find('.hpt-filter-chips');

		$trigger.on('click', function() {
			var expanded = !$overlay.attr('hidden');
			if (!expanded) {
				$overlay.removeAttr('hidden').attr('aria-hidden', 'false');
				$trigger.attr('aria-expanded', 'true');
				loadTips();
			} else {
				closePanel();
			}
		});

		$close.on('click', function() {
			closePanel();
		});

		$overlay.on('click', function(e) {
			if (e.target === $overlay[0]) closePanel();
		});

		function closePanel() {
			$overlay.attr('hidden', 'hidden').attr('aria-hidden', 'true');
			$trigger.attr('aria-expanded', 'false');
		}

		$('.hpt-add-tip-btn').on('click', function() {
			$overlay.attr('hidden', 'hidden');
			$('#hpt-add-tip-modal').removeAttr('hidden');
			initAddTipForm();
		});

		$filterToggle.on('click', function() {
			$filterChips.toggle();
			if ($filterChips.is(':visible') && $filterChips.children().length === 0) {
				loadFilterOptions();
			}
		});

		$bubble.find('.hpt-nav-prev').on('click', function() {
			prevTip();
		});
		$bubble.find('.hpt-nav-next').on('click', function() {
			nextTip();
		});

		$(document).on('click', '.hpt-filter-chip', function() {
			var $chip = $(this);
			var type = $chip.data('type');
			var id = $chip.data('id');
			if (type === 'subject') {
				filters.subject_id = filters.subject_id === id ? 0 : id;
			} else if (type === 'grade') {
				filters.grade_id = filters.grade_id === id ? 0 : id;
			} else if (type === 'tag') {
				var idx = filters.tag_ids.indexOf(id);
				if (idx >= 0) filters.tag_ids.splice(idx, 1);
				else filters.tag_ids.push(id);
			}
			$chip.toggleClass('active', (type === 'subject' && filters.subject_id === id) ||
				(type === 'grade' && filters.grade_id === id) ||
				(type === 'tag' && filters.tag_ids.indexOf(id) >= 0));
			loadTips();
		});
	}

	function loadFilterOptions() {
		$.ajax({
			url: (window.hptBubble && window.hptBubble.restUrl) ? window.hptBubble.restUrl + 'filter-options' : '/wp-json/hpt/v1/filter-options',
			method: 'GET'
		}).done(function(data) {
			var $container = $('.hpt-filter-chips');
			$container.empty();
			if (data.subjects && data.subjects.length) {
				data.subjects.forEach(function(s) {
					var active = filters.subject_id === s.id ? ' active' : '';
					$container.append('<button type="button" class="hpt-filter-chip' + active + '" data-type="subject" data-id="' + s.id + '">' + escapeHtml(s.name) + '</button>');
				});
			}
			if (data.grades && data.grades.length) {
				data.grades.forEach(function(g) {
					var active = filters.grade_id === g.id ? ' active' : '';
					$container.append('<button type="button" class="hpt-filter-chip' + active + '" data-type="grade" data-id="' + g.id + '">' + escapeHtml(g.name) + '</button>');
				});
			}
			if (data.tags && data.tags.length) {
				data.tags.forEach(function(t) {
					var active = filters.tag_ids.indexOf(t.id) >= 0 ? ' active' : '';
					$container.append('<button type="button" class="hpt-filter-chip' + active + '" data-type="tag" data-id="' + t.id + '">' + escapeHtml(t.name) + '</button>');
				});
			}
		});
	}

	function loadTips() {
		var $loading = $('.hpt-tip-loading');
		var $display = $('.hpt-tip-display');
		var $empty = $('.hpt-tip-empty');
		$loading.show();
		$display.hide();
		$empty.hide();

		var hasFilters = filters.subject_id > 0 || filters.grade_id > 0 || filters.tag_ids.length > 0;
		var url = (window.hptBubble && window.hptBubble.restUrl) ? window.hptBubble.restUrl + 'tips' : '/wp-json/hpt/v1/tips';
		url += '?per_page=20';
		url += '&subject_id=' + filters.subject_id + '&grade_id=' + filters.grade_id;
		if (filters.tag_ids.length) url += '&tag_ids=' + filters.tag_ids.join(',');
		if (!hasFilters) url += '&random=1';

		$.ajax({
			url: url,
			method: 'GET'
		}).done(function(res) {
			$loading.hide();
			tips = res.tips || [];
			if (tips.length === 0) {
				$empty.show();
				return;
			}
			currentIndex = 0;
			renderTip(tips[0]);
			$display.show();
		}).fail(function() {
			$loading.hide();
			$empty.text('שגיאה בטעינת הטיפים.').show();
		});
	}

	function prevTip() {
		if (tips.length === 0) {
			loadTips();
			return;
		}
		currentIndex = (currentIndex - 1 + tips.length) % tips.length;
		renderTip(tips[currentIndex]);
	}

	function nextTip() {
		if (tips.length === 0) {
			loadTips();
			return;
		}
		currentIndex = (currentIndex + 1) % tips.length;
		renderTip(tips[currentIndex]);
	}

	function renderTip(tip) {
		var $media = $('.hpt-tip-media');
		var $body = $('.hpt-tip-body');
		var $credit = $('.hpt-tip-credit');

		$media.empty();
		if (tip.emoji) {
			$media.html('<span>' + escapeHtml(tip.emoji) + '</span>');
		} else if (tip.image_url) {
			$media.html('<img src="' + escapeHtml(tip.image_url) + '" alt="">');
		}

		$body.html(tip.content || '');
		$credit.text(tip.credit ? 'מאת: ' + tip.credit : '');
	}

	function escapeHtml(text) {
		if (!text) return '';
		var div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	function initAddTipForm() {
		var $modal = $('#hpt-add-tip-modal');
		var $form = $('#hpt-add-tip-form');
		var $close = $modal.find('.hpt-modal-close');

		$close.on('click', function() {
			$modal.attr('hidden', 'hidden');
			$('#hpt-bubble-overlay').removeAttr('hidden');
		});

		$modal.on('click', function(e) {
			if (e.target === $modal[0]) {
				$modal.attr('hidden', 'hidden');
				$('#hpt-bubble-overlay').removeAttr('hidden');
			}
		});

		$form.find('input[name="media_type"]').on('change', function() {
			var type = $(this).val();
			$form.find('.hpt-form-emoji-wrap').toggle(type === 'emoji');
			$form.find('.hpt-form-image-wrap').toggle(type === 'image');
		});

		// Emoji picker
		var commonEmojis = ['💡', '📚', '✏️', '🎯', '🌟', '👍', '💪', '🧠', '❤️', '🎨', '🔬', '📖', '✨', '🌈', '🎓'];
		$form.find('.hpt-form-emoji-pick').off('click').on('click', function() {
			var $input = $('#hpt-form-emoji');
			var $pop = $('.hpt-popover-emoji');
			if ($pop.length) {
				$pop.toggle();
				return;
			}
			$pop = $('<div class="hpt-popover-emoji" style="position:absolute;background:#fff;border:1px solid #e9ebee;border-radius:8px;padding:8px;box-shadow:0 5px 20px rgba(0,0,0,0.2);z-index:100001;"></div>');
			commonEmojis.forEach(function(emoji) {
				$pop.append($('<button type="button" class="hpt-emoji-btn" style="font-size:24px;padding:4px;margin:2px;border:none;background:none;cursor:pointer;">').text(emoji));
			});
			$('body').append($pop);
			var pos = $input.offset();
			$pop.css({ top: pos.top - 100, right: pos.right }).show();
			$pop.on('click', '.hpt-emoji-btn', function() {
				$input.val($(this).text());
				$pop.remove();
			});
			$(document).one('click', function() { $pop.remove(); });
		});

		// Image upload
		$form.find('.hpt-form-upload-image').off('click').on('click', function() {
			if (typeof wp === 'undefined' || !wp.media) return;
			var frame = wp.media({ library: { type: 'image' }, multiple: false });
			frame.on('select', function() {
				var att = frame.state().get('selection').first().toJSON();
				$('#hpt-form-image-id').val(att.id);
				$form.find('.hpt-form-image-preview').html('<img src="' + att.url + '" style="max-width:80px;height:auto;">');
			});
			frame.open();
		});

		// Load filter options for selects
		$.get((window.hptBubble && window.hptBubble.restUrl) ? window.hptBubble.restUrl + 'filter-options' : '/wp-json/hpt/v1/filter-options',
			function(data) {
				var $sub = $('#hpt-form-subject'), $grade = $('#hpt-form-grade');
				$sub.find('option:not(:first)').remove();
				$grade.find('option:not(:first)').remove();
				(data.subjects || []).forEach(function(s) {
					$sub.append('<option value="' + s.id + '">' + escapeHtml(s.name) + '</option>');
				});
				(data.grades || []).forEach(function(g) {
					$grade.append('<option value="' + g.id + '">' + escapeHtml(g.name) + '</option>');
				});
			}
		);

		$form.off('submit').on('submit', function(e) {
			e.preventDefault();
			var $msg = $form.find('.hpt-form-message');
			$msg.hide();
			var content = $('#hpt-form-content').val().trim();
			if (!content) {
				$msg.removeClass('success').addClass('error').text('התוכן חובה').show();
				return;
			}
			var data = {
				content: content,
				credit: $('#hpt-form-credit').val().trim(),
				media_type: $form.find('input[name="media_type"]:checked').val(),
				emoji: $('#hpt-form-emoji').val(),
				image_id: parseInt($('#hpt-form-image-id').val(), 10) || 0,
				subject_id: parseInt($('#hpt-form-subject').val(), 10) || 0,
				grade_id: parseInt($('#hpt-form-grade').val(), 10) || 0,
				tags: $('#hpt-form-tags').val().trim()
			};
			$.ajax({
				url: (window.hptBubble && window.hptBubble.restUrl) ? window.hptBubble.restUrl + 'tips' : '/wp-json/hpt/v1/tips',
				method: 'POST',
				contentType: 'application/json',
				data: JSON.stringify(data),
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', (window.hptBubble && window.hptBubble.nonce) || '');
				}
			}).done(function(res) {
				$msg.removeClass('error').addClass('success').text('הטיפ נשלח לאישור. תודה!').show();
				$form[0].reset();
				$('#hpt-form-image-id').val(0);
				$form.find('.hpt-form-image-preview').empty();
				setTimeout(function() {
					$modal.attr('hidden', 'hidden');
					$('#hpt-bubble-overlay').removeAttr('hidden');
				}, 1500);
			}).fail(function(xhr) {
				var err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'שגיאה בשליחה';
				$msg.removeClass('success').addClass('error').text(err).show();
			});
		});
	}

	$(document).ready(init);
})(jQuery);
