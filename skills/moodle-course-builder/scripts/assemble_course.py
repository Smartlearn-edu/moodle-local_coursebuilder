#!/usr/bin/env python3
"""
assemble_course.py — Generic Coursebuilder Assembly Script

Reads all unit-*.json files from a folder and produces:
  1. CourseName.csv   — Moodle course structure
  2. CourseNameQuestions.xml — Moodle XML question bank

Usage:
    python3 assemble_course.py <units_folder> <course_name> [output_folder]

Example:
    python3 assemble_course.py ./units "Python Programming" ./output

The unit JSON files must follow the schema defined in schemas/unit_schema.json.
Files are sorted by the 'unit_number' field inside each JSON.
"""

import csv
import json
import os
import sys
import glob


def load_units(folder):
    """Load and sort all unit-*.json files from the given folder."""
    pattern = os.path.join(folder, "unit-*.json")
    files = sorted(glob.glob(pattern))

    if not files:
        print(f"ERROR: No unit-*.json files found in '{folder}'")
        sys.exit(1)

    units = []
    for filepath in files:
        with open(filepath, "r", encoding="utf-8") as f:
            unit = json.load(f)
        units.append(unit)
        print(f"  Loaded: {os.path.basename(filepath)} → {unit['unit_name']}")

    # Sort by unit_number to guarantee correct order
    units.sort(key=lambda u: u["unit_number"])
    return units


def validate_unit(unit, filename):
    """Basic validation that required fields exist."""
    required = ["unit_number", "unit_name", "lessons"]
    missing = [f for f in required if f not in unit]
    if missing:
        print(f"ERROR in {filename}: Missing required fields: {missing}")
        sys.exit(1)

    quiz = unit.get("quiz")
    if quiz and quiz.get("questions"):
        for i, q in enumerate(quiz["questions"]):
            if len(q.get("wrong_answers", [])) != 3:
                print(f"ERROR in {filename}: Question {i+1} must have exactly 3 wrong answers")
                sys.exit(1)


