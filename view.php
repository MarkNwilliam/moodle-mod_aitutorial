<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - View Page (Upgraded UI)
 *
 * @package    mod_aitutorial
 * @copyright  2026 Nlugwa Mark William
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

    $fs = get_file_storage();

    // Read the uploaded PDF from the filepicker's draft area.
    $draftfiles = $fs->get_area_files(
        context_user::instance($USER->id)->id, 'user', 'draft',
        (int)$data->pdf_file, 'id DESC', false
    );
    if (empty($draftfiles)) {
        print_error('generationerror', 'mod_aitutorial');
    }
    $draftfile = reset($draftfiles);

    // Build the job record (sourcefileid is updated once the file is stored).
    $job = new stdClass();
    $job->aitutorialid = $aitutorial->id;
    $job->userid = $USER->id;
    $job->courseid = $course->id;
    $job->sourcefileid = 0;
    $job->job_type = $data->generationmode;
    $job->status = 'pending';
    $job->progress = 0;
    $job->timecreated = time();
    $job->timemodified = time();

    $jobid = $DB->insert_record('aitutorial_jobs', $job);

    // Store the uploaded PDF permanently against this job.
    $filerecord = [
        'contextid' => $context->id,
        'component' => 'mod_aitutorial',
        'filearea' => 'submission',
        'itemid' => $jobid,
        'filepath' => '/',
        'filename' => $draftfile->get_filename(),
        'userid' => $USER->id,
    ];
    $pdffile = $fs->create_file_from_storedfile($filerecord, $draftfile);
    $DB->set_field('aitutorial_jobs', 'sourcefileid', $pdffile->get_id(), ['id' => $jobid]);

    $firebasetoken = $data->firebase_token ?? '';
    aitutorial_trigger_generation($jobid, $pdffile, $data->generationmode, $context, $firebasetoken);
    redirect($PAGE->url, get_string('generationstarted', 'mod_aitutorial'));
}

echo $OUTPUT->header();

// Add Firebase Login UI (Sign In / Sign Up / Google)
echo html_writer::start_tag('div', ['id' => 'cuppa-auth-container', 'class' => 'card mb-4 p-3', 'style' => 'border-left: 5px solid #4285F4;']);
echo html_writer::tag('div', 'Loading Cuppa AI...', ['id' => 'auth-status', 'style' => 'font-weight: bold; margin-bottom: 10px;']);

echo html_writer::start_tag('div', ['id' => 'cuppa-auth-tabs', 'class' => 'btn-group btn-group-sm mb-2', 'role' => 'group', 'style' => 'width: 100%;']);
echo html_writer::tag('button', 'Sign In', ['type' => 'button', 'id' => 'tab-signin', 'class' => 'btn btn-outline-primary btn-auth-tab active']);
echo html_writer::tag('button', 'Sign Up', ['type' => 'button', 'id' => 'tab-signup', 'class' => 'btn btn-outline-primary btn-auth-tab']);
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['id' => 'cuppa-auth-fields', 'class' => 'mb-2', 'style' => 'max-width: 380px; display: none;']);
echo html_writer::start_tag('div', ['class' => 'form-group mb-2']);
echo html_writer::tag('input', '', ['type' => 'email', 'id' => 'cuppa-auth-email', 'class' => 'form-control form-control-sm', 'placeholder' => 'Email address', 'autocomplete' => 'email']);
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', ['class' => 'form-group mb-2']);
echo html_writer::tag('input', '', ['type' => 'password', 'id' => 'cuppa-auth-password', 'class' => 'form-control form-control-sm', 'placeholder' => 'Password (min 6 characters)', 'autocomplete' => 'new-password']);
echo html_writer::end_tag('div');
echo html_writer::tag('button', 'Sign In', ['type' => 'button', 'id' => 'btn-cuppa-auth-submit', 'class' => 'btn btn-success btn-sm', 'style' => 'width: 100%;']);
echo html_writer::end_tag('div');

