<?php
// This file is part of Moodle - http://moodle.org/

namespace local_coursebuilder;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->libdir . '/questionlib.php');

/**
 * Handles importing Moodle XML questions and mapping them to quizzes.
 */
class question_importer {
    
    protected $courseid;
    protected $course;
    
    /** @var array Map of category path => array of question bank entry IDs */
    protected $categoryquestions = [];
    
    public function __construct($courseid) {
        global $DB;
        $this->courseid = $courseid;
        $this->course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    }
    
    /**
     * Import questions from a Moodle XML file into the question bank.
     *
     * @param string $filepath Path to the XML file
     * @return array Map of category name => array of question bank entry IDs
     */
    public function import_xml($filepath) {
        global $DB, $USER;
        
        // Get or create the default question bank instance for this course.
        $qbank = \core_question\local\bank\question_bank_helper::get_default_open_instance_system_type(
            $this->course, true
        );
        $context = \context_module::instance($qbank->id);
        
        // Get the top category for this context.
        $topcategory = question_get_top_category($context->id, true);
        
        // Setup the XML format importer.
        $qformat = new \qformat_xml();
        $qformat->setFilename($filepath);
        $qformat->setRealfilename(basename($filepath));
        $qformat->setCourse($this->course);
        $qformat->setCategory($topcategory);
        $qformat->setCatfromfile(1);  // Create categories from XML.
        $qformat->setContextfromfile(0);  // Use our context.
        $qformat->setStoponerror(false);
        $qformat->set_display_progress(false);
        $qformat->setContexts([$context]);
        $qformat->setMatchgrades('nearest');
        
        // Run the import.
        ob_start();
        $result = $qformat->importprocess();
        ob_end_clean();
        
        if (!$result) {
            throw new \Exception('Failed to import questions from XML file.');
        }
        
        // Build a map of category path => question bank entry IDs.
        $this->build_category_map($context->id);
        
        return $this->categoryquestions;
    }
    
    /**
     * Build a map of category paths to their question bank entry IDs.
     *
     * @param int $contextid The context ID for the question bank
     */
    protected function build_category_map($contextid) {
        global $DB;
        
        $this->categoryquestions = [];
        
        // Get all categories in this context.
        $categories = $DB->get_records('question_categories', ['contextid' => $contextid]);
        
        foreach ($categories as $cat) {
            if ($cat->name === 'top') {
                continue;
            }
            
            // Build the full path for this category.
            $path = $this->get_category_path($cat, $categories);
            
            // Get all question bank entries in this category.
            $entries = $DB->get_records('question_bank_entries', ['questioncategoryid' => $cat->id]);
            
            if (!empty($entries)) {
                $this->categoryquestions[$path] = array_keys($entries);
            }
        }
    }
    
