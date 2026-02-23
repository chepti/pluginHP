<?php
/**
 * הוספת חברים ישירות לקבוצה – חיפוש משתמש בודד או ייבוא רשימת אימיילים
 *
 * @package Homer_Patuach_BP_Tweaks
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * בודק אם למשתמש הנוכחי יש הרשאה להוסיף חברים לקבוצה (מנהל או מודרטור)
 */
function hp_bp_tweaks_can_add_group_members( $group_id = 0 ) {
    if ( ! $group_id && function_exists( 'bp_get_current_group_id' ) ) {
        $group_id = bp_get_current_group_id();
    }
    if ( ! $group_id ) {
        return false;
    }
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return false;
    }
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }
    if ( function_exists( 'groups_is_user_admin' ) && groups_is_user_admin( $user_id, $group_id ) ) {
        return true;
    }
    if ( function_exists( 'groups_is_user_mod' ) && groups_is_user_mod( $user_id, $group_id ) ) {
        return true;
    }
    return false;
}

/**
 * מציג את טופס הוספת חברים בעמוד ניהול החברים
 */
function hp_bp_tweaks_enqueue_add_members_assets() {
    static $enqueued = false;
    if ( $enqueued ) {
        return;
    }

    wp_enqueue_script(
        'hp-add-group-members',
        HP_BP_TWEAKS_PLUGIN_DIR_URL . 'assets/js/group-add-members.js',
        [ 'jquery' ],
        HP_BP_TWEAKS_VERSION . '-' . time(),
        true
    );

    wp_localize_script( 'hp-add-group-members', 'hpAddMembers', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'hp_add_group_members' ),
    ] );

    $enqueued = true;
}

