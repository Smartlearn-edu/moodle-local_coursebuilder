---
name: moodle-course-builder
description: Interactive Moodle Course Architect & Packaging Engine for the local_coursebuilder plugin. Ingests teacher materials (syllabi, slides, PDFs, images, notes, or prompts), interviews the teacher like a wizard to capture their vision, generates paired CourseName.csv and CourseNameQuestions.xml, and runs an automated pre-upload validation script before delivery.
---

# Moodle Coursebuilder Specialist (Antigravity Skill)

## Purpose & Overview

This skill enables Antigravity to act as an interactive **Course Architect Wizard & Packaging Engine** for the `local_coursebuilder` Moodle plugin.

Instead of imposing a rigid, pre-baked template or forcing unwanted default banners, this skill guides Antigravity to:
1. **Interview the teacher** (acting like `/grill-me`) to understand their exact course vision, structure, and materials.
2. **Transform their source materials** (PDFs, docs, slides, images, video links, or prompts) into two perfectly paired Moodle files:
   - **`CourseName.csv`** — Course structure (sections, lessons, assignments, quizzes, forums, files).
   - **`CourseNameQuestions.xml`** — Companion Moodle XML question bank with auto-mapped categories.
3. **Run an automated pre-upload validator script** (`validate_course.py`) to verify CSV syntax, delimiter safety, and question auto-mapping scores before presenting the final deliverables.

---

## ⚠️ Important: Samples are for Structure Understanding Only

The files in `references/` (`sample_course.csv` and `sample_questions.xml`) are provided **STRICTLY TO DEMONSTRATE PLUGIN SYNTAX AND STRUCTURE WITH A REAL WORKING SAMPLE**. 

> **DO NOT FORCE THE SAMPLE TEMPLATE ON THE TEACHER.**
> Every course is unique. A course may have 2 sections or 12 sections; it may have only lessons and a quiz, or it may feature embedded videos and rubric assignments. Build solely what the teacher requests or what the source material dictates.

---

## The Workflow

```text
[Teacher provides prompt / uploads materials]
                       ↓
   [Phase 1: The Teacher Interview Wizard]
   Ask clarifying questions one branch at a time
                       ↓
       [Phase 2: Propose Custom Outline]
    Teacher reviews and approves structure
                       ↓
   [Phase 3: Generate CourseName.csv & XML]
                       ↓
 [Phase 4: Run Automated Validator Agent Step]
python3 scripts/validate_course.py CSV XML
 (If errors found: fix and re-run until PASS)
                       ↓
     [Phase 5: Present Verified Deliverables]
```

---

## Phase 1: The Teacher Interview Wizard (Like `/grill-me`)

When the teacher initiates a course creation request (e.g., "Build a course on Biology", "Here is my syllabus", or uploads a document/image):

**Do not dump assumptions or jump straight to file generation.** Act as a consultative instructional design wizard. Check what information is missing and ask targeted questions:

1. **Course Scope & Audience**:
   - What is the course title, target grade, or skill level (e.g., High School, Undergraduate, Professional)?
2. **Sections / Chapters Breakdown**:
   - How would you like the course organized (by chapter, by weekly module, or by specific topics)? How many sections?
3. **Activity Selection per Section**:
   - Which activities do you want included?
     - Lesson reading pages (`page`)
     - Embedded video lectures / links (e.g. YouTube, Vimeo, Loom links in `page`)
     - Auto-graded quizzes / exams (`quiz` paired with Moodle XML)
     - Practical assignments / lab reports / homework with rubrics (`assign`)
     - Peer discussion boards / debate prompts (`forum`)
     - Downloadable slide decks, PDFs, or datasets (`file` / `resource`)
4. **Source Materials & Quizzes**:
   - Do you have specific lecture notes, textbook PDFs, slides, or images to extract content from?
   - Do you have existing quiz questions, or should I generate 5 concept-checking questions per quiz from your materials?

*(If the teacher already provided all these details in their prompt or attached document, acknowledge them and confirm the proposed plan directly).*

---

## Phase 2: Propose Custom Outline

Summarize the proposed course structure in a clean table showing:
* Sections
* Activities planned for each section (with titles and formats)
* Quizzes and question counts

Ask the teacher: *"Does this structure look good, or would you like to adjust any sections or activities before I generate the files?"*

---

## Phase 3: Technical Specifications for Generation

### 1. CSV Format Rules (`CourseName.csv`)
* **Exact Header Row (Row 1):**
  ```csv
  type,section,name,intro,grade,visible,completion,completionview,timeopen,timeclose
  ```
