(function($) {
	'use strict';

	var mediaUploader;

	function init() {
		// Media type toggle
		$('input[name="hpt_has_media_type"]').on('change', function() {
			var type = $(this).val();
			$('.hpt-emoji-field').toggle(type === 'emoji');
			$('.hpt-image-field').toggle(type === 'image');
		});

		// Image upload
		$('.hpt-upload-image').on('click', function(e) {
			e.preventDefault();
			if (mediaUploader) {
				mediaUploader.open();
				return;
			}
			mediaUploader = wp.media({
				title: 'בחר תמונה',
				button: { text: 'בחר' },
				library: { type: 'image' },
				multiple: false
			});
			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				$('#hpt_image_id').val(attachment.id);
				$('.hpt-image-preview').html('<img src="' + attachment.url + '" alt="">');
				$('.hpt-remove-image').show();
			});
			mediaUploader.open();
		});

		$('.hpt-remove-image').on('click', function(e) {
			e.preventDefault();
			$('#hpt_image_id').val('');
			$('.hpt-image-preview').empty();
			$(this).hide();
		});

		// Emoji picker
		initEmojiPicker();
	}

	function initEmojiPicker() {
		var $input = $('#hpt_emoji');
		var $trigger = $('.hpt-emoji-picker-trigger');
		if (!$input.length || !$trigger.length) return;

		var commonEmojis = ['💡', '📚', '✏️', '🎯', '🌟', '👍', '💪', '🧠', '❤️', '🎨', '🔬', '📖', '✨', '🌈', '🎓'];
		var $picker = $('<div class="hpt-emoji-picker-popover" style="display:none; position:absolute; background:#fff; border:1px solid #e9ebee; border-radius:8px; padding:8px; box-shadow:0 5px 20px rgba(0,0,0,0.2); z-index:100000;"></div>');
		commonEmojis.forEach(function(emoji) {
			$picker.append($('<button type="button" class="hpt-emoji-btn" style="font-size:24px; padding:4px; margin:2px; border:none; background:none; cursor:pointer;">').text(emoji));
		});
		$('body').append($picker);

		$trigger.on('click', function(e) {
			e.preventDefault();
			if ($picker.is(':visible')) {
				$picker.hide();
				return;
			}
			var offset = $trigger.offset();
			$picker.css({ top: offset.top - 120, right: offset.right }).show();
		});

		$picker.on('click', '.hpt-emoji-btn', function() {
			$input.val($(this).text()).trigger('change');
			$picker.hide();
		});

		$(document).on('click', function(e) {
			if (!$(e.target).closest('.hpt-emoji-picker-trigger, .hpt-emoji-picker-popover').length) {
				$picker.hide();
			}
		});
	}

	$(document).ready(init);
})(jQuery);