function hp_bp_tweaks_render_add_members_box() {
    if ( ! function_exists( 'bp_get_current_group_id' ) || ! bp_get_current_group_id() ) {
        return;
    }
    $group_id = bp_get_current_group_id();
    if ( ! hp_bp_tweaks_can_add_group_members( $group_id ) ) {
        return;
    }
    hp_bp_tweaks_enqueue_add_members_assets();
    $nonce = wp_create_nonce( 'hp_add_group_members' );
    ?>
    <div class="hp-add-members-box bp-widget" id="hp-add-members-box">
        <h4 class="hp-add-members-title">הוסף חברים לקבוצה</h4>
        <p class="hp-add-members-desc">הוסף משתמש רשום בודד או ייבא רשימת אימיילים (כתובת אחת בשורה).</p>

        <div class="hp-add-members-tabs">
            <button type="button" class="hp-add-members-tab active" data-tab="single">הוסף חבר בודד</button>
            <button type="button" class="hp-add-members-tab" data-tab="import">ייבוא רשימת אימיילים</button>
        </div>

        <div class="hp-add-members-tab-content active" data-tab-content="single">
            <div class="hp-add-single-member">
                <label for="hp-member-search">חפש משתמש (שם משתמש או אימייל):</label>
                <input type="text" id="hp-member-search" class="hp-member-search-input" placeholder="הקלד כדי לחפש..." autocomplete="off" />
                <div id="hp-member-search-results" class="hp-member-search-results"></div>
                <div id="hp-member-search-status" class="hp-member-search-status"></div>
            </div>
        </div>

        <div class="hp-add-members-tab-content" data-tab-content="import">
            <div class="hp-import-emails">
                <label for="hp-emails-list">רשימת אימיילים (כתובת אחת בשורה):</label>
                <textarea id="hp-emails-list" class="hp-emails-textarea" rows="6" placeholder="user1@example.com&#10;user2@example.com&#10;user3@example.com"></textarea>
                <button type="button" id="hp-import-emails-btn" class="button button-primary">יבא והוסף לקבוצה</button>
                <div id="hp-import-status" class="hp-import-status"></div>
            </div>
        </div>

        <input type="hidden" id="hp-add-members-group-id" value="<?php echo (int) $group_id; ?>" />
        <input type="hidden" id="hp-add-members-nonce" value="<?php echo esc_attr( $nonce ); ?>" />
        <input type="hidden" id="hp-add-members-ajax-url" value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" />
    </div>
    <script>
    (function() {
        if (window.hpAddMembersInitDone) return;
        var box = document.getElementById('hp-add-members-box');
        if (!box) return;

        var groupIdInput = document.getElementById('hp-add-members-group-id');
        var nonceInput = document.getElementById('hp-add-members-nonce');
        var ajaxUrlInput = document.getElementById('hp-add-members-ajax-url');
        var groupId = groupIdInput ? parseInt(groupIdInput.value, 10) || 0 : 0;
        var nonce = nonceInput ? nonceInput.value : '';
        var ajaxUrl = ajaxUrlInput ? ajaxUrlInput.value : '';
        if (!ajaxUrl) return;

        var searchInput = document.getElementById('hp-member-search');
        var searchResults = document.getElementById('hp-member-search-results');
        var searchStatus = document.getElementById('hp-member-search-status');
        var importBtn = document.getElementById('hp-import-emails-btn');
        var importStatus = document.getElementById('hp-import-status');
        var emailsField = document.getElementById('hp-emails-list');
        var searchTimeout = null;

        function setStatus(el, message, cls) {
            if (!el) return;
            el.className = (el.className || '').replace(/\berror\b|\bsuccess\b/g, '').trim();
            if (cls) el.className += (el.className ? ' ' : '') + cls;
            el.textContent = message || '';
        }

        function post(data) {
            return fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                credentials: 'same-origin',
                body: new URLSearchParams(data).toString()
            }).then(function(r) { return r.json(); });
        }

        box.addEventListener('click', function(e) {
            var tabBtn = e.target.closest('.hp-add-members-tab');
            if (tabBtn) {
                var tab = tabBtn.getAttribute('data-tab');
                box.querySelectorAll('.hp-add-members-tab').forEach(function(btn) { btn.classList.remove('active'); });
                box.querySelectorAll('.hp-add-members-tab-content').forEach(function(content) { content.classList.remove('active'); });
                tabBtn.classList.add('active');
                var activeContent = box.querySelector('.hp-add-members-tab-content[data-tab-content="' + tab + '"]');
                if (activeContent) activeContent.classList.add('active');
                return;
            }

            var addBtn = e.target.closest('.hp-add-one-btn');
            if (addBtn) {
                var row = addBtn.closest('li');
                var userId = row ? parseInt(row.getAttribute('data-user-id'), 10) || 0 : 0;
                if (!userId) return;
                addBtn.disabled = true;
                addBtn.textContent = '...';
                post({
                    action: 'hp_add_group_members_add_one',
                    nonce: nonce,
                    group_id: groupId,
                    user_id: userId
                }).then(function(res) {
                    if (res && res.success) {
                        setStatus(searchStatus, (res.data && res.data.message) ? res.data.message : 'Added', 'success');
                        if (row) {
                            row.classList.add('hp-is-member');
                            addBtn.remove();
                        }
                    } else {
                        setStatus(searchStatus, (res && res.data && res.data.message) ? res.data.message : 'Error', 'error');
                        addBtn.disabled = false;
                        addBtn.textContent = 'הוסף';
                    }
                }).catch(function() {
                    setStatus(searchStatus, 'Network error', 'error');
                    addBtn.disabled = false;
                    addBtn.textContent = 'הוסף';
                });
                return;
            }

            if (importBtn && (e.target === importBtn || e.target.closest('#hp-import-emails-btn'))) {
                var emails = emailsField ? emailsField.value.trim() : '';
                if (!emails) {
                    setStatus(importStatus, 'יש להזין לפחות אימייל אחד.', 'error');
                    return;
                }
                importBtn.disabled = true;
                setStatus(importStatus, 'מעבד...', '');
                post({
                    action: 'hp_add_group_members_import',
                    nonce: nonce,
                    group_id: groupId,
                    emails: emails
                }).then(function(res) {
                    if (res && res.success) {
                        setStatus(importStatus, (res.data && res.data.message) ? res.data.message : 'Completed', 'success');
                        if (emailsField) emailsField.value = '';
                    } else {
                        setStatus(importStatus, (res && res.data && res.data.message) ? res.data.message : 'Error', 'error');
                    }
                    importBtn.disabled = false;
                }).catch(function() {
                    setStatus(importStatus, 'Network error', 'error');
                    importBtn.disabled = false;
                });
            }
        });

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                var value = (searchInput.value || '').trim();
                if (searchTimeout) clearTimeout(searchTimeout);
                if (searchResults) searchResults.innerHTML = '';
                setStatus(searchStatus, '', '');
                if (value.length < 2) return;

                searchTimeout = setTimeout(function() {
                    post({
                        action: 'hp_add_group_members_search',
                        nonce: nonce,
                        group_id: groupId,
                        search: value
                    }).then(function(res) {
                        if (!searchResults) return;
                        if (res && res.success && res.data && Array.isArray(res.data.users) && res.data.users.length) {
                            var html = '<ul class="hp-member-list">';
                            res.data.users.forEach(function(u) {
                                var isMember = !!u.is_member;
                                html += '<li class="' + (isMember ? 'hp-is-member' : '') + '" data-user-id="' + u.id + '">';
                                html += '<span class="hp-user-name">' + (u.name || u.login || '') + '</span> ';
                                html += '<span class="hp-user-email">' + (u.email || '') + '</span>';
                                if (isMember) html += ' (כבר בקבוצה)';
                                if (!isMember) html += ' <button type="button" class="button button-small hp-add-one-btn">הוסף</button>';
                                html += '</li>';
                            });
                            html += '</ul>';
                            searchResults.innerHTML = html;
                        } else {
                            searchResults.innerHTML = '<p class="hp-no-results">לא נמצאו משתמשים.</p>';
                        }
                    }).catch(function() {
                        setStatus(searchStatus, 'שגיאה בחיפוש.', 'error');
                    });
                }, 300);
            });
        }

        window.hpAddMembersInitDone = true;
    })();
    </script>
    <?php
}
/**
 * רישום ה-hook לפי template pack – Legacy משתמש ב-bp_before_group_manage_members_admin,
 * Nouveau (כולל Astra + BP 14) מזריק דרך bp_template_content ו-bp_before_group_body.
 */
