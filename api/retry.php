<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Job Retry API Endpoint
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
require_capability('mod/aitutorial:submit', $context);

// Only the job owner (or a manager) may retry it.
if ($job->userid != $USER->id) {
    require_capability('mod/aitutorial:manage', $context);
}

// Only allow retry for failed jobs.
if ($job->status !== 'failed') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Only failed jobs can be retried']);
    exit;
}

// Reset job status.
$job->status = 'pending';
$job->progress = 0;
$job->error_message = null;
$job->timemodified = time();

$DB->update_record('aitutorial_jobs', $job);

// Trigger generation via backend API.
$fs = get_file_storage();
$pdffile = $fs->get_file_by_id($job->sourcefileid);
if (!$pdffile) {
    $job->status = 'failed';
    $job->error_message = 'Source file missing; cannot retry';
    $job->timemodified = time();
    $DB->update_record('aitutorial_jobs', $job);

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Source file missing; cannot retry']);
    exit;
}

$success = aitutorial_trigger_generation($job->id, $pdffile, $job->job_type, $context);

header('Content-Type: application/json');
if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to trigger backend generation']);
}
exit;
