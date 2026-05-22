<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Admin Settings
 *
 * @package    mod_aitutorial
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('mod_aitutorial_settings', get_string('pluginname', 'mod_aitutorial'));
    $ADMIN->add('modsettings', $settings);

    // Backend API URL.
    $settings->add(new admin_setting_configtext(
        'mod_aitutorial/api_url',
        get_string('apiurl', 'mod_aitutorial'),
        get_string('apiurl_desc', 'mod_aitutorial'),
        'https://aitutorial-api-1776284710.eastus.cloudapp.azure.com',
        PARAM_URL
    ));

    // Enable video generation.
    $settings->add(new admin_setting_configcheckbox(
        'mod_aitutorial/enable_video',
        get_string('enablevideo', 'mod_aitutorial'),
        get_string('enablevideo_desc', 'mod_aitutorial'),
        1
    ));

    // Enable poster generation.
    $settings->add(new admin_setting_configcheckbox(
        'mod_aitutorial/enable_poster',
        get_string('enableposter', 'mod_aitutorial'),
        get_string('enableposter_desc', 'mod_aitutorial'),
        1
    ));

    // Max file size (MB).
    $settings->add(new admin_setting_configtext(
        'mod_aitutorial/max_filesize',
        get_string('maxfilesize', 'mod_aitutorial'),
        get_string('maxfilesize_desc', 'mod_aitutorial'),
        '50',
        PARAM_INT
    ));

    // TTS Voice selection.
    $settings->add(new admin_setting_configtext(
        'mod_aitutorial/tts_voice',
        get_string('ttsvoice', 'mod_aitutorial'),
        get_string('ttsvoice_desc', 'mod_aitutorial'),
        'en-US-GuyNeural',
        PARAM_TEXT
    ));

    // Firebase Settings.
    $settings->add(new admin_setting_configtext(
        'mod_aitutorial/firebase_apikey',
        get_string('firebase_apikey', 'mod_aitutorial'),
        get_string('firebase_apikey_desc', 'mod_aitutorial'),
        '',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'mod_aitutorial/firebase_authdomain',
        get_string('firebase_authdomain', 'mod_aitutorial'),
        get_string('firebase_authdomain_desc', 'mod_aitutorial'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'mod_aitutorial/firebase_projectid',
        get_string('firebase_projectid', 'mod_aitutorial'),
        get_string('firebase_projectid_desc', 'mod_aitutorial'),
        '',
        PARAM_TEXT
    ));
}
