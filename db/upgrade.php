<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Upgrade Script
 *
 * @package    mod_aitutorial
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_aitutorial_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026041100) {
        // Initial installation - tables created in install.xml.
        upgrade_mod_savepoint(true, 2026041100, 'aitutorial');
    }

    return true;
}