function hp_bp_tweaks_register_add_members_hook() {
    if ( ! function_exists( 'bp_get_theme_package_id' ) ) {
        add_action( 'bp_before_group_manage_members_admin', 'hp_bp_tweaks_render_add_members_box', 5 );
        return;
    }
    $pack = bp_get_theme_package_id();
    if ( 'nouveau' === $pack ) {
        add_action( 'bp_template_content', 'hp_bp_tweaks_render_add_members_box_nouveau', 1 );
        add_action( 'bp_before_group_body', 'hp_bp_tweaks_render_add_members_box_nouveau', 5 );
    } else {
        add_action( 'bp_before_group_manage_members_admin', 'hp_bp_tweaks_render_add_members_box', 5 );
    }
}
add_action( 'bp_groups_setup_nav', 'hp_bp_tweaks_register_add_members_hook', 999 );

/**
 * Nouveau – מזריק את תיבת הוספת החברים רק בעמוד manage-members.
 * משתמש ב-static כדי למנוע כפילות אם שני ה-hooks מפעילים.
 */
function hp_bp_tweaks_render_add_members_box_nouveau() {
    if ( ! function_exists( 'bp_is_group_admin_screen' ) || ! bp_is_group_admin_screen( 'manage-members' ) ) {
        return;
    }
    static $rendered = false;
    if ( $rendered ) {
        return;
    }
    $rendered = true;
    hp_bp_tweaks_render_add_members_box();
}

/**
 * AJAX: חיפוש משתמשים לפי שם משתמש או אימייל
 */
function hp_bp_tweaks_ajax_search_users() {
    check_ajax_referer( 'hp_add_group_members', 'nonce' );
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
    if ( ! $group_id || ! hp_bp_tweaks_can_add_group_members( $group_id ) ) {
        wp_send_json_error( [ 'message' => 'אין הרשאה.' ] );
    }
    if ( strlen( $search ) < 2 ) {
        wp_send_json_error( [ 'message' => 'הקלד לפחות 2 תווים.' ] );
    }
    global $wpdb;
    $like = '%' . $wpdb->esc_like( $search ) . '%';
    $users = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID, user_login, user_email, display_name 
             FROM {$wpdb->users} 
             WHERE user_login LIKE %s OR user_email LIKE %s OR display_name LIKE %s 
             ORDER BY display_name ASC 
             LIMIT 20",
            $like,
            $like,
            $like
        )
    );
    $results = [];
    foreach ( (array) $users as $u ) {
        $is_member = false;
        if ( function_exists( 'groups_is_user_member' ) ) {
            $is_member = groups_is_user_member( (int) $u->ID, $group_id );
        }
        $results[] = [
            'id'         => (int) $u->ID,
            'login'      => $u->user_login,
            'email'      => $u->user_email,
            'name'       => $u->display_name,
            'is_member'  => $is_member,
        ];
    }
    wp_send_json_success( [ 'users' => $results ] );
}
add_action( 'wp_ajax_hp_add_group_members_search', 'hp_bp_tweaks_ajax_search_users' );

/**
 * Add a user to group with support for private/hidden groups.
 */
function hp_bp_tweaks_add_user_to_group( $group_id, $user_id ) {
    $group_id = absint( $group_id );
    $user_id  = absint( $user_id );

    if ( ! $group_id || ! $user_id ) {
        return false;
    }

    if ( function_exists( 'groups_add_member' ) ) {
        return (bool) groups_add_member( $user_id, $group_id );
    }

    if ( function_exists( 'groups_join_group' ) ) {
        return (bool) groups_join_group( $group_id, $user_id );
    }

    return false;
}

