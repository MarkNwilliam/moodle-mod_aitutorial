<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - View Page (Upgraded UI)
 *
 * @package    mod_aitutorial
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/aitutorial/locallib.php');
require_once($CFG->dirroot . '/mod/aitutorial/lib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('aitutorial', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$aitutorial = $DB->get_record('aitutorial', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/aitutorial:view', $context);

$PAGE->set_url('/mod/aitutorial/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($aitutorial->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_activity_record($aitutorial);

// Initialize submission form.
$mform = new \mod_aitutorial\submission_form(null, ['id' => $cm->id]);
$mform->set_data(['id' => $cm->id, 'generationmode' => $aitutorial->defaultmode ?? 'both']);

// Handle form submission.
if ($data = $mform->get_data()) {
    require_capability('mod/aitutorial:submit', $context);
    
    // ... file handling ...
    
    $jobid = $DB->insert_record('aitutorial_jobs', $job);
    $pdffile->set_itemid($jobid);

    $firebasetoken = $data->firebase_token ?? '';
    aitutorial_trigger_generation($jobid, $pdffile, $data->generationmode, $context, $firebasetoken);
    redirect($PAGE->url, get_string('generationstarted', 'mod_aitutorial'));
}

echo $OUTPUT->header();

// Add Firebase Login UI
echo html_writer::start_tag('div', ['id' => 'cuppa-auth-container', 'class' => 'card mb-4 p-3', 'style' => 'border-left: 5px solid #4285F4;']);
echo html_writer::tag('div', 'Cuppa AI Login', ['id' => 'auth-status', 'style' => 'font-weight: bold; margin-bottom: 10px;']);
echo html_writer::tag('button', 'Sign in with Google to start', [
    'id' => 'btn-cuppa-login', 
    'class' => 'btn btn-primary',
    'style' => 'display: none;'
]);
echo html_writer::tag('div', 'Checking subscription...', ['id' => 'subscription-status', 'class' => 'text-muted small']);
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['id' => 'aitutorial-main-content', 'style' => 'display: none;']);

echo html_writer::start_tag('div', ['class' => 'aitutorial-container']);

// 1. HELP / INSTRUCTION CARD
echo html_writer::start_tag('div', ['class' => 'aitutorial-help-card']);
echo html_writer::tag('h3', '✨ ' . get_string('howitworks', 'mod_aitutorial'));
echo html_writer::tag('p', get_string('modulename_help', 'mod_aitutorial'));

echo html_writer::start_tag('div', ['class' => 'aitutorial-help-steps']);

$steps = [
    ['icon' => '📄', 'title' => 'step1_title', 'desc' => 'step1_desc'],
    ['icon' => '⚙️', 'title' => 'step2_title', 'desc' => 'step2_desc'],
    ['icon' => '🤖', 'title' => 'step3_title', 'desc' => 'step3_desc'],
    ['icon' => '🎓', 'title' => 'step4_title', 'desc' => 'step4_desc'],
];

foreach ($steps as $step) {
    echo html_writer::start_tag('div', ['class' => 'aitutorial-step']);
    echo html_writer::tag('span', $step['icon'], ['class' => 'aitutorial-step-icon']);
    echo html_writer::tag('strong', get_string($step['title'], 'mod_aitutorial'), ['style' => 'display:block; margin-bottom:5px;']);
    echo html_writer::tag('small', get_string($step['desc'], 'mod_aitutorial'));
    echo html_writer::end_tag('div');
}

echo html_writer::end_tag('div');
echo html_writer::tag('p', '💡 ' . get_string('helptip', 'mod_aitutorial'), ['style' => 'margin-top: 20px; font-size: 0.9em; opacity: 0.9;']);
echo html_writer::end_tag('div');

// 2. SUBMISSION FORM
echo html_writer::start_tag('div', ['class' => 'aitutorial-upload-form']);
$mform->display();
echo html_writer::end_tag('div');

// 3. JOB HISTORY GRID
$userjobs = $DB->get_records('aitutorial_jobs', 
    ['aitutorialid' => $aitutorial->id, 'userid' => $USER->id], 
    'timecreated DESC');

echo html_writer::tag('h3', '🕒 ' . get_string('jobhistory', 'mod_aitutorial'), ['style' => 'margin-bottom: 20px;']);