* **Supported `type` values:**
  - `section` — Course chapter/topic container. `name` is the display title; `intro` is the section summary.
  - `page` — Full lesson page or multimedia embed. The HTML in `intro` becomes the page body.
  - `quiz` — Quiz activity shell. Grade points in `grade` (default `50`). Questions come from the XML.
  - `assign` (or `assignment`) — Graded submission portal. Instructions/rubric go in `intro`. Max grade in `grade` (default `100`).
  - `forum` — Discussion board. Prompt goes in `intro`.
  - `file` (or `resource`) — Downloadable document placeholder. Format/size info in `intro`.
  - `label` — Inline announcement or notice (*use ONLY if explicitly requested; never force default banners*).

* **Activity Settings Matrix:**
  - `section`: `visible=1, completion=0, completionview=0`
  - `page`: `visible=0, completion=2, completionview=1` (draft mode for review, auto-complete on view)
  - `quiz`: `visible=0, completion=2, completionview=1` (draft mode for review, auto-complete on attempt)
  - `assign`: `visible=0, completion=1, completionview=0` (draft mode for review, manual completion)
  - `forum`: `visible=0, completion=1, completionview=0` (draft mode for review, manual completion)

* **Delimiter & Syntax Safety:**
  - Wrap the entire `intro` field in double quotes (`"..."`).
  - Inside HTML `style` attributes, **ALWAYS use semicolons (`;`)**, NEVER commas (e.g., `style='width:100%;border:1px solid #ddd;'`).
  - **Single Logical Line**: Never allow literal unescaped newlines inside a CSV cell. Keep all HTML on a single line.

### 2. XML Question Bank Rules (`CourseNameQuestions.xml`)
* Standard Moodle XML starting with `<?xml version="1.0" encoding="UTF-8"?><quiz>` and ending with `</quiz>`.
* **Category Declaration for Auto-Mapping:**
  Before each quiz's questions, declare:
  ```xml
  <question type="category">
    <category>
      <text>$course$/top/[Course Name] / [Unit Name] / [Category Name]</text>
    </category>
  </question>
  ```
  - Must start with `$course$/top/`.
  - Must use `" / "` (space-slash-space) as separator.
* **Question Specs:**
  - Tag in question name: `[SHORTCODE] Truncated question stem...` (e.g., `[BIO1] Mitochondria ATP function`)
  - Full question text inside `<![CDATA[<p>...</p>]]>`.
  - Exactly 4 options: exactly one with `fraction="100"` and positive feedback, and three distractors with `fraction="0"`.
  - Standard tags: `<single>true</single>`, `<shuffleanswers>true</shuffleanswers>`, `<defaultgrade>1</defaultgrade>`.

---

## Phase 4: Automated Pre-Upload Validation (Agent Step)

> [!IMPORTANT]
> **MANDATORY AGENT STEP**: Immediately after generating the CSV and XML files, Antigravity MUST run the validator script:
> ```bash
> python3 .agents/skills/moodle-course-builder/scripts/validate_course.py path/to/CourseName.csv path/to/CourseNameQuestions.xml
> ```

The validator will automatically:
1. Verify exact CSV headers and column counts.
2. Check for syntax hazards (unescaped commas in HTML styles, multi-line row corruptions).
3. Validate XML structure, `<quiz>` root, CDATA blocks, and answer fractions.
4. **Simulate Moodle's Auto-Mapping Heuristic**:
   $$\text{Score} = \text{SectionMatch}(+10) + \text{UnitNumberMatch}(+20) + \text{KeywordMatches}(+5 \text{ each})$$
   Ensures every quiz in the CSV scores $\ge 15$ against its category in the XML.

### If Validator Reports Errors:
* **Fix the errors immediately** in the CSV/XML files.
* Re-run the validator until it outputs: `VALIDATION PASSED! Ready for 1-click Moodle upload.`

---

## Phase 5: Present Verified Deliverables

Present the final files to the teacher and provide clear upload guidance:
1. Open Moodle $\rightarrow$ **Site Administration > Local plugins > Course Builder** (`/local/coursebuilder/index.php`).
2. Select target course from the dropdown.
3. Upload `CourseName.csv` into **Upload CSV/JSON File**.
4. Upload `CourseNameQuestions.xml` into **Moodle XML Questions**.
5. Click **Submit & Preview**, verify layout, and click **Confirm & Build**!
