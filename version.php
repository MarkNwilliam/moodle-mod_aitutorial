<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * AI Tutorial Generator - Version Information
 *
 * @package    mod_aitutorial
 * @copyright  2026 Nlugwa Mark William
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_aitutorial';
$plugin->version = 2026092800;
$plugin->release = '1.2.2';
$plugin->requires = 2022041900;  // Moodle 4.0+
$plugin->maturity = MATURITY_BETA;
$plugin->cron = 60;  // Run cron every 60 seconds to check job status
