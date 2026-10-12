# Moodle Course Builder (`local_coursebuilder`)

[![Moodle Version](https://img.shields.io/badge/Moodle-4.0%20to%205.x%2B-orange.svg)](https://moodle.org)
[![Plugin Type](https://img.shields.io/badge/Plugin%20Type-Local%20Plugin-green.svg)](https://docs.moodle.org)
[![License](https://img.shields.io/badge/License-GPLv3%20or%20later-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.html)
[![Easy for Teachers](https://img.shields.io/badge/Designed%20for-Teachers%20%26%20Educators-purple.svg)](https://smartlearn.education)

> **Turn your syllabus, curriculum documents, or rough ideas into a complete, ready-to-teach Moodle course in under 60 seconds.**  
> No technical skills or coding required.

---

## Table of Contents

- [What is Course Builder?](#what-is-course-builder)
- [How It Works: The Simple Idea](#how-it-works-the-simple-idea)
- [How to Use It: Step-by-Step](#how-to-use-it-step-by-step)
  - [Option 1: Build a Complete Course with AI (Recommended)](#option-1-build-a-complete-course-with-ai-recommended)
  - [Option 2: Generate Directly Inside Moodle (Quick Prototype)](#option-2-generate-directly-inside-moodle-quick-prototype)
- [Ready-to-Copy AI Prompts](#ready-to-copy-ai-prompts)
- [What Activities Can It Build For You?](#what-activities-can-it-build-for-you)
- [Adding Videos & PDFs to Your Lessons](#adding-videos--pdfs-to-your-lessons)
- [Simple Spreadsheet (CSV) Template](#simple-spreadsheet-csv-template)
- [Frequently Asked Questions (FAQ)](#frequently-asked-questions-faq)
- [Administrator Installation (Quick Setup)](#administrator-installation-quick-setup)
- [License & Support](#license--support)

---

## What is Course Builder?

Normally, setting up a new course in Moodle takes hours of repetitive work: creating topics, typing lesson pages, copying rubrics into assignments, setting up discussion forums, and manually building quiz questions one by one.

**Course Builder does all of that for you automatically.**

You can give it a syllabus, a list of topics, or an AI prompt, and Course Builder instantly creates:
- 📁 **Organized Sections** (Weeks or Units)
- 📖 **Full Lesson Pages** (With titles, learning goals, and complete readings)
- ❓ **Auto-Graded Quizzes** (With multiple-choice questions, answer options, and instant feedback)
- 📝 **Assignments** (With student briefs, instructions, and grading rubrics)
- 💬 **Discussion Forums** (With engaging conversation prompts)
- 🎬 **Embedded Videos & Images**

---

## How It Works: The Simple Idea

Think of course creation as a two-person team:
1. **The AI (Your Assistant Writer)**: Reads your syllabus or ideas and writes all the lessons, quiz questions, and assignments into two neat files.
2. **The Moodle Plugin (Your Course Builder)**: Takes those two files and builds the live course in Moodle in 5 seconds.

```
┌──────────────────────────────────────┐       ┌──────────────────────────────────────┐
│       OUTSIDE MOODLE: AI CHAT        │       │            INSIDE MOODLE             │
│    (ChatGPT, Claude, or Gemini)      │       │        (Your Moodle Website)         │
├──────────────────────────────────────┤       ├──────────────────────────────────────┤
│ 1. You paste your syllabus or topic. │       │                                      │
│                                      │       │                                      │
│ 2. The AI writes everything and      │       │                                      │
│    gives you TWO files:              │       │                                      │
│    • Course.csv (Lessons & Layout)   │ ────> │ 1. Go to Course Builder.             │
│    • Questions.xml (Quiz Questions)  │       │ 2. Select your course.               │
│                                      │       │ 3. Upload the 2 files.               │
│                                      │       │ 4. Click "Confirm & Build".          │
│                                      │       │                                      │
│                                      │       │ ✨ DONE! Your course is live!         │
└──────────────────────────────────────┘       └──────────────────────────────────────┘
```

---

## How to Use It: Step-by-Step

### Option 1: Build a Complete Course with AI (Recommended)

*Use this method when you want deep, complete lessons with real quiz questions and rubrics.*

#### Step 1: Open your favorite AI Chat
Open **ChatGPT**, **Claude**, **Gemini**, or **Antigravity**.

#### Step 2: Copy & Paste this Master Prompt
Copy the prompt below, paste your syllabus or topic at the bottom, and send it to the AI:

```text
You are a Moodle Course Specialist. I want you to build a complete, ready-to-import Moodle course for me.

Please generate TWO separate files:
1. "Course.csv" (The course structure and lesson content)
2. "Questions.xml" (The auto-graded quiz questions)

Requirements for Course.csv:
- Header must be: type,section,name,intro,grade,visible,completion,completionview,timeopen,timeclose
- For each unit/week, create:
  • 1 section (The week or unit title)
  • 1 label (Unit banner and overview)
  • 2 or 3 page activities with COMPLETE, detailed lesson content (learning objectives, explanations, practical examples in HTML)
  • 1 quiz (grade=50, visible=0, completion=2, completionview=1)
  • 1 assign activity with full instructions and a grading rubric table (grade=100)
  • 1 forum activity with an engaging discussion prompt
- Put all HTML on ONE single line per cell. Use semicolons (;) instead of commas inside CSS style attributes. Wrap all HTML cells in double quotes.

Requirements for Questions.xml:
- Root tag: <quiz>
- Each unit must have a category declaration matching the course and unit name:
  <question type="category"><category><text>$course$/top/[Course Name] / [Unit Name] / [Quiz Name]</text></category></question>
- For every quiz, write 5 multiple-choice questions testing the lesson content.
- Each question must have 4 options: 1 correct (fraction="100") and 3 wrong (fraction="0"). Include feedback explaining why the answer is correct. Wrap text in <![CDATA[ ... ]]>.

Here is my course topic / syllabus:
[PASTE YOUR SYLLABUS, LESSON TOPICS, OR COURSE IDEA HERE]
```

#### Step 3: Save the Two Files
The AI will give you two blocks of code:
- Save the first block as **`Course.csv`**
- Save the second block as **`Questions.xml`**

#### Step 4: Upload into Moodle (Takes 30 seconds)
1. In Moodle, go to:
   ```
   http://your-moodle-site.com/local/coursebuilder/index.php
   ```
2. **Target Course**: Select your course from the dropdown menu.
3. **Data File (CSV/JSON)**: Upload your `Course.csv`.
4. **Questions File (XML)**: Upload your `Questions.xml`.
5. Click **Generate / Upload**.
6. Check the visual preview on your screen, then click **Confirm & Build Course**.

**That’s it!** Moodle creates all the sections, writes the lesson pages, links the quiz questions, and opens your finished course.

---

### Option 2: Generate Directly Inside Moodle (Quick Prototype)

*Use this method when you want to quickly test an idea directly inside Moodle without leaving your web browser.*

1. Go to:
   ```
   http://your-moodle-site.com/local/coursebuilder/index.php
   ```
2. Select your **Target Course** from the dropdown menu.
3. In the **AI Course Prompt** box, type what you want to teach (see examples below).
4. *(Optional)* If you have a syllabus document as a text file (`.txt` or `.md`), upload it under **Syllabus File**.
5. Click **Generate / Upload**.
6. Review the interactive preview.
7. **Refine with AI**: Want to change something before finalizing? In the box at the bottom, type:
   > *"Add a quiz to Unit 2"* or *"Change the assignment grade to 50"*  
   and click **Modify Course**.
8. Click **Confirm & Build Course**.

---

## Ready-to-Copy AI Prompts

You can copy and paste any of these prompts directly into the Moodle **AI Course Prompt** box or into ChatGPT/Claude:

### 💡 For Computer Science / Coding
```text
Create a 4-week introductory course on "Python for Beginners".
Week 1: Getting Started, Variables, and Data Types.
Week 2: Conditions (if/else) and Loops (for/while).
Week 3: Functions, Parameters, and Return Values.
Week 4: Final Project: Building a Text Adventure Game.
For each week, create 3 complete lesson pages, an assignment with a rubric, a quiz, and a discussion topic.
```

### 💡 For Business & Marketing
```text
Create a 4-week course on "Digital Marketing & Social Media".
Week 1: Understanding Target Audiences and Buyer Personas.
Week 2: Content Strategy and Organic Social Media.
Week 3: Paid Advertising on Meta and Google Ads.
Week 4: Analytics, KPIs, and Conversion Rate Optimization.
For each week, include 3 detailed lesson pages, an assignment with instructions, a quiz, and a discussion forum.
```

### 💡 For High School Science
```text
Create a 3-unit course on "High School Biology: Cells & Genetics".
Unit 1: Cell Structure and Organelles.
Unit 2: Cellular Energy (Photosynthesis and Respiration).
Unit 3: DNA, Genes, and Heredity.
For each unit, include 3 structured lesson pages, a unit lab assignment with rubric, a quiz, and a discussion question.
```

---

## What Activities Can It Build For You?

| Activity Icon | Activity Type | What Gets Created For You |
|:---:|---|---|
| 📁 | **Course Sections** | Organizes your course into clean weeks or topic units. |
| 🏷️ | **Visual Banners (Labels)** | Welcome messages, pacing calendars, and unit introductions shown directly on the course page. |
| 📖 | **Complete Lessons (Pages)** | Full reading materials with headings, learning outcomes, explanations, and bullet points. **Not empty placeholders.** |
| ❓ | **Quizzes** | Auto-graded quizzes with 5 questions per unit, 4 multiple-choice answers, automatic grading, and feedback for students. |
| 📝 | **Assignments** | Student project briefs, submission requirements, and formatted grading rubric tables. |
| 💬 | **Discussion Forums** | Open-ended discussion scenarios with guidance notes for teacher facilitation. |

---

## Adding Videos & PDFs to Your Lessons

You can easily have the AI include playable videos and downloadable documents inside your lesson pages:

### 1. YouTube, Vimeo, or Loom Videos
Ask the AI: *"Include a relevant YouTube video embed in Lesson 1."*  
The AI inserts a responsive video player that students can play directly inside Moodle:
```html
<div class="ratio ratio-16x9 my-3">
  <iframe src="https://www.youtube.com/embed/YOUR_VIDEO_ID" allowfullscreen></iframe>
</div>
```

### 2. Downloadable PDF Guides
Ask the AI: *"Add a download button for the unit PDF worksheet."*  
The AI inserts a download card:
```html
<div class="alert alert-info">
  <h5>📄 Download Lesson Notes</h5>
  <p>Download the PDF summary sheet to follow along with this lesson.</p>
  <a href="https://your-site.com/handbook.pdf" target="_blank" class="btn btn-primary">Download PDF</a>
</div>
```

---

## Simple Spreadsheet (CSV) Template

If you prefer using **Excel** or **Google Sheets** to plan your course, you can create a spreadsheet with these 10 columns:

```csv
type,section,name,intro,grade,visible,completion,completionview,timeopen,timeclose
```

### What each column means (in simple words):

1. **`type`**: What activity this row is (`section`, `label`, `page`, `quiz`, `assign`, or `forum`).
2. **`section`**: The name or number of the week/unit (e.g. `Unit 1`).
3. **`name`**: The title students see in Moodle (e.g. `Lesson 1: Introduction`).
4. **`intro`**: The lesson text or assignment instructions (in HTML).
5. **`grade`**: Points for quizzes or assignments (e.g. `100` or `50`).
6. **`visible`**: Set to `0` to keep hidden while you review, or `1` to show to students immediately.
7. **`completion`**: `1` for manual checkoff by student, `2` for automatic completion.
8. **`completionview`**: `1` to automatically mark complete when the student opens the page.
9. **`timeopen`** / **`timeclose`**: Optional dates for quizzes.

### Ready-to-use Sample CSV:
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

## Frequently Asked Questions (FAQ)

#### Do I need to know how to code or program?
**No, not at all.** You only need to copy and paste the prompt into ChatGPT or Claude, save the two files it gives you, and upload them into Moodle.

#### Are the lesson pages empty?
**No.** Every lesson page is generated with complete, multi-paragraph text, learning objectives, formatting, and examples.

#### Can I edit the course after it is built?
**Yes, 100%.** Course Builder creates standard Moodle activities. You can turn on "Edit mode" at any time to reword lessons, change dates, add extra questions, or delete activities just like any regular Moodle course.

#### Can students see the course while I am building it?
By default, the plugin sets activities to **hidden (`visible=0`)**. This lets you review everything privately in teacher view. When you are ready, you can make the sections visible to students with one click.

#### How do the quiz questions connect to the quizzes?
The plugin includes an automatic matching tool. As long as your quiz name (e.g. *Unit 1 Quiz*) matches the unit in the questions file (e.g. *Unit 1*), Moodle automatically places the questions inside the quiz for you. You don't have to link them by hand.

---

## Administrator Installation (Quick Setup)

For Moodle Administrators installing this plugin:

1. Download or clone this folder into your Moodle installation under `local/coursebuilder`:
   ```bash
   cd /path/to/moodle/local
   git clone https://github.com/Smartlearn-edu/moodle-local_coursebuilder.git coursebuilder
   ```
2. In your browser, log in as an administrator and go to **Site administration > Notifications**, or run:
   ```bash
   php admin/cli/upgrade.php
   ```
3. *(Optional)* To enable the built-in **AI Course Prompt** in Moodle 4.5+:
   - Go to **Site administration > AI > AI settings**.
   - Enable your preferred AI provider (e.g., OpenAI, Azure, DeepSeek, or Ollama) and ensure the "Generate text" action is active.

---

## License & Support

- **License**: [GNU General Public License v3 or later](https://www.gnu.org/licenses/gpl-3.0.html)
- **Author**: Mohammad Nabil `<mohammad@smartlearn.education>`
- **Organization**: [SmartLearn Education](https://smartlearn.education)
