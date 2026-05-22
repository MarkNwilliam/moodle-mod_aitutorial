<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Cron Handler
 *
 * @package    mod_aitutorial
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Cron function to check job status and update progress.
 */
function aitutorial_cron() {
    global $DB;

    mtrace("Running AI Tutorial Generator cron...");

    // Get all processing jobs.
    $processingjobs = $DB->get_records('aitutorial_jobs', ['status' => 'processing'], 'timecreated ASC');

    if (empty($processingjobs)) {
        mtrace("No processing jobs found.");
        return;
    }

    $apibaseurl = get_config('mod_aitutorial', 'api_url');
    if (empty($apibaseurl)) {
        $apibaseurl = 'https://aitutorial-api-1776284710.eastus.cloudapp.azure.com';
    }

    $curl = new curl();
    $curl->setopt([
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_TIMEOUT' => 10,
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

        // Poll backend API for status.
        $apiurl = $apibaseurl . '/api/status/' . $job->id;
        $response = $curl->get($apiurl);

        if ($response === false) {
            mtrace("Failed to get status for job {$job->id}");
            continue;
        }

        $result = json_decode($response, true);
        if (!$result) {
            continue;
        }

        // Update job status and progress.
        $update = new stdClass();
        $update->id = $job->id;
        $update->timemodified = time();

        if (isset($result['progress'])) {
            $update->progress = $result['progress'];
        }

        if (isset($result['status'])) {
            $update->status = $result['status'];
        }

        if (isset($result['error'])) {
            $update->error_message = $result['error'];
        }

        // Check if video/poster files are ready to download.
        if ($result['status'] === 'completed') {
            $context = context_module::instance_by_id($job->aitutorialid);
            
            $videopath = null;
            $posterpath = null;

            // Download video if available.
            if (!empty($result['video_url'])) {
                $videotempfile = $CFG->tempdir . '/aitutorial_video_' . $job->id . '.mp4';
                $videodata = $curl->get($result['video_url']);
                if ($videodata !== false) {
                    file_put_contents($videotempfile, $videodata);
                    $videopath = $videotempfile;
                }
            }

            // Download poster if available.
            if (!empty($result['poster_url'])) {
                $postertempfile = $CFG->tempdir . '/aitutorial_poster_' . $job->id . '.pdf';
                $posterdata = $curl->get($result['poster_url']);
                if ($posterdata !== false) {
                    file_put_contents($postertempfile, $posterdata);
                    $posterpath = $postertempfile;
                }
            }

            // Complete the job.
            aitutorial_complete_job($job->id, $videopath, $posterpath, $context);
            mtrace("Job {$job->id} completed successfully.");
        } else {
            $DB->update_record('aitutorial_jobs', $update);
            mtrace("Updated job {$job->id}: {$update->status} ({$update->progress}%)");
        }
    }

    mtrace("AI Tutorial Generator cron finished.");
}
