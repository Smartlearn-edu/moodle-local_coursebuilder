# Moodle Course Builder (`local_coursebuilder`)

[![Moodle Version](https://img.shields.io/badge/Moodle-4.0%20to%205.x%2B-orange.svg)](https://moodle.org)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.1-blue.svg)](https://php.net)
[![Plugin Type](https://img.shields.io/badge/Plugin%20Type-Local%20(local__coursebuilder)-green.svg)](https://docs.moodle.org/dev/Local_plugins)
[![License](https://img.shields.io/badge/License-GPLv3%20or%20later-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.html)
[![AI Integration](https://img.shields.io/badge/AI%20Subsystem-core__ai%20Ready-purple.svg)](https://moodle.org)

An enterprise-grade Moodle local plugin that automates the rapid generation, structuring, and population of comprehensive Moodle courses. Transform raw curriculum documents, syllabi, structured CSV/JSON files, or natural-language AI prompts into fully realized Moodle courses with sections, lessons, discussion forums, graded assignments, auto-graded quizzes, and automated Question Bank mapping in seconds.

---

## Table of Contents

- [Overview](#overview)
- [How to Use (Quick Start Guide)](#how-to-use-quick-start-guide)
  - [1. Accessing the Plugin](#1-accessing-the-plugin)
  - [2. Method A: Upload CSV & Companion XML Question Bank](#2-method-a-upload-csv--companion-xml-question-bank-recommended)
  - [3. Method B: Generate from an AI Prompt or Syllabus](#3-method-b-generate-from-an-ai-prompt-or-syllabus)
  - [4. Method C: Interactive Visual Review & "Refine with AI"](#4-method-c-interactive-visual-review--refine-with-ai)
  - [5. Quick-Start CSV Example](#5-quick-start-csv-example)
- [Key Features](#key-features)
- [Architecture & Execution Flow](#architecture--execution-flow)
- [Directory Structure](#directory-structure)
- [Input Formats & Specifications](#input-formats--specifications)
  - [1. Course Structure CSV Specification](#1-course-structure-csv-specification)
  - [2. Moodle XML Question Bank Specification](#2-moodle-xml-question-bank-specification)
  - [3. JSON Course Structure Specification](#3-json-course-structure-specification)
- [Heuristic Question-to-Quiz Mapping Algorithm](#heuristic-question-to-quiz-mapping-algorithm)
- [AI Companion & CourseMaker Tooling (`coursemaker/`)](#ai-companion--coursemaker-tooling-coursemaker)
  - [The 3-Step Chunked Workflow](#the-3-step-chunked-workflow)
  - [Unit JSON Schema](#unit-json-schema)
  - [CLI Assembly Script (`assemble_course.py`)](#cli-assembly-script-assemble_coursepy)
- [Installation & Prerequisites](#installation--prerequisites)
  - [Prerequisites](#prerequisites)
  - [Standard Installation](#standard-installation)
  - [Moodle AI Subsystem Configuration](#moodle-ai-subsystem-configuration)
- [Completion Tracking & Visibility Conventions](#completion-tracking--visibility-conventions)
- [Troubleshooting & Best Practices](#troubleshooting--best-practices)
- [Developer Notes & Standards](#developer-notes--standards)
- [License & Copyright](#license--copyright)

---

## Overview

In traditional Moodle administration, creating a robust, multi-unit course is a labor-intensive process requiring educators to manually configure sections, draft HTML lesson pages, set up assignment rubrics, instantiate discussion forums, create quiz activities, and link question bank items one by one.

**Course Builder (`local_coursebuilder`)** bridges this gap by providing an end-to-end automation engine that operates directly within Moodle:

1. **Ingests Structured Data or Generates via AI**: Accepts structured CSV/JSON curriculum blueprints, extracts content from uploaded syllabus documents, or accepts free-form natural language prompts via Moodle's native AI subsystem (`\core_ai\manager`).
2. **Interactive Visual Preview**: Renders an interactive, styled preview of all sections and modular activities before database execution.
3. **Conversational Refinement ("Refine with AI")**: Allows instructional designers to iteratively modify the generated structure (e.g., *"Add a lab assignment to Unit 2"*, *"Rename Section 1 to Orientation"*) using conversational prompts.
4. **Automated Moodle Module Instantiation**: Direct, low-overhead creation of native Moodle course modules (`label`, `page`, `quiz`, `assign`, `forum`, `resource`) with full Gradebook integration, review options, and activity completion settings.
5. **Intelligent XML Question Mapping**: Imports companion Moodle XML question banks into the course-level question bank and employs a scoring algorithm to automatically populate quizzes with matching questions.

---

## How to Use (Quick Start Guide)

### 1. Accessing the Plugin

Log into your Moodle site as an Administrator or Teacher with editing rights, then navigate to:
```
http://your-moodle-site.com/local/coursebuilder/index.php
```

You will see the **Upload Course Data** form:

```text
+---------------------------------------------------------------+
|  Upload Course Data                                           |
+---------------------------------------------------------------+
|  Target Course:          [ Select an existing course...  v ]  |
|                                                               |
|  Data File (CSV/JSON):   [ Choose File ] (e.g. Physics.csv)   |
|  Questions File (XML):   [ Choose File ] (e.g. Questions.xml) |
|                                                               |
|  -- OR Generate with AI --                                    |
|  AI Course Prompt:       [ Describe your course here...    ]  |
|  Syllabus File (TXT/MD): [ Choose File ] (e.g. syllabus.md)   |
|                                                               |
|  [ Generate / Upload ]                                        |
+---------------------------------------------------------------+
```

---

### 2. Method A: Upload CSV & Companion XML Question Bank (Recommended)

Use this method when you already have a structured curriculum spreadsheet (CSV) and optional Question Bank file (XML).

1. **Select Target Course**: Pick an existing empty or prepared course from the **Target Course** dropdown.
2. **Upload Course CSV**: In the **Data File (CSV/JSON)** file picker, upload your `.csv` file.
3. **Upload Questions XML (Optional)**: In the **Questions File (Moodle XML)** file picker, upload your companion `.xml` questions file.
4. **Click "Generate / Upload"**: The plugin parses your files and renders an instant visual preview.
5. **Review & Build**: Inspect the preview, then click **Confirm & Build Course**.
6. **Done**: The plugin creates all sections, activities, and auto-mapped quiz questions, then redirects you directly into your live Moodle course!

---

### 3. Method B: Generate from an AI Prompt or Syllabus

Use this method when starting from a course description or an existing syllabus document without creating a CSV file first.

1. **Select Target Course**: Choose the target course from the dropdown.
2. **Enter AI Prompt**: In the **AI Course Prompt** box, type a clear description of the desired course:
   > *"Create a 4-week introductory Python course. Week 1 covers Variables and Data Types; Week 2 covers Control Flow & Loops; Week 3 covers Functions & Modules; Week 4 covers Object-Oriented Programming. For each week, include 3 lesson pages, a discussion forum, a programming assignment, and a quiz."*
3. **Optional Syllabus File**: You can also upload a `.txt` or `.md` syllabus file. The AI reads your document and extracts the units and lesson structure automatically.
4. **Click "Generate / Upload"**: Moodle's native AI Subsystem generates the entire course blueprint.

---

### 4. Method C: Interactive Visual Review & "Refine with AI"

Before any changes are committed to your Moodle database, the plugin displays an interactive accordion preview of your course:

```text
=================================================================
  Review Generated Course (Course Structure Preview)
=================================================================
  v Section 1: Introduction to Python
    [Page]   Lesson 1 — Getting Started with Python
    [Page]   Lesson 2 — Variables and Types
    [Quiz]   Unit 1 Quiz — Variables & Types
    [Assign] Unit 1 Assignment — Price Calculator
    [Forum]  Unit 1 Discussion — Automation Ideas
  
  v Section 2: Control Flow & Loops
    [Page]   Lesson 1 — Conditional Statements
    ...
=================================================================
  Refine with AI
  [ Add a 50-point lab assignment to Section 2 and rename Lesson 1 ]
  [ Modify Course ]
=================================================================
  [ Confirm & Build Course ]     [ Cancel ]
```

- **Make Adjustments**: Type any refinement request into the **Refine with AI** box (e.g. *"Add a quiz to Week 2"*, *"Rename Section 1 to Orientation"*, *"Increase assignment grades to 100"*) and click **Modify Course**.
- **Build**: Once the structure matches your needs, click **Confirm & Build Course** to instantiate all modules in Moodle.

---

### 5. Quick-Start CSV Example

Here is a minimal, complete 2-section CSV file you can copy, save as `sample_course.csv`, and upload immediately:

```csv
type,section,name,intro,grade,visible,completion,completionview,timeopen,timeclose
section,Welcome,Welcome to the Course,,,1,0,0,,
label,Welcome,Important Announcements,"<div><h3>Welcome!</h3><p>Please read through all materials before starting.</p></div>",,1,0,0,,
page,Welcome,Course Overview & Syllabus,"<div><p>This course provides an introduction to core concepts.</p><h3>Objectives</h3><ul><li>Understand fundamentals</li><li>Apply practical skills</li></ul></div>",,0,2,1,,
forum,Welcome,Introductions Forum,"<div><h3>Introduce Yourself</h3><p>Post a brief introduction and share what you hope to learn.</p></div>",,0,1,0,,
section,Unit 1,Unit 1: Fundamentals,,,1,0,0,,
label,Unit 1,Unit 1 Overview,"<div><h3>Unit 1: Fundamentals</h3><p>Core foundations and concepts.</p></div>",,1,0,0,,
page,Unit 1,Lesson 1.1 — Basic Concepts,"<div><h3>Core Concepts</h3><p>Detailed lesson body goes here...</p></div>",,0,2,1,,
page,Unit 1,Lesson 1.2 — Practical Applications,"<div><h3>Practical Applications</h3><p>Applied examples and exercises.</p></div>",,0,2,1,,
quiz,Unit 1,Unit 1 Quiz — Fundamentals,"<div><p>Auto-graded assessment on core concepts.</p></div>",50,0,2,1,,
assign,Unit 1,Unit 1 Assignment — Practical Project,"<div><p><strong>Instructions:</strong> Complete the exercise and submit your response.</p></div>",100,0,1,0,,
forum,Unit 1,Unit 1 Discussion — Reflections,"<div><blockquote>How do these fundamentals apply to your daily workflow?</blockquote></div>",,0,1,0,,
```

---

## Key Features

- **Multi-Source Input Support**:
  - **CSV File Upload**: High-performance batch generation using strict tabular columns.
  - **JSON File Upload**: Hierarchical structure support for automated generation pipelines.
  - **Moodle AI Text Prompt**: Create comprehensive course architectures from a simple instruction prompt.
  - **Syllabus Document Ingestion**: Upload plain text (`.txt`) or Markdown (`.md`) syllabus files; the AI parses learning objectives and maps them into progressive units.
- **Granular Activity Generation**:
  - **Sections**: Topics/weeks created with custom titles and HTML overview summaries.
  - **Labels (`mod_label`)**: Visual HTML unit banners, pacing guides, and section callouts.
  - **Pages (`mod_page`)**: Rich lesson pages with objectives, differentiated notes, and full HTML formatting.
  - **Assignments (`mod_assign`)**: Formatted instructions, student briefs, grading rubrics, and gradebook items.
  - **Forums (`mod_forum`)**: Engaging discussion prompts and teacher facilitation instructions.
  - **Quizzes (`mod_quiz`)**: Pre-configured quiz instances with review options, attempt handling, and grade scales.
- **Automatic XML Question Bank Import & Mapping**:
  - Upload a companion Moodle XML questions file alongside your course structure.
  - Parses questions directly into the course question bank hierarchy.
  - Uses a heuristic keyword/unit/section matching algorithm to link questions into quiz slots without manual intervention.
- **Interactive Review & Modification UI**:
  - Accordion-style preview displaying activity icons, titles, and section breakdowns.
  - Integrated "Refine with AI" form to adjust course elements before running the build.
- **Native Moodle Integration**:
  - Leverages Moodle Core Course APIs (`course_create_sections_if_missing`, `course_add_cm_to_section`, `rebuild_course_cache`).
  - Compatible with Moodle 4.0 through Moodle 4.5+ and Moodle 5.x.
  - Uses Moodle 4.5+ Dependency Injection (`\core\di::get(\core_ai\manager::class)`) for AI generation actions.

---

## Architecture & Execution Flow

```mermaid
flowchart TD
    A[User Input] --> B{Input Type}
    
    B -->|CSV / JSON File| C[builder::parse_csv / parse_json]
    B -->|AI Prompt / Syllabus| D[builder::call_moodle_ai]
    
    D --> E[Moodle AI Subsystem core_ai]
    E --> F[JSON Course Blueprint]
    
    C --> G[Interactive Preview UI output::render_preview]
    F --> G
    
    G --> H{User Action}
    
    H -->|Refine with AI| I[builder::modify_moodle_ai]
    I --> E
    
    H -->|Confirm & Build| J[builder::build_from_array]
    
    J --> K[Create / Update Sections]
    J --> L[Instantiate Course Modules label, page, assign, forum, quiz]
    J --> M[Register Gradebook Items & Settings]
    
    subgraph Optional Companion XML
        N[Moodle XML Question Bank] --> O[question_importer::import_xml]
        O --> P[Course Question Bank Entries]
        P --> Q[question_importer::map_questions_to_quiz]
        Q --> R[Create quiz_slots & question_references]
    end
    
    L --> Q
    R --> S[Rebuild Course Cache]
    M --> S
    S --> T[Redirect to Live Course]
```

---

## Directory Structure

```text
local/coursebuilder/
├── classes/
│   ├── form/
│   │   └── upload_form.php         # Moodle Formslib definition (filepickers, course selector, AI prompt)
│   ├── builder.php                 # Core business logic: CSV/JSON parsing, core_ai caller, module builder
│   ├── output.php                  # Interactive preview renderer with FontAwesome activity icons
│   └── question_importer.php       # Moodle XML importer & heuristic quiz-slot mapping engine
├── coursemaker/                    # External AI companion toolkit for Claude / LLMs
│   ├── CLAUDE_SKILL_PROMPT.md      # Direct Claude skill prompt instructions
│   ├── README.md                   # Documentation for the 3-step chunked generation workflow
│   ├── samples/                    # Ready-to-use sample courses (CSV + XML)
│   │   ├── Physics_Course.csv
│   │   └── Physics_CourseQuestions.xml
│   ├── schemas/
│   │   └── unit_schema.json        # JSON Schema definition for unit JSON files
│   └── scripts/
│       └── assemble_course.py      # Python CLI script compiling unit-*.json into CSV + XML
├── courses/                        # Comprehensive sample datasets & reference generators
│   ├── generate_course.py          # Reference Python script demonstrating programmatic generation
│   ├── Python_Course.csv           # Full 6-unit Python course CSV
│   ├── Python_CourseQuestions.xml  # Companion auto-graded Question Bank XML (30+ questions)
│   └── python_units/               # Modular unit-0.json and unit-1.json examples
├── lang/
│   └── en/
│       └── local_coursebuilder.php # English localization strings
├── final_plan.md                   # Plugin architectural specification & design notes
├── index.php                       # Main UI controller, dispatcher & preview view
├── moodle-course-builder.skill     # Packaged AI agent skill archive
├── settings.php                    # Site administration settings entrypoint
├── SKILL.md                        # Primary AI Agent Skill documentation and prompt reference
├── test_course.csv                 # Minimal test CSV sample
├── test.json                       # Minimal test JSON sample
└── version.php                     # Moodle plugin metadata, version, and dependencies
```

---

## Input Formats & Specifications

### 1. Course Structure CSV Specification

The CSV input must adhere to the exact header layout shown below.

#### Header Row (Mandatory)
```csv
type,section,name,intro,grade,visible,completion,completionview,timeopen,timeclose
```

#### Field Definitions

| Column | Type | Required | Description |
|---|---|---|---|
| `type` | String | **Yes** | Activity or structure type: `section`, `label`, `page`, `quiz`, `assign`, `forum`, `resource` (or `file`). |
| `section` | String / Int | **Yes** for sections | Parent section identifier. Accepts numeric section index (`1`, `2`) or string title (`Unit 1`). Activities inherit the preceding section if left blank. |
| `name` | String | **Yes** | Display title rendered in the course page navigation. |
| `intro` | HTML String | **Yes** | Rich HTML content. For `page`: lesson body. For `label`: inline banner. For `assign`: instructions + rubric. For `quiz`: overview. For `forum`: discussion prompt. |
| `grade` | Float / Int | Optional | Maximum grade points for gradable activities (`assign` defaults to `100`, `quiz` defaults to `10` or `50`). |
| `visible` | Integer (0/1) | Optional | Visibility state: `1` = visible to students, `0` = hidden (teacher review mode). Default: `1`. |
| `completion` | Integer (0-2) | Optional | Completion tracking: `0` = none, `1` = manual by student, `2` = automatic upon conditions. |
| `completionview` | Integer (0/1) | Optional | Automatic completion rule: `1` = student must view the activity to complete it. |
| `timeopen` | Integer | Optional | Unix timestamp for opening quizzes or assignments. |
| `timeclose` | Integer | Optional | Unix timestamp for due/closing dates. |

#### Critical CSV Formatting Rules
1. **Semicolons for Inline Styles**: Never use commas inside HTML inline styles (`style="color: #0284c7; font-size: 14px;"` ✅). Commas will break CSV cell boundaries.
2. **Double Quote Wrapping**: Always encapsulate cells containing HTML, commas, or quotes inside double quotes (`"<div>...</div>"`).
3. **Single Line Per Row**: Do not include raw literal newlines within HTML cells. All content for a single activity row must exist on one continuous line.
4. **Character Encoding**: Save files strictly in **UTF-8** (with or without BOM; the plugin automatically handles UTF-8 BOM headers).

---

### 2. Moodle XML Question Bank Specification

Quizzes generated by `local_coursebuilder` are automatically populated using a companion **Moodle XML Question Bank** file.

#### File Structure
```xml
<?xml version="1.0" encoding="UTF-8"?>
<quiz>
  <!-- Category Declaration (Used by question_importer for quiz auto-mapping) -->
  <question type="category">
    <category>
      <text>$course$/top/[Course Name] / [Section or Subject] / [Category Name]</text>
    </category>
  </question>

  <!-- Multiple Choice Question Instance -->
  <question type="multichoice">
    <name><text>[TAG] Question Preview Stem...</text></name>
    <questiontext format="html">
      <text><![CDATA[<p>What is the primary function of an operating system kernel?</p>]]></text>
    </questiontext>
    <generalfeedback format="html"><text></text></generalfeedback>
    <defaultgrade>1</defaultgrade>
    <penalty>0.3333333</penalty>
    <hidden>0</hidden>
    <single>true</single>
    <shuffleanswers>true</shuffleanswers>
    <answernumbering>abc</answernumbering>
    <correctfeedback format="html"><text>Correct!</text></correctfeedback>
    <incorrectfeedback format="html"><text>Incorrect. Please review the module notes.</text></incorrectfeedback>
    
    <!-- Exactly 1 Correct Answer (fraction="100") -->
    <answer fraction="100" format="html">
      <text><![CDATA[<p>Managing hardware resources and mediating system calls</p>]]></text>
      <feedback format="html"><text>Correct!</text></feedback>
    </answer>
    
    <!-- Exactly 3 Distractors (fraction="0") -->
    <answer fraction="0" format="html">
      <text><![CDATA[<p>Compiling high-level programming code into bytecode</p>]]></text>
    </answer>
    <answer fraction="0" format="html">
      <text><![CDATA[<p>Rendering graphics in the user window manager</p>]]></text>
    </answer>
    <answer fraction="0" format="html">
      <text><![CDATA[<p>Formatting network packets for HTTP transport</p>]]></text>
    </answer>
  </question>
</quiz>
```

#### XML Guidelines
- **Category Hierarchy**: Must start with `$course$/top/`. Separate category levels using space-slash-space (` / `).
- **CDATA Blocks**: Always wrap question text and answer choices in `<![CDATA[ ... ]]>` to prevent XML parsing syntax errors with HTML characters.
- **Answer Fractions**: Assign `fraction="100"` to the single correct option and `fraction="0"` to the three distractors.

---

### 3. JSON Course Structure Specification

The plugin natively accepts JSON files formatted as an array of section and activity objects:

```json
[
  {
    "type": "section",
    "name": "Unit 1: Fundamentals of Computing",
    "intro": "<p>Introduction to computer hardware, memory architectures, and binary logic.</p>"
  },
  {
    "type": "label",
    "name": "Unit 1 Overview Banner",
    "intro": "<div class='alert alert-info'><h3>Welcome to Unit 1</h3><p>Complete lessons 1-3 before attempting the quiz.</p></div>",
    "visible": 1
  },
  {
    "type": "page",
    "name": "Lesson 1 — Architecture Overview",
    "intro": "<div><h3>Learning Objectives</h3><ul><li>Understand CPU execution cycles</li></ul><p>Content goes here...</p></div>",
    "visible": 0,
    "completion": 2,
    "completionview": 1
  },
  {
    "type": "quiz",
    "name": "Unit 1 Quiz — Fundamentals of Computing",
    "intro": "<div><p>Auto-graded quiz testing fundamental computing knowledge.</p></div>",
    "grade": 50,
    "visible": 0,
    "completion": 2,
    "completionview": 1
  },
  {
    "type": "assign",
    "name": "Unit 1 Assignment — Memory Mapping",
    "intro": "<div><p>Complete the memory layout schematic attached below.</p></div>",
    "grade": 100,
    "visible": 0,
    "completion": 1
  },
  {
    "type": "forum",
    "name": "Unit 1 Discussion — Moore's Law",
    "intro": "<div><blockquote>Is Moore's Law still relevant in modern semiconductor manufacturing?</blockquote></div>",
    "visible": 0,
    "completion": 1
  }
]
```

---

## Heuristic Question-to-Quiz Mapping Algorithm

When a companion Moodle XML questions file is uploaded, `question_importer.php` automatically links questions into the newly created quizzes without requiring manual slot assignment in Moodle's quiz editing interface.

The matcher evaluates each quiz against every XML category path in the question bank using the following scoring heuristics:

| Match Criterion | Weight / Points | Evaluation Logic |
|---|---|---|
| **Section Name Match** | `+10` points | Case-insensitive check if the course section name appears in the XML category path. |
| **Unit Number Match** | `+20` points | Regex extraction of unit number (e.g. `Unit 2` in quiz name vs `Unit 2` in category path). |
| **Topic Keyword Overlap** | `+5` points per word | Tokenizes quiz title (excluding generic terms like `unit`, `quiz`, `test`, `exam`), checks overlap with words $> 3$ characters. |
| **Minimum Confidence Threshold** | **`15` points** | A match is only confirmed if the total heuristic score is $\ge 15$. |

### Auto-Mapping Example
- **CSV Quiz Activity**: `Unit 1 Quiz — Python Basics` inside Section `Unit 1`
- **XML Category Path**: `$course$/top/Python Programming / Unit 1 / Unit 1 - Python Basics`
- **Scoring**:
  - Section name match (`Unit 1` in path): `+10`
  - Unit number match (`Unit 1` in path): `+20`
  - Keyword match (`python`, `basics`): `+10`
  - **Total Score = 40** (Firm match ✅). The importer binds all questions in that category to the quiz's `quiz_slots` and `question_references`.

---

## AI Companion & CourseMaker Tooling (`coursemaker/`)

The repository includes a dedicated AI generation suite in the `coursemaker/` folder. This toolset enables external LLMs (Claude, GPT, Gemini) or CLI scripts to transform lengthy source materials (PDF textbooks, Word syllabi, slide decks) into paired CSV + XML bundles.

### The 3-Step Chunked Workflow

Large documents often overwhelm AI context limits when attempting to generate entire courses at once. `coursemaker` uses a 3-step chunked generation methodology:

1. **Step 1: Read & Plan**:
   - The AI reviews the entire source document once.
   - Outputs a course structure plan (course name, unit count, titles, assessments).
2. **Step 2: Unit-by-Unit Processing (JSON per Unit)**:
   - The AI creates a `./units/` folder and processes each unit individually into a validated `unit-N.json` file.
   - Ensures deep pedagogical detail, full HTML content, rubrics, and customized quiz questions.
3. **Step 3: CLI Assembly**:
   - The generic Python assembler script aggregates all `unit-*.json` files into the final `CourseName.csv` and `CourseNameQuestions.xml`.

### Unit JSON Schema

Defined in `coursemaker/schemas/unit_schema.json`:

```json
{
  "unit_number": 1,
  "unit_name": "Unit 1",
  "section_name": "Unit 1: Object-Oriented Programming",
  "tag": "[OOP1]",
  "description": "Master classes, inheritance, polymorphism, and encapsulation.",
  "lessons": [
    {
      "title": "Classes and Instances",
      "html_content": "<p>In Python, classes provide a means of bundling data and functionality...</p>"
    }
  ],
  "quiz": {
    "title": "Unit 1 Quiz — Object-Oriented Programming",
    "category_name": "Unit 1 - Object-Oriented Programming",
    "grade": 50,
    "questions": [
      {
        "question_text": "Which method is the constructor in Python classes?",
        "correct_answer": "__init__()",
        "wrong_answers": ["__new__()", "__construct()", "init()"]
      }
    ]
  },
  "assignment": {
    "title": "Unit 1 Assignment — Bank Account Class",
    "instructions": "<p>Design a BankAccount class supporting deposit and withdrawal...</p>",
    "grade": 100
  },
  "forum": {
    "title": "Unit 1 Discussion — Composition vs Inheritance",
    "prompt": "When is object composition preferred over classical inheritance?"
  }
}
```

### CLI Assembly Script (`assemble_course.py`)

Run the script from any terminal:

```bash
python3 coursemaker/scripts/assemble_course.py <path_to_units_dir> "<Course Name>" [output_dir]
```

**Example:**
```bash
python3 coursemaker/scripts/assemble_course.py ./courses/python_units "Python Programming" ./output
```

**Output:**
```text
============================================================
  Coursebuilder Assembly Script
  Course: Python Programming
  Units folder: ./courses/python_units
  Output folder: ./output
============================================================

Loading unit files...
  Loaded: unit-0.json → Introduction
  Loaded: unit-1.json → Unit 1

Validating 2 units...
  All units valid ✓

  Sections:    2
  Lessons:     6
  Quizzes:     2
  Questions:   10
  Assignments: 2
  Forums:      2

Generating output files...
  CSV created: ./output/Python_Programming.csv
  XML created: ./output/Python_ProgrammingQuestions.xml
```

---

## Installation & Prerequisites

### Prerequisites

- **Moodle Version**: Moodle 4.0 or higher (Moodle 4.5+ or 5.x recommended for native AI features).
- **PHP**: PHP 8.1 or PHP 8.2 with `php-xml` and `php-json` extensions enabled.
- **Permissions**: Moodle Site Administrator or Teacher with course editing capabilities.

### Standard Installation

1. **Clone or Download the Repository**:
   Place the plugin files into your Moodle installation's `local/coursebuilder` directory:
   ```bash
   cd /path/to/moodle/local
   git clone https://github.com/Smartlearn-edu/moodle-local_coursebuilder.git coursebuilder
   ```
   *(Ensure the folder name is strictly `coursebuilder`).*

2. **Run the Moodle Database Upgrade**:
   - **Via Web Interface**: Log in as an administrator and navigate to **Site administration > Notifications**. Follow the on-screen prompts to complete installation.
   - **Via Command Line (Recommended)**:
     ```bash
     php admin/cli/upgrade.php
     ```

3. **Verify Installation**:
   Navigate to **Site administration > Plugins > Local plugins > Course Builder**.

### Moodle AI Subsystem Configuration

To enable the built-in **AI Course Prompt** and **Syllabus File** generation features in Moodle 4.5+:

1. Navigate to **Site administration > AI > AI settings**.
2. Enable the AI Subsystem.
3. Configure your desired AI Provider (e.g., OpenAI, Ollama, DeepSeek, or Azure).
4. Ensure the **Generate text** action is enabled and permitted for the system context.

---

## Completion Tracking & Visibility Conventions

To ensure courses meet institutional quality standards before being released to students, `local_coursebuilder` implements the following standard settings:

| Activity | `visible` | `completion` | `completionview` | Purpose |
|---|:---:|:---:|:---:|---|
| **Section** | `1` | `0` | `0` | Course topic containers remain accessible. |
| **Label** (Banners) | `1` | `0` | `0` | Headers and navigation aids remain visible. |
| **Page** (Lessons) | `0` | `2` | `1` | Hidden for teacher review; automatically marks complete when viewed. |
| **Quiz** | `0` | `2` | `1` | Hidden for teacher review; marks complete upon view/attempt. |
| **Assignment** | `0` | `1` | `0` | Hidden for teacher review; manual completion checkoff by default. |
| **Forum** | `0` | `1` | `0` | Hidden for teacher review; manual completion checkoff by default. |

> **Best Practice**: Activities are set to `visible=0` (hidden from students) by default so instructors can review learning materials, adjust dates, and check question banks before making the course live.

---

## Troubleshooting & Best Practices

### 1. CSV Parsing Issues
- **Error: "Invalid or empty CSV file"**: Verify that the first line contains the exact header string without leading whitespace.
- **Malformed Rows or Broken Columns**: Check your HTML in the `intro` column. Ensure all CSS style declarations use semicolons (`;`) instead of commas (`,`), and ensure double quotes inside HTML attributes are properly escaped.
- **Character Encoding**: If special characters or Arabic/diacritic text appear corrupted, re-save the CSV file with explicit **UTF-8** encoding.

### 2. XML Question Auto-Mapping Issues
- **Questions imported into Question Bank but not assigned to Quizzes**:
  - Check the category path in your XML file. It must start with `$course$/top/`.
  - Ensure the unit number in the quiz title (e.g. `Unit 2 Quiz`) matches the unit number in the XML category path (`... / Unit 2 / ...`).
  - Check that topic keywords in the quiz title overlap with the category name. The matching algorithm requires a minimum score of **15 points**.

### 3. AI Subsystem Timeouts
- When generating large 10+ week courses with AI, the LLM provider may take 30–60 seconds to respond. Ensure your PHP execution timeout (`max_execution_time`) is set to at least `120` seconds in `php.ini`.

---

## Developer Notes & Standards

- **Frankenstyle Compliance**: All component identifiers use the `local_coursebuilder` frankenstyle prefix.
- **Moodle Coding Style**: Strict separation of concerns between form definitions (`classes/form/`), presentation logic (`classes/output.php`), data construction (`classes/builder.php`), and external importers (`classes/question_importer.php`).
- **Cache Management**: Automatically executes `\rebuild_course_cache($courseid)` upon build completion, ensuring navigation trees, section modules, and completion rules immediately reflect changes.
- **Gradebook Integration**: Programmatically updates the Moodle Gradebook via `quiz_grade_item_update()` and `quiz_update_grades()`, preventing grade synchronization discrepancies.

---

## License & Copyright

- **License**: [GNU General Public License v3 or later](https://www.gnu.org/licenses/gpl-3.0.html)
- **Copyright**: © 2025–2026 Mohammad Nabil `<mohammad@smartlearn.education>`
- **Organization**: [SmartLearn Education](https://smartlearn.education)
