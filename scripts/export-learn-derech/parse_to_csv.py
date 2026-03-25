#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
ייצוא משאבים מקובץ XLSX (ייצוא וורדפרס / עמודת תוכן מקודדת) ל-CSV לייבוא ב-acf-csv-importer.

מצב ללא API (מומלץ כשיש בעיות מכסה/רשת):  --no-api
  רק חילוץ מהאקסל + זיהוי פלטפורמה מה-URL + ברירות מינימליות בקטגוריות/כיתות/תחום (לעריכה ידנית בגיליון).

מפתחות API (לא לשים בגיט) — רק בלי --no-api:
  .env או משתני סביבה: GEMINI_API_KEY / GOOGLE_API_KEY או ANTHROPIC_API_KEY (--llm anthropic)

מיפוי מומלץ בוורדפרס (ידני במסך הייבוא):
  כותרת → post_title
  תאריך פרסום → post_date (או שדה ACF / מטא — לפי האתר)
  פתיח → post_intro
  קישור לתוכן → post_link
  קרדיט → post_credit
  פלטפורמה → post_platform
  קישור לתמונה ראשית → post_thumbnail
  קטגוריות → tax_category | כיתות → tax_class | תחומי דעת → tax_subject | תגיות → tax_post_tag
"""

from __future__ import annotations

import argparse
import csv
import hashlib
import html as html_lib
import json
import os
import re
import sys
import time
from pathlib import Path
from typing import Any
from urllib.parse import urlparse, urljoin

from bs4 import BeautifulSoup

try:
    from dotenv import load_dotenv
except ImportError:
    load_dotenv = None  # type: ignore

# מודלים — לעדכן מול התיעוד אם השם השתנה
CLAUDE_MODEL = "claude-sonnet-4-20250514"
# gemini-1.5-flash ירד מה-API (404). ניתן לדרוס ב-.env (למשל gemini-3-flash-preview)
DEFAULT_GEMINI_MODEL = "gemini-2.5-flash"
GEMINI_MAX_RETRIES = 20
# אחרי כך ניסיונות 429/503 לשורה אחת — עוצרים (מכסה יומית/דקה; cache ימשיך בריצה הבאה)
GEMINI_QUOTA_RETRY_CAP = 8

ALLOWED_CATEGORIES = frozenset(
    {
        "מצגת",
        "פעילות",
        "מערך שיעור",
        "כלי דיגיטלי",
        "להדפסה",
        "סרטון",
        "אחר",
    }
)

ALLOWED_GRADES = frozenset(
    {
        "א",
        "ב",
        "ג",
        "ד",
        "ה",
        "ו",
        "ז",
        "ח",
        "ט",
        "י",
        "יא",
        "יב",
        "א-יב",
        "גנים",
        "מורים",
    }
)

ALLOWED_SUBJECTS = frozenset(
    {
        "אמנות",
        "תקשורת",
        "אנגלית",
        "היסטוריה",
        "כישורי חיים",
        "מדע וטכנולוגיה",
        "פיזיקה",
        "כימיה",
        "ביולוגיה",
        "כללי",
        "אחר",
        "מולדת חברה ואזרחות",
        "מעגל השנה",
        "מתמטיקה",
        "שפה",
        "לשון",
        "ספרות",
        'תנ"ך',
        "תורה",
        "נביא",
        "משנה",
        'תושב"ע',
        "תרבות יהודית ישראלית",
    }
)

CSV_HEADERS = [
    "כותרת",
    "תאריך פרסום",
    "פתיח",
    "קישור לתוכן",
    "קרדיט",
    "פלטפורמה",
    "קישור לתמונה ראשית",
    "קטגוריות",
    "תגיות",
    "כיתות",
    "תחומי דעת",
]

def _norm_header(s: str | None) -> str:
    if s is None:
        return ""
    return str(s).strip()


def find_encoded_column_index(headers: list[str]) -> int | None:
    for i, h in enumerate(headers):
        hn = _norm_header(h).lower()
        if not hn:
            continue
        if hn == "ns3:encoded" or "encoded" in hn:
            return i
    return None


def find_column_flexible(headers: list[str], needles: tuple[str, ...]) -> int | None:
    """מזהה עמודה לפי שם מדויק או חלקי (case insensitive)."""
    for i, h in enumerate(headers):
        hn = _norm_header(h).lower()
        if not hn:
            continue
        for n in needles:
            nl = n.lower()
            if hn == nl or nl in hn or hn in nl:
                return i
    return None


def _header_normalized_for_match(h: str) -> str:
    hn = _norm_header(h).lower()
    return hn.replace("_", " ").replace(":", " ")


def find_date_column_index(headers: list[str]) -> int | None:
    """תאריך פרסום מקורי של הפוסט — לפני pubDate (לעיתים תאריך הייצוא)."""
    priority_needles = (
        "post date",  # POST DATE באקסל
        "post_date",
        "ns2 post date",
        "wp post date",
        "post date gmt",
        "post_date_gmt",
        "post modified",
        "post_modified",
        "ns2 post modified",
        "pubdate",  # אחרון ברירת משנה
        "pub date",
        "published",
        "תאריך פרסום",
        "תאריך",
    )
    for needle in priority_needles:
        nl = needle.lower()
        for i, h in enumerate(headers):
            hn = _header_normalized_for_match(h)
            if not hn:
                continue
            if hn == nl or nl in hn:
                return i
    return None


def format_pub_date_cell(val: Any) -> str:
    if val is None or val == "":
        return ""
    if hasattr(val, "strftime"):
        try:
            return val.strftime("%Y-%m-%d")
        except Exception:
            pass
    s = str(val).strip()
    if not s:
        return ""
    if re.match(r"^\d{4}-\d{2}-\d{2}", s):
        return s[:10]
    m = re.search(r"(\d{4})-(\d{2})-(\d{2})", s)
    if m:
        return m.group(0)
    try:
        from email.utils import parsedate_to_datetime

        dt = parsedate_to_datetime(s)
        if dt:
            return dt.strftime("%Y-%m-%d")
    except Exception:
        pass
    return s[:32]


def resolve_row_credit(headers: list[str], row: list[Any]) -> str:
    """בוחר עמודת יוצר ראשונה עם טקסט אנושי (לא מזהה מספרי של post_author)."""
    tried: list[tuple[str, ...]] = [
        ("author_display_name", "ns2:author_display_name"),
        ("author_first_name", "ns2:author_first_name"),
        ("dc:creator", "ns4:creator"),
        ("creator",),
        ("wp:author", "author_name", "author_login", "ns2:author_login"),
        ("author",),
        ("post_author",),
    ]
    for needles in tried:
        idx = find_column_flexible(headers, needles)
        if idx is None or idx >= len(row):
            continue
        v = row[idx]
        if v is None:
            continue
        raw = re.sub(r"[\r\n]+", " ", str(v).strip())
        if not raw:
            continue
        if raw.isdigit():
            continue
        return raw[:500]
    return ""


def infer_subject_from_title(title: str) -> str:
    """יוריסטיקה לפי מילות מפתח בכותרת (סדר: ספציפי לפני כללי)."""
    t = title or ""
    rules: list[tuple[tuple[str, ...], str]] = [
        (("אנגלית", "english"), "אנגלית"),
        (("פיזיקה",), "פיזיקה"),
        (("כימיה",), "כימיה"),
        (("ביולוגיה", "אנטומיה", "צמח"), "ביולוגיה"),
        (("היסטוריה",), "היסטוריה"),
        (("גאומטריה", "חשבון", "מתמטיקה", "כפל", "חיבור", "חיסור"), "מתמטיקה"),
        (("מדע", "מדעים", "ניסוי"), "מדע וטכנולוגיה"),
        (("אמנות", "ציור", "צביעה", "יצירה"), "אמנות"),
        (("תקשורת", "מדיה"), "תקשורת"),
        (("כישורי חיים",), "כישורי חיים"),
        (("רגש", "אוורור", "חברתי"), "כישורי חיים"),
        (("מולדת", "אזרחות"), "מולדת חברה ואזרחות"),
        (("מעגל השנה", "חג", "חנוכה", "פסח"), "מעגל השנה"),
        (("לשון", "דקדוק", "תחביר"), "לשון"),
        (("משנה",), "משנה"),
        (('תושב"ע', "תלמוד"), 'תושב"ע'),
        (("נביא",), "נביא"),
        (("תורה", "פרשה", "פרשת"), "תורה"),
        (('תנ"ך', "תנך", "מקרא"), 'תנ"ך'),
        (("תרבות יהודית", "יהדות", "מסורת"), "תרבות יהודית ישראלית"),
        (("ספרות",), "ספרות"),
        (
            (
                "קמץ",
                "פתח",
                "שווא",
                "ניקוד",
                "דיבור",
                "הברה",
                "פונולוג",
                "אות",
                "עברית",
                "קריאה",
                "כתיבה",
                "הבנת הנקרא",
                "מילה",
                "משפט",
            ),
            "שפה",
        ),
    ]
    for kws, subj in rules:
        for kw in kws:
            if kw in t:
                return subj
    return "כללי"


def infer_category_from_title_and_url(title: str, content_url: str) -> list[str]:
    t = title or ""
    u = (content_url or "").lower()
    if "מצגת" in t:
        return ["מצגת"]
    if "youtube.com" in u or "youtu.be" in u:
        return ["סרטון"]
    if u.endswith(".pdf") or ".pdf?" in u or ".pdf&" in u:
        return ["להדפסה"]
    return ["פעילות"]


def normalize_content_url(url: str, base_url: str | None) -> str | None:
    url = (url or "").strip()
    if not url:
        return None
    if url.startswith("//"):
        url = "https:" + url
    parsed = urlparse(url)
    if parsed.scheme and parsed.netloc:
        return url
    if base_url:
        return urljoin(base_url.rstrip("/") + "/", url)
    return None


def normalize_url_key(url: str) -> str:
    try:
        p = urlparse(url.strip())
        netloc = (p.netloc or "").lower()
        if netloc.startswith("www."):
            netloc = netloc[4:]
        path = (p.path or "").rstrip("/") or "/"
        return f"{netloc}{path}".lower()
    except Exception:
        return url.strip().lower()


def detect_platform(content_url: str) -> str:
    u = (content_url or "").lower()
    parsed = urlparse(content_url)
    path = (parsed.path or "").lower()
    host = (parsed.netloc or "").lower()
    if host.startswith("www."):
        host = host[4:]

    # קישורי Gemini / Bard (לא להסתמך על "g.co" כ-G בלבד)
    if host == "g.co" and "gemini" in path:
        return "Gemini"
    if "gemini" in host or host == "gemini.google.com":
        return "Gemini"
    if "bard.google.com" in host:
        return "Gemini"
    if "makersuite.google.com" in host or "aistudio.google.com" in host:
        return "Gemini"
    if host == "g.co":
        return "Google"

    if "youtu.be" in host or "youtube.com" in host:
        return "YouTube"
    if "vimeo.com" in host:
        return "Vimeo"
    if "canva.site" in host or "canva.com" in host:
        return "Canva"
    # Genially — לפני heuristics של "view" כתת-דומיין
    if "genially.com" in host:
        return "Genially"
    if host.endswith("genial.ly") or "genial.ly" in host:
        return "Genially"
    if "docs.google.com" in host:
        if "/presentation" in path or "/presentation" in u:
            return "Google Slides"
        return "Google Docs"
    if "drive.google.com" in host:
        return "Google Drive"
    if "padlet.com" in host:
        return "Padlet"
    if "miro.com" in host or "miro.app" in host:
        return "Miro"
    if "slides.com" in host:
        return "Slides.com"
    if "prezi.com" in host:
        return "Prezi"
    if "wordwall.net" in host:
        return "Wordwall"
    if "learningapps.org" in host:
        return "LearningApps"
    if "websim.com" in host or "websim." in u:
        return "WebSim"

    if host:
        parts = host.split(".")
        brand = parts[0]
        if brand in ("www", "app", "my") and len(parts) > 1:
            brand = parts[1]
        if brand == "view" and len(parts) > 1:
            brand = parts[1]
        return brand.replace("-", " ").title() if brand else host
    return "אחר"


def _clean_text(el) -> str:
    if el is None:
        return ""
    return re.sub(r"\s+", " ", el.get_text(strip=True))


def extract_figures_from_html(
    html_raw: str, base_url: str | None
) -> list[dict[str, str]]:
    if not html_raw or not str(html_raw).strip():
        return []
    text = html_lib.unescape(str(html_raw))
    soup = BeautifulSoup(text, "html.parser")
    out: list[dict[str, str]] = []
    for fig in soup.find_all("figure"):
        a = fig.find("a", href=True)
        img = fig.find("img")
        cap = fig.find("figcaption")
        if not a:
            continue
        href = a.get("href") or ""
        abs_url = normalize_content_url(href, base_url)
        if not abs_url:
            continue
        img_src = ""
        if img and img.get("src"):
            img_src = normalize_content_url(img["src"], base_url) or img["src"].strip()
        title = _clean_text(cap)
        if not title and img and img.get("alt"):
            title = (img.get("alt") or "").strip()
        if not title:
            title = _clean_text(a)
        if not title:
            title = "(ללא כותרת)"
        out.append(
            {
                "title": title,
                "content_url": abs_url,
                "thumb_url": img_src or "",
            }
        )
    return out


def load_cache(path: Path) -> dict[str, Any]:
    if not path.exists():
        return {}
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except json.JSONDecodeError:
        return {}


def save_cache(path: Path, data: dict[str, Any]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")


def cache_key(title: str, url: str) -> str:
    h = hashlib.sha256(f"{title}\n{url}".encode("utf-8")).hexdigest()
    return h


def parse_llm_json(text: str) -> dict[str, Any]:
    text = text.strip()
    m = re.search(r"```(?:json)?\s*([\s\S]*?)\s*```", text)
    if m:
        text = m.group(1).strip()
    return json.loads(text)


def validate_enrichment(d: dict[str, Any]) -> dict[str, Any]:
    categories = [c.strip() for c in d.get("categories") or [] if str(c).strip()]
    categories = [c for c in categories if c in ALLOWED_CATEGORIES]
    if not categories:
        categories = ["פעילות"]
    grades = [g.strip() for g in d.get("grades") or [] if str(g).strip()]
    grades = [g for g in grades if g in ALLOWED_GRADES]
    if not grades:
        grades = ["א", "ב"]
    subj = (d.get("subject") or "").strip()
    if subj not in ALLOWED_SUBJECTS:
        subj = ""
    if not subj:
        subj = "כללי"
    tags = d.get("tags") or []
    if isinstance(tags, str):
        tags = [t.strip() for t in tags.split(",") if t.strip()]
    tags = [str(t).strip() for t in tags if str(t).strip()][:12]
    desc = (d.get("description") or "").strip()
    return {
        "categories": categories,
        "grades": grades,
        "subject": subj,
        "tags": tags,
        "description": desc,
    }


def build_prompt(title: str, content_url: str, platform: str) -> str:
    cat_list = ", ".join(sorted(ALLOWED_CATEGORIES))
    grade_list = ", ".join(sorted(ALLOWED_GRADES))
    subj_list = ", ".join(sorted(ALLOWED_SUBJECTS))
    return f"""אתה מסווג משאב חינוכי בעברית. החזר JSON תקף בלבד (בלי טקסט לפני או אחרי).

