<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Local Library Functions
 *
 * @package    mod_aitutorial
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Trigger generation job via backend API.
 *
 * @param int $jobid Job record ID
 * @param stored_file $pdffile Uploaded PDF file
 * @param string $generationmode Generation mode (video, poster, both)
 * @param context $context Module context
 * @return bool
 */
function aitutorial_trigger_generation($jobid, $pdffile, $generationmode, $context, $firebase_token = '') {
    global $DB, $CFG;

    $apibaseurl = get_config('mod_aitutorial', 'api_url');
    if (empty($apibaseurl)) {
        $apibaseurl = 'https://aitutorial-api-1776284710.eastus.cloudapp.azure.com';
    }

    // ... (rest of temp file logic)
    $pdffile->copy_content_to($tempfile);

    // Prepare API request.
    $apiurl = $apibaseurl . '/api/generate';
    
    $curl = new curl();
    $curl->setopt([
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_TIMEOUT' => 60,
    ]);

    if (!empty($firebase_token)) {
        $curl->setHeader('Authorization: Bearer ' . $firebase_token);
    }

    // Create multipart form data.
    $postdata = [
        'job_id' => $jobid,
        'generation_mode' => $generationmode,
        'voice' => get_config('mod_aitutorial', 'tts_voice') ?: 'en-US-GuyNeural',
        'pdf_file' => new CURLFile($tempfile, 'application/pdf', $pdffile->get_filename()),
    ];

    // Make API call.
    $response = $curl->post($apiurl, $postdata);
    $httpcode = $curl->info['http_code'];

    if ($httpcode !== 200) {
        // Mark job as failed.
        $DB->set_field('aitutorial_jobs', 'status', 'failed', ['id' => $jobid]);
        $DB->set_field('aitutorial_jobs', 'error_message', 'API call failed: HTTP ' . $httpcode, ['id' => $jobid]);
        
        // Notify user.
        aitutorial_send_notification($jobid, 'failed', 'API call failed: HTTP ' . $httpcode);
        
        return false;
    }

    $result = json_decode($response, true);
    if (!$result || !isset($result['status'])) {
        $DB->set_field('aitutorial_jobs', 'status', 'failed', ['id' => $jobid]);
        $DB->set_field('aitutorial_jobs', 'error_message', 'Invalid API response', ['id' => $jobid]);
        return false;
    }

    // Update job status to processing.
    $DB->set_field('aitutorial_jobs', 'status', 'processing', ['id' => $jobid]);

    return true;
}

/**
 * Send notification to user about job status.
 *
 * @param int $jobid Job record ID
 * @param string $status Job status (complete, failed)
 * @param string $message Additional message
 */
function aitutorial_send_notification($jobid, $status, $message = '') {
    global $DB, $USER;

    $job = $DB->get_record('aitutorial_jobs', ['id' => $jobid]);
    if (!$job) {
        return;
    }

    $user = $DB->get_record('user', ['id' => $job->userid]);
    if (!$user) {
        return;
    }

    $eventtype = ($status === 'complete') ? 'generationcomplete' : 'generationfailed';
    $messagebody = ($status === 'complete') 
        ? get_string('notification_body_complete', 'mod_aitutorial', 'tutorial')
        : get_string('notification_body_failed', 'mod_aitutorial', $message);

    // Create Moodle notification.
    \core\notification::add(
        $user,
        get_string($eventtype, 'mod_aitutorial'),
        $messagebody,
        ($status === 'complete') ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_ERROR
    );
}

/**
 * Update job progress.
 *
 * @param int $jobid Job record ID
 * @param int $progress Progress percentage (0-100)
 * @param string $status Job status
 */
function aitutorial_update_job_progress($jobid, $progress, $status = 'processing') {
    global $DB;

    $update = new stdClass();
    $update->id = $jobid;
    $update->progress = $progress;
    $update->status = $status;
    $update->timemodified = time();

    $DB->update_record('aitutorial_jobs', $update);
}

/**
 * Complete a generation job and store output files.
 *
 * @param int $jobid Job record ID
 * @param string|null $videopath Path to generated video file
 * @param string|null $posterpath Path to generated poster file
 * @param context $context Module context
 * @return bool
 */
function aitutorial_complete_job($jobid, $videopath = null, $posterpath = null, $context = null) {
    global $DB, $USER;

    $job = $DB->get_record('aitutorial_jobs', ['id' => $jobid]);
    if (!$job) {
        return false;
    }

    $fs = get_file_storage();
    $filerecord = null;

    // Store video file.
    if ($videopath && file_exists($videopath)) {
        $filerecord = [
            'contextid' => $context->id,
            'component' => 'mod_aitutorial',
            'filearea' => 'generated_video',
            'itemid' => $jobid,
            'filepath' => '/',
            'filename' => 'tutorial_video_' . $jobid . '.mp4',
            'userid' => $job->userid,
        ];
        
        $videofile = $fs->create_file_from_pathname($filerecord, $videopath);
        $job->video_fileid = $videofile->get_id();
        
        // Clean up temp file.
        @unlink($videopath);
    }

    // Store poster file.
    if ($posterpath && file_exists($posterpath)) {
        $filerecord = [
            'contextid' => $context->id,
            'component' => 'mod_aitutorial',
            'filearea' => 'generated_poster',
            'itemid' => $jobid,
            'filepath' => '/',
            'filename' => 'tutorial_poster_' . $jobid . '.pdf',
            'userid' => $job->userid,
        ];
        
        $posterfile = $fs->create_file_from_pathname($filerecord, $posterpath);
        $job->poster_fileid = $posterfile->get_id();
        
        // Clean up temp file.
        @unlink($posterpath);
    }

    // Update job record.
    $job->status = 'completed';
    $job->progress = 100;
    $job->timecompleted = time();
    $job->timemodified = time();

    $DB->update_record('aitutorial_jobs', $job);

    // Send notification.
    aitutorial_send_notification($jobid, 'complete');

    return true;
}
