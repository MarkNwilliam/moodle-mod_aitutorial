<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_aitutorial;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * AI Tutorial Generator - Submission Form
 *
 * @package    mod_aitutorial
 * @copyright  2026 Nlugwa Mark William
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'submissionheader', get_string('submitforgeneration', 'mod_aitutorial'));

        // File picker.
        $mform->addElement('filepicker', 'pdf_file', get_string('uploadpdf', 'mod_aitutorial'), null,
            ['accepted_types' => ['.pdf'], 'maxbytes' => 52428800]); // 50MB
        $mform->addRule('pdf_file', null, 'required', null, 'client');

        // Generation mode.
        $modeoptions = [];
        if (get_config('mod_aitutorial', 'enable_video')) {
            $modeoptions['video'] = get_string('generationmode_video', 'mod_aitutorial');
        }
        if (get_config('mod_aitutorial', 'enable_poster')) {
            $modeoptions['poster'] = get_string('generationmode_poster', 'mod_aitutorial');
        }
        $modeoptions['both'] = get_string('generationmode_both', 'mod_aitutorial');

        $mform->addElement('select', 'generationmode', get_string('generationmode', 'mod_aitutorial'), $modeoptions);
        $mform->setDefault('generationmode', 'both');

        // Hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'firebase_token');
        $mform->setType('firebase_token', PARAM_RAW);

        $this->add_action_buttons(false, get_string('submitforgeneration', 'mod_aitutorial'));
    }
}