    /**
     * Build the full path string for a category (e.g. "Grade 4 / Language Arts / Unit 1 - Oral Communication").
     */
    protected function get_category_path($category, $allcategories) {
        $path = $category->name;
        $current = $category;
        
        while ($current->parent > 0) {
            $found = false;
            foreach ($allcategories as $cat) {
                if ($cat->id == $current->parent) {
                    if ($cat->name !== 'top') {
                        $path = $cat->name . ' / ' . $path;
                    }
                    $current = $cat;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                break;
            }
        }
        
        return $path;
    }
    
    /**
     * Map questions to a quiz by matching the quiz name against category paths.
     *
     * Matching logic:
     * - CSV quiz: "Unit 1 Quiz — Oral Communication" in section "Language Arts"
     * - XML category: "Grade 4 / Language Arts / Unit 1 - Oral Communication"
     * - Matches on: section name + unit number + topic keywords
     *
     * @param int $quizid The quiz instance ID
     * @param string $quizname The quiz activity name
     * @param string $sectionname The course section name the quiz belongs to
     * @return int Number of questions added
     */
    public function map_questions_to_quiz($quizid, $quizname, $sectionname) {
        global $DB;
        
        if (empty($this->categoryquestions)) {
            return 0;
        }
        
        // Extract matching keywords from the quiz name.
        // e.g. "Unit 1 Quiz — Oral Communication" -> unit number "1" and topic "oral communication"
        $bestmatch = null;
        $bestscore = 0;
        
        $quiznamelower = strtolower($quizname);
        $sectionlower = strtolower($sectionname);
        
        // Extract unit number from quiz name.
        $unitnum = '';
        if (preg_match('/unit\s*(\d+)/i', $quizname, $matches)) {
            $unitnum = $matches[1];
        }
        
        foreach ($this->categoryquestions as $catpath => $entryids) {
            $catpathlower = strtolower($catpath);
            $score = 0;
            
            // Check if section name appears in category path.
            if (!empty($sectionlower) && strpos($catpathlower, $sectionlower) !== false) {
                $score += 10;
            }
            
            // Check if unit number matches.
            if (!empty($unitnum) && preg_match('/unit\s*' . $unitnum . '\b/i', $catpath)) {
                $score += 20;
            }
            
            // Check for topic keyword overlap.
            // Remove common words from quiz name to get topic.
            $topicwords = preg_replace('/\b(unit|quiz|test|exam|\d+)\b/i', '', $quiznamelower);
            $topicwords = preg_replace('/[^a-z\s]/', '', $topicwords);
            $keywords = array_filter(array_unique(explode(' ', trim($topicwords))), function($w) {
                return strlen($w) > 3;
            });
            
            foreach ($keywords as $keyword) {
                if (strpos($catpathlower, $keyword) !== false) {
                    $score += 5;
                }
            }
            
            if ($score > $bestscore) {
                $bestscore = $score;
                $bestmatch = $entryids;
            }
        }
        
        if ($bestmatch === null || $bestscore < 15) {
            // No confident match found.
            return 0;
        }
        
        // Add matched questions to the quiz as slots.
        return $this->add_questions_to_quiz($quizid, $bestmatch);
    }
    
    /**
     * Add question bank entries to a quiz as slots.
     *
     * @param int $quizid The quiz instance ID
     * @param array $entryids Array of question_bank_entry IDs
     * @return int Number of questions added
     */
    protected function add_questions_to_quiz($quizid, $entryids) {
        global $DB;
        
        $count = 0;
        $slot = 1;
        
        // Get existing max slot number.
        $maxslot = $DB->get_field_sql(
            "SELECT MAX(slot) FROM {quiz_slots} WHERE quizid = ?",
            [$quizid]
        );
        if ($maxslot) {
            $slot = $maxslot + 1;
        }
        
        $sumgrades = 0;
        
        foreach ($entryids as $entryid) {
            // Get the latest version of this question.
            $version = $DB->get_record_sql(
                "SELECT qv.id, qv.questionid, q.defaultmark
                   FROM {question_versions} qv
                   JOIN {question} q ON q.id = qv.questionid
                  WHERE qv.questionbankentryid = ?
                  ORDER BY qv.version DESC
                  LIMIT 1",
                [$entryid]
            );
            
            if (!$version) {
                continue;
            }
            
            // Check if already added.
            $exists = $DB->record_exists_sql(
                "SELECT 1 FROM {quiz_slots} qs
                   JOIN {question_references} qr ON qr.itemid = qs.id
                                                 AND qr.component = 'mod_quiz'
                                                 AND qr.questionarea = 'slot'
                  WHERE qs.quizid = ? AND qr.questionbankentryid = ?",
                [$quizid, $entryid]
            );
            
            if ($exists) {
                continue;
            }
            
            // Insert quiz slot.
            $slotrecord = new \stdClass();
            $slotrecord->slot = $slot;
            $slotrecord->quizid = $quizid;
            $slotrecord->page = $slot; // One question per page.
            $slotrecord->requireprevious = 0;
            $slotrecord->maxmark = $version->defaultmark;
            $slotrecord->id = $DB->insert_record('quiz_slots', $slotrecord);
            
            // Create question_reference to link slot to question bank entry.
            $cm = $DB->get_record_sql(
                "SELECT cm.id FROM {course_modules} cm
                   JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
                  WHERE cm.instance = ? AND cm.course = ?",
                [$quizid, $this->courseid]
            );
            
            if ($cm) {
                $ref = new \stdClass();
                $ref->usingcontextid = \context_module::instance($cm->id)->id;
                $ref->component = 'mod_quiz';
                $ref->questionarea = 'slot';
                $ref->itemid = $slotrecord->id;
                $ref->questionbankentryid = $entryid;
                $ref->version = null; // Always use latest.
                $DB->insert_record('question_references', $ref);
            }
            
            $sumgrades += $version->defaultmark;
            $slot++;
            $count++;
        }
        
        // Update quiz sumgrades.
        if ($count > 0) {
            $quiz = $DB->get_record('quiz', ['id' => $quizid]);
            $quiz->sumgrades = $sumgrades;
            $DB->update_record('quiz', $quiz);
            
            // Update grade item.
            require_once(__DIR__ . '/../../../mod/quiz/lib.php');
            $quiz->cmidnumber = '';
            quiz_update_grades($quiz);
        }
        
        return $count;
    }
}
