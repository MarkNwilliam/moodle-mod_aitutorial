<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Legacy module cron entry point.
 *
 * Moodle's scheduled cron discovers module-level cron job by the presence
 * of this file at the plugin root combined with $plugin->cron in version.php.
 *
 * @package    mod_aitutorial
 * @copyright  2026 Nlugwa Mark William
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/lib/cron.php');

aitutorial_cron();