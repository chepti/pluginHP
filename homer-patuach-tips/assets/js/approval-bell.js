(function($) {
	'use strict';

	var restUrl = (window.hptApproval && window.hptApproval.restUrl) ? window.hptApproval.restUrl : '/wp-json/hpt/v1/';
	var nonce = (window.hptApproval && window.hptApproval.nonce) || (window.wpApiSettings && window.wpApiSettings.nonce) || '';

	function init() {
		$(document).on('click', '.hpt-approval-bell[data-hpt-modal]', function(e) {
			e.preventDefault();
			openModal();
		});

		$('#hpt-approval-modal .hpt-approval-modal-close, #hpt-approval-modal .hpt-approval-modal-backdrop').on('click', function() {
			closeModal();
		});

		$(document).on('click', '.hpt-approval-tip-approve', function() {
			var $btn = $(this);
			var tipId = $btn.data('tip-id');
			if (!tipId || $btn.hasClass('hpt-approving')) return;
			$btn.addClass('hpt-approving').text('...');
			$.ajax({
				url: restUrl + 'tips/' + tipId + '/approve',
				method: 'POST',
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); }
			}).done(function() {
				$btn.closest('.hpt-approval-tip-item').fadeOut(200, function() { $(this).remove(); });
				updateBellCount();
			}).fail(function() {
				$btn.removeClass('hpt-approving').text('אישור');
			});
		});
	}

	function openModal() {
		var $modal = $('#hpt-approval-modal');
		$modal.removeAttr('hidden').attr('aria-hidden', 'false');
		$modal.find('.hpt-approval-modal-loading').show();
		$modal.find('.hpt-approval-modal-list').empty().hide();
		$modal.find('.hpt-approval-modal-empty').hide();

		var url = restUrl + 'tips/pending';
		if (nonce) url += (url.indexOf('?') >= 0 ? '&' : '?') + '_wpnonce=' + encodeURIComponent(nonce);
		$.ajax({
			url: url,
			method: 'GET',
			beforeSend: function(xhr) { if (nonce) xhr.setRequestHeader('X-WP-Nonce', nonce); },
			credentials: 'same-origin'
		}).done(function(res) {
				$modal.find('.hpt-approval-modal-loading').hide();
				var tips = res.tips || [];
				if (tips.length === 0) {
					$modal.find('.hpt-approval-modal-empty').show();
					return;
				}
				var $list = $modal.find('.hpt-approval-modal-list');
				tips.forEach(function(tip) {
					var html = '<div class="hpt-approval-tip-item" data-tip-id="' + tip.id + '">';
					html += '<div class="hpt-approval-tip-body">' + (tip.content || '') + '</div>';
					html += '<div class="hpt-approval-tip-meta">';
					if (tip.credit) html += '<span class="hpt-approval-tip-credit">' + escapeHtml('מאת: ' + tip.credit) + '</span>';
					html += '<a href="' + escapeHtml(tip.edit_url || '#') + '" class="hpt-approval-tip-edit" target="_blank">✎</a>';
					html += '<button type="button" class="hpt-approval-tip-approve" data-tip-id="' + tip.id + '">אישור</button>';
					html += '</div></div>';
					$list.append(html);
				});
				$list.show();
			})
			.fail(function() {
				$modal.find('.hpt-approval-modal-loading').text('שגיאה בטעינה').show();
			});
	}

	function closeModal() {
		$('#hpt-approval-modal').attr('hidden', 'hidden').attr('aria-hidden', 'true');
	}

	function updateBellCount() {
		var $list = $('#hpt-approval-modal .hpt-approval-tip-item');
		var count = $list.length;
		var $bell = $('.hpt-approval-bell');
		var $badge = $bell.find('.hpt-pending-count');
		if (count === 0) {
			$badge.remove();
			$bell.removeClass('hpt-bell-has-pending');
			$('#hpt-approval-modal .hpt-approval-modal-list').hide();
			$('#hpt-approval-modal .hpt-approval-modal-empty').show();
		} else {
			if ($badge.length) $badge.text(count);
			else $bell.append('<span class="hpt-pending-count">' + count + '</span>');
		}
	}

	function escapeHtml(text) {
		if (!text) return '';
		var div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	$(document).ready(init);
})(jQuery);
