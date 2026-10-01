<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Course Index Page
 *
 * @package    mod_aitutorial
 * @copyright  2026 Nlugwa Mark William
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/aitutorial/lib.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_login($course);

$context = context_course::instance($course->id);
require_capability('mod/aitutorial:addinstance', $context);

$PAGE->set_url('/mod/aitutorial/index.php', ['id' => $course->id]);
$PAGE->set_title(get_string('modulenameplural', 'mod_aitutorial'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_aitutorial'));

// Get all aitutorial instances in this course.
$aitutorials = $DB->get_records('aitutorial', ['course' => $course->id], 'name ASC');

if (!empty($aitutorials)) {
    $table = new html_table();
    $table->head = [
        get_string('name'),
        get_string('generatedon', 'mod_aitutorial'),
        get_string('status_completed', 'mod_aitutorial'),
    ];
    
    foreach ($aitutorials as $aitutorial) {
        $cm = get_coursemodule_from_instance('aitutorial', $aitutorial->id, $course->id, false, MUST_EXIST);
        $url = new moodle_url('/mod/aitutorial/view.php', ['id' => $cm->id]);
        
        // Count completed jobs.
        $completed = $DB->count_records('aitutorial_jobs', [
            'aitutorialid' => $aitutorial->id,
            'status' => 'completed'
        ]);
        
        $table->data[] = [
            html_writer::link($url, format_string($aitutorial->name)),
            userdate($aitutorial->timecreated),
            $completed,
        ];
    }
    
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification('No AI Tutorial Generators have been added to this course yet.');
}

echo $OUTPUT->footer();
