<?php
// This file is part of Moodle - http://moodle.org/

$string['pluginname'] = 'Course Builder';
$string['plugin_description'] = 'Upload a structured CSV or JSON file to automatically build activities and sections within an existing Moodle course.';
$string['upload_header'] = 'Upload Course Data';
$string['select_course'] = 'Target Course';
$string['upload_file'] = 'Data File (CSV/JSON)';
$string['aiprompt'] = 'AI Course Prompt';
$string['aiprompt_desc'] = 'Describe the course you want to build. Our AI will generate the structure for you.';
$string['submit_upload'] = 'Build Course';

// Settings
$string['webhookurl'] = 'n8n Webhook URL';
$string['webhookurl_desc'] = 'The full URL to your n8n webhook endpoint (e.g., https://n8n.yourdomain.com/webhook/course-builder).';
$string['webhooktoken'] = 'n8n Auth Token';
$string['webhooktoken_desc'] = 'Optional Bearer token if your webhook requires authentication.';

// Errors
$string['error_missing_input'] = 'You must provide either a Data File or an AI Course Prompt.';
$string['error_webhook'] = 'Error communicating with AI service: {$a}';
