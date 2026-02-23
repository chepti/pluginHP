/**
 * Group Folders – תיקיות להגשת פוסטים בתוך קבוצה
 */
(function($) {
    'use strict';

    const nonce = typeof wp !== 'undefined' && wp.apiFetch ? null : (window.hpg_group_folders_nonce || '');

    // יצירת תיקייה
    $(document).on('click', '.hpg-group-folder-create-btn', function() {
        const $btn = $(this);
        const groupId = $btn.data('group-id');
        const $input = $btn.siblings('.hpg-group-folder-name-input');
        const name = $input.val().trim();

        if (!name) {
            alert('יש להזין שם לתיקייה.');
            return;
        }

        $btn.prop('disabled', true).text('יוצר...');

        $.post(window.hpg_group_folders_globals.ajax_url, {
            action: 'hpg_create_group_folder',
            nonce: window.hpg_group_folders_globals.nonce,
            group_id: groupId,
            name: name
        }).done(function(res) {
            if (res.success && res.data && res.data.folder) {
                const f = res.data.folder;
                const $grid = $('.hpg-group-folders-grid');
                const $empty = $('.hpg-group-folders-empty');
                if ($grid.length) {
                    const isMember = res.data.is_member === true || $('.hpg-group-folder-add-btn').length > 0;
                    const isMod = $('.hpg-group-folder-delete-btn').length > 0;
                    const baseUrl = (window.hpg_group_folders_globals && window.hpg_group_folders_globals.group_permalink) ? window.hpg_group_folders_globals.group_permalink : (window.location.pathname.replace(/\/$/, '') + '/');
                    const filterUrl = baseUrl + (baseUrl.indexOf('?') !== -1 ? '&' : '?') + 'hpg_folder=' + encodeURIComponent(f.id);
                    let card = '<div class="hpg-group-folder-card" data-folder-id="' + f.id + '" data-group-id="' + groupId + '">';
                    card += '<a href="' + filterUrl + '" class="hpg-group-folder-name hpg-folder-card-filter-link" title="הצג פוסטים בתיקייה זו">' + escapeHtml(f.name) + '</a>';
                    if (isMember) {
                        card += '<a href="#" class="hpg-group-folder-add-btn hpg-open-popup-button" data-folder-id="' + f.id + '" data-group-id="' + groupId + '" title="הוסף פוסט לתיקייה">';
                        card += '<span class="hpg-folder-plus">+</span></a>';
                    }
                    if (isMod) {
                        card += '<button type="button" class="hpg-group-folder-delete-btn" data-folder-id="' + f.id + '" data-group-id="' + groupId + '" title="מחק תיקייה">&times;</button>';
                    }
                    card += '</div>';
                    $grid.append(card);
                }
                $input.val('');
                $empty.hide();
            } else {
                alert(res.data && res.data.message ? res.data.message : 'שגיאה ביצירת התיקייה.');
            }
        }).fail(function() {
            alert('שגיאה בתקשורת.');
        }).always(function() {
            $btn.prop('disabled', false).text('יצירת תיקייה');
        });
    });

    // מחיקת תיקייה
    $(document).on('click', '.hpg-group-folder-delete-btn', function() {
        if (!confirm('למחוק את התיקייה? פוסטים שמוגשו אליה לא יימחקו.')) {
            return;
        }
        const $btn = $(this);
        const folderId = $btn.data('folder-id');
        const groupId = $btn.data('group-id');

        $btn.prop('disabled', true);

        $.post(window.hpg_group_folders_globals.ajax_url, {
            action: 'hpg_delete_group_folder',
            nonce: window.hpg_group_folders_globals.nonce,
            group_id: groupId,
            folder_id: folderId
        }).done(function(res) {
            if (res.success) {
                $btn.closest('.hpg-group-folder-card').fadeOut(200, function() {
                    $(this).remove();
                    if ($('.hpg-group-folder-card').length === 0) {
                        $('.hpg-group-folders-empty').show();
                    }
                });
            } else {
                alert(res.data && res.data.message ? res.data.message : 'שגיאה במחיקה.');
                $btn.prop('disabled', false);
            }
        }).fail(function() {
            alert('שגיאה בתקשורת.');
            $btn.prop('disabled', false);
        });
    });

    // כשנפתח הטופס מהכפתור הרגיל (לא מתיקייה) – נקה את שדה התיקייה
    $(document).on('click', 'a.hpg-open-popup-button:not(.hpg-group-folder-add-btn), a#hpg-open-popup-button', function() {
        $('#hpg_group_folder_id').val('');
    });

    // לחיצה על + – פתיחת טופס הגשה עם folder_id
    $(document).on('click', '.hpg-group-folder-add-btn', function(e) {
        e.preventDefault();
        const folderId = $(this).data('folder-id');
        const groupId = $(this).data('group-id');

        let $input = $('#hpg_group_folder_id');
        if ($input.length === 0) {
            $input = $('<input type="hidden" id="hpg_group_folder_id" name="hpg_group_folder_id" value="">');
            $('#hpg-submission-form').append($input);
        }
        $input.val(folderId);

        const $overlay = $('#hpg-popup-overlay');
        if ($overlay.length) {
            $overlay.removeClass('hpg-popup-hidden').addClass('hpg-popup-visible');
            $('body').addClass('hpg-popup-active');
        }
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})(jQuery);
