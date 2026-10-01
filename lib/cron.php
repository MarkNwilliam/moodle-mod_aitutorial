<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Cron Handler
 *
 * @package    mod_aitutorial
 * @copyright  2026 Nlugwa Mark William
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/aitutorial/locallib.php');

/**
 * Cron function to check job status and update progress.
 */
function aitutorial_cron() {
    global $DB, $CFG;

    mtrace("Running AI Tutorial Generator cron...");

    // Get all processing jobs.
    $processingjobs = $DB->get_records('aitutorial_jobs', ['status' => 'processing'], 'timecreated ASC');

    if (empty($processingjobs)) {
        mtrace("No processing jobs found.");
        return;
    }

    $apibaseurl = get_config('mod_aitutorial', 'api_url');
    if (empty($apibaseurl)) {
        $apibaseurl = 'https://cuppai.top';
    }

    $curl = new curl();
    $curl->setopt([
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_CONNECTTIMEOUT' => 20,
        'CURLOPT_LOW_SPEED_LIMIT' => 5000,
        'CURLOPT_LOW_SPEED_TIME' => 90,
        'CURLOPT_TIMEOUT' => 1800,
    ]);

    foreach ($processingjobs as $job) {
        // Check if job has timed out (2 hours max).
        $timeout = 2 * 60 * 60;  // 2 hours
        if (time() - $job->timemodified > $timeout) {
            mtrace("Job {$job->id} timed out.");
            $DB->set_field('aitutorial_jobs', 'status', 'failed', ['id' => $job->id]);
            $DB->set_field('aitutorial_jobs', 'error_message', 'Job timed out after 2 hours', ['id' => $job->id]);
            continue;
        }

        // The backend mirrors Moodle's job id, but prefer the stored backend id
        // if it differs.
        $backendid = !empty($job->backend_job_id) ? $job->backend_job_id : $job->id;

        // Poll backend API for status. The no-auth status endpoint allows the
        // Moodle server (which has no Firebase session) to read job progress.
        $apiurl = $apibaseurl . '/api/status-no-auth/' . $backendid;
        $response = $curl->get($apiurl);
        $httpcode = !empty($curl->info['http_code']) ? $curl->info['http_code'] : 0;

        if ($response === false || $httpcode !== 200) {
            mtrace("Failed to get status for job {$job->id} (HTTP {$httpcode})");
            continue;
        }

        $result = json_decode($response, true);
        if (!$result || empty($result['status'])) {
            mtrace("Invalid status response for job {$job->id}");
            continue;
        }

        // Cast the status to a plain string (never interpolate a raw object).
        $status = aitutorial_str($result['status']);
        if ($status === '') {
            mtrace("Invalid status response for job {$job->id}");
            continue;
        }

        // Job is still running: just sync progress.
        $running = ['pending', 'processing', 'ai_thinking', 'rendering'];
        if (in_array($status, $running)) {
            $update = new stdClass();
            $update->id = $job->id;
            $update->timemodified = time();
            if (isset($result['progress'])) {
                $update->progress = (int)$result['progress'];
            }
            $DB->update_record('aitutorial_jobs', $update);
            mtrace("Updated job {$job->id}: {$status} (" . (int)($result['progress'] ?? 0) . "%)");
            continue;
        }

        // Job failed on the backend.
        if ($status === 'failed') {
            $errormsg = aitutorial_str($result['error'] ?? null, 'Generation failed on backend');
            $DB->set_field('aitutorial_jobs', 'status', 'failed', ['id' => $job->id]);
            $DB->set_field('aitutorial_jobs', 'error_message', $errormsg, ['id' => $job->id]);
            mtrace("Job {$job->id} failed: {$errormsg}");
            continue;
        }

        // Job completed: download the output files into the Moodle file system.
        if ($status === 'completed') {
            // $job->aitutorialid is the activity INSTANCE id, not the course module id.
            $cm = get_coursemodule_from_instance('aitutorial', $job->aitutorialid);
            if (empty($cm)) {
                mtrace("Job {$job->id}: course module not found for instance {$job->aitutorialid}");
                continue;
            }
            $context = context_module::instance($cm->id);

            $videopath = null;
            $video_url = $apibaseurl . '/api/download/' . $backendid . '/video';
            $videodata = $curl->get($video_url);
            $httpcode = !empty($curl->info['http_code']) ? $curl->info['http_code'] : 0;
            $attempts = 1;
            while (($videodata === false || $httpcode !== 200) && $attempts < 4) {
                mtrace("Job {$job->id}: video download attempt {$attempts} failed (HTTP {$httpcode}), retrying...");
                $attempts++;
                $videodata = $curl->get($video_url);
                $httpcode = !empty($curl->info['http_code']) ? $curl->info['http_code'] : 0;
            }
            if ($videodata !== false && $httpcode === 200) {
                $videotempfile = $CFG->tempdir . '/aitutorial_video_' . $job->id . '.mp4';
                file_put_contents($videotempfile, $videodata);
                $videopath = $videotempfile;
            } else {
                mtrace("Job {$job->id}: video download failed from {$video_url}");
            }

            $posterpath = null;
            if ($job->job_type === 'poster' || $job->job_type === 'both') {
                $poster_url = $apibaseurl . '/api/download/' . $backendid . '/poster';
                $posterdata = $curl->get($poster_url);
                if ($posterdata !== false && !empty($curl->info['http_code']) && $curl->info['http_code'] === 200) {
                    $postertempfile = $CFG->tempdir . '/aitutorial_poster_' . $job->id . '.pdf';
                    file_put_contents($postertempfile, $posterdata);
                    $posterpath = $postertempfile;
                } else {
                    mtrace("Job {$job->id}: poster download failed from {$poster_url}");
                }
            }

            // Complete the job (stores files, flips status to completed).
            if ($videopath === null && $posterpath === null) {
                $DB->set_field('aitutorial_jobs', 'status', 'failed', ['id' => $job->id]);
                $DB->set_field('aitutorial_jobs', 'error_message',
                    'Generation completed on backend but output could not be downloaded', ['id' => $job->id]);
                mtrace("Job {$job->id}: no output downloaded (video or poster) - marked failed.");
                continue;
            }
            aitutorial_complete_job($job->id, $videopath, $posterpath, $context);
            mtrace("Job {$job->id} completed successfully.");
        }
    }

    mtrace("AI Tutorial Generator cron finished.");
}
