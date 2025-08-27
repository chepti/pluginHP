# Homer Patuach - Annual Timeline
**גרסה:** 1.0.0
**מחבר:** Chepti

## תיאור
תוסף ציר זמן שנתי אינטראקטיבי ללמידה עם אפשרות גרירה והנחה של פריטים. התוסף מאפשר ליצור צירי זמן מותאמים אישית לקבוצות לימוד שונות (תחום דעת + שכבת גיל) עם ממשק גרירה אינטואיטיבי.

## תכונות עיקריות

### 🎯 ציר זמן אינטראקטיבי
- **ציר זמן חזותי** עם ספריית Vis.js
- **הגדלה והקטנה דינמית** של הציר
- **ניווט אינטואיטיבי** עם כפתורי בקרה
- **תצוגה מותאמת** למסך מלא

### 🎨 ניהול נושאים
- **נושאי לימוד מוגדרים** עם טווחי תאריכים
- **צבעים מותאמים אישית** לכל נושא
- **עריכת נושאים** בזמן אמת
- **חלוקה לרצועות** שונות בציר

### 📚 חיפוש והוספה
- **חיפוש מתקדם** של פריטים
- **גרירה והנחה** אינטואיטיבית
- **בחירת צורה וצבע** לכל פריט
- **תמיכה ב-4 צורות:** ריבוע, עיגול, משולש, כוכב
- **צבעים מותאמים** או צבעי נושא

### 👥 ניהול קבוצות
- **ציר זמן לכל קבוצת לימוד** נפרדת
- **שיוך לפי תחום דעת** ושכבת גיל
- **הרשאות מותאמות** לעורכים
- **ממשק ניהול ידידותי**

## דרישות מערכת

### WordPress
- **גרסה מינימלית:** 5.0
- **גרסה מומלצת:** 6.0+
- **PHP:** 7.4+

### תוספים נדרשים
- **ACF Pro** - לתמיכה בשדות מותאמים
- **BuddyPress** (אופציונלי) - לשילוב עם פרופילי משתמשים

### מסד נתונים
התוסף יוצר אוטומטית את הטבלאות הנדרשות:
- `wp_hpat_timelines` - צירי זמן
- `wp_hpat_timeline_topics` - נושאי לימוד
- `wp_hpat_timeline_items` - פריטים בציר

## התקנה

### 1. התקנה ידנית
1. הורד את קבצי התוסף
2. העלה את התיקיה `homer-patuach-annual-timeline` לתיקיה `wp-content/plugins/`
3. הפעל את התוסף דרך מסך התוספים ב-WordPress

### 2. התקנה באמצעות Git
```bash
cd wp-content/plugins
git clone [repository-url] homer-patuach-annual-timeline
```

### 3. הפעלה ראשונה
1. עבור אל **תוספים > Homer Patuach - Annual Timeline**
2. לחץ על **הגדרות** כדי להתאים את התוסף לצרכים שלך
3. צור ציר זמן ראשון דרך **צירי זמן > צור ציר זמן חדש**

## שימוש

### יצירת ציר זמן חדש
1. עבור אל **Homer Patuach > צירי זמן**
2. לחץ על **צור ציר זמן חדש**
3. הזן:
   - **מזהה קבוצה:** מזהה ייחודי (לדוגמה: `math_7th_grade`)
   - **שם הקבוצה:** שם תיאורי (לדוגמה: `מתמטיקה - כיתה ז'`)
   - **שנת לימוד:** שנת הלימוד הרלוונטית

### הוספת נושאי לימוד
1. ערוך את הציר הזמן הרצוי
2. הוסף נושאים עם:
   - כותרת
   - תאריך התחלה וסיום
   - צבע
   - סדר הצגה

### שימוש בשורטקוד
```php
[annual_timeline group_id="math_7th_grade" academic_year="2024-2025" height="500"]
```

