<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');

$PAGE->set_url(new moodle_url('/local/coursebuilder/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_coursebuilder'));
$PAGE->set_heading(get_string('pluginname', 'local_coursebuilder'));

require_login();

$action = optional_param('action', '', PARAM_ALPHANUM);

if ($action === 'build') {
    require_sesskey();
    
    $courseid = required_param('courseid', PARAM_INT);
    $course_json = required_param('course_json', PARAM_RAW);
    
    try {
        $builder = new \local_coursebuilder\builder($courseid);
        $tempdir = make_request_directory();
        $filepath = $tempdir . '/upload.json';
        file_put_contents($filepath, $course_json);
        
        $builder->process_json($filepath);
        
        redirect(new moodle_url('/course/view.php', ['id' => $courseid]), 'Course successfully built!', null, \core\output\notification::NOTIFY_SUCCESS);
    } catch (\Exception $e) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification('Error building course: ' . $e->getMessage(), 'error');
        echo $OUTPUT->continue_button(new moodle_url('/local/coursebuilder/index.php'));
        echo $OUTPUT->footer();
        die();
    }
}

// Instantiate the form.
$mform = new \local_coursebuilder\form\upload_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/coursebuilder/index.php'));
} else if ($data = $mform->get_data()) {
    try {
        $builder = new \local_coursebuilder\builder($data->courseid);
        
        $tempdir = make_request_directory();
        
        $data_array = [];
        $json_data_str = '';
        
        if (!empty(trim($data->aiprompt))) {
            // Process AI Prompt
            $json_response = $builder->call_moodle_ai(trim($data->aiprompt));
            
            // Check if response is valid JSON
            $data_array = json_decode($json_response, true);
            if ($data_array === null) {
                throw new \Exception('Invalid JSON received from AI Webhook.');
            }
            $json_data_str = $json_response;
            
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
                $data_array = $builder->parse_json($filepath);
                $json_data_str = $content;
            } else {
                $data_array = $builder->parse_csv($filepath);
                $json_data_str = json_encode($data_array);
            }
        }
        
        // Render Preview
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('preview_heading', 'local_coursebuilder'));
        
        echo \local_coursebuilder\output::render_preview($data_array);
        
        // Render Confirmation Form
        echo '<div class="mt-4">';
        echo '<form method="post" action="index.php">';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'">';
        echo '<input type="hidden" name="action" value="build">';
        echo '<input type="hidden" name="courseid" value="'.s($data->courseid).'">';
        echo '<input type="hidden" name="course_json" value="'.s($json_data_str).'">';
        echo '<button type="submit" class="btn btn-primary mr-2">' . get_string('confirm_build', 'local_coursebuilder') . '</button>';
        echo '<a href="index.php" class="btn btn-secondary">' . get_string('cancel', 'moodle') . '</a>';
        echo '</form>';
        echo '</div>';
        
        echo $OUTPUT->footer();
        die();
        
    } catch (\Exception $e) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification('Error parsing course data: ' . $e->getMessage(), 'error');
        echo $OUTPUT->continue_button(new moodle_url('/local/coursebuilder/index.php'));
        echo $OUTPUT->footer();
        die();
    }
}

echo $OUTPUT->header();

echo html_writer::tag('p', get_string('plugin_description', 'local_coursebuilder'));

$mform->display();

echo $OUTPUT->footer();
