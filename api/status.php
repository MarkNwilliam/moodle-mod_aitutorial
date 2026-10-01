<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Job Status API Endpoint
 *
 * @package    mod_aitutorial
 * @copyright  2026 Nlugwa Mark William
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/aitutorial/locallib.php');

require_sesskey();

$jobid = required_param('jobid', PARAM_INT);

require_login();

global $DB;
$job = $DB->get_record('aitutorial_jobs', ['id' => $jobid], '*', MUST_EXIST);

// Check permission.
$cm = get_coursemodule_from_instance('aitutorial', $job->aitutorialid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
require_capability('mod/aitutorial:view', $context);

// Return job status as JSON.
header('Content-Type: application/json');
echo json_encode([
    'jobid' => $job->id,
    'status' => $job->status,
    'progress' => (int)$job->progress,
    'error' => $job->error_message ?: null,
    'timecreated' => $job->timecreated,
    'timemodified' => $job->timemodified,
    'timecompleted' => $job->timecompleted,
]);
exit;