def create_csv(units, course_name, output_path):
    """Generate the Moodle course structure CSV."""
    filepath = os.path.join(output_path, f"{course_name.replace(' ', '_')}.csv")

    with open(filepath, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow([
            "type", "section", "name", "intro", "grade",
            "visible", "completion", "completionview", "timeopen", "timeclose"
        ])

        for unit in units:
            section = unit["unit_name"]
            section_title = unit.get("section_name", section)

            # --- Section row (native title & summary, no extra banner) ---
            writer.writerow([
                "section", section, section_title,
                unit.get("description", ""), "", "1", "0", "0", "", ""
            ])

            # --- Optional labels (only if explicitly requested) ---
            if unit.get("labels"):
                for lbl in unit["labels"]:
                    writer.writerow([
                        "label", section, lbl.get("title", ""),
                        lbl.get("content", ""), "", "1", "0", "0", "", ""
                    ])

            # --- Lesson pages ---
            for i, lesson in enumerate(unit.get("lessons", []), 1):
                writer.writerow([
                    "page", section,
                    f"Lesson {i} \u2014 {lesson['title']}",
                    f"<div>{lesson['html_content']}</div>",
                    "", "0", "2", "1", "", ""
                ])

            # --- Quiz ---
            if "quiz" in unit and unit["quiz"]:
                quiz = unit["quiz"]
                grade = quiz.get("grade", 50)
                writer.writerow([
                    "quiz", section, quiz["title"],
                    f"<div><p>{len(quiz.get('questions', []))} auto-marked questions loaded from Moodle Question Bank.</p></div>",
                    str(grade), "0", "2", "1", "", ""
                ])

            # --- Assignment (optional) ---
            if "assignment" in unit and unit["assignment"]:
                assign = unit["assignment"]
                a_grade = assign.get("grade", 100)
                writer.writerow([
                    "assign", section, assign["title"],
                    f"<div><p><strong>Instructions</strong><br>{assign['instructions']}</p></div>",
                    str(a_grade), "0", "1", "0", "", ""
                ])

            # --- Forum (optional) ---
            if "forum" in unit and unit["forum"]:
                forum = unit["forum"]
                writer.writerow([
                    "forum", section, forum["title"],
                    f"<div><h3>Discussion Prompt</h3><blockquote>{forum['prompt']}</blockquote></div>",
                    "", "0", "1", "0", "", ""
                ])

    print(f"\n  CSV created: {filepath}")
    return filepath


def create_xml(units, course_name, output_path):
    """Generate the Moodle XML question bank file manually (for safe CDATA handling)."""
    filepath = os.path.join(output_path, f"{course_name.replace(' ', '_')}Questions.xml")

    lines = []
    lines.append('<?xml version="1.0" encoding="UTF-8"?>')
    lines.append('<quiz>')

    from xml.sax.saxutils import escape as xml_escape

    for unit in units:
        quiz = unit.get("quiz")
        if not quiz or not quiz.get("questions"):
            continue

        section = unit["unit_name"]
        cat_name = quiz.get("category_name", f"{section} - Quiz")
        tag = unit.get("tag", f"[{section[:4].upper()}]")

        cat_path = xml_escape(f"$course$/top/{course_name} / {section} / {cat_name}")

        # Category declaration
        lines.append('')
        lines.append('  <question type="category">')
        lines.append('    <category>')
        lines.append(f'      <text>{cat_path}</text>')
        lines.append('    </category>')
        lines.append('  </question>')

        # Questions
        for q in quiz["questions"]:
            q_text = q["question_text"]
            trunc = q_text[:50] + "..." if len(q_text) > 50 else q_text
            name_text = xml_escape(f"{tag} {trunc}")

            lines.append('')
            lines.append('  <question type="multichoice">')
            lines.append(f'    <name><text>{name_text}</text></name>')
            lines.append('    <questiontext format="html">')
            lines.append(f'      <text><![CDATA[<p>{q_text}</p>]]></text>')
            lines.append('    </questiontext>')
            lines.append('    <generalfeedback format="html"><text></text></generalfeedback>')
            lines.append('    <defaultgrade>1</defaultgrade>')
            lines.append('    <penalty>0.3333333</penalty>')
            lines.append('    <hidden>0</hidden>')
            lines.append('    <single>true</single>')
            lines.append('    <shuffleanswers>true</shuffleanswers>')
            lines.append('    <answernumbering>abc</answernumbering>')
            lines.append('    <correctfeedback format="html"><text>Correct!</text></correctfeedback>')
            lines.append('    <partiallycorrectfeedback format="html"><text></text></partiallycorrectfeedback>')
            lines.append('    <incorrectfeedback format="html"><text>Review the lesson content and try again.</text></incorrectfeedback>')

            # Correct answer
            lines.append(f'    <answer fraction="100" format="html">')
            lines.append(f'      <text><![CDATA[<p>{q["correct_answer"]}</p>]]></text>')
            lines.append(f'      <feedback format="html"><text>Correct!</text></feedback>')
            lines.append(f'    </answer>')

            # Wrong answers
            for wrong in q["wrong_answers"]:
                lines.append(f'    <answer fraction="0" format="html">')
                lines.append(f'      <text><![CDATA[<p>{wrong}</p>]]></text>')
                lines.append(f'      <feedback format="html"><text></text></feedback>')
                lines.append(f'    </answer>')

            lines.append('  </question>')

    lines.append('')
    lines.append('</quiz>')

    with open(filepath, "w", encoding="utf-8") as f:
        f.write('\n'.join(lines))

    print(f"  XML created: {filepath}")
    return filepath


def main():
    if len(sys.argv) < 3:
        print("Usage: python3 assemble_course.py <units_folder> <course_name> [output_folder]")
        print('Example: python3 assemble_course.py ./units "Python Programming" ./output')
        sys.exit(1)

    units_folder = sys.argv[1]
    course_name = sys.argv[2]
    output_folder = sys.argv[3] if len(sys.argv) > 3 else "."

    if not os.path.isdir(units_folder):
        print(f"ERROR: Units folder '{units_folder}' not found")
        sys.exit(1)

    os.makedirs(output_folder, exist_ok=True)

    print(f"\n{'='*60}")
    print(f"  Coursebuilder Assembly Script")
    print(f"  Course: {course_name}")
    print(f"  Units folder: {units_folder}")
    print(f"  Output folder: {output_folder}")
    print(f"{'='*60}\n")

    # Load units
    print("Loading unit files...")
    units = load_units(units_folder)

    # Validate
    print(f"\nValidating {len(units)} units...")
    for unit in units:
        validate_unit(unit, f"unit-{unit['unit_number']}.json")
    print("  All units valid ✓")

    # Count totals
    total_lessons = sum(len(u["lessons"]) for u in units)
    total_questions = sum(len(u["quiz"]["questions"]) for u in units)
    print(f"\n  Sections:    {len(units)}")
    print(f"  Lessons:     {total_lessons}")
    print(f"  Quizzes:     {len(units)}")
    print(f"  Questions:   {total_questions}")
    print(f"  Assignments: {len(units)}")
    print(f"  Forums:      {len(units)}")

    # Generate output files
    print(f"\nGenerating output files...")
    csv_path = create_csv(units, course_name, output_folder)
    xml_path = create_xml(units, course_name, output_folder)

    print(f"\n{'='*60}")
    print(f"  DONE! Upload these two files together to Coursebuilder:")
    print(f"  1. {os.path.basename(csv_path)}")
    print(f"  2. {os.path.basename(xml_path)}")
    print(f"{'='*60}\n")


if __name__ == "__main__":
    main()