#### פרמטרים זמינים:
- `group_id` - מזהה קבוצת הלימוד
- `academic_year` - שנת הלימוד
- `height` - גובה הציר בפיקסלים (ברירת מחדל: 400)
- `zoom_level` - רמת הגדלה ברירת מחדל (ברירת מחדל: 30)
- `show_search` - הצג חיפוש (true/false)
- `show_controls` - הצג כפתורי בקרה (true/false)

## ממשק המשתמש

### למשתמשים רגילים
- **חיפוש פריטים** בתחתית המסך
- **גרירה והנחה** לציר הזמן
- **בחירת צורה וצבע** לכל פריט
- **הגדלה והקטנה** של הציר

### לעורכים ומנהלים
- **עריכת נושאים** בזמן אמת
- **ניהול צירי זמן** מלא
- **הגדרות מתקדמות** בממשק הניהול
- **סטטיסטיקות** ודוחות

## ממשקי API

### REST API Endpoints
```
GET  /wp-json/hpat/v1/timelines
GET  /wp-json/hpat/v1/timeline/{id}
POST /wp-json/hpat/v1/timeline/{id}/items
```

### JavaScript Events
```javascript
// פריט נוסף לציר
$(document).on('hpat:item-added', function(e, data) {
    console.log('Item added:', data);
});

// ציר עודכן
$(document).on('hpat:timeline-updated', function(e, data) {
    console.log('Timeline updated:', data);
});
```

## הרשאות

### משתמשים רגילים
- צפייה בציר הזמן
- חיפוש פריטים
- הוספת פריטים לציר

### עורכים
- כל ההרשאות של משתמשים רגילים
- עריכת נושאי לימוד
- ניהול צירי זמן

### מנהלים
- כל ההרשאות של עורכים
- הגדרות התוסף
- ניהול כל הצירים
- סטטיסטיקות מערכת

## התאמות אישיות

### CSS
התוסף מגיע עם קבצי CSS מותאמים שניתן לעקוף ב-theme:
```css
/* דוגמה לעקיפת סגנון */
.hpat-timeline-container {
    /* התאמות אישיות */
}
```

### JavaScript
ניתן להרחיב את הפונקציונליות:
```javascript
// דוגמה להרחבת אירועים
$(document).on('hpat:timeline-loaded', function(e, timeline) {
    // קוד מותאם אישית
});
```

## פתרון בעיות

### בעיות נפוצות

#### הציר לא מוצג
1. בדוק שיש נתונים בציר הזמן
2. ודא שספריית Vis.js נטענה
3. בדוק שיש הרשאות מתאימות

#### לא ניתן לגרור פריטים
1. ודא שהגדרת `enable_drag_drop` פעילה
2. בדוק שיש הרשאות הוספת פריטים
3. ודא שה-JavaScript נטען ללא שגיאות

#### בעיות ביצועים
1. הגבל את מספר הפריטים המוצגים
2. השתמש ב-caching
3. בדוק את הגדרות מסד הנתונים

### לוג שגיאות
התוסף רושם שגיאות בקובץ `wp-content/debug.log` כאשר WP_DEBUG פעיל.

## עדכונים עתידיים

### גרסה 1.1 (מתוכננת)
- תמיכה בייצוא/יבוא צירי זמן
- ממשק נייד משופר
- אינטגרציה עם Google Calendar

### גרסה 1.2 (מתוכננת)
- מצב שיתוף עם תלמידים
- מערכת התראות
- דוחות התקדמות

## תמיכה
לשאלות ותמיכה:
- פורום התמיכה: [forum-url]
- דוא"ל: support@homerpatuach.com
- מסמכים: [docs-url]

## רישיון
התוסף מופץ תחת רישיון GPL-2.0+

## קרדיט
פותח על ידי צוות Homer Patuach
- עיצוב UI/UX: [designer-name]
- פיתוח Backend: Chepti
- QA וטסטים: [qa-name]
