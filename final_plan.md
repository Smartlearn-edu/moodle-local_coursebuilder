# CSV to Moodle Course Builder (`local_coursebuilder`)

## Background Context
Standard Moodle does not natively support creating granular course activities (pages, labels, quizzes, assignments, forums) from a CSV file. To automate the conversion of a structured CSV into a living Moodle course for the Proof of Concept, we are building a custom Moodle Local plugin. 

## Final Agreed Approach
1. **Target Course**: The plugin will allow the teacher to select an *existing* empty course to populate. This is safer and avoids the complexity of generating course metadata (shortname, category, etc.) programmatically.
2. **XML Import**: We will ignore the XML Question Bank import within this plugin. The XML import will be handled manually via Moodle's built-in Question Bank UI, keeping this plugin focused strictly on parsing the custom CSV.
3. **Supported Activities**: The plugin will read the `type` column from the CSV and programmatically create:
   - Sections (Topics/Weeks)
   - Labels
   - Pages
   - Quizzes
   - Assignments
   - Forums

## Proposed Architecture

The plugin will be a Local Plugin named `local_coursebuilder`.

### Directory Structure
```text
local/coursebuilder/
├── lang/
│   └── en/
│       └── local_coursebuilder.php   # Localization strings
├── classes/
│   ├── form/
│   │   └── upload_form.php           # Moodle form (CSV file picker + Course dropdown)
│   └── builder.php                   # Core business logic for parsing and creating modules
├── index.php                         # Main controller/UI page
└── version.php                       # Plugin version and requirements
```

## Technical Implementation Details

### 1. CSV Parsing Logic
The `classes/builder.php` script will map the CSV columns (`type`, `section`, `name`, `intro`, `grade`, `visible`, `completion`, `completionview`, `timeopen`, `timeclose`) to Moodle's internal data structures.

- **Sections**: When `type = 'section'`, the script will update the corresponding course section (e.g., rename it).
- **Modules**: For `type = 'label', 'page', 'quiz', 'assign', 'forum'`, the script will instantiate the respective Moodle activity within the current active section.

### 2. Moodle Core API Integration
We will leverage Moodle's core module creation functions (e.g., `course_add_cm_to_section()` and module-specific `add_instance` functions or `$DB->insert_record` combined with `rebuild_course_cache()`):

*   **HTML Content**: The rich HTML provided in the `intro` column of the CSV will be mapped to the `intro` field (for labels, forums, quizzes, assignments) or the `content` field (for pages), setting the format to `FORMAT_HTML`.
*   **Settings mapping**: The `grade` column will be used to set the maximum grade for gradable activities (quizzes, assignments).

### 3. Execution Flow
1. User navigates to the plugin UI (`/local/coursebuilder/index.php`).
2. User selects an existing course from a dropdown and uploads the CSV file.
3. Upon submission, the form data is passed to `classes/builder.php`.
4. The builder reads the CSV file row by row.
5. It interacts with the Moodle Database and Course APIs to inject the sections and modules in the exact order they appear in the CSV.
6. The user is redirected to the newly populated course with a success message.
