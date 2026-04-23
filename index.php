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
    // Process the form data.
    $content = $mform->get_file_content('csvfile');
    
    if (!$content) {
        throw new \moodle_exception('errorreadingfile', 'error');
    }
    
    // Save file temporarily for fgetcsv.
    $tempdir = make_request_directory();
    $filepath = $tempdir . '/upload.csv';
    file_put_contents($filepath, $content);
    
    try {
        $builder = new \local_coursebuilder\builder($data->courseid);
        $builder->process_csv($filepath);
        
        // Redirect to the populated course.
        redirect(new moodle_url('/course/view.php', ['id' => $data->courseid]), 'Course successfully built from CSV!', null, \core\output\notification::NOTIFY_SUCCESS);
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
