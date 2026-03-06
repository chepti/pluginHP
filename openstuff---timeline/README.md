# OpenStuff Academic Year Timeline

תוסף Gutenberg לציר זמן שנתי - ארגון חומרי למידה לפי נושאים עם גרירה ושחרור.

## יצירת ZIP להפצה

לאחר `npm run build`, הרץ:
```bash
npm run zip
```
נוצר `openstuff-timeline.zip` בתיקיית ADDONS עם: `openstuff-timeline.php`, `includes/`, `build/`, `README.md` — בלי `node_modules` ו-`src`.

## התקנה

1. העתק את התיקייה `openstuff-timeline` (או חלץ את ה-ZIP) לתיקיית `wp-content/plugins/`
2. הרץ בנייה (רק לפיתוח):
   ```bash
   cd openstuff-timeline
   npm install
   npm run build
   ```
3. הפעל את התוסף בלוח הבקרה

## מבנה

- `openstuff-timeline.php` - קובץ ראשי, רישום CPTs ו-REST
- `includes/` - מחלקות PHP
- `src/block/` - בלוק עורך (המחסן + ציר)
- `src/frontend/` - בלוק תצוגה
- `build/` - פלט Webpack (נוצר אחרי `npm run build`)

## בלוקים

- **ציר זמן שנתי (עורך)** - עורך מלא עם המחסן וציר הנושאים. גרור חומרים מהמחסן אל נושאים.
- **ציר זמן שנתי (תצוגה)** - תצוגה ציבורית לקוראים.

## REST API

- `GET /os-timeline/v1/timelines` - רשימת צירים
- `GET /os-timeline/v1/timeline/{id}` - ציר מלא עם נושאים ונעיצות
- `POST /os-timeline/v1/pin` - יצירת נעיצה (מחובר)
- `PUT /os-timeline/v1/pin/{id}/approve` - אישור נעיצה (מנהל)
- `GET /os-timeline/v1/posts?timeline={id}` - פוסטים התואמים לציר
- `POST /os-timeline/v1/topic` - יצירת נושא (מחובר)

## טקסונומיות נדרשות

התוסף משתמש ב:
- `subject` (תחום דעת)
- `class` (כיתה)
- `category` (לסינון סוג תוכן)

## דרישות

- WordPress 6.0+
- PHP 8.0+
- Node.js (לבנייה)