/**
 * AJAX: הוספת משתמש בודד לקבוצה
 */
function hp_bp_tweaks_ajax_add_single_member() {
    check_ajax_referer( 'hp_add_group_members', 'nonce' );
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    $user_id  = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
    if ( ! $group_id || ! $user_id || ! hp_bp_tweaks_can_add_group_members( $group_id ) ) {
        wp_send_json_error( [ 'message' => 'אין הרשאה.' ] );
    }
    if ( ! get_userdata( $user_id ) ) {
        wp_send_json_error( [ 'message' => 'משתמש לא נמצא.' ] );
    }
    if ( function_exists( 'groups_is_user_member' ) && groups_is_user_member( $user_id, $group_id ) ) {
        wp_send_json_error( [ 'message' => 'המשתמש כבר חבר בקבוצה.' ] );
    }
    if ( ! function_exists( 'groups_add_member' ) && ! function_exists( 'groups_join_group' ) ) {
        wp_send_json_error( [ 'message' => 'פונקציית BuddyPress לא זמינה.' ] );
    }
    $joined = hp_bp_tweaks_add_user_to_group( $group_id, $user_id );
    if ( $joined ) {
        if ( function_exists( 'bp_update_user_last_activity' ) ) {
            bp_update_user_last_activity( $user_id );
        }
        $display_name = get_userdata( $user_id ) ? get_userdata( $user_id )->display_name : '';
        wp_send_json_success( [ 'message' => 'נוסף בהצלחה: ' . esc_html( $display_name ) ] );
    }
    wp_send_json_error( [ 'message' => 'שגיאה בהוספה.' ] );
}
add_action( 'wp_ajax_hp_add_group_members_add_one', 'hp_bp_tweaks_ajax_add_single_member' );

/**
 * AJAX: ייבוא רשימת אימיילים והוספת כל המשתמשים הרשומים לקבוצה
 */
function hp_bp_tweaks_ajax_import_emails() {
    check_ajax_referer( 'hp_add_group_members', 'nonce' );
    $group_id = isset( $_POST['group_id'] ) ? absint( $_POST['group_id'] ) : 0;
    $emails   = isset( $_POST['emails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['emails'] ) ) : '';
    if ( ! $group_id || ! hp_bp_tweaks_can_add_group_members( $group_id ) ) {
        wp_send_json_error( [ 'message' => 'אין הרשאה.' ] );
    }
    $lines = array_filter( array_map( 'trim', explode( "\n", $emails ) ) );
    $emails_list = array_filter( array_unique( $lines ), 'is_email' );
    if ( empty( $emails_list ) ) {
        wp_send_json_error( [ 'message' => 'לא נמצאו אימיילים תקינים.' ] );
    }
    $added   = 0;
    $skipped = 0;
    $not_found = 0;
    foreach ( $emails_list as $email ) {
        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            $not_found++;
            continue;
        }
        $user_id = (int) $user->ID;
        if ( function_exists( 'groups_is_user_member' ) && groups_is_user_member( $user_id, $group_id ) ) {
            $skipped++;
            continue;
        }
        $joined = hp_bp_tweaks_add_user_to_group( $group_id, $user_id );
        if ( $joined ) {
            $added++;
            if ( function_exists( 'bp_update_user_last_activity' ) ) {
                bp_update_user_last_activity( $user_id );
            }
        }
    }
    $message = sprintf(
        'נוספו: %d | דולגו (כבר בקבוצה): %d | לא נמצאו במערכת: %d',
        $added,
        $skipped,
        $not_found
    );
    wp_send_json_success( [
        'message'   => $message,
        'added'     => $added,
        'skipped'   => $skipped,
        'not_found' => $not_found,
    ] );
}
add_action( 'wp_ajax_hp_add_group_members_import', 'hp_bp_tweaks_ajax_import_emails' );

/**
 * טוען את ה-JS להוספת חברים
 */
function hp_bp_tweaks_enqueue_add_members_script() {
    $is_manage_members_screen = function_exists( 'bp_is_group_admin_screen' ) && bp_is_group_admin_screen( 'manage-members' );

    if ( ! $is_manage_members_screen ) {
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
        $is_manage_members_screen = ( false !== strpos( $request_uri, '/groups/' ) && false !== strpos( $request_uri, '/manage-members' ) );
    }

    if ( ! $is_manage_members_screen ) {
        return;
    }

    hp_bp_tweaks_enqueue_add_members_assets();
}
add_action( 'bp_enqueue_scripts', 'hp_bp_tweaks_enqueue_add_members_script', 20 );
add_action( 'wp_enqueue_scripts', 'hp_bp_tweaks_enqueue_add_members_script', 25 );
