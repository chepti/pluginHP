#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
ייצוא משאבים מקובץ XLSX (ייצוא וורדפרס / עמודת תוכן מקודדת) ל-CSV לייבוא ב-acf-csv-importer.

מיפוי מומלץ בוורדפרס (לא אוטומטי — ידני במסך הייבוא):
  כותרת → post_title
  תיאור → post_intro
  קישור לתוכן → post_link
  פלטפורמה → post_platform
  קישור לתמונה ראשית → post_thumbnail
  קטגוריות → tax_category
  כיתות → tax_class
  תחומי דעת → tax_subject
  תגיות → tax_post_tag
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

# מודל — לעדכן מול https://docs.anthropic.com אם השם השתנה
CLAUDE_MODEL = "claude-sonnet-4-20250514"

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

ALLOWED_GRADES = frozenset({"א-יב", "גנים", "מורים"})

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
    "תיאור",
    "קישור לתוכן",
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
    u = content_url.lower()
    parsed = urlparse(content_url)
    path = (parsed.path or "").lower()
    host = (parsed.netloc or "").lower()
    if host.startswith("www."):
        host = host[4:]

    if "youtu.be" in host or "youtube.com" in host:
        return "YouTube"
    if "vimeo.com" in host:
        return "Vimeo"
    if "canva.com" in host:
        return "Canva"
    if "view.genial.ly" in host or "genial.ly" in host:
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

    if host:
        return host.split(".")[0].title() if "." in host else host
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
    grades = [g.strip() for g in d.get("grades") or [] if str(g).strip()]
    grades = [g for g in grades if g in ALLOWED_GRADES]
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
- "description": תיאור קצר בעברית, משפט אחד או שניים, מתאים לפתיח באתר

מידע על המשאב:
- כותרת: {title}
- קישור: {content_url}
- פלטפורמה שזוהתה אוטומטית: {platform}

הנחיות: בחר קטגוריות וכיתות רלוונטיות; אם לא בטוח — העדף "אחר" בקטגוריות ו-"א-יב" בכיתות אם זה מתאים לבית ספר."""


def call_claude(
    title: str,
    content_url: str,
    platform: str,
    dry_run: bool,
    api_key: str | None,
) -> dict[str, Any]:
    if dry_run:
        return {
            "categories": ["אחר"],
            "grades": ["א-יב"],
            "subject": "כללי",
            "tags": ["ייבוא", "משאב"],
            "description": f"משאב: {title[:200]}",
        }
    if not api_key:
        raise SystemExit("חסר ANTHROPIC_API_KEY (סביבה) — או הרץ עם --dry-run")
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
    parser.add_argument("--dry-run", action="store_true", help="ללא קריאות ל-API; ערכי דמה")
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
    args = parser.parse_args()

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

    script_dir = Path(__file__).resolve().parent
    cache_path = args.cache or (script_dir / ".enrichment_cache.json")
    cache = load_cache(cache_path)

    figures_flat: list[dict[str, Any]] = []
    for row in rows:
        if enc_idx >= len(row):
            continue
        cell = row[enc_idx]
        if cell is None:
            continue
        base = args.base_url or resolve_link_base(headers, row)
        for fig in extract_figures_from_html(str(cell), base):
            fig["_source_base"] = base
            figures_flat.append(fig)

    seen_keys: set[str] = set()
    unique_figs: list[dict[str, Any]] = []
    for fig in figures_flat:
        key = normalize_url_key(fig["content_url"])
        if not args.no_dedupe and key in seen_keys:
            continue
        seen_keys.add(key)
        unique_figs.append(fig)

    api_key = os.environ.get("ANTHROPIC_API_KEY")

    out_rows: list[list[str]] = []
    for fig in unique_figs:
        title = fig["title"]
        url = fig["content_url"]
        platform = detect_platform(url)
        ck = cache_key(title, url)
        if ck in cache:
            enriched = validate_enrichment(cache[ck])
        else:
            enriched = call_claude(title, url, platform, args.dry_run, api_key)
            cache[ck] = enriched
            save_cache(cache_path, cache)
            if not args.dry_run and args.delay > 0:
                time.sleep(args.delay)

        cat_str = ",".join(enriched["categories"])
        grades_str = ",".join(enriched["grades"])
        tags_str = ",".join(enriched["tags"])
        subj_str = enriched["subject"]

        out_rows.append(
            [
                title,
                enriched["description"],
                url,
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
