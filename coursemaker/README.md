---
name: Coursebuilder Output Generator
description: Generate paired CSV + Moodle XML files for the local_coursebuilder Moodle plugin. Takes any source material (docx, pdf, syllabus, curriculum document, or user prompt) and produces two files that can be uploaded together to instantly create a complete Moodle course with auto-graded quizzes.
---

# Coursebuilder Output Generator

## Purpose

This skill produces **two paired output files** that are uploaded together into the `local_coursebuilder` Moodle plugin to create a complete course in one step:

1. **`CourseName.csv`** — The course structure file (sections, lessons, quizzes, assignments, forums)
2. **`CourseNameQuestions.xml`** — The Moodle XML question bank file (auto-graded quiz questions)

These files work as a matched pair. The plugin uploads both simultaneously, builds all activities from the CSV, imports all questions from the XML, and **automatically maps questions to quizzes** using a name-matching algorithm.

---

## Workflow: 3-Step Chunked Process

Source documents can be messy, unstructured, or very long. To ensure quality and accuracy, **always follow this 3-step chunked process**:

### Step 1: Read & Plan (Full Document)

Read the entire source document once to understand its overall structure.

**Output a plan listing:**
- Course name
- How many units/chapters the document contains
- A one-line summary of each unit's topic
- Any structural notes (e.g., "Unit 3 has no quiz questions — I will create them")

Share this plan with the user before proceeding.

### Step 2: Process Unit-by-Unit (One JSON per Unit)

Create a folder (e.g., `./units/`) and process each unit individually:

1. Re-read the source material for **just that one unit**
2. Structure it into lessons, quiz questions, assignment, and forum
3. Write a `unit-N.json` file following the schema in `schemas/unit_schema.json`

**File naming:** `unit-0.json`, `unit-1.json`, `unit-2.json`, etc.

This is where the AI adds the most value — interpreting messy human content, creating quiz questions from lesson material, designing assignments, and writing discussion prompts. Each unit gets focused attention because only one unit is being processed at a time.

**The user can review/edit the unit JSON files before Step 3.**

### Step 3: Assemble (Generic Script)

Run the reusable assembly script:

```bash
python3 /path/to/skills/coursebuilder_output/scripts/assemble_course.py ./units "Course Name" ./output
```

This script:
1. Loads all `unit-*.json` files from the folder
2. Validates required fields
3. Generates the final `CourseName.csv` and `CourseNameQuestions.xml`

**This script never changes.** It works for any course, any document, any structure.

---

## Unit JSON Schema

Each `unit-N.json` file must contain:

```json
{
  "unit_number": 1,
  "unit_name": "Unit 1",
  "section_name": "Unit 1: Full Display Title Here",
  "tag": "[PY1]",
  "description": "Brief learning objectives summary",
  "lessons": [
    {
      "title": "Lesson Display Name",
      "html_content": "<p>Full HTML content...</p>"
    }
  ],
  "quiz": {
    "title": "Unit 1 Quiz — Topic Name",
    "category_name": "Unit 1 - Topic Name",
    "grade": 50,
    "questions": [
      {
        "question_text": "Question stem?",
        "correct_answer": "The correct answer",
        "wrong_answers": ["Wrong 1", "Wrong 2", "Wrong 3"]
      }
    ]
  },
  "assignment": {
    "title": "Assignment Name",
    "instructions": "HTML instructions with rubric",
    "grade": 100
  },
  "forum": {
    "title": "Unit 1 Discussion",
    "prompt": "Discussion prompt text"
  }
}
```

Full schema definition: `schemas/unit_schema.json`

### Key Schema Rules

