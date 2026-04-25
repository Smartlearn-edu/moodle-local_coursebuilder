<?php
// This file is part of Moodle - http://moodle.org/

require_once('../../config.php');

$PAGE->set_url(new moodle_url('/local/coursebuilder/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_coursebuilder'));
$PAGE->set_heading(get_string('pluginname', 'local_coursebuilder'));

require_login();

$action = optional_param('action', '', PARAM_ALPHANUM);

if ($action === 'modify') {
    require_sesskey();
    $courseid = required_param('courseid', PARAM_INT);
    $course_json = required_param('course_json', PARAM_RAW);
    $modify_prompt = required_param('modify_prompt', PARAM_TEXT);
    $questions_xml = optional_param('questions_xml', '', PARAM_RAW);
    
    try {
        $builder = new \local_coursebuilder\builder($courseid);
        
        $debug_output = '';
        if (!empty(trim($modify_prompt))) {
            $debug_output .= "<div class='alert alert-warning mt-3'><strong>Debug Info (Sent to AI):</strong><br><strong>Prompt:</strong> " . s($modify_prompt) . "<br><strong>Current JSON:</strong> <pre>" . s($course_json) . "</pre></div>";
            
            $json_response = $builder->modify_moodle_ai($course_json, trim($modify_prompt));
            $debug_output .= "<div class='alert alert-info'><strong>Debug Info (Received from AI):</strong><br><pre>" . s($json_response) . "</pre></div>";
            
            $data_array = json_decode($json_response, true);
            if ($data_array === null) {
                throw new \Exception('Invalid JSON received from AI Webhook during modification. Raw response: ' . s($json_response));
            }
            $json_data_str = $json_response;
        } else {
            // If empty prompt, just keep existing
            $data_array = json_decode($course_json, true);
            $json_data_str = $course_json;
        }
        
        // Render Preview
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('preview_heading', 'local_coursebuilder'));
        
        if (!empty($debug_output)) {
            echo $debug_output;
        }
        
        echo \local_coursebuilder\output::render_preview($data_array);
        
        // Render Confirmation Form with Chat
        echo '<div class="mt-4 p-4 border rounded" style="background: #f8f9fa;">';
        echo '<h4>' . get_string('modify_heading', 'local_coursebuilder') . '</h4>';
        echo '<form method="post" action="index.php">';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'">';
        echo '<input type="hidden" name="action" value="modify">';
        echo '<input type="hidden" name="courseid" value="'.s($courseid).'">';
        echo '<input type="hidden" name="course_json" value="'.s($json_data_str).'">';
        echo '<input type="hidden" name="questions_xml" value="'.s($questions_xml).'">';
        echo '<div class="form-group">';
        echo '<textarea name="modify_prompt" class="form-control" rows="3" placeholder="' . get_string('modify_placeholder', 'local_coursebuilder') . '"></textarea>';
        echo '</div>';
        echo '<button type="submit" class="btn btn-info">' . get_string('modify_btn', 'local_coursebuilder') . '</button>';
        echo '</form>';
        echo '</div>';
        
        echo '<div class="mt-4">';
        echo '<form method="post" action="index.php" style="display:inline-block;">';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'">';
        echo '<input type="hidden" name="action" value="build">';
        echo '<input type="hidden" name="courseid" value="'.s($courseid).'">';
        echo '<input type="hidden" name="course_json" value="'.s($json_data_str).'">';
        echo '<input type="hidden" name="questions_xml" value="'.s($questions_xml).'">';
        echo '<button type="submit" class="btn btn-primary mr-2">' . get_string('confirm_build', 'local_coursebuilder') . '</button>';
        echo '</form>';
        echo '<a href="index.php" class="btn btn-secondary">' . get_string('cancel', 'moodle') . '</a>';
        echo '</div>';
        
        echo $OUTPUT->footer();
        die();
        
    } catch (\Exception $e) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification('Error modifying course data: ' . $e->getMessage(), 'error');
        // If error, fall back to initial form
        echo $OUTPUT->continue_button(new moodle_url('/local/coursebuilder/index.php'));
        echo $OUTPUT->footer();
        die();
    }
}