echo html_writer::tag('div', 'or', ['style' => 'text-align:center; opacity:0.7; margin: 4px 0;']);
echo html_writer::tag('button', 'Sign in with Google', [
    'id' => 'btn-cuppa-login',
    'class' => 'btn btn-primary btn-sm',
    'style' => 'display: none; width: 100%;'
]);
echo html_writer::tag('div', '', ['id' => 'subscription-status', 'class' => 'text-muted small']);
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
        echo html_writer::start_tag('div', [
            'class' => 'aitutorial-job-card',
            'data-jobid' => $job->id,
            'data-backendid' => s($job->backend_job_id ?? '')
        ]);
        
        // Status Badge
        $statusclass = 'status-' . $job->status;
        echo html_writer::tag('span', get_string('status_' . $job->status, 'mod_aitutorial'), 
            ['class' => 'badge ' . $statusclass]);

        // Job Title / Source Filename.
        // get_file_by_id() returns false (it never throws), so guard the result.
        $sourcefile = !empty($job->sourcefileid) ? $fs->get_file_by_id($job->sourcefileid) : false;
        $sourcename = ($sourcefile) ? $sourcefile->get_filename() : ('Tutorial Job #' . $job->id);
        echo html_writer::tag('h4', '📄 ' . $sourcename, ['style' => 'margin: 15px 0 5px 0; font-size: 1.1rem;']);

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
            
            if (!empty($job->video_fileid)) {
                $videofile = $fs->get_file_by_id($job->video_fileid);
                if ($videofile) {
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
                }
            }

            if (!empty($job->poster_fileid)) {
                $posterfile = $fs->get_file_by_id($job->poster_fileid);
                if ($posterfile) {
                    $posterurl = moodle_url::make_pluginfile_url(
                        $context->id, 'mod_aitutorial', 'generated_poster', $job->id, '/', $posterfile->get_filename()
                    )->out(false);

                    echo html_writer::tag('a', '📊 ' . get_string('generatedposter', 'mod_aitutorial'), [
                        'href' => $posterurl, 'class' => 'btn btn-outline-info btn-sm btn-download', 'target' => '_blank', 'style' => 'margin-top:10px;'
                    ]);
                }
            }
            echo html_writer::end_tag('div');
        }

        // Error & Retry
        if ($job->status === 'failed') {
            echo html_writer::tag('div', '❌ ' . s(aitutorial_str($job->error_message, 'Generation failed')), ['class' => 'alert alert-danger', 'style' => 'margin-top:15px; font-size:0.85rem;']);
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

// Firebase config. Ships with working defaults so the plugin runs immediately
// after install; site admins can override each value under
// Site Administration -> Plugins -> AI Tutorial Generator.
$firebaseconfig = [
    'apiKey' => get_config('mod_aitutorial', 'firebase_apikey') ?: 'AIzaSyDbF0Pcal-OfnnmLVLcgd_5on4_rZl8lHs',
    'authDomain' => get_config('mod_aitutorial', 'firebase_authdomain') ?: 'cuppaai.firebaseapp.com',
    'projectId' => get_config('mod_aitutorial', 'firebase_projectid') ?: 'cuppaai',
    'storageBucket' => get_config('mod_aitutorial', 'firebase_storagebucket') ?: 'cuppaai.firebasestorage.app',
    'messagingSenderId' => get_config('mod_aitutorial', 'messaging_sender_id') ?: '390647117252',
    'appId' => get_config('mod_aitutorial', 'firebase_appid') ?: '1:390647117252:web:926052958af71480bb2091',
];
$PAGE->requires->js_call_amd('mod_aitutorial/auth_helper', 'init', [$firebaseconfig]);
$PAGE->requires->js_call_amd('mod_aitutorial/job_tracker', 'init', [
    $cm->id,
    get_config('mod_aitutorial', 'api_url') ?: 'https://cuppai.top'
]);
echo $OUTPUT->footer();
