<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Library Functions
 *
 * @package    mod_aitutorial
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/aitutorial/locallib.php');

/**
 * List of features supported in aitutorial module.
 */
function aitutorial_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Given an object containing all the necessary data, create a new aitutorial instance.
 */
function aitutorial_add_instance(stdClass $aitutorial, mod_aitutorial_mod_form $mform) {
    global $DB;

    $aitutorial->timecreated = time();
    $aitutorial->timemodified = time();

    $id = $DB->insert_record('aitutorial', $aitutorial);
    $aitutorial->id = $id;

    aitutorial_grade_item_update($aitutorial);

    return $id;
}

/**
 * Update an existing aitutorial instance.
 */
function aitutorial_update_instance(stdClass $aitutorial, mod_aitutorial_mod_form $mform) {
    global $DB;

    $aitutorial->timemodified = time();
    $aitutorial->id = $aitutorial->instance;

    $result = $DB->update_record('aitutorial', $aitutorial);

    aitutorial_grade_item_update($aitutorial);

    return $result;
}

/**
 * Delete an aitutorial instance.
 */
function aitutorial_delete_instance($id) {
    global $DB;

    if (!$aitutorial = $DB->get_record('aitutorial', ['id' => $id])) {
        return false;
    }

    // Delete associated jobs and files.
    $DB->delete_records('aitutorial_jobs', ['aitutorialid' => $id]);

    $DB->delete_records('aitutorial', ['id' => $id]);

    return true;
}

/**
 * Create or update grade item for given aitutorial.
 */
function aitutorial_grade_item_update(stdClass $aitutorial, $reset=false) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = ['itemname' => $aitutorial->name];

    if (!$reset) {
        grade_update('mod/aitutorial', $aitutorial->courseid, 'mod', 'aitutorial',
                     $aitutorial->id, 0, null, $params);
    }
}

/**
 * Return the URL for viewing a particular aitutorial.
 */
function aitutorial_view_url($id, stdClass $aitutorial) {
    return new moodle_url('/mod/aitutorial/view.php', ['id' => $id]);
}
