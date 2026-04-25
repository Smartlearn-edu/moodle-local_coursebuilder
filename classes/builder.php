<?php
// This file is part of Moodle - http://moodle.org/

namespace local_coursebuilder;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

class builder {
    protected $courseid;
    protected $course;
    
    public function __construct($courseid) {
        global $DB;
        $this->courseid = $courseid;
        $this->course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    }
    
    public function parse_csv($filepath) {
        $file = fopen($filepath, 'r');
        if (!$file) {
            throw new \moodle_exception('cannotopenfile');
        }
        
        $bom = fread($file, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($file);
        }
        
        $headers = fgetcsv($file);
        if (!$headers) {
            throw new \Exception('Invalid or empty CSV file.');
        }
        
        $headers = array_map('strtolower', $headers);
        $headers = array_map('trim', $headers);
        
        $result = [];
        while (($data = fgetcsv($file)) !== false) {
            if (count($headers) !== count($data)) {
                continue;
            }
            $row = array_combine($headers, $data);
            if ($row) {
                $result[] = $row;
            }
        }
        fclose($file);
        return $result;
    }
    
    public function process_csv($filepath) {
        $data = $this->parse_csv($filepath);
        $this->build_from_array($data);
    }
    
    public function parse_json($filepath) {
        $content = file_get_contents($filepath);
        if ($content === false) {
            throw new \moodle_exception('cannotopenfile');
        }
        
        $data = json_decode($content, true);
        if (!is_array($data)) {
            throw new \Exception('Invalid JSON format.');
        }
        return $data;
    }
    
    public function process_json($filepath) {
        $data = $this->parse_json($filepath);
        $this->build_from_array($data);
    }
    
    public function build_from_array($data) {
        $currentsection = 0;
        
        foreach ($data as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            
            $type = trim(strtolower($row['type'] ?? ''));
            $name = trim($row['name'] ?? '');
            
            if (empty($type) || empty($name)) {
                continue;
            }
            
            if ($type === 'section') {
                $sectionnum = intval($row['section'] ?? $currentsection + 1);
                $this->create_or_update_section($sectionnum, $name, $row['intro'] ?? '');
                $currentsection = $sectionnum;
            } else {
                $modsection = !empty($row['section']) ? intval($row['section']) : $currentsection;
                $this->create_module($type, $modsection, $row);
            }
        }
        
        \rebuild_course_cache($this->courseid);
    }
    
    public function call_moodle_ai($prompt) {
        global $USER;
        
        // Define the instruction to force JSON output
        $system_prompt = "You are an assistant that generates course structures for a Moodle plugin. " .
            "You MUST return ONLY valid JSON and no other text. Do not use markdown blocks like ```json. " .
            "The JSON must be an array of objects representing sections and activities. " .
            "Example valid format: " .
            '[{"type":"section","name":"Week 1","intro":"Introduction"},{"type":"page","name":"Lesson 1","intro":"Content"}] ' .
            "User Prompt: " . $prompt;
            
        $systemcontext = \context_system::instance();

        // Create the core AI action
        $action = new \core_ai\aiactions\generate_text(
            $systemcontext->id,
            $USER->id,
            $system_prompt
        );

        // Get manager using Moodle 4.5 dependency injection
        $manager = \core\di::get(\core_ai\manager::class);
        $result = $manager->process_action($action);

        if (!$result->get_success()) {
            throw new \moodle_exception('error_ai_provider', 'local_coursebuilder', '', $result->get_error_message());
        }

        $data = $result->get_response_data();
        $generated_text = $data['generatedcontent'] ?? '';

        // Clean up if the AI returned markdown code blocks (e.g. ```json ... ```)
        $generated_text = preg_replace('/```json\s*/', '', $generated_text);
        $generated_text = preg_replace('/```\s*/', '', $generated_text);

        return trim($generated_text);
    }
    
    protected function create_or_update_section($sectionnum, $name, $summary) {
        global $DB;
        
        // Ensure section exists.
        \course_create_sections_if_missing($this->course, [$sectionnum]);
        
        // Update section name and summary.
        $section = $DB->get_record('course_sections', ['course' => $this->courseid, 'section' => $sectionnum]);
        if ($section) {
            $section->name = $name;
            $section->summary = $summary;
            $section->summaryformat = FORMAT_HTML;
            $section->timemodified = time();
            $DB->update_record('course_sections', $section);
        }
    }
    
    protected function create_module($type, $sectionnum, $row) {
        global $DB;
        
        $modname = $this->map_type_to_modname($type);
        if (!$modname) {
            return; // Unsupported type
        }
        
        $module = $DB->get_record('modules', ['name' => $modname]);
        if (!$module) {
            return; // Module not installed
        }
        
        \course_create_sections_if_missing($this->course, [$sectionnum]);
        
        $instance = new \stdClass();
        $instance->course = $this->courseid;
        $instance->name = $row['name'];
        $instance->intro = $row['intro'] ?? '';
        $instance->introformat = FORMAT_HTML;
        $instance->timecreated = time();
        $instance->timemodified = time();
        
        // Apply specific defaults for module types
        if ($modname === 'page') {
            $instance->content = $row['intro'] ?? '';
            $instance->contentformat = FORMAT_HTML;
            $instance->display = 5; // RESOURCELIB_DISPLAY_OPEN
        } else if ($modname === 'assign') {
            $instance->alwaysshowdescription = 1;
            $instance->submissiondrafts = 0;
            $instance->requiresubmissionstatement = 0;
            $instance->sendnotifications = 0;
            $instance->sendlatenotifications = 0;
            $instance->duedate = 0;
            $instance->cutoffdate = 0;
            $instance->allowsubmissionsfromdate = 0;
            $instance->grade = !empty($row['grade']) ? floatval($row['grade']) : 100;
        } else if ($modname === 'quiz') {
            $instance->timeopen = 0;
            $instance->timeclose = 0;
            $instance->grade = !empty($row['grade']) ? floatval($row['grade']) : 10;
            $instance->sumgrades = 0;
        }
        
        $instanceid = $DB->insert_record($modname, $instance);
        
        $cm = new \stdClass();
        $cm->course = $this->courseid;
        $cm->module = $module->id;
        $cm->instance = $instanceid;
        $cm->section = 0;
        $cm->idnumber = '';
        $cm->added = time();
        $cm->visible = 1;
        
        $cm->id = $DB->insert_record('course_modules', $cm);
        
        // Add module to section and link it.
        $sectionid = \course_add_cm_to_section($this->courseid, $cm->id, $sectionnum);
        $DB->set_field('course_modules', 'section', $sectionid, ['id' => $cm->id]);
        
        // Create context.
        \context_module::instance($cm->id);
    }
    
    protected function map_type_to_modname($type) {
        $map = [
            'label' => 'label',
            'page' => 'page',
            'quiz' => 'quiz',
            'assignment' => 'assign',
            'assign' => 'assign',
            'forum' => 'forum'
        ];
        return $map[$type] ?? null;
    }
}
