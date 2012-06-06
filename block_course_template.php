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
 * Course Template Block.
 *
 * @package      blocks
 * @subpackage   course_template
 * @copyright    2012 Catalyst-IT Europe
 * @author       Joby Harding <joby.harding@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_course_template extends block_base {

    public function init() {

        $this->title = get_string('pluginname', 'block_course_template');
    }

    public function instance_allow_multiple() {

        return false;
    }

    public function applicable_formats() {

        return array(
            'site-index' => true,
            'mod' => false,
            'course' => false
        );
    }

    public function get_content() {
        global $CFG, $DB, $OUTPUT;

        if($this->content !== null) {
            return $this->content;
        }

        // Make sure that the block only displays if the current view is of an allowed course format
        if (isset($CFG->block_course_template_allowedformats)) {
            $allowedformats = explode(',', $CFG->block_course_template_allowedformats);
            if (!in_array($this->page->course->format, $allowedformats)) {
                return null;
            }
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $tempurl = new moodle_url('/blocks/course_template/edit.php', array('c' => $this->page->course->id));
        $courseurl = new moodle_url('/blocks/course_template/newcourse.php');
        $viewurl = new moodle_url('/blocks/course_template/view.php');

        $this->content->text .= html_writer::start_tag('ul');

        $context = get_context_instance(CONTEXT_SYSTEM);
        $canedit = has_capability('block/course_template:edit', $context);

        // New template
        if ($canedit) {
            $this->content->text .= html_writer::start_tag('li');
            $this->content->text .= html_writer::link($tempurl, get_string('newtemplate', 'block_course_template'));
            $this->content->text .= html_writer::end_tag('li');
        }

        $this->content->text .= html_writer::start_tag('li');
        $this->content->text .= html_writer::link($courseurl, get_string('newcourse', 'block_course_template'));
        $this->content->text .= html_writer::end_tag('li');

        // View all
        $this->content->text .= html_writer::start_tag('li');
        $this->content->text .= html_writer::link($viewurl, get_string('alltemplates', 'block_course_template'));
        $this->content->text .= html_writer::end_tag('li');

        $this->content->text .= html_writer::end_tag('ul');

        return $this->content;
    }
}


