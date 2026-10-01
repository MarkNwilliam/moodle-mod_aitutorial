<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Library Functions
 *
 * @package    mod_aitutorial
 * @copyright  2026 Nlugwa Mark William
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/aitutorial/locallib.php');
require_once($CFG->dirroot . '/mod/aitutorial/lib/cron.php');
require_once($CFG->dirroot . '/mod/aitutorial/gradebook.php');

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

    aitutorial_grade_item_delete($aitutorial);

    $DB->delete_records('aitutorial', ['id' => $id]);

    return true;
}

/**
 * Return the URL for viewing a particular aitutorial.
 */
function aitutorial_view_url($id, stdClass $aitutorial) {
    return new moodle_url('/mod/aitutorial/view.php', ['id' => $id]);
}

/**
 * Serve plugin files (generated_video, generated_poster).
 *
 * Standard Moodle pluginfile callback handled by core /pluginfile.php.
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param context_module $context context object
 * @param string $filearea file area
 * @param array $args remaining arguments (itemid, filepath, filename)
 * @param bool $forcedownload force download flag
 * @param array $options additional options
 * @return bool false if file not found
 */
function mod_aitutorial_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if (!in_array($filearea, ['generated_video', 'generated_poster'])) {
        return false;
    }

    require_login($course, false, $cm);
    require_capability('mod/aitutorial:view', $context);

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? implode('/', $args) . '/' : '/';

    // Verify the job belongs to the current user unless they can manage the module.
    $job = $DB->get_record('aitutorial_jobs', ['id' => (int)$itemid]);
    if (!$job) {
        return false;
    }
    if ($job->userid != $USER->id) {
        require_capability('mod/aitutorial:manage', $context);
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_aitutorial', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    $options['forcedownload'] = $forcedownload ? true : ($filearea === 'generated_poster');
    send_stored_file($file, 86400, 0, $forcedownload, $options);

    return true;
}