שדות JSON:
- "categories": מערך מחרוזות מתוך הרשימה בלבד: {cat_list}
- "grades": מערך מחרוזות מתוך הרשימה בלבד: {grade_list}
- "subject": מחרוזת אחת בדיוק מתוך הרשימה: {subj_list} (או מחרוזת ריקה אם אי אפשר)
- "tags": מערך של 3 עד 8 תגיות חופשיות בעברית (מילות מפתח קצרות)
- "description": פתיח קצר בעברית — מה הפריט ומה מטרתו; בלי להזכיר את שם הפלטפורמה בטקסט

מידע על המשאב:
- כותרת: {title}
- קישור: {content_url}
- פלטפורמה (לשימושך בלבד, לא לשכפל בפתיח): {platform}

הנחיות: בחר קטגוריות וכיתות (אפשר א,ב,ג או א-יב, גנים, מורים); לתחום דעת בחר מהרשימה המדויקת בהתאם לכותרת.
"""


def enrichment_offline_simple(
    title: str, platform: str, content_url: str = ""
) -> dict[str, Any]:
    """ללא API: פתיח = כותרת בלבד (בלי פלטפורמה); קטגוריה ברירת מחדל פעילות; כיתות א,ב; תחום לפי יוריסטיקה."""
    intro = (title or "").strip() or "(ללא כותרת)"
    return {
        "categories": infer_category_from_title_and_url(title, content_url),
        "grades": ["א", "ב"],
        "subject": infer_subject_from_title(title),
        "tags": [],
        "description": intro[:500],
    }


def call_claude(
    title: str,
    content_url: str,
    platform: str,
    dry_run: bool,
    api_key: str | None,
) -> dict[str, Any]:
    if dry_run:
        return enrichment_offline_simple(title, platform, content_url)
    if not api_key:
        raise SystemExit(
            "חסר ANTHROPIC_API_KEY — הגדר ב-.env או הרץ עם --no-api / --llm gemini"
        )
    import anthropic

    client = anthropic.Anthropic(api_key=api_key)
    prompt = build_prompt(title, content_url, platform)
    msg = client.messages.create(
        model=CLAUDE_MODEL,
        max_tokens=1024,
        messages=[{"role": "user", "content": prompt}],
    )
    parts: list[str] = []
    for block in msg.content:
        if hasattr(block, "text"):
            parts.append(block.text)
    text = "".join(parts) if parts else str(msg.content)
    data = parse_llm_json(text)
    return validate_enrichment(data)


def _gemini_retry_sleep_seconds(exc_message: str, attempt: int) -> float:
    m = re.search(r"retry in ([\d.]+)\s*s", exc_message, re.I)
    if m:
        return min(120.0, float(m.group(1)) + 2.0)
    return min(120.0, 8.0 * (attempt + 1))


def _resolve_gemini_model_name() -> str:
    """תווי BOM/מחרוזות ישנות; מודלי 1.5 הוסרו מה-API."""
    raw = (os.environ.get("GEMINI_MODEL") or DEFAULT_GEMINI_MODEL).strip().strip(
        "\"'"
    )
    if not raw:
        return DEFAULT_GEMINI_MODEL
    low = raw.lower()
    if "1.5-flash" in low or "1.5-pro" in low or low in (
        "gemini-1.5-flash",
        "gemini-1.5-flash-latest",
        "gemini-1.5-pro",
    ):
        print(
            f"[Gemini] המודל {raw!r} לא זמין ב-v1beta — מחליפים ל-{DEFAULT_GEMINI_MODEL}",
            file=sys.stderr,
        )
        return DEFAULT_GEMINI_MODEL
    return raw


def call_gemini(
    title: str,
    content_url: str,
    platform: str,
    dry_run: bool,
    api_key: str | None,
    max_retries: int = GEMINI_MAX_RETRIES,
) -> dict[str, Any]:
    if dry_run:
        return enrichment_offline_simple(title, platform, content_url)
    if not api_key:
        raise SystemExit(
            "חסר GEMINI_API_KEY או GOOGLE_API_KEY — שים ב-.env או הרץ עם --no-api"
        )
    import httpx
    from google import genai
    from google.genai import errors as genai_errors
    from google.genai import types

    model_name = _resolve_gemini_model_name()
    client = genai.Client(api_key=api_key)
    prompt = build_prompt(title, content_url, platform)

    response = None
    quota_strikes = 0
    for attempt in range(max_retries):
        try:
            response = client.models.generate_content(
                model=model_name,
                contents=prompt,
                config=types.GenerateContentConfig(
                    response_mime_type="application/json",
                    temperature=0.2,
                ),
            )
            break
        except (
            httpx.ConnectError,
            httpx.ConnectTimeout,
            httpx.ReadTimeout,
            httpx.WriteTimeout,
        ) as e:
            if attempt >= max_retries - 1:
                raise SystemExit(
                    "שגיאת רשת (אין חיבור / DNS). חברי אינטרנט והריצי שוב אותה פקודה — "
                    "הקובץ .enrichment_cache.json שומר התקדמות.\n"
                    f"פרטים: {e}"
                ) from e
            wait = min(120.0, 15.0 * (attempt + 1))
            print(
                f"[Gemini] רשת — ממתין {wait:.0f} שניות ({attempt + 1}/{max_retries})...",
                file=sys.stderr,
            )
            time.sleep(wait)
        except genai_errors.APIError as e:
            if e.code == 404:
                raise SystemExit(
                    f"מודל Gemini לא נמצא (404): {model_name!r}\n"
                    "עדכני ב-.env לדוגמה:\n"
                    "  GEMINI_MODEL=gemini-2.5-flash\n"
                    "  או GEMINI_MODEL=gemini-3-flash-preview\n"
                    f"פרטים: {e.message or e}"
                ) from e
            retry_codes = (429, 503)
            if e.code not in retry_codes:
                raise SystemExit(
                    f"שגיאת Gemini ({e.code}): {e.message or e}"
                ) from e
            quota_strikes += 1
            err_txt = str(e)
            if quota_strikes >= GEMINI_QUOTA_RETRY_CAP:
                raise SystemExit(
                    "חוזרות יותר מדי שגיאות מכסה (429/503) — עוצרים כדי לא לבזבז זמן.\n"
                    "• נסי שוב מאוחר יותר (מחר או אחרי שעה); אותה פקודה — הקאש ימשיך\n"
                    "• הגדלי --delay ל-10 או 30; או GEMINI_MODEL=gemini-3-flash-preview\n"
                    "• לייצוא בלי AI: --dry-run (ערכי מילוי), אחר כך עריכה ידנית בגיליון\n"
                    f"פרטים: {err_txt[:900]}"
                ) from e
            if attempt >= max_retries - 1:
                raise SystemExit(
                    "מכסת Gemini / שרת אחרי כל ניסיונות השחזור.\n"
                    "• המתן או הגדל --delay\n"
                    f"פרטים: {err_txt[:900]}"
                ) from e
            wait = _gemini_retry_sleep_seconds(err_txt, attempt)
            print(
                f"[Gemini] {e.code} — ממתין {wait:.0f} שניות "
                f"(מכסה {quota_strikes}/{GEMINI_QUOTA_RETRY_CAP}, ניסיון {attempt + 1}/{max_retries})...",
                file=sys.stderr,
            )
            time.sleep(wait)
    assert response is not None
    text = response.text
    if not text:
        raise SystemExit("תשובת Gemini ריקה — נסי מודל אחר או הרצה חוזרת.")
    data = parse_llm_json(text)
    return validate_enrichment(data)


def enrich_row(
    title: str,
    content_url: str,
    platform: str,
    dry_run: bool,
    llm: str,
    anthropic_key: str | None,
    gemini_key: str | None,
    gemini_max_retries: int,
) -> dict[str, Any]:
    if llm == "gemini":
        return call_gemini(
            title, content_url, platform, dry_run, gemini_key, gemini_max_retries
        )
    return call_claude(title, content_url, platform, dry_run, anthropic_key)


def read_workbook_rows(
    xlsx_path: Path, sheet_name: str | None
) -> tuple[list[str], list[list[Any]]]:
    import openpyxl

    wb = openpyxl.load_workbook(xlsx_path, read_only=True, data_only=True)
    ws = wb[sheet_name] if sheet_name else wb.active
    rows_iter = ws.iter_rows(values_only=True)
    try:
        header_row = next(rows_iter)
    except StopIteration:
        wb.close()
        return [], []
    headers = [_norm_header(c) for c in header_row]
    data = [list(r) for r in rows_iter]
    wb.close()
    return headers, data


def resolve_link_base(headers: list[str], row: list[Any]) -> str | None:
    """מחפש עמודת קישור לפוסט לפי שמות נפוצים בייצוא וורדפרס."""
    candidates = ("link", "guid", "wp:permalink", "permalink", "קישור")
    lower_map = {h.lower(): i for i, h in enumerate(headers)}
    for c in candidates:
        idx = lower_map.get(c.lower())
        if idx is not None and idx < len(row):
            v = row[idx]
            if v and str(v).strip().startswith("http"):
                return str(v).strip()
    return None


def list_columns(xlsx_path: Path, sheet_name: str | None) -> None:
    headers, _ = read_workbook_rows(xlsx_path, sheet_name)
    enc = find_encoded_column_index(headers)
    print(f"גיליון: {sheet_name or '(ברירת מחדל)'}")
    print(f"סה\"כ עמודות: {len(headers)}")
    for i, h in enumerate(headers):
        mark = " ← נבחרה עמודת encoded" if i == enc else ""
        print(f"  [{i}] {h!r}{mark}")
    if enc is None:
        print("\nאזהרה: לא נמצאה עמודה עם 'encoded' בשם.")


def main() -> None:
    parser = argparse.ArgumentParser(
        description="חילוץ figure מ-XLSX (תוכן HTML) וייצוא CSV לחומר פתוח",
    )
    parser.add_argument(
        "--input",
        "-i",
        type=Path,
        required=True,
        help="נתיב לקובץ .xlsx",
    )
    parser.add_argument(
        "--output",
        "-o",
        type=Path,
        default=None,
        help="נתיב לקובץ CSV (ברירת מחדל: ליד קובץ ה-XLSX, <שם>_parsed.csv)",
    )
    parser.add_argument("--sheet", type=str, default=None, help="שם גיליון (ברירת מחדל: פעיל)")
    parser.add_argument(
        "--base-url",
        type=str,
        default=None,
        help="בסיס לאיחוד URL יחסי (אופציונלי)",
    )
    parser.add_argument(
        "--no-api",
        action="store_true",
        help="ייצוב פשוט: בלי Gemini/Claude, בלי מפתחות ומכסה — רק חילוץ + פלטפורמה + ברירות מינימליות",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="מעודכן כמו --no-api (תאימות לאחור)",
    )
    parser.add_argument(
        "--list-columns",
        action="store_true",
        help="הדפס עמודות וצא",
    )
    parser.add_argument(
        "--no-dedupe",
        action="store_true",
        help="אל תסיר כפילויות לפי קישור תוכן",
    )
    parser.add_argument(
        "--cache",
        type=Path,
        default=None,
        help="קובץ cache ל-JSON (ברירת מחדל: ליד הסקריפט .enrichment_cache.json)",
    )
    parser.add_argument(
        "--delay",
        type=float,
        default=0.25,
        help="השהיה בשניות בין קריאות API (ברירת מחדל 0.25)",
    )
    parser.add_argument(
        "--llm",
        choices=("gemini", "anthropic"),
        default="gemini",
        help="ספק העשרה: gemini (ברירת מחדל) או anthropic (Claude)",
    )
    parser.add_argument(
        "--gemini-retries",
        type=int,
        default=GEMINI_MAX_RETRIES,
        metavar="N",
        help="ניסיונות חוזרים אחרי 429/מכסה Gemini (ברירת מחדל %(default)s)",
    )
    args = parser.parse_args()
    use_offline = bool(args.no_api or args.dry_run)

    script_dir = Path(__file__).resolve().parent
    if load_dotenv is not None:
        env_path = script_dir / ".env"
        load_dotenv(env_path)

    xlsx_path = args.input.expanduser().resolve()
    if not xlsx_path.is_file():
        sys.exit(f"קובץ לא נמצא: {xlsx_path}")

    if args.list_columns:
        list_columns(xlsx_path, args.sheet)
        return

    headers, rows = read_workbook_rows(xlsx_path, args.sheet)
    enc_idx = find_encoded_column_index(headers)
    if enc_idx is None:
        sys.exit(
            "לא נמצאה עמודת encoded. הרץ עם --list-columns ובדוק את שמות העמודות."
        )

    date_col_idx = find_date_column_index(headers)
    if date_col_idx is not None:
        print(
            f"עמודת תאריך נבחרה: {headers[date_col_idx]!r} (אינדקס {date_col_idx})",
            file=sys.stderr,
        )
    else:
        print("לא נמצאה עמודת תאריך — עמודת «תאריך פרסום» תישאר ריקה.", file=sys.stderr)
    cache_path = args.cache or (script_dir / ".enrichment_cache.json")
    cache: dict[str, Any] = {}
    if not use_offline:
        cache = load_cache(cache_path)
    else:
        print(
            "מצב ללא API — אין קריאות למודל שפה; ערכי קטגוריה/כיתה/תחום — ברירת מחדל לעריכה בגיליון.",
            file=sys.stderr,
        )

    figures_flat: list[dict[str, Any]] = []
    for row in rows:
        if enc_idx >= len(row):
            continue
        cell = row[enc_idx]
        if cell is None:
            continue
        base = args.base_url or resolve_link_base(headers, row)
        pub_date = ""
        if date_col_idx is not None and date_col_idx < len(row):
            pub_date = format_pub_date_cell(row[date_col_idx])
        credit_cell = resolve_row_credit(headers, row)
        for fig in extract_figures_from_html(str(cell), base):
            fig["_source_base"] = base
            fig["pub_date"] = pub_date
            fig["credit"] = credit_cell
            figures_flat.append(fig)

    seen_keys: set[str] = set()
    unique_figs: list[dict[str, Any]] = []
    for fig in figures_flat:
        key = normalize_url_key(fig["content_url"])
        if not args.no_dedupe and key in seen_keys:
            continue
        seen_keys.add(key)
        unique_figs.append(fig)

    anthropic_key = os.environ.get("ANTHROPIC_API_KEY")
    gemini_key = os.environ.get("GEMINI_API_KEY") or os.environ.get("GOOGLE_API_KEY")

    out_rows: list[list[str]] = []
    for fig in unique_figs:
        title = fig["title"]
        url = fig["content_url"]
        platform = detect_platform(url)
        if use_offline:
            enriched = enrichment_offline_simple(title, platform, url)
        else:
            ck = cache_key(title, url)
            if ck in cache:
                enriched = validate_enrichment(cache[ck])
            else:
                enriched = enrich_row(
                    title,
                    url,
                    platform,
                    False,
                    args.llm,
                    anthropic_key,
                    gemini_key,
                    args.gemini_retries,
                )
                cache[ck] = enriched
                save_cache(cache_path, cache)
                if args.delay > 0:
                    time.sleep(args.delay)

        cat_str = ",".join(enriched["categories"])
        grades_str = ",".join(enriched["grades"])
        tags_str = ",".join(enriched["tags"])
        subj_str = enriched["subject"]

        out_rows.append(
            [
                title,
                fig.get("pub_date", "") or "",
                enriched["description"],
                url,
                fig.get("credit", "") or "",
                platform,
                fig["thumb_url"],
                cat_str,
                tags_str,
                grades_str,
                subj_str,
            ]
        )

    out_path = args.output
    if out_path is None:
        out_path = xlsx_path.with_name(f"{xlsx_path.stem}_parsed.csv")
    else:
        out_path = out_path.expanduser().resolve()
    out_path.parent.mkdir(parents=True, exist_ok=True)

    with out_path.open("w", encoding="utf-8-sig", newline="") as f:
        w = csv.writer(f)
        w.writerow(CSV_HEADERS)
        w.writerows(out_rows)

    print(f"נכתבו {len(out_rows)} שורות ל-{out_path}")


if __name__ == "__main__":
    main()
