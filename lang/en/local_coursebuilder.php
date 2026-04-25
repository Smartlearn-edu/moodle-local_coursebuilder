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

// Errors
$string['error_missing_input'] = 'You must provide either a Data File or an AI Course Prompt.';
$string['error_ai_provider'] = 'Error communicating with Moodle AI Provider: {$a}';
