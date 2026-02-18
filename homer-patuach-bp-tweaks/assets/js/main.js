jQuery(document).ready(function($) {
    'use strict';
    
    // הסרנו את no-ajax class כדי לאפשר ל-BuddyPress לטפל ב-pagination בעצמו
    // $('.bp-pagination[data-bp-pagination="mlpage"]').addClass('no-ajax');

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

    // --- תפריט מובייל [hp_mobile_nav]: המבורגר + דרור ---
    var $navWrap = $('#hp-mobile-nav-wrap');
    var $navToggle = $navWrap.find('.hp-mobile-nav-toggle');
    var $navDrawer = $('#hp-mobile-nav-drawer');
    var $navOverlay = $('#hp-mobile-nav-overlay');
    if ($navWrap.length && $navToggle.length) {
        $navToggle.on('click', function() {
            var isOpen = $navWrap.hasClass('is-open');
            $navWrap.toggleClass('is-open', !isOpen);
            $navToggle.attr('aria-expanded', !isOpen);
            $navOverlay.attr('aria-hidden', isOpen);   /* פתוח = overlay גלוי */
            $navDrawer.attr('aria-hidden', isOpen);
            $('body').toggleClass('hp-mobile-nav-body-open', !isOpen);
        });
        function closeMobileNav() {
            $navWrap.removeClass('is-open');
            $navToggle.attr('aria-expanded', 'false');
            $navOverlay.attr('aria-hidden', 'true');
            $navDrawer.attr('aria-hidden', 'true');
            $('body').removeClass('hp-mobile-nav-body-open');
        }
        $navOverlay.on('click', closeMobileNav);
        $navDrawer.find('a').on('click', closeMobileNav);
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $navWrap.hasClass('is-open')) closeMobileNav();
        });
    }

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
            'Friend': 'חבר',
            'My Groups': 'הקבוצות שלי',
            'All Groups': 'כל הקבוצות'
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
            const el = this;
            
            // בדוק שהאלמנט תקין
            if (!$el.length || !el || !el.parentNode || el.parentNode.nodeType !== 1) {
                return;
            }
            
            // בדוק שהאלמנט לא בתוך script או style
            if (el.tagName === 'SCRIPT' || el.tagName === 'STYLE' || 
                $(el).closest('script, style').length > 0) {
                return;
            }
            
            try {
                let text = $el.text();
                // דפוס: מספר + " Members" או " Member"
                let newText = text.replace(/(\d+)\s+Members?/gi, function(match, num) {
                    return num === '1' ? num + ' חבר' : num + ' חברים';
                });
                // דפוס: "My Groups X" -> "הקבוצות שלי X" (עם עטיפה למספר)
                newText = newText.replace(/My\s+Groups\s+(\d+)/gi, 'הקבוצות שלי <span class="hp-group-count-badge">$1</span>');
                // דפוס: "All Groups X" -> "כל הקבוצות X" (עם עטיפה למספר)
                newText = newText.replace(/All\s+Groups\s+(\d+)/gi, 'כל הקבוצות <span class="hp-group-count-badge">$1</span>');
                if (newText !== text && el.parentNode && el.parentNode.nodeType === 1) {
                    $el.text(newText);
                }
            } catch(e) {
                // דלג בשקט על שגיאות
            }
        });

        // תרגום זמן - כולל שבועות, ימים, שעות, דקות
        // רק אלמנטים ספציפיים - לא כל האלמנטים כדי למנוע בעיות
        $('span, div, p, li, td, th, a, h1, h2, h3, h4, h5, h6').each(function() {
            const $el = $(this);
            const el = this;
            
            // בדוק שהאלמנט קיים וניתן לעדכון
            if (!$el.length || !el || !el.parentNode || el.parentNode.nodeType !== 1) {
                return;
            }
            
            // בדוק שהאלמנט לא בתוך script או style
            if (el.tagName === 'SCRIPT' || el.tagName === 'STYLE' || 
                $(el).closest('script, style').length > 0) {
                return;
            }
            
            // בדוק שהאלמנט לא בתוך modal שלא צריך לעדכן
            if ($(el).closest('.hpg-modal-overlay, .hpg-group-modal-overlay').length > 0) {
                return;
            }
            
            let html;
            try {
                html = $el.html();
            } catch(e) {
                return; // אם לא ניתן לקרוא את ה-HTML, דלג
            }
            
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
            
            try {
                const currentHtml = $el.html();
                if (html !== currentHtml) {
                    // בדוק שהאלמנט עדיין קיים לפני עדכון
                    if (el.parentNode && el.parentNode.nodeType === 1) {
                        $el.html(html);
                    }
                }
            } catch (e) {
                // אם יש שגיאה, דלג על האלמנט הזה בשקט
                // console.warn('Error updating element:', e);
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
     * Wrap Group Count Numbers - עטיפת מספרים ברקע עגול
     * ===============================================
     */
    function wrapGroupCountNumbers() {
        // רק בעמודי קבוצות
        if (!$('body').hasClass('groups')) {
            return;
        }

        // מצא את כל הטקסטים שמכילים "הקבוצות שלי" או "כל הקבוצות" עם מספר
        $('a, span, li, button, div').each(function() {
            const $el = $(this);
            const text = $el.text();
            
            // אם כבר יש span עם class, דלג
            if ($el.find('.hp-group-count-badge').length > 0) {
                return;
            }
            
            // דפוס: "הקבוצות שלי X" או "כל הקבוצות X" (X הוא מספר)
            const pattern1 = /(הקבוצות שלי)\s+(\d+)/;
            const pattern2 = /(כל הקבוצות)\s+(\d+)/;
            
            let newHtml = $el.html();
            let updated = false;
            
            if (pattern1.test(text)) {
                newHtml = newHtml.replace(/(הקבוצות שלי)\s+(\d+)/g, '$1 <span class="hp-group-count-badge">$2</span>');
                updated = true;
            }
            
            if (pattern2.test(text)) {
                newHtml = newHtml.replace(/(כל הקבוצות)\s+(\d+)/g, '$1 <span class="hp-group-count-badge">$2</span>');
                updated = true;
            }
            
            if (updated && newHtml !== $el.html()) {
                $el.html(newHtml);
            }
        });
    }

    // הרץ עטיפת מספרים בהתחלה ואחרי טעינה דינמית
    wrapGroupCountNumbers();
    $(document).ajaxComplete(function() {
        setTimeout(wrapGroupCountNumbers, 150);
    });
    // גם אחרי שינוי DOM
    setTimeout(wrapGroupCountNumbers, 500);

    /**
     * ===============================================
     * Fix Group Name Links - שינוי קישור שם הקבוצה ל-group-posts
     * ===============================================
     */
    function fixGroupNameLinks() {
        // רק ברשימת הקבוצות (לא בעמוד קבוצה בודדת)
        if (!$('body').hasClass('groups')) {
            return;
        }
        
        // רק אם זה לא עמוד קבוצה בודדת
        if (window.location.href.indexOf('/groups/') !== -1 && 
            window.location.href.match(/\/groups\/[^\/]+\/?$/)) {
            // זה עמוד קבוצה בודדת, לא רשימה
            return;
        }

        // מצא את כל הקישורים לשמות קבוצות ברשימה
        $('#groups-list a, #groups-dir-list a, .item-list a, .groups-list a').each(function() {
            const $link = $(this);
            const href = $link.attr('href');
            
            // בדוק אם זה קישור לקבוצה (מכיל /groups/ ושם קבוצה)
            if (href && href.indexOf('/groups/') !== -1) {
                // בדוק אם זה לא כבר group-posts או members
                if (href.indexOf('/group-posts') === -1 && 
                    href.indexOf('/members') === -1 &&
                    href.indexOf('/admin') === -1 &&
                    href.indexOf('/settings') === -1 &&
                    href.indexOf('/send-invites') === -1) {
                    
                    // נסה להוסיף /group-posts/ - אם לא עובד, ננסה /members/
                    // קודם נבדוק אם יש כבר / בסוף
                    let newHref = href.replace(/\/$/, '') + '/group-posts/';
                    
                    // אם הקישור הוא לשם הקבוצה (לא לטאב אחר), שנה אותו
                    // בדוק אם הקישור הוא לשם הקבוצה עצמו (לא לטאב)
                    const groupSlugMatch = href.match(/\/groups\/([^\/]+)\/?$/);
                    if (groupSlugMatch) {
                        // זה קישור לשם הקבוצה - שנה ל-group-posts
                        $link.attr('href', newHref);
                    } else {
                        // בדוק אם זה קישור בתוך רשימת קבוצות (item-title או שם קבוצה)
                        const $parent = $link.closest('li.item, .group-item, .item-list-item');
                        if ($parent.length > 0) {
                            // בדוק אם הקישור הוא על שם הקבוצה (בתוך item-title או list-title)
                            const $title = $link.closest('.item-title, .list-title, h3, h4, h5');
                            if ($title.length > 0) {
                                // זה כנראה קישור על שם הקבוצה - שנה ל-group-posts
                                $link.attr('href', newHref);
                            }
                        }
                    }
                }
            }
        });
    }

    // הרץ תיקון קישורים בהתחלה ואחרי טעינה דינמית
    fixGroupNameLinks();
    $(document).ajaxComplete(function() {
        setTimeout(fixGroupNameLinks, 200);
    });
    // גם אחרי שינוי DOM
    setTimeout(fixGroupNameLinks, 600);

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
            
            // הסתר רק באדג'ים כפולים שלא בתוך wrapper (display:none בלבד – מונע שכבת אטימות)
            $card.find('.hpg-member-badges, .hpg-badge-circle, .hpg-profile-badges-header, .hpg-badges-profile').each(function() {
                const $badge = $(this);
                if (!$badge.closest('.hpg-member-badges-wrapper').length) {
                    $badge.css('display', 'none');
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
    
    // גם אחרי AJAX של BuddyPress
    $(document).ajaxComplete(function() {
        setTimeout(fixMemberNameLinks, 200);
    });
    
    // גם אחרי ש-BuddyPress טוען תוכן חדש
    $(document).on('bp-ajax-loaded', function() {
        setTimeout(fixMemberNameLinks, 200);
    });

    /**
     * ===============================================
     * Fix Pagination Links - גרסה מפושטת שלא חוסמת AJAX
     * ===============================================
     */
    function fixPaginationLinks() {
        // רק בעמודי קבוצה - בדוק גם לפי URL
        const isGroupPage = $('body').hasClass('bp-group') || 
                           $('body').hasClass('groups') ||
                           window.location.pathname.indexOf('/groups/') !== -1;
        
        if (!isGroupPage) {
            return;
        }

        // תיקון pagination של פוסטים - רק תיקון URL, לא חסימה
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
    }
    
    // הרץ תיקון pagination - רק לפוסטים, לא לחברים
    fixPaginationLinks();
    
    // גם אחרי AJAX של BuddyPress
    $(document).ajaxComplete(function() {
        setTimeout(fixPaginationLinks, 200);
    });
    
    // גם אחרי ש-BuddyPress טוען תוכן חדש
    $(document).on('bp-ajax-loaded', function() {
        setTimeout(fixPaginationLinks, 200);
    });
    
    // גם אחרי טעינה מלאה
    $(window).on('load', function() {
        setTimeout(fixPaginationLinks, 300);
    });

    /**
     * הוספת אופציית "מיון לפי פוסטים" בתפריט מיון חברי קבוצה (למנהלים)
     */
    (function injectPostCountSortOption() {
        const $select = $('#groups_members-order-by');
        if (!$select.length) return;
        if ($select.find('option[value="post_count"]').length) return;
        $select.append('<option value="post_count">מיון לפי פוסטים</option>');
    })();

    /**
     * ===============================================
     * סינון מיון חברי קבוצה – ניווט בדף מלא (במקום AJAX שלא עובד)
     * ===============================================
     */
    $(document).on('change', '#groups_members-order-by', function() {
        const val = $(this).val();
        if (!val) return;
        const url = new URL(window.location.href);
        url.searchParams.set('members_order_by', val);
        url.searchParams.set('type', val);
        url.searchParams.delete('mlpage');
        if ($('#group-members-search').length && $('#group-members-search').val()) {
            url.searchParams.set('members_search', $('#group-members-search').val());
        }
        window.location.href = url.toString();
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