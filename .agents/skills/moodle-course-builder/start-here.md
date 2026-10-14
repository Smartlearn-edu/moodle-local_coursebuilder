# START HERE: Moodle Coursebuilder Guide for Antigravity

Welcome to the **Moodle Coursebuilder** toolkit for Antigravity. This folder contains everything Antigravity needs to act as an autonomous, interactive **Course Architect Wizard & Packaging Engine** for the `local_coursebuilder` Moodle plugin.

---

## 📂 Folder Manifest & Asset Guide

When Antigravity operates in this directory, it has access to the following assets:

| File / Folder | Purpose & Role |
| :--- | :--- |
| **`start-here.md`** | *(This file)* Master execution protocol and onboarding guide for Antigravity. |
| **`SKILL.md`** | Formal Antigravity skill specification with full YAML frontmatter and technical constraints. |
| **`references/`** | **REAL WORKING SAMPLES (FOR STRUCTURE UNDERSTANDING ONLY)**<br>• `sample_course.csv`: Working CSV demonstrating all activity types (`section`, `page`, `quiz`, `assign`, `forum`, `file`, `label`).<br>• `sample_questions.xml`: Working Moodle XML question bank with categories configured for auto-mapping.<br>*(Never force these sample topics or banners on the teacher; use them only to understand valid syntax).* |
| **`scripts/validate_course.py`** | **PRE-UPLOAD VALIDATOR (MANDATORY AGENT STEP)**<br>Automated script that tests CSV headers, cell formatting, semicolon safety in styles, XML syntax, and calculates the exact Moodle question auto-mapping score ($\ge 15$). |
| **`scripts/assemble_course.py`** | Generic Python course builder for compiling chunked unit JSON files into final CSV and XML deliverables. |
| **`schemas/unit_schema.json`** | JSON Schema used when breaking down massive multi-chapter textbooks or lengthy syllabi into modular units. |
| **`moodle-course-builder.skill`** | Complete pre-packaged `.skill` zip bundle ready to share with teachers across other machines or environments. |

---

## 🧙‍♂️ How Antigravity Starts with the Teacher (The 5-Phase Wizard)

Whenever a teacher initiates a conversation (e.g. *"I want to build a course on X"*, *"Here is my syllabus"*, or uploads PDFs, lecture slides, Word docs, or images), Antigravity **MUST** execute the following protocol:

```text
[Teacher provides prompt or uploads files]
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

### Phase 1: The Teacher Interview Wizard (Like `/grill-me`)

**Do not guess, make assumptions, or spit out rigid templates with unwanted banners.**
Antigravity greets the teacher, acknowledges any uploaded files, and asks targeted questions to understand their specific vision:

1. **Course Scope & Audience**:
   - What is the course title, target grade, or skill level (e.g., High School, Undergraduate, Professional Certification)?
2. **Sections / Chapters Breakdown**:
   - How would you like the course organized (by chapter, by weekly module, or by specific topics)? How many sections?
3. **Activity Selection per Section**:
   - Which activities do you want in each section?
     - Lesson reading pages (`page`)
     - Multimedia & video links (embedded YouTube, Vimeo, or Loom links in `page`)
     - Auto-graded quizzes / exams (`quiz` paired with Moodle XML)
     - Practical assignments / lab reports / homework with rubrics (`assign`)
     - Peer discussion boards / reflection prompts (`forum`)
     - Downloadable slide decks, PDFs, or datasets (`file` / `resource`)
4. **Source Materials & Quizzes**:
   - Do you have specific lecture notes, textbook PDFs, slides, or images to extract content from?
   - Do you have existing quiz questions, or should I generate 5 concept-checking questions per quiz from your materials?

*(If the teacher already provided all these details in their initial message or attached files, confirm the plan directly without asking redundant questions).*

---

### Phase 2: Propose Custom Outline

Based on the teacher's answers and source documents, Antigravity presents a clean preview table:
* Sections and titles
* Planned activities per section (lessons, videos, quizzes, assignments)
* Quiz question counts and topics

Antigravity asks:
> *"Does this structure reflect your vision, or would you like to adjust any topics, lessons, or activities before I generate the Moodle files?"*

---

### Phase 3: Technical File Generation

Once the teacher approves (or says *"generate immediately"*), Antigravity builds the paired files according to strict plugin rules:

#### 1. `CourseName.csv` Rules:
* **Header (Row 1)**: `type,section,name,intro,grade,visible,completion,completionview,timeopen,timeclose`
* **Single-Line Cells**: All HTML content inside `intro` must reside on a **single line** (never allow unescaped newlines inside a cell).
* **Delimiter Safety**: Inside HTML `style` attributes, **ALWAYS use semicolons (`;`)**, NEVER commas (e.g., `style='width:100%;border:1px solid #ddd;'`).
* **Enclosure**: Wrap the entire `intro` field in double quotes (`"..."`).
* **No Default Banners**: Do NOT add boilerplate `label` banners to units. Sections in Moodle display their title and summary natively. Only use `label` if the teacher explicitly requested an announcement.

#### 2. `CourseNameQuestions.xml` Rules:
* Root `<quiz>` tag wrapping all categories and questions.
* **Category Declaration for Auto-Mapping**:
  ```xml
  <question type="category">
    <category>
      <text>$course$/top/[Course Name] / [Unit Name] / [Category Name]</text>
    </category>
  </question>
  ```
  - Must start with `$course$/top/`.
  - Must use `" / "` (space-slash-space) as separator.
* **Auto-Mapping Guarantee**: The quiz title in the CSV and the category name in the XML must share the **Unit Number** (e.g. `Unit 1`) and **Topic Keywords** so Moodle's scoring formula hits $\ge 15$.
* **Questions**: Exactly 4 choices for multiple choice: 1 with `fraction="100"` and feedback, 3 distractors with `fraction="0"`.

---

### Phase 4: Automated Pre-Upload Validation (Agent Step)

> [!IMPORTANT]
> **MANDATORY AGENT STEP**: Immediately after generating the CSV and XML files, Antigravity **MUST execute** the pre-upload validator tool:
> ```bash
> python3 scripts/validate_course.py path/to/CourseName.csv path/to/CourseNameQuestions.xml
> ```

The validator checks:
* CSV header compliance and column counts.
* Style attribute comma hazards and unescaped newline corruptions.
* XML syntax, CDATA blocks, and fraction weights.
* **Moodle Auto-Mapping Scoring Simulation**:
  $$\text{Score} = \text{SectionMatch}(+10) + \text{UnitNumberMatch}(+20) + \text{KeywordMatches}(+5 \text{ each})$$
  Ensures every quiz scores $\ge 15$.

**If the validator reports any error or low score:**
Antigravity automatically fixes the file and re-runs the validator until it reports:
`VALIDATION PASSED! Ready for 1-click Moodle upload.`

---

### Phase 5: Present Verified Deliverables

Antigravity presents the finished files to the teacher and provides simple 1-2-3 upload instructions:
1. Open Moodle $\rightarrow$ **Site Administration > Local plugins > Course Builder** (`/local/coursebuilder/index.php`).
2. Select your target course from the dropdown.
3. Upload `CourseName.csv` into **Upload CSV/JSON File**.
4. Upload `CourseNameQuestions.xml` into **Moodle XML Questions**.
5. Click **Submit & Preview**, check the visual layout, and click **Confirm & Build**!
