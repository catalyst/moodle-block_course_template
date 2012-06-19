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
 * Form for filtering course templates by tag.
 *
 * @package      blocks
 * @subpackage   course_template
 * @copyright    2012 Catalyst-IT Europe
 * @author       Joby Harding <joby.harding@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No direct script access.
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class block_course_template_tag_filter_form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;
        $tags = $this->_customdata['tags'];
        $filtertag = $this->_customdata['filtertag'];
        $courseid = $this->_customdata['course'];

        $mform->addElement('header', 'activetags', get_string('activetags', 'block_course_template'));

        $mform->addElement('html', html_writer::tag('p', get_string('filtertext', 'block_course_template')));

        $group = array();
        foreach ($tags as $tag) {
            $group[] =& $mform->createElement('advcheckbox', $tag->id, '', "{$tag->name} ", array('group' => 1));
            if ($filtertag) {
                $mform->setDefault('tags[' . $filtertag . ']', 1);
            }
        }

        $mform->addGroup($group, 'tags', array(' '), false);
        $this->add_checkbox_controller(1);

        $mform->addElement('hidden', 'c', $courseid);

        $this->add_action_buttons(false, get_string('filtertemplates', 'block_course_template'));
    }
}
