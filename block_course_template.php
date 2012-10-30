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

class block_course_template extends block_list {

    public function init() {
        $this->title = get_string('pluginname', 'block_course_template');
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function applicable_formats() {
        return array(
            'site-index' => true,
            'course' => true,
            'mod' => false,
        );
    }

    public function get_content() {
        global $CFG, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $renderer = $PAGE->get_renderer('block_course_template');

        $this->content = new stdClass();
        $this->content->icons = array();
        $this->content->items = $renderer->display_block_links($PAGE->course->id);
        $this->content->footer = '';

        return $this->content;
    }
}