| Field | Rule |
|---|---|
| `unit_number` | 0 for intro, 1+ for content units. Controls sort order. |
| `unit_name` | Becomes the Moodle **section** (sidebar navigation). Keep short. |
| `section_name` | Full display title shown as a label inside the section. |
| `tag` | Short code like `[PY1]` prefixed to question names in XML. |
| `lessons` | Minimum 1, typically 3. Each becomes a Moodle `page` activity. |
| `quiz.title` | MUST contain unit number AND topic keywords for auto-mapping. |
| `quiz.category_name` | MUST mirror quiz title keywords. Used in XML category path. |
| `questions` | Each needs exactly 1 correct + 3 wrong answers. Typically 5 per quiz. |

---

## Output File 1: Course Structure CSV

### CSV Headers (REQUIRED — in this exact order)

```
type,section,name,intro,grade,visible,completion,completionview,timeopen,timeclose
```

### Row Types

| `type` value | Moodle Activity | Description |
|---|---|---|
| `section` | Course Section | A topic/week container. The `section` column = the section's text name |
| `label` | Label | A visual HTML banner or info block — NOT clickable |
| `page` | Page | A lesson page with full HTML content |
| `quiz` | Quiz | A quiz activity — questions come from the XML file |
| `assign` | Assignment | A graded assignment with rubric in HTML |
| `forum` | Forum | A discussion forum with a prompt |

### Column Definitions

| Column | Required | Description |
|---|---|---|
| `type` | ✅ | One of: `section`, `label`, `page`, `quiz`, `assign`, `forum` |
| `section` | ✅ for sections, recommended for activities | Text name of the parent section. Activities inherit the most recent section if omitted |
| `name` | ✅ | Display name shown in Moodle |
| `intro` | ✅ | HTML content. For pages: this becomes the page body. For quizzes: a preview description. For assignments: instructions + rubric |
| `grade` | For quiz/assign | Max grade points. Quiz default: `50`. Assignment default: `100` |
| `visible` | Optional | `0` = hidden (teacher-only), `1` = visible to students. Default: `1` |
| `completion` | Optional | `0` = none, `1` = manual, `2` = automatic conditions |
| `completionview` | Optional | `1` = mark complete when student views the activity |
| `timeopen` | Optional | Unix timestamp for quiz open time |
| `timeclose` | Optional | Unix timestamp for quiz close time |

### CRITICAL: Each Unit = Its Own Section

Every unit MUST emit a `section` row. This creates a proper Moodle sidebar section (collapsible chapter). **Do NOT put all units under one section** — that creates a single flat list with labels as visual dividers, which is wrong.

**Correct pattern per unit:**

```
section  → Unit 1              ← creates sidebar entry
label    → Unit 1: Full Title  ← header inside the section
page     → Lesson 1 — Title
page     → Lesson 2 — Title
page     → Lesson 3 — Title
quiz     → Unit 1 Quiz — Topic
assign   → Unit 1 Assignment
forum    → Unit 1 Discussion
```

### Completion Settings Convention

| Activity Type | `visible` | `completion` | `completionview` |
|---|---|---|---|
| `section` | `1` | `0` | `0` |
| `label` (banners) | `1` | `0` | `0` |
| `page` (lessons) | `0` | `2` | `1` |
| `quiz` | `0` | `2` | `1` |
| `assign` | `0` | `1` | `0` |
| `forum` | `0` | `1` | `0` |

> **Note:** `visible=0` is used so the teacher can review content before releasing it to students.

### HTML Content Guidelines for `intro` Column

1. **Use semicolons (`;`) NOT commas inside HTML style attributes** — commas break CSV parsing
2. **Wrap the entire `intro` value in double quotes** when it contains commas or special characters
3. **No line breaks** inside a CSV cell — all HTML must be on a single line
4. **Use `&amp;` for `&`** inside HTML content when needed
5. **Do NOT include any inline CSS styling or branding colors** — focus only on semantic HTML structure

---

## Output File 2: Moodle XML Question Bank

### File Structure

