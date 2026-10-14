#!/usr/bin/env python3
"""
validate_course.py — Pre-Upload Validator for local_coursebuilder CSV and XML

Validates course structure CSV and Moodle XML question banks before uploading to Moodle.
Simulates Moodle's CSV parser and question_importer scoring algorithm to guarantee
zero runtime import failures and 100% question-to-quiz auto-mapping success.

Usage:
    python3 validate_course.py <course.csv> [questions.xml]

Exit codes:
    0 = All checks passed (Ready for Moodle upload)
    1 = Critical errors found (Must fix before uploading)
"""

import csv
import os
import re
import sys
import xml.etree.ElementTree as ET

# Terminal styling
GREEN = "\033[92m"
YELLOW = "\033[93m"
RED = "\033[91m"
BLUE = "\033[94m"
BOLD = "\033[1m"
RESET = "\033[0m"

EXPECTED_HEADERS = [
    "type", "section", "name", "intro", "grade",
    "visible", "completion", "completionview", "timeopen", "timeclose"
]

VALID_TYPES = {
    "section", "label", "page", "quiz", "assignment", "assign", "forum", "file", "resource"
}


def print_header(title):
    print(f"\n{BOLD}{BLUE}{'=' * 65}{RESET}")
    print(f"{BOLD}{BLUE}  {title}{RESET}")
    print(f"{BOLD}{BLUE}{'=' * 65}{RESET}\n")


def validate_csv(csv_path):
    errors = []
    warnings = []
    stats = {
        "sections": 0,
        "pages": 0,
        "quizzes": 0,
        "assignments": 0,
        "forums": 0,
        "labels": 0,
        "files": 0,
    }
    quizzes = []

    if not os.path.isfile(csv_path):
        return None, None, [f"CSV file not found: {csv_path}"], []

    try:
        with open(csv_path, "r", encoding="utf-8-sig") as f:
            reader = csv.reader(f)
            rows = list(reader)
    except Exception as e:
        return None, None, [f"Failed to read CSV: {str(e)}"], []

    if not rows:
        return None, None, ["CSV file is completely empty."], []

    header = [h.strip().lower() for h in rows[0]]
    if header != EXPECTED_HEADERS:
        errors.append(
            f"Invalid CSV header.\n  Expected: {','.join(EXPECTED_HEADERS)}\n  Found:    {','.join(header)}"
        )

    current_section = ""

    for line_num, row in enumerate(rows[1:], start=2):
        if not row or all(c.strip() == "" for c in row):
            continue

        if len(row) != len(EXPECTED_HEADERS):
            errors.append(
                f"Line {line_num}: Column count mismatch. Expected {len(EXPECTED_HEADERS)} columns, found {len(row)}."
            )
            continue

        row_dict = dict(zip(EXPECTED_HEADERS, row))
        act_type = row_dict["type"].strip().lower()
        sec_name = row_dict["section"].strip()
        act_name = row_dict["name"].strip()
        intro = row_dict["intro"].strip()
        grade = row_dict["grade"].strip()
        visible = row_dict["visible"].strip()
        completion = row_dict["completion"].strip()

        # 1. Type validation
        if not act_type:
            errors.append(f"Line {line_num}: Missing 'type' field.")
            continue
        if act_type not in VALID_TYPES:
            errors.append(f"Line {line_num}: Unknown type '{act_type}'. Allowed: {', '.join(sorted(VALID_TYPES))}")

        # 2. Name validation
        if not act_name:
            errors.append(f"Line {line_num}: Missing 'name' field.")

        # 3. Section tracking
        if act_type == "section":
            current_section = act_name or sec_name
            stats["sections"] += 1
        else:
            if not sec_name and not current_section:
                warnings.append(f"Line {line_num} ('{act_name}'): Activity declared before any section.")
            effective_section = sec_name or current_section

            # Track stats
            if act_type == "page":
                stats["pages"] += 1
            elif act_type == "quiz":
                stats["quizzes"] += 1
                quizzes.append({
                    "name": act_name,
                    "section": effective_section,
                    "line": line_num
                })
            elif act_type in ("assign", "assignment"):
                stats["assignments"] += 1
            elif act_type == "forum":
                stats["forums"] += 1
            elif act_type == "label":
                stats["labels"] += 1
            elif act_type in ("file", "resource"):
                stats["files"] += 1

        # 4. Intro HTML syntax check (commas inside CSS style attributes)
        if intro:
            bad_style_match = re.search(r"style\s*=\s*['\"][^'\"]*,[^'\"]*['\"]", intro, re.IGNORECASE)
            if bad_style_match:
                errors.append(
                    f"Line {line_num}: Found comma inside HTML style attribute in 'intro'. "
                    f"Always use semicolons (;) for styles to prevent CSV corruption:\n"
                    f"  Snippet: {bad_style_match.group(0)}"
                )

        # 5. Visible flag check
        if visible and visible not in ("0", "1"):
            warnings.append(f"Line {line_num}: 'visible' should be 0 or 1, found '{visible}'.")

        # 6. Completion check
        if completion and completion not in ("0", "1", "2"):
            warnings.append(f"Line {line_num}: 'completion' should be 0, 1, or 2, found '{completion}'.")

        # 7. Grade numeric check
        if act_type in ("quiz", "assign", "assignment") and grade:
            try:
                float(grade)
            except ValueError:
                errors.append(f"Line {line_num}: 'grade' must be numeric, found '{grade}'.")

    if stats["sections"] == 0:
        errors.append("No 'section' rows found in CSV. Moodle requires at least one section container.")

    return stats, quizzes, errors, warnings