if ($action === 'build') {
    require_sesskey();
    
    $courseid = required_param('courseid', PARAM_INT);
    $course_json = required_param('course_json', PARAM_RAW);
    $questions_xml = optional_param('questions_xml', '', PARAM_RAW);
    
    try {
        $builder = new \local_coursebuilder\builder($courseid);
        $tempdir = make_request_directory();
        $filepath = $tempdir . '/upload.json';
        file_put_contents($filepath, $course_json);
        
        $builder->process_json($filepath);
        
        // Import XML questions if provided.
        $questionsummary = '';
        if (!empty($questions_xml)) {
            $xmlpath = $tempdir . '/questions.xml';
            file_put_contents($xmlpath, $questions_xml);
            
            $importer = new \local_coursebuilder\question_importer($courseid);
            $importer->import_xml($xmlpath);
            
            // Map questions to quizzes.
            $totalquestions = 0;
            foreach ($builder->get_created_quizzes() as $quizinfo) {
                $mapped = $importer->map_questions_to_quiz(
                    $quizinfo['instanceid'],
                    $quizinfo['name'],
                    $quizinfo['sectionname']
                );
                $totalquestions += $mapped;
            }
            
            if ($totalquestions > 0) {
                $questionsummary = ' ' . get_string('questions_imported', 'local_coursebuilder', $totalquestions);
            }
        }
        
        redirect(new moodle_url('/course/view.php', ['id' => $courseid]), 'Course successfully built!' . $questionsummary, null, \core\output\notification::NOTIFY_SUCCESS);
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
        $debug_output = '';
        
        $hassyllabus = !empty($mform->get_new_filename('syllabus_file'));
        $hasprompt = !empty(trim($data->aiprompt));
        
        if ($hassyllabus || $hasprompt) {
            $final_prompt = trim($data->aiprompt ?? '');
            
            if ($hassyllabus) {
                $syllabus_content = $mform->get_file_content('syllabus_file');
                if ($syllabus_content) {
                    $final_prompt .= "\n\n--- SYLLABUS CONTENT ---\n" . trim($syllabus_content);
                }
            }
            
            // Process AI Prompt
            $json_response = $builder->call_moodle_ai($final_prompt);
            $debug_output .= "<div class='alert alert-info mt-3'><strong>Debug Info (Initial Generate Received from AI):</strong><br><pre>" . s($json_response) . "</pre></div>";
            
            // Check if response is valid JSON
            $data_array = json_decode($json_response, true);
            if ($data_array === null) {
                throw new \Exception('Invalid JSON received from AI Webhook. Raw response: ' . s($json_response));
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
        
        // Capture XML questions file if uploaded.
        $questions_xml = '';
        $hasquestionsfile = !empty($mform->get_new_filename('questionsfile'));
        if ($hasquestionsfile) {
            $questions_xml = $mform->get_file_content('questionsfile');
        }
        
        // Render Preview
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('preview_heading', 'local_coursebuilder'));
        
        if (!empty($debug_output)) {
            echo $debug_output;
        }
        
        echo \local_coursebuilder\output::render_preview($data_array);
        
        // Render Confirmation Form with Chat
        echo '<div class="mt-4 p-4 border rounded" style="background: #f8f9fa;">';
        echo '<h4>' . get_string('modify_heading', 'local_coursebuilder') . '</h4>';
        echo '<form method="post" action="index.php">';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'">';
        echo '<input type="hidden" name="action" value="modify">';
        echo '<input type="hidden" name="courseid" value="'.s($data->courseid).'">';
        echo '<input type="hidden" name="course_json" value="'.s($json_data_str).'">';
        echo '<input type="hidden" name="questions_xml" value="'.s($questions_xml).'">';
        echo '<div class="form-group">';
        echo '<textarea name="modify_prompt" class="form-control" rows="3" placeholder="' . get_string('modify_placeholder', 'local_coursebuilder') . '"></textarea>';
        echo '</div>';
        echo '<button type="submit" class="btn btn-info">' . get_string('modify_btn', 'local_coursebuilder') . '</button>';
        echo '</form>';
        echo '</div>';
        
        echo '<div class="mt-4">';
        echo '<form method="post" action="index.php" style="display:inline-block;">';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'">';
        echo '<input type="hidden" name="action" value="build">';
        echo '<input type="hidden" name="courseid" value="'.s($data->courseid).'">';
        echo '<input type="hidden" name="course_json" value="'.s($json_data_str).'">';
        echo '<input type="hidden" name="questions_xml" value="'.s($questions_xml).'">';
        echo '<button type="submit" class="btn btn-primary mr-2">' . get_string('confirm_build', 'local_coursebuilder') . '</button>';
        echo '</form>';
        echo '<a href="index.php" class="btn btn-secondary">' . get_string('cancel', 'moodle') . '</a>';
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
