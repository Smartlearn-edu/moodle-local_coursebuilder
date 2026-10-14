# Moodle Course Builder Skill (Antigravity Ready-to-Use Folder)

This folder is a complete, self-contained **Antigravity Skill** for generating paired CSV and Moodle XML question banks for the `local_coursebuilder` plugin.

---

## Folder Structure

```text
skills/moodle-course-builder/
├── SKILL.md                 # Antigravity skill definition (with /grill-me wizard & validator instructions)
├── README.md                # This guide
├── references/              # Real working samples (FOR SYNTAX UNDERSTANDING ONLY)
│   ├── sample_course.csv    # Reference course structure CSV
│   └── sample_questions.xml # Reference Moodle XML question bank
├── scripts/
│   ├── validate_course.py   # Pre-upload validator & auto-mapping scoring simulator
│   └── assemble_course.py   # Course assembly engine
└── schemas/
    └── unit_schema.json     # Optional unit JSON schema for chunked processing
```

---

## How Antigravity Uses This Folder

1. **Auto-Discovery**: Antigravity automatically detects this skill from `skills/moodle-course-builder/SKILL.md` (or `.agents/skills/moodle-course-builder/SKILL.md`).
2. **Interactive Wizard**: Whenever a teacher asks to build a course or uploads materials (PDFs, slides, images, notes), the AI acts as an interactive wizard (like `/grill-me`) to ask about their course vision before generating.
3. **Automated Verification**: Immediately after writing the CSV and XML files, Antigravity executes:
   ```bash
   python3 scripts/validate_course.py <course.csv> <questions.xml>
   ```
   If any syntax hazard or low auto-mapping score (< 15) is detected, Antigravity fixes it before presenting the final files to the teacher!

---

## How to Share with Other Teachers
To use this skill in another repository or machine:
* **Option A (Per Project)**: Copy the `moodle-course-builder` folder into any project's `skills/` or `.agents/skills/`.
* **Option B (Global)**: Copy into `~/.gemini/config/skills/moodle-course-builder/`.
