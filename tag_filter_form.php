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

class block_course_template_tag_filter_form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;
        $showtag = $this->_customdata['tag'];

        //
        // Active tags fieldset
        //
        $mform->addElement('header', 'activetags', get_string('activetags', 'block_course_template'));


        // tags
        $tags =  $DB->get_records('course_template_tag');
        if (empty($tags)) {
            $mform->addElement('html', html_writer::tag('p', get_string('notags', 'block_course_template')));
        } else {
            $groupelems  = array();
            foreach ($tags as $tag) {
                $groupelems[] =& $mform->createElement('advcheckbox', $tag->name, $tag->name,  $tag->rawname, array('group' => 1));
                // if a single tag filter has been passed through _customdata
                if ($showtag !== -1) {
                    if ($tag->id == $showtag) {
                        $mform->setDefault('tags[' . $tag->name . ']', 1);
                    }
                }
            }
            // html description
            $mform->addElement('html', html_writer::tag('p', get_string('filtertext', 'block_course_template')));

            $mform->addGroup($groupelems, 'tags', array(''), false);
            $this->add_checkbox_controller(1);

            //
            // Action buttons
            //
            $this->add_action_buttons(false, get_string('filtertemplates', 'block_course_template'));
        }
    }
}
