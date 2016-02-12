<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Form for creating and editing course templates.
 *
 * @package      block_course_template
 * @copyright    Catalyst IT Europe
 * @author       Botond Hegedus <botond.hegedus@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No direct script access.
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class duplicate_course_form extends moodleform {

    public function definition() {
        $mform =& $this->_form;

        extract($this->_customdata);

        // Set the new full name.
        $mform->addElement('text','fullname', get_string('fullnamecourse'),'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);
        $mform->setDefault('fullname', $course->fullname);
        // Set the new short name.
        $mform->addElement('text', 'shortname', get_string('shortnamecourse'), 'maxlength="100" size="20"');
        $mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_TEXT);
        $mform->setDefault('shortname', $course->shortname);
        // Set the category
        $displaylist = coursecat::make_categories_list('moodle/course:create');
        $mform->addElement('select', 'category', get_string('coursecategory'), $displaylist);
        $mform->addHelpButton('category', 'coursecategory');
        $mform->setDefault('category', $course->category);
        $no  = get_string('no');
        $yes = get_string('yes');
        // Set visibility.
        $visibilitychoices = array (
            0 => $no,
            1 => $yes,
        );
        $mform->addElement('select', 'visibility', get_string('visibility', 'block_course_template'), $visibilitychoices);
        $mform->addHelpButton('visibility', 'visibility', 'block_course_template');
        $mform->setDefault('visibility', 1);
        // Duplicate enrolment data.
        $enrolmentchoices = array (
            0 => $no,
            1 => $yes,
        );
        $mform->addElement('select', 'enrolment', get_string('enrolment', 'block_course_template'), $enrolmentchoices);
        $mform->addHelpButton('enrolment', 'enrolment', 'block_course_template');
        $mform->setDefault('enrolment', 0);
        // Pass some values.
        $mform->addElement('hidden', 'courseid', $course->id);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'pagetype', $pagetype);
        $mform->setType('pagetype', PARAM_URL);
        // Add buttons.
        $this->add_action_buttons(true, get_string('duplicatecourse', 'block_course_template'));
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        // Add field validation check for duplicate shortname.
        if ($course = $DB->get_record('course', array('shortname' => $data['shortname']), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $course->id != $data['id']) {
                $errors['shortname'] = get_string('shortnametaken', '', $course->shortname);
            }
        }

        return $errors;
    }
}