if (!empty($userjobs)) {
    echo html_writer::start_tag('div', ['class' => 'aitutorial-jobs-grid']);
    $fs = get_file_storage();

    foreach ($userjobs as $job) {
        echo html_writer::start_tag('div', ['class' => 'aitutorial-job-card', 'data-jobid' => $job->id]);
        
        // Status Badge
        $statusclass = 'status-' . $job->status;
        echo html_writer::tag('span', get_string('status_' . $job->status, 'mod_aitutorial'), 
            ['class' => 'badge ' . $statusclass]);

        // Job Title / Source Filename
        try {
            $sourcefile = $fs->get_file_by_id($job->sourcefileid);
            echo html_writer::tag('h4', '📄 ' . $sourcefile->get_filename(), ['style' => 'margin: 15px 0 5px 0; font-size: 1.1rem;']);
        } catch (Exception $e) {
            echo html_writer::tag('h4', '📄 Tutorial Job #' . $job->id, ['style' => 'margin: 15px 0 5px 0; font-size: 1.1rem;']);
        }

        // Progress Section
        if ($job->status === 'processing' || $job->status === 'pending') {
            echo html_writer::start_tag('div', ['class' => 'progress']);
            echo html_writer::start_tag('div', [
                'class' => 'progress-bar progress-bar-striped progress-bar-animated',
                'role' => 'progressbar',
                'style' => "width: {$job->progress}%",
                'aria-valuenow' => $job->progress,
                'aria-valuemin' => '0',
                'aria-valuemax' => '100'
            ]);
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
            echo html_writer::tag('p', "{$job->progress}% " . get_string('progress', 'mod_aitutorial'), ['class' => 'progress-text']);
        }

        // Media Content
        if ($job->status === 'completed') {
            echo html_writer::start_tag('div', ['style' => 'margin-top: 10px; flex-grow: 1;']);
            
            if ($job->video_fileid) {
                try {
                    $videofile = $fs->get_file_by_id($job->video_fileid);
                    $videourl = moodle_url::make_pluginfile_url(
                        $context->id, 'mod_aitutorial', 'generated_video', $job->id, '/', $videofile->get_filename()
                    )->out(false);
                    
                    echo html_writer::start_tag('div', ['class' => 'generated-media-container']);
                    echo html_writer::tag('video', '', [
                        'src' => $videourl, 'controls' => 'controls', 'width' => '100%', 'style' => 'display:block;'
                    ]);
                    echo html_writer::end_tag('div');
                    echo html_writer::tag('a', '📹 ' . get_string('downloadvideo', 'mod_aitutorial'), [
                        'href' => $videourl, 'class' => 'btn btn-outline-primary btn-sm btn-download', 'download' => ''
                    ]);
                } catch (Exception $e) {}
            }

            if ($job->poster_fileid) {
                try {
                    $posterfile = $fs->get_file_by_id($job->poster_fileid);
                    $posterurl = moodle_url::make_pluginfile_url(
                        $context->id, 'mod_aitutorial', 'generated_poster', $job->id, '/', $posterfile->get_filename()
                    )->out(false);
                    
                    echo html_writer::tag('a', '📊 ' . get_string('generatedposter', 'mod_aitutorial'), [
                        'href' => $posterurl, 'class' => 'btn btn-outline-info btn-sm btn-download', 'target' => '_blank', 'style' => 'margin-top:10px;'
                    ]);
                } catch (Exception $e) {}
            }
            echo html_writer::end_tag('div');
        }

        // Error & Retry
        if ($job->status === 'failed') {
            echo html_writer::tag('div', '❌ ' . s($job->error_message), ['class' => 'alert alert-danger', 'style' => 'margin-top:15px; font-size:0.85rem;']);
            echo html_writer::tag('button', '🔄 ' . get_string('retry', 'mod_aitutorial'), [
                'class' => 'btn btn-warning btn-sm btn-retry', 'data-jobid' => $job->id, 'style' => 'width:100%; margin-top:5px;'
            ]);
        }

        echo html_writer::tag('div', get_string('generatedon', 'mod_aitutorial') . ': ' . userdate($job->timecreated, '%d %b, %H:%M'), 
            ['style' => 'font-size: 0.75rem; color: #94a3b8; margin-top: 20px; border-top: 1px solid #f1f5f9; padding-top: 10px;']);

        echo html_writer::end_tag('div');
    }
    echo html_writer::end_tag('div');
} else {
    // Beautiful Empty State
    echo html_writer::start_tag('div', ['class' => 'aitutorial-empty']);
    echo html_writer::tag('span', '🚀', ['class' => 'aitutorial-empty-icon']);
    echo html_writer::tag('h4', get_string('nogenerationyet', 'mod_aitutorial'));
    echo html_writer::tag('p', get_string('step1_desc', 'mod_aitutorial'), ['style' => 'color: #64748b;']);
    echo html_writer::end_tag('div');
}

echo html_writer::end_tag('div'); // End aitutorial-container
echo html_writer::end_tag('div'); // End aitutorial-main-content

$firebaseapikey = get_config('mod_aitutorial', 'firebase_apikey');
$firebaseauthdomain = get_config('mod_aitutorial', 'firebase_authdomain');
$firebaseprojectid = get_config('mod_aitutorial', 'firebase_projectid');

$PAGE->requires->js_call_amd('mod_aitutorial/auth_helper', 'init', [
    'apiKey' => $firebaseapikey ?: '',
    'authDomain' => $firebaseauthdomain ?: '',
    'projectId' => $firebaseprojectid ?: ''
]);
$PAGE->requires->js_call_amd('mod_aitutorial/job_tracker', 'init', [$cm->id]);
echo $OUTPUT->footer();