def validate_xml(xml_path):
    errors = []
    warnings = []
    categories = []
    questions_count = 0

    if not os.path.isfile(xml_path):
        return categories, questions_count, [f"XML file not found: {xml_path}"], []

    try:
        tree = ET.parse(xml_path)
        root = tree.getroot()
    except Exception as e:
        return categories, questions_count, [f"Malformed XML syntax: {str(e)}"], []

    if root.tag != "quiz":
        errors.append(f"Root XML element must be <quiz>, found <{root.tag}>.")

    current_cat = None
    for q_idx, q in enumerate(root.findall("question"), start=1):
        q_type = q.get("type")

        # Category question
        if q_type == "category":
            cat_elem = q.find("category")
            text_elem = cat_elem.find("text") if cat_elem is not None else None
            cat_text = text_elem.text.strip() if text_elem is not None and text_elem.text else ""

            if not cat_text:
                errors.append(f"Question #{q_idx}: Empty <category><text> path.")
            else:
                if not cat_text.startswith("$course$/top/"):
                    warnings.append(
                        f"Question #{q_idx}: Category path '{cat_text}' should start with '$course$/top/'."
                    )
                if " / " not in cat_text:
                    warnings.append(
                        f"Question #{q_idx}: Category path '{cat_text}' does not use ' / ' (space-slash-space) separator."
                    )
                current_cat = cat_text
                categories.append(cat_text)

        elif q_type == "multichoice":
            questions_count += 1
            name_elem = q.find("name")
            q_name = name_elem.find("text").text if name_elem is not None and name_elem.find("text") is not None else f"Q#{q_idx}"
            
            # Check answers
            answers = q.findall("answer")
            if len(answers) < 2:
                errors.append(f"Question '{q_name}': Multiple choice question has only {len(answers)} answer options.")
            
            fractions = [a.get("fraction", "0") for a in answers]
            correct_answers = [f for f in fractions if f == "100"]
            if not correct_answers:
                errors.append(f"Question '{q_name}': No answer choice has fraction='100' (correct answer).")

    return categories, questions_count, errors, warnings


def simulate_automapping(quizzes, categories):
    """
    Replicates the exact matching algorithm from question_importer.php:
      - Section name match: +10
      - Unit number match:  +20
      - Keyword match (>3 chars): +5 each
      - Threshold required: 15
    """
    results = []

    for q in quizzes:
        quiz_name = q["name"]
        sec_name = q["section"]
        quiz_lower = quiz_name.lower()
        sec_lower = sec_name.lower()

        # Extract unit number
        unit_num = ""
        m = re.search(r"unit\s*(\d+)", quiz_name, re.IGNORECASE)
        if m:
            unit_num = m.group(1)

        # Extract keywords
        topic_words = re.sub(r"\b(unit|quiz|test|exam|\d+)\b", "", quiz_lower, flags=re.IGNORECASE)
        topic_words = re.sub(r"[^a-z\s]", "", topic_words)
        keywords = [w for w in set(topic_words.split()) if len(w) > 3]

        best_cat = None
        best_score = 0
        details = []

        for cat in categories:
            cat_lower = cat.lower()
            score = 0
            score_reasons = []

            if sec_lower and sec_lower in cat_lower:
                score += 10
                score_reasons.append("section(+10)")

            if unit_num and re.search(r"unit\s*" + unit_num + r"\b", cat, re.IGNORECASE):
                score += 20
                score_reasons.append(f"unit#{unit_num}(+20)")

            for kw in keywords:
                if kw in cat_lower:
                    score += 5
                    score_reasons.append(f"'{kw}'(+5)")

            if score > best_score:
                best_score = score
                best_cat = cat
                details = score_reasons

        passed = best_score >= 15
        results.append({
            "quiz": quiz_name,
            "section": sec_name,
            "best_cat": best_cat,
            "score": best_score,
            "details": ", ".join(details) if details else "None",
            "passed": passed,
            "line": q["line"]
        })

    return results


