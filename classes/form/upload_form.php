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

        // File picker for CSV.
        $mform->addElement('filepicker', 'csvfile', get_string('upload_csv', 'local_coursebuilder'), null, ['accepted_types' => ['.csv']]);
        $mform->addRule('csvfile', null, 'required', null, 'client');

        // Submit button.
        $this->add_action_buttons(false, get_string('submit_upload', 'local_coursebuilder'));
    }
}
