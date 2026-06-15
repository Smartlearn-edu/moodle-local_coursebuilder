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

### Structural Pattern Per Unit

Each unit within a subject section MUST follow this exact pattern:

```
1. label    → Unit header banner
2. page     → Lesson 1
3. page     → Lesson 2
4. page     → Lesson 3 (with Tarbiyah checkpoint if applicable)
5. quiz     → Unit Quiz (with question preview in intro)
6. assign   → Unit Assignment (with rubric in intro)
7. forum    → Unit Discussion (with prompt and teacher facilitation notes)
```

### Section Organization

```
section  → Subject Name (e.g., "Language Arts")
label    → Subject Overview and Pacing
label    → UNIT 1: [Topic Name]     ← unit header
page     → Lesson 1 — [Title]
page     → Lesson 2 — [Title]
page     → Lesson 3 — [Title]
quiz     → Unit 1 Quiz — [Topic]
assign   → Unit 1 Assignment — [Title]
forum    → Unit 1 Discussion — [Topic]
label    → UNIT 2: [Topic Name]     ← next unit
...repeat...
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

> **Note:** `visible=0` is used so the teacher/Academic Director can review content before releasing it to students.

### HTML Content Guidelines for `intro` Column

The `intro` column contains inline HTML. Key rules:

1. **Use semicolons (`;`) NOT commas inside HTML style attributes** — commas break CSV parsing
2. **Wrap the entire `intro` value in double quotes** when it contains commas or special characters
3. **No line breaks** inside a CSV cell — all HTML must be on a single line
4. **Use `&amp;` for `&`** inside HTML content when needed
5. **Do NOT include any inline CSS styling or branding colors** — focus only on semantic HTML structure. The Moodle theme handles styling.

### Minimal HTML Structure Templates

#### Page Lesson Content
```html
<div><p>Brief lesson summary.</p><h3>Learning Objectives</h3><ul><li>Objective 1</li><li>Objective 2</li><li>Objective 3</li></ul><h3>Lesson Content</h3><p>Paragraph 1...</p><p>Paragraph 2...</p><p>Paragraph 3...</p><h3>Differentiation Notes</h3><p><strong>Below grade level:</strong> Support strategy<br><strong>On grade level:</strong> Standard activity<br><strong>Above grade level:</strong> Extension activity</p></div>
```

#### Quiz Preview Description
```html
<div><p>5 auto-marked questions loaded from Moodle Question Bank.</p><h3>Unit X Quiz — [Topic]</h3><ol><li><strong>Question text?</strong><ul><li>Wrong answer</li><li>Correct answer ✓</li><li>Wrong answer</li><li>Wrong answer</li></ul></li></ol></div>
```

#### Assignment with Rubric
```html
<div><p><strong>Assignment Instructions</strong><br>Detailed instructions...</p><h3>Rubric</h3><table border='1' cellpadding='7' style='border-collapse:collapse;width:100%'><tr><th>Category</th><th>Level 4</th><th>Level 3</th><th>Level 2</th><th>Level 1</th></tr><tr><td><strong>Knowledge</strong></td><td>Excellent descriptor</td><td>Good descriptor</td><td>Developing descriptor</td><td>Beginning descriptor</td></tr></table></div>
```

#### Forum Discussion
```html
<div><h3>Discussion Prompt</h3><blockquote>Open-ended question that invites personal reflection?</blockquote><p><strong>Facilitation Notes for Teacher</strong><br>Guidance on how to moderate...</p></div>
```

---

## Output File 2: Moodle XML Question Bank

### File Structure

```xml
<?xml version="1.0" encoding="UTF-8"?>
<quiz>

  <!-- Category declaration — groups questions for auto-mapping -->
  <question type="category">
    <category>
      <text>$course$/top/[Course Name] / [Subject] / Unit [N] - [Topic Name]</text>
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
    <answer fraction="0" format="html">
      <text><![CDATA[<p>Wrong answer A</p>]]></text>
      <feedback format="html"><text></text></feedback>
    </answer>
    <answer fraction="100" format="html">
      <text><![CDATA[<p>Correct answer</p>]]></text>
      <feedback format="html"><text>Correct!</text></feedback>
    </answer>
    <answer fraction="0" format="html">
      <text><![CDATA[<p>Wrong answer B</p>]]></text>
      <feedback format="html"><text></text></feedback>
    </answer>
    <answer fraction="0" format="html">
      <text><![CDATA[<p>Wrong answer C</p>]]></text>
      <feedback format="html"><text></text></feedback>
    </answer>
  </question>