def main():
    if len(sys.argv) < 2:
        print(f"Usage: python3 {sys.argv[0]} <course.csv> [questions.xml]")
        sys.exit(1)

    csv_path = sys.argv[1]
    xml_path = sys.argv[2] if len(sys.argv) > 2 else None

    print_header("Moodle Coursebuilder Pre-Upload Validator")

    total_errors = 0
    total_warnings = 0

    # 1. Validate CSV
    print(f"{BOLD}1. Validating CSV Structure:{RESET} {csv_path}")
    stats, quizzes, csv_errors, csv_warnings = validate_csv(csv_path)

    if csv_errors:
        for err in csv_errors:
            print(f"  {RED}✖ ERROR:{RESET} {err}")
        total_errors += len(csv_errors)
    else:
        print(f"  {GREEN}✔ CSV structure & headers are 100% valid.{RESET}")

    if csv_warnings:
        for w in csv_warnings:
            print(f"  {YELLOW}⚠ WARNING:{RESET} {w}")
        total_warnings += len(csv_warnings)

    if stats:
        print(f"\n  Found in CSV:")
        print(f"    • Sections:    {stats['sections']}")
        print(f"    • Pages:       {stats['pages']}")
        print(f"    • Quizzes:     {stats['quizzes']}")
        print(f"    • Assignments: {stats['assignments']}")
        print(f"    • Forums:      {stats['forums']}")
        print(f"    • Labels:      {stats['labels']}")
        print(f"    • Files:       {stats['files']}")

    # 2. Validate XML if provided or required
    categories = []
    if xml_path:
        print(f"\n{BOLD}2. Validating Moodle XML Question Bank:{RESET} {xml_path}")
        categories, q_count, xml_errors, xml_warnings = validate_xml(xml_path)

        if xml_errors:
            for err in xml_errors:
                print(f"  {RED}✖ ERROR:{RESET} {err}")
            total_errors += len(xml_errors)
        else:
            print(f"  {GREEN}✔ XML syntax & categories are 100% valid ({q_count} questions across {len(categories)} categories).{RESET}")

        if xml_warnings:
            for w in xml_warnings:
                print(f"  {YELLOW}⚠ WARNING:{RESET} {w}")
            total_warnings += len(xml_warnings)
    elif stats and stats["quizzes"] > 0:
        print(f"\n{YELLOW}⚠ WARNING: CSV contains {stats['quizzes']} quiz(zes), but no companion XML file was passed.{RESET}")
        total_warnings += 1

    # 3. Simulate Auto-Mapping
    if quizzes and categories:
        print(f"\n{BOLD}3. Simulating Plugin Auto-Mapping Algorithm:{RESET}")
        mapping_results = simulate_automapping(quizzes, categories)

        for res in mapping_results:
            if res["passed"]:
                print(
                    f"  {GREEN}✔ PASS (Score: {res['score']}):{RESET} Quiz '{BOLD}{res['quiz']}{RESET}' (Line {res['line']})\n"
                    f"         Mapped to category: {res['best_cat']}\n"
                    f"         Matched on: {res['details']}"
                )
            else:
                total_errors += 1
                print(
                    f"  {RED}✖ FAIL (Score: {res['score']} < 15):{RESET} Quiz '{BOLD}{res['quiz']}{RESET}' (Line {res['line']})\n"
                    f"         Best category attempted: {res['best_cat'] or 'None'}\n"
                    f"         CRITICAL: Moodle will NOT attach questions to this quiz automatically!\n"
                    f"         Fix: Ensure Unit number or topic keywords in quiz match XML category name."
                )

    # Summary
    print(f"\n{BOLD}{'=' * 65}{RESET}")
    if total_errors == 0:
        print(f"{BOLD}{GREEN}  VALIDATION PASSED! ✨ Ready for 1-click Moodle upload.{RESET}")
        if total_warnings > 0:
            print(f"  ({total_warnings} warnings to review if needed)")
        print(f"{BOLD}{'=' * 65}{RESET}\n")
        sys.exit(0)
    else:
        print(f"{BOLD}{RED}  VALIDATION FAILED with {total_errors} error(s). Please fix before uploading.{RESET}")
        print(f"{BOLD}{'=' * 65}{RESET}\n")
        sys.exit(1)


if __name__ == "__main__":
    main()