```xml
<?xml version="1.0" encoding="UTF-8"?>
<quiz>

  <!-- Category declaration — groups questions for auto-mapping -->
  <question type="category">
    <category>
      <text>$course$/top/[Course Name] / [Unit Name] / [Category Name]</text>
    </category>
  </question>

  <!-- Individual question — multichoice -->
  <question type="multichoice">
    <name><text>[TAG] Question stem preview...</text></name>
    <questiontext format="html">
      <text><![CDATA[<p>Full question text here</p>]]></text>
    </questiontext>
    <generalfeedback format="html"><text></text></generalfeedback>
    <defaultgrade>1</defaultgrade>
    <penalty>0.3333333</penalty>
    <hidden>0</hidden>
    <single>true</single>
    <shuffleanswers>true</shuffleanswers>
    <answernumbering>abc</answernumbering>
    <correctfeedback format="html"><text>Correct!</text></correctfeedback>
    <partiallycorrectfeedback format="html"><text></text></partiallycorrectfeedback>
    <incorrectfeedback format="html"><text>Review the lesson content and try again.</text></incorrectfeedback>
    <answer fraction="100" format="html">
      <text><![CDATA[<p>Correct answer</p>]]></text>
      <feedback format="html"><text>Correct!</text></feedback>
    </answer>
    <answer fraction="0" format="html">
      <text><![CDATA[<p>Wrong answer</p>]]></text>
      <feedback format="html"><text></text></feedback>
    </answer>
  </question>

</quiz>
```

### Critical XML Rules

1. **Category path format**: `$course$/top/[CourseName] / [UnitName] / [CategoryName]`
   - This MUST use ` / ` (space-slash-space) as separator
   - `[UnitName]` = the `unit_name` field (matches the CSV section name)
   - `[CategoryName]` = the `quiz.category_name` field

2. **Question name tag convention**: `[SHORTCODE] Question stem truncated...`
   - Example: `[PY1]` = Python Unit 1, `[INT]` = Introduction

3. **Always exactly 4 answer choices** per multichoice question

4. **Exactly one answer** has `fraction="100"` (correct), the other three have `fraction="0"`

5. **5 questions per unit/quiz** is the standard count

6. **Use `<![CDATA[...]]>` wrappers** for all HTML content inside `<text>` tags

7. **`<shuffleanswers>true</shuffleanswers>`** — answer order is randomized for students

### How Question-to-Quiz Mapping Works

The `question_importer.php` uses a scoring algorithm to match XML question categories to CSV quizzes:

| Match Criterion | Score |
|---|---|
| Section name appears in category path | +10 |
| Unit number matches | +20 |
| Topic keywords overlap (words > 3 chars) | +5 each |
| **Minimum score to match** | **15** |

**Example mapping:**
- CSV quiz: `Unit 1 Quiz — Python Basics` in section `Unit 1`
- XML category: `$course$/top/Python Programming / Unit 1 / Unit 1 - Python Basics`
- Score: section match (+10) + unit number match (+20) + "python" (+5) + "basics" (+5) = **40** ✅

---

## Common Mistakes to Avoid

1. **Putting all units in one section** — Each unit MUST have its own `section` row. Otherwise Moodle creates one flat list.
2. **Using commas in CSS style attributes** — `style='color:red,font-size:14px'` breaks CSV. Use semicolons.
3. **Mismatched category paths** — If the XML says `Unit 1 - Oral` but the CSV quiz says `Unit 1 Quiz — Communication`, the mapping score will be too low.
4. **Forgetting the `$course$/top/` prefix** in XML category paths.
5. **Using `fraction="1"` instead of `fraction="100"`** for correct answers.
6. **Adding line breaks inside CSV cells** — all HTML must be on one line per cell.

---

## File Locations

| File | Path |
|---|---|
| This skill | `skills/coursebuilder_output/SKILL.md` |
| Unit JSON schema | `skills/coursebuilder_output/schemas/unit_schema.json` |
| Assembly script | `skills/coursebuilder_output/scripts/assemble_course.py` |