</quiz>
```

### Critical XML Rules

1. **Category path format**: `$course$/top/[CourseName] / [Subject] / Unit [N] - [Topic]`
   - This MUST use ` / ` (space-slash-space) as separator
   - The path after `$course$/top/` is matched against quiz names and section names

2. **Question name tag convention**: `[SHORTCODE] Question stem truncated...`
   - Example: `[LAN1]` = Language Arts Unit 1, `[MAT2]` = Mathematics Unit 2, `[SCI3]` = Science Unit 3

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
- CSV quiz: `Unit 1 Quiz — Oral Communication` in section `Language Arts`
- XML category: `$course$/top/Grade 4 / Language Arts / Unit 1 - Oral Communication`
- Score: section match (+10) + unit number match (+20) + "oral" (+5) + "communication" (+5) = **40** ✅

### Naming Convention for Reliable Mapping

To guarantee correct mapping, follow these naming rules:

| Element | CSV | XML Category |
|---|---|---|
| Section | `Language Arts` | `.../ Language Arts /...` |
| Unit | `Unit 1 Quiz — Oral Communication` | `.../ Unit 1 - Oral Communication` |
| Topic words | Include topic keywords in quiz name | Include same keywords in category name |

---

## Step-by-Step Generation Workflow

When the user provides source material (PDF, DOCX, syllabus, curriculum document, or a text prompt describing a course):

### Step 1: Analyze the Source Material
- Identify the course name/grade level
- Extract all subjects/topics
- Identify units within each subject
- Extract or create learning objectives per lesson
- Identify assessable content for quiz questions

### Step 2: Plan the Course Structure
- Map subjects to sections
- Plan 3 lessons per unit (minimum)
- Plan 1 quiz + 1 assignment + 1 forum per unit
- Plan 5 multichoice questions per quiz

### Step 3: Generate the CSV File
Create the CSV following the exact structure pattern. Rules:
- First row must be the header: `type,section,name,intro,grade,visible,completion,completionview,timeopen,timeclose`
- No trailing commas on rows shorter than the header
- HTML in `intro` must use semicolons in style attributes, not commas
- Entire `intro` value must be wrapped in double quotes if it contains commas or special characters

### Step 4: Generate the XML File
Create the Moodle XML question file:
- One `<question type="category">` before each group of questions
- Category path must include the subject name AND unit name matching the CSV
- 5 questions per category/unit
- All questions must be multiple choice with exactly 4 options
- Questions must test content from the lesson pages in that unit

### Step 5: Validate Cross-References
Verify that:
- Every quiz in the CSV has a corresponding category in the XML
- Section names in CSV match subject names in XML category paths
- Unit numbers in quiz names match unit numbers in XML category paths
- Topic keywords overlap between quiz names and category names

---

## Minimal Working Example

### `ExampleCourse.csv`
```csv
type,section,name,intro,grade,visible,completion,completionview,timeopen,timeclose
section,Welcome,Welcome,,,1,0,0,,
label,Welcome,Course Introduction,<div><h2>Welcome to the Course</h2><p>This course covers key topics.</p></div>,,1,0,0,,
section,History,History,,,1,0,0,,
label,History,UNIT 1: Ancient Civilizations,<div><h3>Unit 1</h3><p>Ancient Civilizations</p></div>,,1,0,0,,
page,History,Lesson 1 — Early River Civilizations,<div><p>Students explore early civilizations.</p><h3>Learning Objectives</h3><ul><li>Identify major river civilizations</li><li>Describe geographic advantages of river valleys</li></ul><h3>Lesson Content</h3><p>The earliest civilizations arose along rivers...</p></div>,,0,2,1,,
quiz,History,Unit 1 Quiz — Ancient Civilizations,<div><p>5 auto-marked questions.</p></div>,50,0,2,1,,
assign,History,Unit 1 Assignment — Civilization Report,<div><p><strong>Instructions</strong><br>Write a one-page report on one ancient civilization.</p></div>,100,0,1,0,,
forum,History,Unit 1 Discussion — Ancient Civilizations,<div><h3>Discussion Prompt</h3><blockquote>Which ancient civilization do you think had the greatest impact on the modern world? Why?</blockquote></div>,,0,1,0,,
```

### `ExampleCourseQuestions.xml`
```xml
<?xml version="1.0" encoding="UTF-8"?>
<quiz>

  <question type="category">
    <category>
      <text>$course$/top/Example Course / History / Unit 1 - Ancient Civilizations</text>
    </category>
  </question>

  <question type="multichoice">
    <name><text>[HIS1] The earliest civilizations developed near:...</text></name>
    <questiontext format="html">
      <text><![CDATA[<p>The earliest civilizations developed near:</p>]]></text>
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
    <answer fraction="0" format="html">
      <text><![CDATA[<p>Mountain peaks with strategic defensive advantages</p>]]></text>
      <feedback format="html"><text></text></feedback>
    </answer>
    <answer fraction="100" format="html">
      <text><![CDATA[<p>Rivers that provided water, fertile soil, and transportation</p>]]></text>
      <feedback format="html"><text>Correct!</text></feedback>
    </answer>
    <answer fraction="0" format="html">
      <text><![CDATA[<p>Dense forests with abundant timber resources</p>]]></text>
      <feedback format="html"><text></text></feedback>
    </answer>
    <answer fraction="0" format="html">
      <text><![CDATA[<p>Coastal areas with access to ocean trade routes</p>]]></text>
      <feedback format="html"><text></text></feedback>
    </answer>
  </question>

  <!-- ... 4 more questions for this unit ... -->

</quiz>
```

---

## Common Mistakes to Avoid

1. **Using commas in CSS style attributes** — `style='color:red,font-size:14px'` breaks CSV. Use semicolons: `style='color:red;font-size:14px'`
2. **Mismatched category paths** — If the XML says `Unit 1 - Oral` but the CSV quiz says `Unit 1 Quiz — Communication`, the mapping score will be too low
3. **Forgetting the `$course$/top/` prefix** in XML category paths
4. **Using `fraction="1"` instead of `fraction="100"`** for correct answers
5. **Adding line breaks inside CSV cells** — all HTML must be on one line per cell
6. **Missing the trailing empty columns** — rows can have fewer columns than the header but the header must have all 10 columns
