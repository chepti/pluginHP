/**
 * כפתור פעמון לאישור פוסטים ממתינים של חברי קבוצה.
 * פותח חלונית מודל עם פוסטים ממתינים (pending) של חברי הקבוצה בלבד.
 */
jQuery(document).ready(function ($) {
    const globals = window.hp_bp_tweaks_group_bell_globals;
    if (!globals) {
        return;
    }

    const groupId = globals.group_id;
    const groupName = globals.group_name || '';

    function getModalTitle() {
        return 'פוסטים הממתינים לאישור' + (groupName ? ' ' + groupName : ' (חברי הקבוצה)');
    }

    // מודל – מזהה ייחודי כדי לא להתנגש עם admin-bell
    const modalHTML = `
        <div id="hpg-group-pending-modal" class="hpg-modal-overlay">
            <div class="hpg-modal-container">
                <div class="hpg-modal-header">
                    <h2>` + getModalTitle() + `</h2>
                    <button class="hpg-modal-close-btn" type="button">&times;</button>
                </div>
                <div class="hpg-modal-body">
                    <div class="hpg-modal-loader"><div class="spinner"></div></div>
                </div>
            </div>
        </div>
    `;
    $('body').append(modalHTML);

    // מודל שליחת מייל (מבודד מזה של admin-bell)
    const emailModalHTML = `
        <div class="hpg-email-to-author-overlay" id="hpg-group-email-to-author-overlay">
            <div class="hpg-email-to-author-box">
                <div class="hpg-email-to-author-header">
                    <h3>שליחת מייל לתורם</h3>
                    <button type="button" class="hpg-email-to-author-close">&times;</button>
                </div>
                <div class="hpg-email-to-author-body">
                    <textarea class="hpg-email-to-author-text" rows="4" placeholder="הודעה (אופציונלי). קישור לפוסט יתווסף אוטומטית."></textarea>
                    <div class="hpg-email-to-author-actions">
                        <button type="button" class="hpg-email-to-author-send">שלח</button>
                        <button type="button" class="hpg-email-to-author-cancel">ביטול</button>
                    </div>
                </div>
                <div class="hpg-email-to-author-status"></div>
            </div>
        </div>
    `;
    $('body').append(emailModalHTML);

    const modalOverlay = $('#hpg-group-pending-modal');
    const modalBody = modalOverlay.find('.hpg-modal-body');
    const closeBtn = modalOverlay.find('.hpg-modal-close-btn');

    const emailOverlay = $('#hpg-group-email-to-author-overlay');
    const emailBox = emailOverlay.find('.hpg-email-to-author-box');
    const emailText = emailOverlay.find('.hpg-email-to-author-text');
    const emailSendBtn = emailOverlay.find('.hpg-email-to-author-send');
    const emailCancelBtn = emailOverlay.find('.hpg-email-to-author-cancel');
    const emailCloseBtn = emailOverlay.find('.hpg-email-to-author-close');
    const emailStatus = emailOverlay.find('.hpg-email-to-author-status');

    // לחיצה על פעמון הקבוצה – פתיחת חלונית וטעינת פוסטים ממתינים
    $('body').on('click', '.hpg-group-approval-bell', function (e) {
        e.preventDefault();
        e.stopPropagation();
        modalOverlay.find('.hpg-modal-header h2').text(getModalTitle());
        modalOverlay.addClass('hpg-modal-visible');
        fetchGroupPendingPosts();
    });

    closeBtn.on('click', function () {
        modalOverlay.removeClass('hpg-modal-visible');
    });

    modalOverlay.on('click', function (e) {
        if ($(e.target).is(modalOverlay)) {
            modalOverlay.removeClass('hpg-modal-visible');
        }
    });

    // האזנה לכפתורי אשר/טיוטה/מחק בכרטיסים (מאותו מבנה כ-admin-bell)
    modalBody.on('click', '.hpg-button-approve', function (e) {
        e.preventDefault();
        approvePost($(this).data('post-id'), $(this));
    });
    modalBody.on('click', '.hpg-button-draft', function (e) {
        e.preventDefault();
        draftPost($(this).data('post-id'), $(this));
    });
    modalBody.on('click', '.hpg-button-delete', function (e) {
        e.preventDefault();
        deletePost($(this).data('post-id'), $(this));
    });

    // שליחת מייל לתורם – פתיחת מודל ושליחה
    modalBody.on('click', '.hpg-button-email-to-author', function (e) {
        e.preventDefault();
        var postId = $(this).data('post-id');
        if (!postId) return;
        emailText.val('');
        emailStatus.text('').removeClass('hpg-success hpg-error');
        emailBox.data('post-id', postId);
        emailOverlay.addClass('hpg-email-visible');
        emailText.focus();
    });

    function closeGroupEmailModal() {
        emailOverlay.removeClass('hpg-email-visible');
        emailText.val('');
        emailStatus.text('').removeClass('hpg-success hpg-error');
    }
    emailCloseBtn.on('click', closeGroupEmailModal);
    emailCancelBtn.on('click', closeGroupEmailModal);
    emailOverlay.on('click', function (e) {
        if (e.target === emailOverlay[0]) closeGroupEmailModal();
    });
    emailSendBtn.on('click', function () {
        var postId = emailBox.data('post-id');
        if (!postId) return;
        var message = emailText.val().trim();
        emailSendBtn.prop('disabled', true);
        emailStatus.text('שולח...').removeClass('hpg-success hpg-error');
        $.ajax({
            url: globals.ajax_url,
            type: 'POST',
            data: {
                action: 'hpg_send_custom_email_to_author',
                nonce: globals.admin_nonce,
                post_id: postId,
                message: message
            },
            success: function (res) {
                if (res.success) {
                    emailStatus.text((res.data && res.data.message) ? res.data.message : 'המייל נשלח בהצלחה.').addClass('hpg-success');
                    setTimeout(closeGroupEmailModal, 1200);
                } else {
                    emailStatus.text((res.data && res.data.message) || 'שגיאה בשליחה.').addClass('hpg-error');
                }
            },
            error: function () {
                emailStatus.text('שגיאת רשת. נסה שוב.').addClass('hpg-error');
            },
            complete: function () {
                emailSendBtn.prop('disabled', false);
            }
        });
    });

    function fetchGroupPendingPosts() {
        modalBody.html('<div class="hpg-modal-loader"><div class="spinner"></div></div>');
        $.ajax({
            url: globals.ajax_url,
            type: 'POST',
            data: {
                action: 'hp_bp_tweaks_get_group_pending_posts',
                nonce: globals.nonce,
                group_id: groupId
            },
            success: function (res) {
                if (res.success && res.data && res.data.length > 0) {
                    const grid = $('<div class="hpg-pending-grid hpg-results-grid-wrapper"></div>');
                    res.data.forEach(function (p) {
                        grid.append(p.html || '');
                    });
                    modalBody.html(grid);
                } else if (res.success) {
                    modalBody.html('<div class="hpg-modal-message hpg-success-message">אין פוסטים שממתינים לאישור. כל הכבוד!</div>');
                } else {
                    modalBody.html('<div class="hpg-modal-message hpg-error-message">שגיאה: לא ניתן היה לטעון את הפוסטים.</div>');
                }
            },
            error: function () {
                modalBody.html('<div class="hpg-modal-message hpg-error-message">שגיאת רשת. נסה שוב מאוחר יותר.</div>');
            }
        });
    }

    function approvePost(postId, button) {
        var card = button.closest('.hpg-pending-card');
        card.addClass('approving');
        button.addClass('loading').html('<svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 24 24" width="18" fill="currentColor"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>מאשר...');
        $.ajax({
            url: globals.ajax_url,
            type: 'POST',
            data: { action: 'hpg_approve_post', nonce: globals.admin_nonce, post_id: postId },
            success: function (r) {
                if (r.success) {
                    card.fadeOut(400, function () {
                        $(this).remove();
                        updateGroupPendingCount(-1);
                        if (!modalBody.find('.hpg-pending-card').length) {
                            modalBody.html('<div class="hpg-modal-message hpg-success-message">כל הפוסטים אושרו!</div>');
                        }
                    });
                } else {
                    alert('שגיאה באישור: ' + (r.data || ''));
                    card.removeClass('approving');
                    button.removeClass('loading').html('<svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 24 24" width="18" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>אשר');
                }
            },
            error: function () {
                alert('שגיאת רשת.');
                card.removeClass('approving');
                button.removeClass('loading').html('<svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 24 24" width="18" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>אשר');
            }
        });
    }

    function draftPost(postId, button) {
        var card = button.closest('.hpg-pending-card');
        card.addClass('approving');
        button.addClass('loading').text('מעביר...');
        $.ajax({
            url: globals.ajax_url,
            type: 'POST',
            data: { action: 'hpg_draft_post', nonce: globals.admin_nonce, post_id: postId },
            success: function (r) {
                if (r.success) {
                    card.fadeOut(400, function () {
                        $(this).remove();
                        updateGroupPendingCount(-1);
                        if (!modalBody.find('.hpg-pending-card').length) {
                            modalBody.html('<div class="hpg-modal-message hpg-success-message">כל הפוסטים טופלו!</div>');
                        }
                    });
                } else {
                    alert('שגיאה: ' + (r.data || ''));
                    card.removeClass('approving');
                    button.removeClass('loading').text('טיוטה');
                }
            },
            error: function () {
                alert('שגיאת רשת.');
                card.removeClass('approving');
                button.removeClass('loading').text('טיוטה');
            }
        });
    }

    function deletePost(postId, button) {
        var card = button.closest('.hpg-pending-card');
        card.addClass('approving');
        button.addClass('loading').html('<svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 24 24" width="18" fill="currentColor"><circle cx="12" cy="12" r="10" opacity="0.3"/></svg>');
        $.ajax({
            url: globals.ajax_url,
            type: 'POST',
            data: { action: 'hpg_delete_post', nonce: globals.admin_nonce, post_id: postId },
            success: function (r) {
                if (r.success) {
                    card.fadeOut(400, function () {
                        $(this).remove();
                        updateGroupPendingCount(-1);
                        if (!modalBody.find('.hpg-pending-card').length) {
                            modalBody.html('<div class="hpg-modal-message hpg-success-message">כל הפוסטים טופלו!</div>');
                        }
                    });
                } else {
                    alert('שגיאה: ' + (r.data || ''));
                    card.removeClass('approving');
                    button.removeClass('loading').html('<svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 24 24" width="18" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM8 9h8v10H8V9zm7.5-5l-1-1h-5l-1 1H5v2h14V4z"/></svg>');
                }
            },
            error: function () {
                alert('שגיאת רשת.');
                card.removeClass('approving');
                button.removeClass('loading').html('<svg xmlns="http://www.w3.org/2000/svg" height="18" viewBox="0 0 24 24" width="18" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM8 9h8v10H8V9zm7.5-5l-1-1h-5l-1 1H5v2h14V4z"/></svg>');
            }
        });
    }

    /**
     * עדכון הספירה בפעמון הקבוצה בלבד (ממתינים לאישור).
     * משתמש ב־.hpg-group-pending-count כדי שלא יידרס על־ידי admin-bell.
     */
    function updateGroupPendingCount(delta) {
        var span = $('.hpg-group-pending-count');
        var bell = $('.hpg-group-approval-bell');
        var n = parseInt(span.text() || '0', 10) + delta;
        n = Math.max(0, n);
        span.text(n);
        if (n === 0) {
            bell.removeClass('hpg-has-pending');
            span.hide();
        } else {
            bell.addClass('hpg-has-pending');
            span.show();
        }
    }
});
