<?php
// This file is part of Moodle - http://moodle.org/

$string['pluginname'] = 'Course Builder';
$string['plugin_description'] = 'Upload a structured CSV or JSON file to automatically build activities and sections within an existing Moodle course.';
$string['upload_header'] = 'Upload Course Data';
$string['select_course'] = 'Target Course';
$string['upload_file'] = 'Data File (CSV/JSON)';
$string['aiprompt'] = 'AI Course Prompt';
$string['aiprompt_help'] = 'Enter a detailed prompt describing the course you want to build. Our AI will generate the required structure for you.';
$string['aiprompt_desc'] = 'Describe the course you want to build. Our AI will generate the structure for you.';
$string['submit_upload'] = 'Generate / Upload';
$string['preview_title'] = 'Course Structure Preview';
$string['preview_heading'] = 'Review Generated Course';
$string['confirm_build'] = 'Confirm & Build Course';
$string['modify_heading'] = 'Refine with AI';
$string['modify_placeholder'] = 'Type your modification request here... (e.g., "Add a quiz to the end of each week", "Rename Section 1 to Introduction")';
$string['modify_btn'] = 'Modify Course';
$string['syllabus_file'] = 'Syllabus File (TXT/MD)';
$string['syllabus_file_help'] = 'Upload a plain text or markdown syllabus. The AI will read this file and automatically convert it into a structured course.';

// Errors
$string['error_missing_input'] = 'You must provide a Data File, an AI Course Prompt, or a Syllabus File.';
$string['error_ai_provider'] = 'Error communicating with Moodle AI Provider: {$a}';

// XML Questions
$string['questions_file'] = 'Questions File (Moodle XML)';
$string['questions_file_help'] = 'Optional. Upload a Moodle XML question bank file. Questions will be imported into the course Question Bank and automatically mapped to matching quiz activities.';
$string['questions_imported'] = '{$a} questions imported and mapped to quizzes.';
