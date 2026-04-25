<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');

$PAGE->set_url(new moodle_url('/local/coursebuilder/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_coursebuilder'));
$PAGE->set_heading(get_string('pluginname', 'local_coursebuilder'));

require_login();

// Instantiate the form.
$mform = new \local_coursebuilder\form\upload_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/coursebuilder/index.php'));
} else if ($data = $mform->get_data()) {
    try {
        $builder = new \local_coursebuilder\builder($data->courseid);
        
        $tempdir = make_request_directory();
        
        if (!empty(trim($data->aiprompt))) {
            // Process AI Prompt
            $json_response = $builder->call_n8n_webhook(trim($data->aiprompt));
            
            // Check if response is valid JSON
            if (json_decode($json_response) === null) {
                throw new \Exception('Invalid JSON received from AI Webhook.');
            }
            
            $filepath = $tempdir . '/upload.json';
            file_put_contents($filepath, $json_response);
            $builder->process_json($filepath);
            
        } else {
            // Process uploaded file
            $content = $mform->get_file_content('datafile');
            $filename = $mform->get_new_filename('datafile');
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!$content) {
                throw new \moodle_exception('errorreadingfile', 'error');
            }
            
            $filepath = $tempdir . '/upload.' . $ext;
            file_put_contents($filepath, $content);
            
            if ($ext === 'json') {
                $builder->process_json($filepath);
            } else {
                $builder->process_csv($filepath);
            }
        }
        
        // Redirect to the populated course.
        redirect(new moodle_url('/course/view.php', ['id' => $data->courseid]), 'Course successfully built!', null, \core\output\notification::NOTIFY_SUCCESS);
    } catch (\Exception $e) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification('Error building course: ' . $e->getMessage(), 'error');
        echo $OUTPUT->continue_button(new moodle_url('/local/coursebuilder/index.php'));
        echo $OUTPUT->footer();
        die();
    }
}

echo $OUTPUT->header();

echo html_writer::tag('p', get_string('plugin_description', 'local_coursebuilder'));

$mform->display();

echo $OUTPUT->footer();
