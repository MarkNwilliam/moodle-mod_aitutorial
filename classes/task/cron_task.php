<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Scheduled task that syncs backend jobs (status + downloaded media) into Moodle.
 *
 * @package    mod_aitutorial
 * @copyright  2026 Nlugwa Mark William
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aitutorial\task;

defined('MOODLE_INTERNAL') || die();

class cron_task extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('pluginname', 'mod_aitutorial');
    }

    public function execute(): void {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->dirroot . '/mod/aitutorial/locallib.php');
        require_once($CFG->dirroot . '/mod/aitutorial/lib/cron.php');
        aitutorial_cron();
    }
}