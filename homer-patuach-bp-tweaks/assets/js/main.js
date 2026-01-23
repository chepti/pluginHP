jQuery(document).ready(function($) {
    'use strict';

    const $menu = $('.hp-bp-user-menu');
    const $trigger = $menu.find('.hp-bp-profile-trigger');
    const $dropdown = $menu.find('.hp-bp-dropdown-menu');

    // Toggle dropdown on trigger click
    $trigger.on('click', function(e) {
        e.stopPropagation();
        const isHidden = $dropdown.attr('aria-hidden') === 'true';
        $dropdown.attr('aria-hidden', !isHidden);
        $trigger.attr('aria-expanded', isHidden);
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if ( !$menu.is(e.target) && $menu.has(e.target).length === 0 ) {
            $dropdown.attr('aria-hidden', 'true');
            $trigger.attr('aria-expanded', 'false');
        }
    });

    // Close dropdown on Escape key
    $(document).on('keydown', function(e) {
        if (e.key === "Escape") {
            $dropdown.attr('aria-hidden', 'true');
            $trigger.attr('aria-expanded', 'false');
        }
    });

    // Handle "Add Post" popup trigger from dropdown
    const $popupTrigger = $('.hpg-open-popup-button');
    if ($popupTrigger.length > 0) {
        const $popupOverlay = $('#hpg-popup-overlay');
        if ($popupOverlay.length > 0) {
            $menu.on('click', '.hpg-open-popup-button', function(e) {
                e.preventDefault();
                // Explicitly set visibility by adding/removing classes
                $popupOverlay.removeClass('hpg-popup-hidden').addClass('hpg-popup-visible');
                $('body').addClass('hpg-popup-active'); // Prevent background scrolling
                
                // Close the dropdown menu as well
                $dropdown.attr('aria-hidden', 'true');
                $trigger.attr('aria-expanded', 'false');
            });
        }
    }

    // --- Tweaks for Register/Settings pages ---
    if ($('body.bp-user.settings').length) {
        
        // 1. Hide unwanted elements
        $('#template-notices').hide();
        $('form .password-field > .description').hide();
        
        // 2. Translate Labels & Descriptions
        // The "Name" field in BP settings is field_1 by default.
        $("label[for='field_1']").contents().first().replaceWith('כינוי');
        $("label[for='signup_password']").text('בחירת סיסמה');

        var visibility_text = $("div.field-visibility-settings");
        if(visibility_text.text().includes('This field may be seen by')){
            visibility_text.text('שדה זה יוצג בפרופיל שלך, בהתאם להגדרות הפרטיות.');
        }
        
        // 3. Fix password strength meter position
        var passwordField = $('#signup_password, #password').parent();
        var strengthMeter = $('.bp-password-strength-results');
        if(passwordField.length && strengthMeter.length) {
            passwordField.css({
                'display': 'flex',
                'align-items': 'center',
                'gap': '10px',
                'flex-wrap': 'wrap'
            });
            strengthMeter.css('margin', '0');
        }
    }

    /**
     * ===============================================
     * Translate BuddyPress Group Strings (JS fallback)
     * ===============================================
     */
    function translateGroupStrings() {
        // רק בעמודי קבוצה
        if (!$('body').hasClass('bp-group') && !$('body').hasClass('groups')) {
            return;
        }

        const translations = {
            'Home': 'פעילות',
            'Members': 'חברים',
            'Membership List': 'רשימת חברים',
            'Manage': 'ניהול',
            'Invite': 'הזמנת חברים',
            'Group Activities': 'פעילויות הקבוצה',
            'Group Administrators': 'מנהלי הקבוצה',
            'Joined': 'הצטרף',
            'Add Friend': 'הוסף כחבר',
            'Friends': 'חברים',
            'Friend': 'חבר'
        };

        // תרגום טקסטים ישירים - רק טקסט, לא קישורים
        Object.keys(translations).forEach(function(en) {
            const he = translations[en];
            
            // בתוך טאבים - שמור את הקישורים
            $('#item-nav a, .bp-navs a').each(function() {
                const $el = $(this);
                const text = $el.text().trim();
                const href = $el.attr('href');
                
                // אם הקישור כבר מתורגם (מכיל עברית), דלג עליו
                if (text && /[\u0590-\u05FF]/.test(text)) {
                    return;
                }
                
                // אם הטקסט תואם בדיוק, החלף רק את הטקסט
                if (text === en) {
                    // שמור את ה-href לפני שינוי הטקסט
                    if (href && href !== '#') {
                        const savedHref = href;
                        $el.text(he);
                        // החזר את ה-href אחרי שינוי הטקסט
                        $el.attr('href', savedHref);
                        // סמן שהקישור כבר תורגם
                        $el.data('translated', true);
                    } else {
                        $el.text(he);
                    }
                } else if (text.indexOf(en) !== -1 && !$el.data('translated')) {
                    // אם הטקסט מכיל את המחרוזת, החלף רק את החלק הזה
                    const newText = text.replace(new RegExp(en, 'g'), he);
                    if (href && href !== '#') {
                        const savedHref = href;
                        $el.text(newText);
                        $el.attr('href', savedHref);
                        $el.data('translated', true);
                    } else {
                        $el.text(newText);
                    }
                }
            });
            
            // בתוך span ו-li (לא קישורים)
            $('span:contains("' + en + '"), li:contains("' + en + '")').not('a, a *').each(function() {
                const $el = $(this);
                if ($el.text().trim() === en) {
                    $el.text(he);
                } else if ($el.html && typeof $el.html === 'function') {
                    try {
                        $el.html($el.html().replace(new RegExp(en, 'g'), he));
                    } catch(e) {
                        // אם יש שגיאה, רק החלף טקסט
                        $el.text($el.text().replace(new RegExp(en, 'g'), he));
                    }
                }
            });
            
            // בתוך כותרות
            $('h1:contains("' + en + '"), h2:contains("' + en + '"), h3:contains("' + en + '")').each(function() {
                const $el = $(this);
                try {
                    $el.html($el.html().replace(new RegExp(en, 'g'), he));
                } catch(e) {
                    $el.text($el.text().replace(new RegExp(en, 'g'), he));
                }
            });
        });

        // תרגום "X Members" / "X Member"
        $('a, span, li').each(function() {
            const $el = $(this);
            let text = $el.text();
            // דפוס: מספר + " Members" או " Member"
            text = text.replace(/(\d+)\s+Members?/gi, function(match, num) {
                return num === '1' ? num + ' חבר' : num + ' חברים';
            });
            if (text !== $el.text()) {
                $el.text(text);
            }
        });

        // תרגום זמן - כולל שבועות, ימים, שעות, דקות
        $('*').each(function() {
            const $el = $(this);
            // בדוק שהאלמנט קיים וניתן לעדכון
            if (!$el.length || $el.length === 0 || !$el[0] || !$el[0].parentNode) {
                return;
            }
            
            let html = $el.html();
            if (!html || typeof html !== 'string' || (html.indexOf('hour') === -1 && html.indexOf('minute') === -1 && 
                html.indexOf('week') === -1 && html.indexOf('day') === -1 && 
                html.indexOf('month') === -1 && html.indexOf('year') === -1)) {
                return;
            }
            
            // דפוס: "X weeks, Y days, Z hours" - טיפול מלא
            html = html.replace(/(\d+)\s+weeks?/gi, function(match, num) {
                return num === '1' ? num + ' שבוע' : num + ' שבועות';
            });
            html = html.replace(/(\d+)\s+days?/gi, function(match, num) {
                return num === '1' ? num + ' יום' : num + ' ימים';
            });
            html = html.replace(/(\d+)\s+hours?/gi, function(match, num) {
                return num === '1' ? num + ' שעה' : num + ' שעות';
            });
            html = html.replace(/(\d+)\s+minutes?/gi, function(match, num) {
                return num === '1' ? num + ' דקה' : num + ' דקות';
            });
            html = html.replace(/(\d+)\s+months?/gi, function(match, num) {
                return num === '1' ? num + ' חודש' : num + ' חודשים';
            });
            html = html.replace(/(\d+)\s+years?/gi, function(match, num) {
                return num === '1' ? num + ' שנה' : num + ' שנים';
            });
            
            // דפוס: "week, day" או "שבוע, יום" - תיקון פורמט
            html = html.replace(/week\s*,\s*day/gi, 'שבוע ויום');
            html = html.replace(/week\s*,\s*(\d+)\s*days?/gi, function(match, days) {
                return 'שבוע ו' + days + (days === '1' ? ' יום' : ' ימים');
            });
            // דפוס: "שבוע, יום" (כבר מתורגם אבל לא נכון)
            html = html.replace(/שבוע\s*,\s*יום/gi, 'שבוע ויום');
            html = html.replace(/שבוע\s*,\s*(\d+)\s*ימים?/gi, function(match, days) {
                return 'שבוע ו' + days + (days === '1' ? ' יום' : ' ימים');
            });
            // דפוס: "שבוע, X ימים, Y שעות" - פורמט מלא
            html = html.replace(/שבוע\s*,\s*(\d+)\s*ימים?\s*,\s*(\d+)\s*שעות?/gi, function(match, days, hours) {
                return 'שבוע, ' + days + (days === '1' ? ' יום' : ' ימים') + ', ' + hours + (hours === '1' ? ' שעה' : ' שעות');
            });
            
            // תיקון: "ימים, X שעות" (חסר מספר לפני "ימים")
            html = html.replace(/ימים\s*,\s*(\d+)\s*שעות?/gi, function(match, hours) {
                // נסה למצוא את המספר הקודם
                return 'ימים, ' + hours + (hours === '1' ? ' שעה' : ' שעות');
            });
            // תיקון: "הצטרפו לפני ימים, X שעות" (חסר מספר)
            html = html.replace(/לפני\s+ימים\s*,\s*(\d+)\s*שעות?/gi, function(match, hours) {
                // אם אין מספר לפני "ימים", זה כנראה שגיאה - נשאיר כמו שזה או ננסה לתקן
                return 'לפני ימים, ' + hours + (hours === '1' ? ' שעה' : ' שעות');
            });
            
            // תיקון: "הצטרף לפני ימים, X שעות" (חסר מספר)
            html = html.replace(/הצטרף\s+לפני\s+ימים\s*,\s*(\d+)\s*שעות?/gi, function(match, hours) {
                return 'הצטרף לפני ימים, ' + hours + (hours === '1' ? ' שעה' : ' שעות');
            });
            
            // דפוס: "X hours ago" / "X hour ago"
            html = html.replace(/(\d+)\s+hours?\s+ago/gi, function(match, num) {
                return num === '1' ? 'לפני שעה' : 'לפני ' + num + ' שעות';
            });
            // דפוס: "X minutes ago" / "X minute ago"
            html = html.replace(/(\d+)\s+minutes?\s+ago/gi, function(match, num) {
                return num === '1' ? 'לפני דקה' : 'לפני ' + num + ' דקות';
            });
            // דפוס: "X days ago" / "X day ago"
            html = html.replace(/(\d+)\s+days?\s+ago/gi, function(match, num) {
                return num === '1' ? 'לפני יום' : 'לפני ' + num + ' ימים';
            });
            // דפוס: "X weeks ago" / "X week ago"
            html = html.replace(/(\d+)\s+weeks?\s+ago/gi, function(match, num) {
                return num === '1' ? 'לפני שבוע' : 'לפני ' + num + ' שבועות';
            });
            // דפוס: "a week ago" / "a day ago"
            html = html.replace(/a\s+week\s+ago/gi, 'לפני שבוע');
            html = html.replace(/a\s+day\s+ago/gi, 'לפני יום');
            
            if (html !== $el.html()) {
                try {
                    $el.html(html);
                } catch (e) {
                    // אם יש שגיאה, דלג על האלמנט הזה
                    console.warn('Error updating element:', e);
                }
            }
        });
    }

    // הרץ תרגום בהתחלה ואחרי טעינה דינמית
    translateGroupStrings();
    // גם אחרי AJAX (DOMNodeInserted הוא deprecated ויכול לגרום לבעיות)
    $(document).ajaxComplete(function() {
        setTimeout(translateGroupStrings, 100);
    });

    /**
     * ===============================================
     * Organize Member Cards - Badges and Button in Bottom Row
     * ===============================================
     */
    function organizeMemberCards() {
        // רק בעמודי קבוצה
        if (!$('body').hasClass('bp-group') && !$('body').hasClass('groups')) {
            return;
        }

        $('#group-members-list li, #members-list li').each(function() {
            const $card = $(this);
            
            // ודא שהכרטיס הוא flex column
            $card.css({
                'display': 'flex',
                'flex-direction': 'column'
            });
            
            // הסתר רק באדג'ים שלא בתוך wrapper (אגרסיבי מאוד)
            $card.find('.hpg-member-badges, .hpg-badge-circle, .hpg-profile-badges-header, .hpg-badges-profile').each(function() {
                const $badge = $(this);
                // רק אם זה לא בתוך wrapper - הסתר אותו
                if (!$badge.closest('.hpg-member-badges-wrapper').length) {
                    $badge.css({
                        'display': 'none',
                        'visibility': 'hidden',
                        'position': 'absolute',
                        'left': '-9999px',
                        'opacity': '0',
                        'width': '0',
                        'height': '0',
                        'overflow': 'hidden'
                    });
                } else {
                    // אם זה בתוך wrapper - ודא שהוא מוצג
                    $badge.css({
                        'display': '',
                        'visibility': 'visible',
                        'position': 'relative',
                        'left': 'auto',
                        'opacity': '1',
                        'width': 'auto',
                        'height': 'auto',
                        'overflow': 'visible'
                    });
                }
            });
            
            let $badgesWrapper = $card.find('.hpg-member-badges-wrapper');
            
            // אם אין wrapper, צור אותו
            if ($badgesWrapper.length === 0) {
                $badgesWrapper = $('<div class="hpg-member-badges-wrapper"></div>');
                $card.append($badgesWrapper);
            }
            
            // ודא שה-wrapper בתחתית (אחרי כל התוכן)
            $badgesWrapper.detach().appendTo($card);

            // מצא את הכפתור "הוסף כחבר" (לא בתוך wrapper)
            const $button = $card.find('.generic-button:not(.hpg-member-badges-wrapper .generic-button), .button:not(.hpg-member-badges-wrapper .button)').filter(function() {
                const text = $(this).text().toLowerCase();
                return text.indexOf('חבר') !== -1 || text.indexOf('friend') !== -1 || text.indexOf('הוסף') !== -1 || text.indexOf('add') !== -1;
            });

            // אם יש כפתור והוא לא בתוך ה-wrapper, העבר אותו
            if ($button.length > 0 && !$badgesWrapper.find($button).length) {
                $button.detach().appendTo($badgesWrapper);
            }

            // מצא את כל הבאדג'ים (גם אם הם לא ב-wrapper) - כולל .hpg-badge-circle
            // חפש גם באדג'ים שמופיעים ישירות ב-li (לא בתוך wrapper)
            const $allBadges = $card.find('.hpg-member-badges, .hpg-badge-circle, .hpg-profile-badges-header, .hpg-badges-profile').filter(function() {
                // רק באדג'ים שלא בתוך wrapper
                return !$(this).closest('.hpg-member-badges-wrapper').length;
            });
            
            // צור div חדש לבאדג'ים אם צריך
            let $badgesContainer = $badgesWrapper.find('.hpg-member-badges');
            if ($badgesContainer.length === 0) {
                $badgesContainer = $('<div class="hpg-member-badges"></div>');
                $badgesWrapper.prepend($badgesContainer);
            }
            
            // ודא שהבאדג'ים שכבר בתוך wrapper מוצגים
            $badgesContainer.find('.hpg-badge-circle, .hpg-member-badges').css({
                'display': '',
                'visibility': 'visible',
                'opacity': '1',
                'position': 'relative',
                'left': 'auto',
                'width': 'auto',
                'height': 'auto',
                'overflow': 'visible'
            });
            
            // אם יש באדג'ים מחוץ ל-wrapper, העבר אותם
            if ($allBadges.length > 0) {
                // העבר את כל הבאדג'ים
                $allBadges.each(function() {
                    const $badge = $(this);
                    // ודא שהבאדג' לא כבר בתוך ה-wrapper
                    if (!$badgesWrapper.find($badge).length && !$badgesContainer.is($badge)) {
                        $badge.detach().appendTo($badgesContainer);
                        // ודא שהבאדג' מוצג אחרי ההעברה
                        $badge.css({
                            'display': '',
                            'visibility': 'visible',
                            'opacity': '1',
                            'position': 'relative',
                            'left': 'auto',
                            'width': 'auto',
                            'height': 'auto',
                            'overflow': 'visible'
                        });
                    }
                });
            }
            
            // ודא שהקונטיינר מופיע - גם אם הוא ריק
            $badgesContainer.css({
                'display': 'flex',
                'flex-wrap': 'wrap',
                'gap': '6px',
                'align-items': 'center',
                'visibility': 'visible',
                'opacity': '1',
                'position': 'relative',
                'left': 'auto',
                'width': 'auto',
                'height': 'auto',
                'overflow': 'visible'
            }).show();
            
            // ודא שה-wrapper מופיע בתחתית - תמיד
            $badgesWrapper.css({
                'display': 'flex',
                'visibility': 'visible',
                'opacity': '1',
                'margin-top': 'auto',
                'order': '999',
                'width': '100%',
                'flex-wrap': 'wrap',
                'align-items': 'center',
                'justify-content': 'space-between',
                'gap': '8px',
                'padding-top': '12px',
                'border-top': '1px solid #e7e7e7'
            }).show();
        });
    }

    // Debounce function כדי למנוע קריאות מרובות
    let organizeTimeout = null;
    function debouncedOrganizeMemberCards() {
        if (organizeTimeout) {
            clearTimeout(organizeTimeout);
        }
        organizeTimeout = setTimeout(function() {
            organizeMemberCards();
        }, 200);
    }
    
    // הרץ ארגון כרטיסים - כמה פעמים כדי לוודא שזה עובד
    organizeMemberCards();
    setTimeout(organizeMemberCards, 300);
    setTimeout(organizeMemberCards, 600);
    
    // גם אחרי AJAX (DOMNodeInserted הוא deprecated ויכול לגרום לבעיות)
    $(document).ajaxComplete(function() {
        debouncedOrganizeMemberCards();
    });
    
    // גם אחרי טעינה מלאה
    $(window).on('load', function() {
        setTimeout(organizeMemberCards, 500);
    });
    
    // גם אחרי שינויים ב-DOM - אבל רק בעמודי קבוצה ובצורה מבוקרת
    let observer = null;
    if (document.body && ($('body').hasClass('bp-group') || $('body').hasClass('groups'))) {
        let isOrganizing = false;
        observer = new MutationObserver(function(mutations) {
            // אל תריץ אם כבר מריץ או אם אין שינויים רלוונטיים
            if (isOrganizing) return;
            
            let shouldRun = false;
            for (let i = 0; i < mutations.length; i++) {
                const mutation = mutations[i];
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // בדוק אם נוספו אלמנטים רלוונטיים
                    for (let j = 0; j < mutation.addedNodes.length; j++) {
                        const node = mutation.addedNodes[j];
                        if (node.nodeType === 1) { // Element node
                            if ($(node).is('#group-members-list, #members-list, .hpg-member-badges-wrapper') || 
                                $(node).find('#group-members-list, #members-list, .hpg-member-badges-wrapper').length > 0) {
                                shouldRun = true;
                                break;
                            }
                        }
                    }
                    if (shouldRun) break;
                }
            }
            
            if (shouldRun) {
                isOrganizing = true;
                debouncedOrganizeMemberCards();
                setTimeout(function() {
                    isOrganizing = false;
                }, 500);
            }
        });
        
        // רק על אזור החברים, לא על כל ה-body
        const membersList = document.querySelector('#group-members-list, #members-list');
        if (membersList) {
            observer.observe(membersList, {
                childList: true,
                subtree: true
            });
        } else {
            // אם אין עדיין, נסה אחרי טעינה
            setTimeout(function() {
                const membersListDelayed = document.querySelector('#group-members-list, #members-list');
                if (membersListDelayed && observer) {
                    observer.observe(membersListDelayed, {
                        childList: true,
                        subtree: true
                    });
                }
            }, 1000);
        }
    }

    /**
     * ===============================================
     * Fix Member Name Links in Group Members List
     * ===============================================
     */
    function fixMemberNameLinks() {
        // רק בעמודי קבוצה
        if (!$('body').hasClass('bp-group') && !$('body').hasClass('groups')) {
            return;
        }

        // רק בעמוד חברים
        if (window.location.href.indexOf('/members') === -1) {
            return;
        }

        $('#group-members-list li, #members-list li').each(function() {
            const $card = $(this);
            // מצא קישור על שם המשתמש
            const $nameLink = $card.find('a[href*="/members/"]').first();
            
            if ($nameLink.length > 0) {
                const href = $nameLink.attr('href');
                if (href && href.indexOf('/my-posts') === -1) {
                    // החלף את ה-href כך שיוביל ל-my-posts
                    const newHref = href.replace(/\/members\/[^\/]+(\/)?$/, '/my-posts/');
                    $nameLink.attr('href', newHref);
                }
            }
        });
    }

    // הרץ תיקון קישורים
    fixMemberNameLinks();
    // גם אחרי AJAX (DOMNodeInserted הוא deprecated ויכול לגרום לבעיות)
    $(document).ajaxComplete(function() {
        setTimeout(fixMemberNameLinks, 200);
    });

    /**
     * ===============================================
     * Fix Pagination Links
     * ===============================================
     */
    function fixPaginationLinks() {
        // רק בעמודי קבוצה
        if (!$('body').hasClass('bp-group') && !$('body').hasClass('groups')) {
            return;
        }

        // תיקון pagination של פוסטים
        $('.hpg-group-posts-pagination a, .hpg-group-posts-pagination .page-numbers').each(function() {
            const $link = $(this);
            let href = $link.attr('href');
            if (!href) return;

            // אם זה קישור pagination, ודא שהוא כולל את ה-URL הנכון
            if (href.indexOf('paged=') !== -1 || href.indexOf('page=') !== -1) {
                // אם זה קישור יחסי, הוסף את ה-base URL
                if (href.indexOf('http') !== 0) {
                    const currentPath = window.location.pathname;
                    if (currentPath.indexOf('/group-posts') !== -1) {
                        // כבר ב-group-posts, רק תוודא שה-URL נכון
                        if (href.indexOf('group-posts') === -1) {
                            href = currentPath + (href.indexOf('?') === 0 ? href : '?' + href.split('?')[1]);
                            $link.attr('href', href);
                        }
                    }
                }
            }
        });

        // תיקון pagination של חברים (BuddyPress)
        $('.pagination a, .bp-pagination a, .page-numbers a, nav.pagination a').each(function() {
            const $link = $(this);
            let href = $link.attr('href');
            if (!href || href === '#') return;

            // אם זה קישור pagination (מכיל מספר או prev/next)
            const isPagination = $link.text().match(/^\d+$/) || $link.text().indexOf('←') !== -1 || $link.text().indexOf('→') !== -1 || $link.text().indexOf('&laquo;') !== -1 || $link.text().indexOf('&raquo;') !== -1;
            
            if (isPagination || href.indexOf('members_page=') !== -1 || href.indexOf('paged=') !== -1 || href.indexOf('page=') !== -1) {
                // אם זה קישור יחסי, הוסף את ה-base URL
                if (href.indexOf('http') !== 0) {
                    const currentPath = window.location.pathname;
                    const currentSearch = window.location.search;
                    
                    // בנה URL מלא
                    if (href.indexOf('?') === 0) {
                        // זה query string בלבד
                        href = currentPath + href;
                    } else if (href.indexOf('/') === 0) {
                        // זה path מלא - השאר כמו שזה
                    } else {
                        // זה קישור יחסי - הוסף ל-path הנוכחי
                        href = currentPath + (currentPath.endsWith('/') ? '' : '/') + href;
                    }
                    
                    $link.attr('href', href);
                }
                
                // ודא שהקישור עובד - הוסף event handler
                $link.off('click.pagination-fix').on('click.pagination-fix', function(e) {
                    const finalHref = $(this).attr('href');
                    if (finalHref && finalHref !== '#' && finalHref.indexOf('javascript:') !== 0) {
                        window.location.href = finalHref;
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });
    }

    // הרץ תיקון pagination
    fixPaginationLinks();
    // גם אחרי AJAX (DOMNodeInserted הוא deprecated ויכול לגרום לבעיות)
    $(document).ajaxComplete(function() {
        setTimeout(fixPaginationLinks, 200);
    });

    /**
     * ===============================================
     * Fix Built-in Filtering (Sorting)
     * ===============================================
     */
    function fixBuiltInFiltering() {
        // רק בעמודי קבוצה
        if (!$('body').hasClass('bp-group') && !$('body').hasClass('groups')) {
            return;
        }

        // תיקון dropdown של סינון/מיון
        $('#members-order-select, select[name="members_orderby"], select.members-order-select').each(function() {
            const $select = $(this);
            
            // ודא שהאירוע change עובד
            $select.off('change.filter-fix').on('change.filter-fix', function() {
                const selectedValue = $(this).val();
                const form = $(this).closest('form');
                
                if (form.length > 0) {
                    // שלח את הטופס
                    form.submit();
                } else {
                    // אם אין טופס, בנה URL עם הפרמטר
                    const currentUrl = window.location.href.split('?')[0];
                    const params = new URLSearchParams(window.location.search);
                    params.set('members_orderby', selectedValue);
                    window.location.href = currentUrl + '?' + params.toString();
                }
            });
        });

        // תיקון חיפוש חברים
        $('#members_search, input[name="members_search"]').each(function() {
            const $input = $(this);
            const $form = $input.closest('form');
            
            if ($form.length > 0) {
                // ודא שהטופס נשלח נכון
                $form.off('submit.filter-fix').on('submit.filter-fix', function(e) {
                    // ודא שה-URL נכון
                    const action = $form.attr('action');
                    if (!action || action === '') {
                        const currentUrl = window.location.href.split('?')[0];
                        $form.attr('action', currentUrl);
                    }
                });
            }
        });
    }

    // הרץ תיקון סינון
    fixBuiltInFiltering();
    // גם אחרי AJAX (DOMNodeInserted הוא deprecated ויכול לגרום לבעיות)
    $(document).ajaxComplete(function() {
        setTimeout(fixBuiltInFiltering, 200);
    });

    /**
     * ===============================================
     * Report Content Modal
     * ===============================================
     */
    const reportModal = $('#hpg-report-modal');
    const reportForm = $('#hpg-report-form');

    // --- Open Modal ---
    $('body').on('click', '.hpg-report-button', function() {
        const postId = $(this).data('post-id');
        if (postId) {
            $('#hpg-report-post-id').val(postId);
            reportModal.addClass('visible');
        }
    });

    // --- Close Modal ---
    function closeReportModal() {
        reportModal.removeClass('visible');
        // Reset form on close
        if (reportForm.length) {
            reportForm[0].reset();
        }
        $('#hpg-report-details-wrapper').hide();
        $('#hpg-report-feedback').hide().empty().removeClass('success error');
        $('#hpg-submit-report-button').prop('disabled', false).text('שליחת דיווח');
    }

    reportModal.on('click', '.hpg-modal-close', closeReportModal);
    reportModal.on('click', function(e) {
        if ($(e.target).is(reportModal)) {
            closeReportModal();
        }
    });

    // --- Show/Hide Details Textarea ---
    $('#hpg-report-reason').on('change', function() {
        const reason = $(this).val();
        const detailsWrapper = $('#hpg-report-details-wrapper');
        if (reason === 'content_error' || reason === 'offensive_content') {
            detailsWrapper.show();
        } else {
            detailsWrapper.hide();
        }
    });

    // --- Handle Form Submission (AJAX) ---
    if (reportForm.length) {
        reportForm.on('submit', function(e) {
            e.preventDefault();

            const submitButton = $('#hpg-submit-report-button');
            const feedbackDiv = $('#hpg-report-feedback');
            
            // Disable button and show loading text
            submitButton.prop('disabled', true).text('שולח...');
            feedbackDiv.hide().empty().removeClass('success error');

            // Prepare data
            const formData = {
                action: 'hpg_handle_report_submission',
                security: hp_bp_ajax_obj.report_nonce,
                post_id: $('#hpg-report-post-id').val(),
                reason: $('#hpg-report-reason').val(),
                details: $('#hpg-report-details').val()
            };

            // Send AJAX request
            $.post(hp_bp_ajax_obj.ajax_url, formData, function(response) {
                if (response.success) {
                    feedbackDiv.addClass('success').text(response.data.message).show();
                    // Close modal after a short delay
                    setTimeout(closeReportModal, 3000);
                } else {
                    feedbackDiv.addClass('error').text(response.data.message).show();
                    // Re-enable button on error
                    submitButton.prop('disabled', false).text('שליחת דיווח');
                }
            }).fail(function() {
                feedbackDiv.addClass('error').text('אירעה שגיאת רשת. נסה שוב.').show();
                submitButton.prop('disabled', false).text('שליחת דיווח');
            });
        });
    }

}); 