# סקירת תוספי וורדפרס - הומר פתוח

## homer-patuach-bp-tweaks
תוסף המתאים את BuddyPress לצרכי האתר:
- התאמת ממשק המשתמש לעברית
- הסתרת סרגל הניהול למשתמשים שאינם מנהלים
- הפניית משתמשים רגילים לדף הבית לאחר התחברות
- כפתור צף להוספת פוסט חדש
- תפריט משתמש מותאם עם אפשרויות ניווט
- מערכת דיווח על תוכן בעייתי
- מערכת מוניטין ומעקב אחר סטטיסטיקות משתמשים (צפיות, לייקים, תגובות)
- טופס הרשמה מותאם עם שדות נוספים
- הצגת ביו משתמש בפרופיל

## homer-patuach-collections
תוסף לניהול אוספים של פוסטים:
- טקסונומיה מותאמת לאוספים
- אפשרות להוסיף פוסטים לאוספים
- תצוגת אוספים בפרופיל המשתמש
- סינון אוספים לפי תחומי דעת
- מערכת לייקים וצפיות לאוספים
- אפשרות לערוך תיאור ומטא-דאטה של אוספים
- תצוגת אוספים בדף הפוסט הבודד

## homer-patuach-grid
תוסף לתצוגת רשת דינמית של פוסטים:
- תצוגת רשת מותאמת עם פילטרים
- טעינה הדרגתית של תכנים
- טופס העלאת תוכן חדש
- תמיכה בתחומי דעת וכיתות
- מערכת אישור תוכן למנהלים
- סטטיסטיקות צפיות ולייקים
- תצוגת פוסטים קשורים
- אינטגרציה עם BuddyPress לתצוגת פוסטים בפרופיל
- תמיכה בחיפוש מותאם
- התראות למנהלים על תכנים הממתינים לאישור



תוסף צירים - 
אני מפתח תוסף וורדפרס שמאפשר ארגון תכנים על ציר זמן שנתי. הנה המפרט המלא:

# מטרת התוסף
התוסף מאפשר ארגון פוסטים קיימים על ציר זמן שנתי (ספטמבר-יוני) עבור קבוצות לימוד שונות. כל קבוצת לימוד היא שילוב של תחום דעת ושכבת גיל.

# מבנה נתונים
1. **קבוצת לימוד** - מורכבת משילוב של:
   - תחום דעת (טקסונומיה `subject` קיימת)
   - שכבת גיל (טקסונומיה `class` קיימת)

2. **ציר זמן** - טקסונומיה `timeline`:
   - מקושר לתחום דעת ושכבת גיל
   - מטא-דאטה:
     - `subject_id` (integer)
     - `class_id` (integer)

3. **נושא בציר** - טקסונומיה `timeline_topic`:
   - מקושר לציר זמן
   - מטא-דאטה:
     - `timeline_id` (integer)
     - `position` (number, 0-100)
     - `length` (number, 0-100)
     - `color` (string, hex color)

4. **נעיצה** - מטא-דאטה לפוסט:
   - אובייקט `timeline_pin`:
     - `timeline_id` (integer)
     - `topic_id` (integer)
     - `position` (number, 0-100)
     - `type` (string: square/circle/triangle/star)
     - `color` (string, hex color)
     - `hidden` (boolean)

# שלבי פיתוח

## שלב 1: תשתית בסיסית
1. הגדרת טקסונומיות `timeline` ו-`timeline_topic`
2. הגדרת מטא-דאטה לטקסונומיות ולפוסטים
3. שורטקוד בסיסי לבדיקה

## שלב 2: ממשק ניהול
1. הוספת שדות לטקסונומיה `timeline`:
   - בחירת תחום דעת
   - בחירת שכבת גיל

2. הוספת שדות לטקסונומיה `timeline_topic`:
   - בחירת ציר זמן
   - מיקום (0-100)
   - אורך (0-100)
   - צבע

## שלב 3: תצוגת ציר
1. CSS בסיסי:
   - תצוגת ציר בחלק העליון
   - חלוקה לנושאים לפי צבעים
   - נעיצות בצורות שונות

2. JavaScript בסיסי:
   - חישוב מיקומים יחסיים
   - זום פנימה/החוצה
   - הצגת טולטיפים

## שלב 4: גרירה ונעיצה
1. חלונית חיפוש:
   - שדה חיפוש
   - תוצאות חיפוש
   - גרירת פריטים

2. נעיצת פריטים:
   - גרירה למיקום על הציר
   - דיאלוג בחירת צורה וצבע
   - שמירת נעיצה

3. עריכת נעיצות:
   - גרירת נעיצות קיימות
   - עדכון מיקום
   - הסתרת נעיצות

## שלב 5: שיפורים
1. אנימציות:
   - אפקט נעיצה
   - מעברי זום חלקים
   - טעינת תוכן

2. תגיות אוטומטיות:
   - הוספת תגית לפי נושא בעת נעיצה
   - הסרת תגית בעת הסרת נעיצה

# הערות חשובות
1. התוסף עובד עם פוסטים קיימים - אין צורך בסביבת יצירת תוכן
2. רק מנהלים יכולים ליצור/לערוך צירים ונושאים
3. משתמשים רגילים יכולים לנעוץ פריטים
4. הגרירה והנעיצה צריכות להיות אינטואיטיביות
5. אין צורך בתאריכים - הכל יחסי על הציר (0-100)

