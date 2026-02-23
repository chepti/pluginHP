/**
 * Homer Patuach - הוספת חברים לקבוצה
 * חיפוש משתמש בודד או ייבוא רשימת אימיילים
 */
(function($) {
    'use strict';

    if (window.hpAddMembersInitDone) {
        return;
    }

    var searchTimeout;
    var $box = $('#hp-add-members-box');
    if (!$box.length) return;
    window.hpAddMembersInitDone = true;

    var groupId = parseInt($('#hp-add-members-group-id').val(), 10) || 0;
    var nonce = $('#hp-add-members-nonce').val() || '';
    var ajaxUrl = $('#hp-add-members-ajax-url').val() ||
        (typeof hpAddMembers !== 'undefined' && hpAddMembers.ajax_url) ||
        (typeof ajaxurl !== 'undefined' ? ajaxurl : '') ||
        '';
    if (!ajaxUrl) {
        $('#hp-member-search-status').addClass('error').text('שגיאה בהפעלת הסקריפט. רענן את הדף.');
        return;
    }

    // העברת התיבה מתחת לתפריט הניהול – למניעת מיקום מבלבל
    var $contentStart = $('#buddypress h2.bp-screen-title').filter(function() {
        var t = $(this).text();
        return t.indexOf('Manage') !== -1 || t.indexOf('ניהול') !== -1;
    }).first();
    if ($contentStart.length) {
        $contentStart.parent().prepend($box);
    }

    // טאבים
    $box.on('click', '.hp-add-members-tab', function() {
        var tab = $(this).data('tab');
        $box.find('.hp-add-members-tab').removeClass('active');
        $box.find('.hp-add-members-tab[data-tab="' + tab + '"]').addClass('active');
        $box.find('.hp-add-members-tab-content').removeClass('active');
        $box.find('.hp-add-members-tab-content[data-tab-content="' + tab + '"]').addClass('active');
    });

    // חיפוש משתמשים
    $('#hp-member-search').on('input', function() {
        var val = $(this).val().trim();
        var $results = $('#hp-member-search-results');
        var $status = $('#hp-member-search-status');

        clearTimeout(searchTimeout);
        $results.empty();
        $status.removeClass('error success').text('');

        if (val.length < 2) {
            return;
        }

        searchTimeout = setTimeout(function() {
            $.post(ajaxUrl, {
                action: 'hp_add_group_members_search',
                nonce: nonce,
                group_id: groupId,
                search: val
            })
            .done(function(res) {
                if (res.success && res.data && res.data.users && res.data.users.length) {
                    var html = '<ul class="hp-member-list">';
                    res.data.users.forEach(function(u) {
                        var memberClass = u.is_member ? 'hp-is-member' : '';
                        var memberLabel = u.is_member ? ' (כבר בקבוצה)' : '';
                        html += '<li class="' + memberClass + '" data-user-id="' + u.id + '">';
                        html += '<span class="hp-user-name">' + (u.name || u.login) + '</span>';
                        html += ' <span class="hp-user-email">' + u.email + '</span>' + memberLabel;
                        if (!u.is_member) {
                            html += ' <button type="button" class="button button-small hp-add-one-btn">הוסף</button>';
                        }
                        html += '</li>';
                    });
                    html += '</ul>';
                    $results.html(html);
                } else {
                    $results.html('<p class="hp-no-results">לא נמצאו משתמשים.</p>');
                }
            })
            .fail(function() {
                $status.addClass('error').text('שגיאה בחיפוש.');
            });
        }, 300);
    });

    // הוספת משתמש בודד
    $box.on('click', '.hp-add-one-btn', function() {
        var $btn = $(this);
        var $li = $btn.closest('li');
        var userId = $li.data('user-id');
        var $status = $('#hp-member-search-status');

        if (!userId) return;
        $btn.prop('disabled', true).text('...');

        $.post(ajaxUrl, {
            action: 'hp_add_group_members_add_one',
            nonce: nonce,
            group_id: groupId,
            user_id: userId
        })
        .done(function(res) {
            if (res.success) {
                $status.removeClass('error').addClass('success').text(res.data.message || 'נוסף בהצלחה');
                $li.addClass('hp-is-member').find('.hp-add-one-btn').remove();
            } else {
                $status.removeClass('success').addClass('error').text(res.data && res.data.message ? res.data.message : 'שגיאה');
                $btn.prop('disabled', false).text('הוסף');
            }
        })
        .fail(function() {
            $status.removeClass('success').addClass('error').text('שגיאה בבקשה.');
            $btn.prop('disabled', false).text('הוסף');
        });
    });

    // ייבוא אימיילים
    $('#hp-import-emails-btn').on('click', function() {
        var $btn = $(this);
        var emails = $('#hp-emails-list').val().trim();
        var $status = $('#hp-import-status');

        if (!emails) {
            $status.removeClass('success').addClass('error').text('הזן לפחות אימייל אחד.');
            return;
        }

        $btn.prop('disabled', true);
        $status.removeClass('error success').text('מעבד...');

        $.post(ajaxUrl, {
            action: 'hp_add_group_members_import',
            nonce: nonce,
            group_id: groupId,
            emails: emails
        })
        .done(function(res) {
            if (res.success) {
                $status.removeClass('error').addClass('success').text(res.data.message || 'הושלם');
                $('#hp-emails-list').val('');
            } else {
                $status.removeClass('success').addClass('error').text(res.data && res.data.message ? res.data.message : 'שגיאה');
            }
            $btn.prop('disabled', false);
        })
        .fail(function() {
            $status.removeClass('success').addClass('error').text('שגיאה בבקשה.');
            $btn.prop('disabled', false);
        });
    });

})(jQuery);
