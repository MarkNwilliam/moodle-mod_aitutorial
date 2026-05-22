<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - File Serving
 *
 * @package    mod_aitutorial
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$relativepath = get_file_argument();
$filepath = explode('/', ltrim($relativepath, '/'), 5);

if (count($filepath) < 5) {
    send_file_not_found();
}

$contextid = (int)$filepath[0];
$component = $filepath[1];
$filearea = $filepath[2];
$itemid = (int)$filepath[3];
$filename = $filepath[4];

$context = context::instance_by_id($contextid, MUST_EXIST);
require_login();

// Check capability.
require_capability('mod/aitutorial:view', $context);

// Verify file belongs to user's job.
if ($filearea === 'generated_video' || $filearea === 'generated_poster') {
    global $DB;
    $job = $DB->get_record('aitutorial_jobs', ['id' => $itemid], '*', MUST_EXIST);
    
    // Allow if user owns the job or is a teacher/manager.
    if ($job->userid != $USER->id) {
        require_capability('mod/aitutorial:manage', $context);
    }
}

$fs = get_file_storage();
$file = $fs->get_file($contextid, 'mod_aitutorial', $filearea, $itemid, '/', $filename);

if (!$file) {
    send_file_not_found();
}

// Force download for video files, inline for posters.
$options = [];
if ($filearea === 'generated_video') {
    $options = ['forceDownload' => false];  // Stream in browser
}

send_stored_file($file, 86400, 0, false, $options);
