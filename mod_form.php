<?php
// This file is part of Moodle - http://moodle.org/

/**
 * AI Tutorial Generator - Activity Form
 *
 * @package    mod_aitutorial
 * @copyright  2026 Nlugwa Mark William
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/aitutorial/lib.php');

class mod_aitutorial_mod_form extends moodleform_mod {
    
    function definition() {
        global $CFG;
        
        $mform = $this->_form;
        
        // Name.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        
        // Description.
        $this->standard_intro_elements();
        
        // Default generation mode (set by teacher).
        $modeoptions = [
            'video' => get_string('generationmode_video', 'mod_aitutorial'),
            'poster' => get_string('generationmode_poster', 'mod_aitutorial'),
            'both' => get_string('generationmode_both', 'mod_aitutorial'),
        ];
        
        $mform->addElement('select', 'defaultmode', get_string('generationmode', 'mod_aitutorial'), $modeoptions);
        $mform->setDefault('defaultmode', 'both');
        $mform->addHelpButton('defaultmode', 'generationmode', 'mod_aitutorial');
        
        $this->standard_coursemodule_elements();
        
        $this->add_action_buttons();
    }
    
    function data_postprocessing($data) {
        parent::data_postprocessing($data);
        
        if (!empty($data->instance)) {
            if (!empty($data->completionunlocked)) {
                $reset = 0;
            }
        }
    }
}
