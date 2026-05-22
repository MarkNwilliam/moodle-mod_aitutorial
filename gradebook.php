<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Gradebook Integration
 *
 * @package    mod_aitutorial
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');

/**
 * Update grades in gradebook for a specific user or all users.
 *
 * @param int $aitutorialid Activity instance ID
 * @param int $userid User ID (0 for all users)
 * @param bool $nullifnone If true, set grade to null if no completed jobs
 */
function aitutorial_update_grades($aitutorialid, $userid = 0, $nullifnone = true) {
    global $DB;

    $aitutorial = $DB->get_record('aitutorial', ['id' => $aitutorialid], '*', MUST_EXIST);

    if ($userid) {
        // Update single user.
        $grade = aitutorial_calculate_user_grade($aitutorialid, $userid);
        
        if ($grade !== false || $nullifnone) {
            aitutorial_grade_item_update($aitutorial, $userid, $grade);
        }
    } else {
        // Update all users with completed jobs.
        $userjobs = $DB->get_records_sql("
            SELECT DISTINCT userid 
            FROM {aitutorial_jobs} 
            WHERE aitutorialid = ? AND status = 'completed'
        ", [$aitutorialid]);

        foreach ($userjobs as $userjob) {
            $grade = aitutorial_calculate_user_grade($aitutorialid, $userjob->userid);
            if ($grade !== false) {
                aitutorial_grade_item_update($aitutorial, $userjob->userid, $grade);
            }
        }
    }
}

/**
 * Calculate grade for a user based on completed generations.
 *
 * Grading scheme:
 * - 1 completed generation = 50% (participation)
 * - 3 completed generations = 75%
 * - 5+ completed generations = 100%
 * 
 * Quality bonuses:
 * - Both video + poster in single generation = +5%
 * - No failed generations = +5%
 *
 * @param int $aitutorialid Activity instance ID
 * @param int $userid User ID
 * @return float|false Grade (0-100) or false if no data
 */
function aitutorial_calculate_user_grade($aitutorialid, $userid) {
    global $DB;

    // Count completed jobs.
    $completed = $DB->count_records('aitutorial_jobs', [
        'aitutorialid' => $aitutorialid,
        'userid' => $userid,
        'status' => 'completed'
    ]);

    if ($completed == 0) {
        return false;
    }

    // Count jobs with both video and poster.
    $bothgenerated = $DB->count_records_sql("
        SELECT COUNT(*)
        FROM {aitutorial_jobs}
        WHERE aitutorialid = ? AND userid = ? AND status = 'completed'
        AND video_fileid IS NOT NULL AND poster_fileid IS NOT NULL
    ", [$aitutorialid, $userid]);

    // Count failed jobs.
    $failed = $DB->count_records('aitutorial_jobs', [
        'aitutorialid' => $aitutorialid,
        'userid' => $userid,
        'status' => 'failed'
    ]);

    // Base grade from participation.
    if ($completed >= 5) {
        $grade = 50.0;
    } elseif ($completed >= 3) {
        $grade = 37.5;
    } else {
        $grade = 25.0;
    }

    // Quality bonuses.
    if ($bothgenerated > 0) {
        $grade += min($bothgenerated * 5, 25);  // Cap at 25% bonus
    }

    if ($failed == 0 && $completed > 0) {
        $grade += 5;  // No failures bonus
    }

    return min($grade, 100.0);
}

/**
 * Create or update grade item for given aitutorial.
 *
 * @param stdClass $aitutorial Activity object
 * @param int $userid User ID (optional)
 * @param float $grade Grade value (optional)
 */
function aitutorial_grade_item_update(stdClass $aitutorial, $userid = 0, $grade = null) {
    global $CFG;

    $params = [
        'itemname' => $aitutorial->name,
        'idnumber' => $aitutorial->id,
    ];

    if (is_null($grade)) {
        // Update grade item settings only.
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = 100;
        $params['grademin'] = 0;
    } else {
        // Update grade for specific user.
        $gradeitem = new stdClass();
        $gradeitem->userid = $userid;
        $gradeitem->rawgrade = $grade;
        
        $grades = [$userid => $gradeitem];
        grade_update('mod/aitutorial', $aitutorial->course, 'mod', 'aitutorial',
                    $aitutorial->id, 0, $grades, $params);
        return;
    }

    grade_update('mod/aitutorial', $aitutorial->course, 'mod', 'aitutorial',
                $aitutorial->id, 0, null, $params);
}

/**
 * Delete grade item for given aitutorial.
 *
 * @param stdClass $aitutorial Activity object
 */
function aitutorial_grade_item_delete(stdClass $aitutorial) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    grade_update('mod/aitutorial', $aitutorial->course, 'mod', 'aitutorial',
                $aitutorial->id, 0, null, ['deleted' => 1]);
}

/**
 * Return grading information for display.
 *
 * @param int $aitutorialid Activity instance ID
 * @param int $userid User ID
 * @return array Grading information
 */
function aitutorial_get_grading_info($aitutorialid, $userid) {
    global $DB;

    $info = [
        'completed' => 0,
        'failed' => 0,
        'pending' => 0,
        'both_generated' => 0,
        'current_grade' => 0,
        'max_possible_grade' => 100,
    ];

    // Count jobs by status.
    $jobs = $DB->get_records('aitutorial_jobs', [
        'aitutorialid' => $aitutorialid,
        'userid' => $userid
    ]);

    foreach ($jobs as $job) {
        $info[$job->status]++;
        
        if ($job->status === 'completed' && 
            $job->video_fileid && $job->poster_fileid) {
            $info['both_generated']++;
        }
    }

    // Calculate current grade.
    $grade = aitutorial_calculate_user_grade($aitutorialid, $userid);
    $info['current_grade'] = $grade !== false ? $grade : 0;

    return $info;
}
