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
        
        $headers = fgetcsv($file, 0, ',', '"', '\\');
        if (!$headers) {
            throw new \Exception('Invalid or empty CSV file.');
        }
        
        $headers = array_map('strtolower', $headers);
        $headers = array_map('trim', $headers);
        
        $result = [];
        while (($data = fgetcsv($file, 0, ',', '"', '\\')) !== false) {
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
        // Map section names to numbers for CSVs that use text names instead of numbers.
        $sectionnamemap = [];
        
        foreach ($data as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            
            $type = trim(strtolower($row['type'] ?? ''));
            $name = trim($row['name'] ?? '');
            
            if (empty($type) || empty($name)) {
                continue;
            }
            
            if ($type === 'section') {
                // Support both numeric and text-based section identifiers.
                $rawsection = trim($row['section'] ?? $row['name'] ?? '');
                if (is_numeric($rawsection)) {
                    $sectionnum = intval($rawsection);
                } else {
                    // Text-based section name — auto-increment.
                    $sectionnum = $currentsection + 1;
                    $sectionnamemap[strtolower($rawsection)] = $sectionnum;
                }
                // Also map the section name itself for activity lookups.
                $sectionnamemap[strtolower($name)] = $sectionnum;
                $this->create_or_update_section($sectionnum, $name, $row['intro'] ?? '');
                $currentsection = $sectionnum;
            } else {
                // Resolve section: numeric, text name lookup, or current section.
                $rawsection = trim($row['section'] ?? '');
                if (!empty($rawsection) && is_numeric($rawsection)) {
                    $modsection = intval($rawsection);
                } else if (!empty($rawsection) && isset($sectionnamemap[strtolower($rawsection)])) {
                    $modsection = $sectionnamemap[strtolower($rawsection)];
                } else {
                    $modsection = $currentsection;
                }
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
            "IMPORTANT Constraints: Do NOT repeat the same section or chapter multiple times. Each section should represent a unique step in the course progression. " .
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
    
    public function modify_moodle_ai($current_json, $prompt) {
        global $USER;
        
        $system_prompt = "You are an assistant that modifies existing course structures for a Moodle plugin. " .
            "You MUST return ONLY valid JSON and no other text. Do not use markdown blocks like ```json. " .
            "The JSON must be an array of objects representing sections and activities. " .
            "IMPORTANT Constraints: Do NOT repeat the same section multiple times. Ensure each section is distinct and follows a logical progression. Do not return empty sections unless explicitly asked. " .
            "Current Course Structure: " . $current_json . " " .
            "User Modification Request: " . $prompt;
            
        $systemcontext = \context_system::instance();

        $action = new \core_ai\aiactions\generate_text(
            $systemcontext->id,
            $USER->id,
            $system_prompt
        );

        $manager = \core\di::get(\core_ai\manager::class);
        $result = $manager->process_action($action);

        if (!$result->get_success()) {
            throw new \moodle_exception('error_ai_provider', 'local_coursebuilder', '', $result->get_error_message());
        }

        $data = $result->get_response_data();
        $generated_text = $data['generatedcontent'] ?? '';

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
        global $DB, $CFG;
        
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
            $instance->timelimit = 0;
            $instance->overduehandling = 'autoabandon';
            $instance->graceperiod = 0;
            $instance->preferredbehaviour = 'deferredfeedback';
            $instance->canredoquestions = 0;
            $instance->attempts = 0;
            $instance->attemptonlast = 0;
            $instance->grademethod = 1; // QUIZ_GRADEHIGHEST
            $instance->decimalpoints = 2;
            $instance->questiondecimalpoints = -1;
            $instance->questionsperpage = 1;
            $instance->navmethod = 'free';
            $instance->shuffleanswers = 1;
            $instance->grade = !empty($row['grade']) ? floatval($row['grade']) : 10;
            $instance->sumgrades = 0;
            $instance->password = '';
            $instance->subnet = '';
            $instance->browsersecurity = '-';
            $instance->delay1 = 0;
            $instance->delay2 = 0;
            $instance->showuserpicture = 0;
            $instance->showblocks = 0;
            $instance->completionattemptsexhausted = 0;
            $instance->completionminattempts = 0;
            $instance->allowofflineattempts = 0;
            // Review options - allow review after quiz is closed.
            $instance->reviewattempt = 69904; // During + Immediately + Open + Closed
            $instance->reviewcorrectness = 4368; // Immediately + Open + Closed
            $instance->reviewmaxmarks = 69904;
            $instance->reviewmarks = 4368;
            $instance->reviewspecificfeedback = 4368;
            $instance->reviewgeneralfeedback = 4368;
            $instance->reviewrightanswer = 4368;
            $instance->reviewoverallfeedback = 4368;
        } else if ($modname === 'forum') {
            $instance->type = 'general';
            $instance->assessed = 0;
            $instance->scale = 0;
            $instance->grade_forum = 0;
            $instance->grade_forum_notify = 0;
            $instance->maxbytes = 0;
            $instance->maxattachments = 1;
            $instance->forcesubscribe = 0;
            $instance->trackingtype = 1;
            $instance->rsstype = 0;
            $instance->rssarticles = 0;
            $instance->warnafter = 0;
            $instance->blockafter = 0;
            $instance->blockperiod = 0;
            $instance->completiondiscussions = 0;
            $instance->completionreplies = 0;
            $instance->completionposts = 0;
            $instance->displaywordcount = 0;
            $instance->lockdiscussionafter = 0;
            $instance->duedate = 0;
            $instance->cutoffdate = 0;
            $instance->assesstimestart = 0;
            $instance->assesstimefinish = 0;
        }
        
        $instanceid = $DB->insert_record($modname, $instance);
        
        $cm = new \stdClass();
        $cm->course = $this->courseid;
        $cm->module = $module->id;
        $cm->instance = $instanceid;
        $cm->section = 0;
        $cm->idnumber = '';
        $cm->added = time();
        $cm->visible = isset($row['visible']) && $row['visible'] !== '' ? intval($row['visible']) : 1;
        
        // Activity completion settings from CSV.
        if (isset($row['completion']) && $row['completion'] !== '') {
            $cm->completion = intval($row['completion']);
        }
        if (isset($row['completionview']) && $row['completionview'] !== '') {
            $cm->completionview = intval($row['completionview']);
        }
        
        $cm->id = $DB->insert_record('course_modules', $cm);
        
        // Add module to section and link it.
        $sectionid = \course_add_cm_to_section($this->courseid, $cm->id, $sectionnum);
        $DB->set_field('course_modules', 'section', $sectionid, ['id' => $cm->id]);
        
        // Create context.
        \context_module::instance($cm->id);
        
        // Quiz-specific post-creation steps.
        if ($modname === 'quiz') {
            // Create the first quiz section (required for adding questions).
            $DB->insert_record('quiz_sections', [
                'quizid' => $instanceid,
                'firstslot' => 1,
                'heading' => '',
                'shufflequestions' => 0,
            ]);
            
            // Create default overall feedback entry.
            $DB->insert_record('quiz_feedback', [
                'quizid' => $instanceid,
                'feedbacktext' => '',
                'feedbacktextformat' => FORMAT_HTML,
                'mingrade' => 0,
                'maxgrade' => $instance->grade + 1,
            ]);
            
            // Create grade item in gradebook.
            $quizobj = new \stdClass();
            $quizobj->id = $instanceid;
            $quizobj->course = $this->courseid;
            $quizobj->name = $instance->name;
            $quizobj->grade = $instance->grade;
            $quizobj->sumgrades = $instance->sumgrades;
            $quizobj->cmidnumber = '';
            $quizobj->decimalpoints = $instance->decimalpoints;
            $quizobj->questiondecimalpoints = $instance->questiondecimalpoints;
            // Minimal review options for grade_item_update.
            $quizobj->reviewattempt = $instance->reviewattempt;
            $quizobj->reviewcorrectness = $instance->reviewcorrectness;
            $quizobj->reviewmaxmarks = $instance->reviewmaxmarks;
            $quizobj->reviewmarks = $instance->reviewmarks;
            $quizobj->reviewspecificfeedback = $instance->reviewspecificfeedback;
            $quizobj->reviewgeneralfeedback = $instance->reviewgeneralfeedback;
            $quizobj->reviewrightanswer = $instance->reviewrightanswer;
            $quizobj->reviewoverallfeedback = $instance->reviewoverallfeedback;
            $quizobj->timeclose = 0;
            $quizobj->visible = 1;
            require_once($CFG->dirroot . '/mod/quiz/lib.php');
            quiz_grade_item_update($quizobj);
        }
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