# דוגמה לשימוש
```php
[homer_timeline subject_id="123" class_id="456"]
```

# מערכת גרירה ונעיצה

## חוויית המשתמש
1. **חלונית חיפוש**:
   - ממוקמת בתחתית המסך
   - מכילה שדה חיפוש ותוצאות
   - תוצאות מוצגות כקלפים הניתנים לגרירה
   - חיפוש בזמן אמת (debounced)

2. **גרירת פריט**:
   - גרירה מחלונית החיפוש אל הציר
   - סמן מיוחד בזמן גרירה שמראה שאפשר לשחרר
   - אפקט hover על נושאים כשגוררים מעליהם
   - קו אנכי שמראה את נקודת הנעיצה

3. **שחרור ונעיצה**:
   - רטט קל בעת שחרור הפריט
   - הופעת דיאלוג בחירת צורה וצבע
   - אנימציית נעיצה כשהפריט מתקבע
   - הוספת תגית אוטומטית לפי הנושא

4. **עריכת נעיצות קיימות**:
   - גרירה לשינוי מיקום
   - גרירה בין נושאים
   - אפשרות להסתרה
   - מחיקת נעיצה

## מימוש טכני

1. **jQuery UI**:
```javascript
// הגדרת draggable
$('.hpt-search-item').draggable({
    helper: 'clone',
    appendTo: 'body',
    zIndex: 1000,
    start: function(e, ui) {
        // עיצוב האלמנט הנגרר
        ui.helper.addClass('hpt-dragging');
    }
});

// הגדרת droppable
$('.hpt-timeline-topic').droppable({
    accept: '.hpt-search-item, .hpt-timeline-item',
    tolerance: 'pointer',
    over: function(e, ui) {
        // הדגשת הנושא כשגוררים מעליו
        $(this).addClass('hpt-topic-hover');
    },
    drop: function(e, ui) {
        // חישוב מיקום יחסי
        const topicOffset = $(this).offset();
        const dropX = e.pageX - topicOffset.left;
        const position = (dropX / $(this).width()) * 100;
        
        // הצגת דיאלוג נעיצה
        showPinDialog(ui.draggable.data('id'), $(this).data('topic-id'), position);
    }
});
```

2. **אפקטים ויזואליים**:
```css
/* אפקט גרירה */
.hpt-dragging {
    transform: scale(0.8);
    opacity: 0.8;
    transition: all 0.2s ease;
}

/* קו נעיצה */
.hpt-drop-indicator {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #fff;
    pointer-events: none;
}

/* אנימציית נעיצה */
@keyframes pinEffect {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.hpt-pin-effect {
    animation: pinEffect 0.3s ease-out;
}
```

3. **טיפול בנעיצה**:
```javascript
function handlePin(itemId, topicId, position, type, color) {
    // שמירת הנעיצה
    $.ajax({
        url: hpt_globals.ajax_url,
        type: 'POST',
        data: {
            action: 'hpt_save_pin',
            nonce: hpt_globals.nonce,
            item_id: itemId,
            topic_id: topicId,
            position: position,
            type: type,
            color: color
        },
        success: function(response) {
            if (response.success) {
                // הוספת הפריט לציר
                addItemToTimeline(response.data);
                // הוספת אפקט נעיצה
                navigator.vibrate && navigator.vibrate(50);
                // הוספת תגית
                addTopicTag(itemId, topicId);
            }
        }
    });
}

// עדכון מיקום נעיצה קיימת
function updatePinPosition(itemId, topicId, position) {
    $.ajax({
        url: hpt_globals.ajax_url,
        type: 'POST',
        data: {
            action: 'hpt_update_pin_position',
            nonce: hpt_globals.nonce,
            item_id: itemId,
            topic_id: topicId,
            position: position
        }
    });
}
```

4. **אינטראקציה עם המשתמש**:
```javascript
// חיפוש עם debounce
let searchTimeout;
$('.hpt-search-input').on('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch($(this).val());
    }, 300);
});

// דיאלוג נעיצה
function showPinDialog(itemId, topicId, position) {
    const $dialog = $('.hpt-pin-dialog');
    
    // שמירת נתונים
    $dialog.data({
        itemId: itemId,
        topicId: topicId,
        position: position
    });

    // הצגת הדיאלוג
    $dialog.add('.hpt-overlay').addClass('open');
    
    // טיפול בשמירה
    $('.hpt-pin-save').one('click', function() {
        const type = $dialog.find('input[name="pin_type"]:checked').val();
        const color = $dialog.find('input[name="pin_color"]').val();
        handlePin(itemId, topicId, position, type, color);
        $dialog.add('.hpt-overlay').removeClass('open');
    });
}
```

## אירועי מקלדת ועכבר
1. **מקשי מקלדת**:
   - Escape - ביטול גרירה/סגירת דיאלוג
   - Delete - מחיקת נעיצה נבחרת
   - Shift+גרירה - העתקת נעיצה

2. **אירועי עכבר**:
   - לחיצה כפולה - עריכת נעיצה
   - לחיצה ימנית - תפריט הקשר
   - גלגלת - זום פנימה/החוצה