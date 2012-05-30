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
 * <one-line description>
 *
 * <longer description [optional]>
 *
 * @package      <package name>
 * @subpackage   <subpackage name>
 * @copyright    2012 Catalyst-IT Europe
 * @author       Joby Harding <joby.harding@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// no direct script access
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/course/lib.php');

class block_course_template_new_course_form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;
        $template = $this->_customdata['template'];
        // prevent file attachments
        $editoroptions = array(
            'maxfiles' => 0,
            'maxbytes' => 0,
            'subdirs' => 0,
            'changeformat' => 0,
            'context' => null,
            'noclean' => 0,
            'trusttext' => 0
        );

        //
        // Details fieldset
        //
        $mform->addElement('header', 'detailsheading', get_string('details', 'block_course_template'));

        // Template
        $selecttemp = array();
        $selecttemp = $DB->get_records('block_course_template');
        if (!empty($selecttemp)) {
            $selecttemp = array_map(function($n){return $n->rawname;}, $selecttemp);
        }
        $mform->addelement('select', 'template', get_string('template', 'block_course_template'), $selecttemp);
        if ($template != -1) {
            $mform->setDefault('template', $template);
        }

        // Category
        $selectcat = array();
        $notused = array();     // make_categories_list requires this param but actually we don't need the result
        make_categories_list($selectcat, $notused);
        $mform->addElement('select', 'category', get_string('category'), $selectcat);

        // Full name
        $mform->addElement('text','fullname', get_string('fullnamecourse'),'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);
        if (!empty($course->id) and !has_capability('moodle/course:changefullname', $coursecontext)) {
            $mform->hardFreeze('fullname');
            $mform->setConstant('fullname', $course->fullname);
        }

        // Short name
        $mform->addElement('text', 'shortname', get_string('shortnamecourse'), 'maxlength="100" size="20"');
        $mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_MULTILANG);
        if (!empty($course->id) and !has_capability('moodle/course:changeshortname', $coursecontext)) {
            $mform->hardFreeze('shortname');
            $mform->setConstant('shortname', $course->shortname);
        }

        // ID
        $mform->addElement('text','idnumber', get_string('idnumbercourse'),'maxlength="100"  size="10"');
        $mform->addHelpButton('idnumber', 'idnumbercourse');
        $mform->setType('idnumber', PARAM_RAW);
        if (!empty($course->id) and !has_capability('moodle/course:changeidnumber', $coursecontext)) {
            $mform->hardFreeze('idnumber');
            $mform->setConstants('idnumber', $course->idnumber);
        }

        // Summary
        $mform->addElement('editor','summary_editor', get_string('coursesummary'), null, $editoroptions);
        $mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);

        // Start date
        $mform->addElement('date_selector', 'startdate', get_string('startdate'));

        //
        // Hidden fields
        //
        $mform->addElement('hidden', 'referer', $this->_customdata['referer']);

        //
        // Action buttons
        //
        $this->add_action_buttons(true, get_string('createcourse', 'block_course_template'));
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        // does the course name already exist?
        //$fullname = $DB->get_field('block_course_template', 'name', array('name' => $data['name']));
        //$shortname = $DB->get_field('block_course_template', 'name', array('name' => $data['name']));


        return $errors;
    }
}
