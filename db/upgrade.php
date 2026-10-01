<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Upgrade Script
 *
 * @package    mod_aitutorial
 * @copyright  2026 Nlugwa Mark William
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_aitutorial_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026041100) {
        // Initial installation - tables created in install.xml.
        upgrade_mod_savepoint(true, 2026041100, 'aitutorial');
    }

    if ($oldversion < 2026050901) {
        // Add backend_job_id field to store the backend's job ID for direct polling.
        $table = new xmldb_table('aitutorial_jobs');
        $field = new xmldb_field('backend_job_id', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'error_message');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026050901, 'aitutorial');
    }

    if ($oldversion < 2026092800) {
        upgrade_mod_savepoint(true, 2026092800, 'aitutorial');
    }

    return true;
}
