<?php
// This file is part of Moodle - http://moodle.org/

namespace local_coursebuilder\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class upload_form extends \moodleform {

    public function definition() {
        global $DB;

        $mform = $this->_form;

        // Header.
        $mform->addElement('header', 'uploadheader', get_string('upload_header', 'local_coursebuilder'));

        // Course selector.
        // Get all courses (excluding site course id = 1).
        $courses = $DB->get_records_select_menu('course', 'id > 1', null, 'fullname ASC', 'id, fullname');
        $mform->addElement('select', 'courseid', get_string('select_course', 'local_coursebuilder'), $courses);
        $mform->addRule('courseid', null, 'required', null, 'client');

        // File picker for CSV/JSON.
        $mform->addElement('filepicker', 'datafile', get_string('upload_file', 'local_coursebuilder'), null, ['accepted_types' => ['.csv', '.json']]);

        // AI Prompt Textarea.
        $mform->addElement('textarea', 'aiprompt', get_string('aiprompt', 'local_coursebuilder'), 'wrap="virtual" rows="5" cols="50"');
        $mform->addHelpButton('aiprompt', 'aiprompt', 'local_coursebuilder');

        // Syllabus File for AI.
        $mform->addElement('filepicker', 'syllabus_file', get_string('syllabus_file', 'local_coursebuilder'), null, ['accepted_types' => ['.txt', '.md', '.csv']]);
        $mform->addHelpButton('syllabus_file', 'syllabus_file', 'local_coursebuilder');

        // Submit button.
        $this->add_action_buttons(false, get_string('submit_upload', 'local_coursebuilder'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        $hasfile = !empty($data['datafile']);
        $hasprompt = !empty(trim($data['aiprompt']));
        $hassyllabus = !empty($data['syllabus_file']);
        
        if (!$hasfile && !$hasprompt && !$hassyllabus) {
            $errors['datafile'] = get_string('error_missing_input', 'local_coursebuilder');
            $errors['aiprompt'] = get_string('error_missing_input', 'local_coursebuilder');
        }
        
        return $errors;
    }
}
