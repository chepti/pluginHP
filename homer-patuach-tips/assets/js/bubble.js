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
			var $clear = $overlay.find('.hpt-filter-clear');
			if ($filterChips.is(':visible')) {
				if ($filterChips.children().length === 0) loadFilterOptions();
				$clear.toggle(filters.subject_id > 0 || filters.grade_id > 0 || filters.tag_ids.length > 0);
			} else {
				$clear.hide();
			}
		});

		$overlay.find('.hpt-filter-clear').on('click', function() {
			filters = { subject_id: 0, grade_id: 0, tag_ids: [] };
			$('.hpt-filter-chip').removeClass('active');
			$overlay.find('.hpt-filter-clear').hide();
			loadTips();
		});

		$overlay.find('.hpt-nav-prev').on('click', function() { prevTip(); });
		$overlay.find('.hpt-nav-next').on('click', function() { nextTip(); });

		$(document).on('click', '.hpt-tip-like', function() {
			var $btn = $(this);
			var tipId = $btn.data('tip-id');
			if (!tipId || $btn.hasClass('hpt-like-loading')) return;
			$btn.addClass('hpt-like-loading');
			$.ajax({
				url: (window.hptBubble && window.hptBubble.restUrl) ? window.hptBubble.restUrl + 'tips/' + tipId + '/like' : '/wp-json/hpt/v1/tips/' + tipId + '/like',
				method: 'POST',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', (window.hptBubble && window.hptBubble.nonce) || '');
				}
			}).done(function(res) {
				$btn.find('.hpt-like-count').text(res.like_count);
				$btn.toggleClass('liked', res.user_has_liked);
				$btn.find('.hpt-like-icon').text(res.user_has_liked ? '♥' : '♡');
				var idx = tips.findIndex(function(t) { return t.id == tipId; });
				if (idx >= 0) {
					tips[idx].like_count = res.like_count;
					tips[idx].user_has_liked = res.user_has_liked;
				}
			}).always(function() {
				$btn.removeClass('hpt-like-loading');
			});
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
			$overlay.find('.hpt-filter-clear').toggle(filters.subject_id > 0 || filters.grade_id > 0 || filters.tag_ids.length > 0);
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
		var loggedIn = (window.hptBubble && window.hptBubble.loggedIn);
		var nonce = loggedIn ? ((window.hptBubble && window.hptBubble.nonce) || '') : '';
		if (nonce) url += '&_wpnonce=' + encodeURIComponent(nonce);

		$.ajax({
			url: url,
			method: 'GET',
			beforeSend: function(xhr) { if (nonce) xhr.setRequestHeader('X-WP-Nonce', nonce); },
			credentials: 'same-origin'
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
		var $footer = $('.hpt-tip-footer');
		var $credit = $footer.find('.hpt-tip-credit');
		var $edit = $footer.find('.hpt-tip-edit');
		var $like = $footer.find('.hpt-tip-like');

		$media.empty();
		if (tip.emoji) {
			$media.html('<span>' + escapeHtml(tip.emoji) + '</span>');
		} else if (tip.image_url) {
			$media.html('<img src="' + escapeHtml(tip.image_url) + '" alt="">');
		}

		$body.html(tip.content || '');
		$credit.text(tip.credit ? 'מאת: ' + tip.credit : '');
		var editUrl = tip.edit_url;
		if (!editUrl && (window.hptBubble && window.hptBubble.canEdit) && window.hptBubble.editBaseUrl && tip.id) {
			editUrl = window.hptBubble.editBaseUrl + '?post=' + tip.id + '&action=edit';
		}
		if (editUrl) {
			$edit.attr('href', editUrl).attr('target', '_blank').show();
		} else {
			$edit.hide();
		}
		$like.data('tip-id', tip.id).find('.hpt-like-count').text(tip.like_count || 0);
		$like.toggleClass('liked', tip.user_has_liked || false);
		$like.find('.hpt-like-icon').text((tip.user_has_liked ? '♥' : '♡'));
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

		// Emoji picker - opens emoji keyboard
		var commonEmojis = ['💡', '📚', '✏️', '🎯', '🌟', '👍', '💪', '🧠', '❤️', '🎨', '🔬', '📖', '✨', '🌈', '🎓'];
		$form.find('.hpt-form-emoji-pick').off('click').on('click', function() {
			var $preview = $form.find('.hpt-form-symbol-preview');
			var $pop = $('.hpt-popover-emoji');
			$('#hpt-form-image-id').val(0);
			if ($pop.length) {
				$pop.toggle();
				return;
			}
			$pop = $('<div class="hpt-popover-emoji"></div>');
			commonEmojis.forEach(function(emoji) {
				$pop.append($('<button type="button" class="hpt-emoji-btn">').text(emoji));
			});
			$('body').append($pop);
			var $btn = $(this);
			var pos = $btn.offset();
			$pop.css({ top: pos.top - 120, right: pos.right }).show();
			$pop.on('click', '.hpt-emoji-btn', function() {
				var e = $(this).text();
				$('#hpt-form-emoji').val(e);
				$preview.html('<span class="hpt-symbol-emoji">' + e + '</span>').show();
				$pop.remove();
			});
			$(document).one('click', function() { $pop.remove(); });
		});

		// Image from computer - file input
		$form.find('.hpt-form-upload-image').off('click').on('click', function() {
			$('#hpt-form-file-input').click();
		});
		$('#hpt-form-file-input').off('change').on('change', function() {
			var file = this.files[0];
			if (!file || !file.type.match('image.*')) return;
			var fd = new FormData();
			fd.append('image', file);
			$form.find('.hpt-form-symbol-preview').html('<span class="hpt-uploading">טוען...</span>');
			$.ajax({
				url: (window.hptBubble && window.hptBubble.restUrl) ? window.hptBubble.restUrl + 'upload-image' : '/wp-json/hpt/v1/upload-image',
				method: 'POST',
				data: fd,
				processData: false,
				contentType: false,
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', (window.hptBubble && window.hptBubble.nonce) || '');
				}
			}).done(function(res) {
				$('#hpt-form-image-id').val(res.id);
				$('#hpt-form-emoji').val('');
				$form.find('.hpt-form-symbol-preview').html('<img src="' + res.url + '" alt="">').show();
			}).fail(function() {
				$form.find('.hpt-form-symbol-preview').html('<span class="hpt-upload-err">שגיאה</span>');
			});
			this.value = '';
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

		// Format toolbar: Bold, Link
		$form.find('.hpt-format-btn').off('click').on('click', function(e) {
			e.preventDefault();
			var cmd = $(this).data('cmd');
			$('#hpt-form-content').focus();
			if (cmd === 'createLink') {
				var url = prompt('הזן כתובת קישור:', 'https://');
				if (url && url !== 'https://') document.execCommand('createLink', false, url);
			} else {
				document.execCommand(cmd, false, null);
			}
		});

		// Paste: preserve HTML (bold, links)
		$form.find('#hpt-form-content').off('paste').on('paste', function(e) {
			e.preventDefault();
			var html = (e.originalEvent.clipboardData || window.clipboardData).getData('text/html');
			var text = (e.originalEvent.clipboardData || window.clipboardData).getData('text/plain');
			document.execCommand('insertHTML', false, html || text);
		});

		$form.off('submit').on('submit', function(e) {
			e.preventDefault();
			var $msg = $form.find('.hpt-form-message');
			$msg.hide();
			var $editable = $('#hpt-form-content');
			var content = $editable.html().trim();
			var tempDiv = document.createElement('div');
			tempDiv.innerHTML = content;
			var text = (tempDiv.textContent || tempDiv.innerText || '').replace(/\s+/g, ' ').trim();
			if (!text) {
				$msg.removeClass('success').addClass('error').text('התוכן חובה').show();
				return;
			}
			var emoji = $('#hpt-form-emoji').val();
			var imageId = parseInt($('#hpt-form-image-id').val(), 10) || 0;
			var data = {
				content: content,
				credit: $('#hpt-form-credit').val().trim(),
				media_type: imageId ? 'image' : 'emoji',
				emoji: emoji,
				image_id: imageId,
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
				$('#hpt-form-content').empty();
				$('#hpt-form-image-id').val(0);
				$('#hpt-form-emoji').val('');
				$form.find('.hpt-form-symbol-preview').empty();
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
