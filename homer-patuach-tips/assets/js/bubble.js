(function($) {
	'use strict';

	var tips = [];
	var currentIndex = 0;
	var filters = { subject_id: 0, grade_id: 0, tag_ids: [] };

	function init() {
		var $bubble = $('#hpt-tips-bubble');
		var $trigger = $bubble.find('.hpt-bubble-trigger');
		var $panel = $('#hpt-bubble-panel');
		var $close = $bubble.find('.hpt-bubble-close');
		var $filterToggle = $bubble.find('.hpt-filter-toggle');
		var $filterChips = $bubble.find('.hpt-filter-chips');

		$trigger.on('click', function() {
			var expanded = $panel.attr('hidden') === undefined || $panel.attr('hidden') === false;
			if (!expanded) {
				$panel.removeAttr('hidden');
				$trigger.attr('aria-expanded', 'true');
				loadTips();
			} else {
				$panel.attr('hidden', 'hidden');
				$trigger.attr('aria-expanded', 'false');
			}
		});

		$close.on('click', function() {
			$panel.attr('hidden', 'hidden');
			$trigger.attr('aria-expanded', 'false');
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

	$(document).ready(init);
})(jQuery);
