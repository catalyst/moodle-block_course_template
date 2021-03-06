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
 * Delete a single template
 *
 * @package      blocks
 * @subpackage   course_template
 * @copyright    2012 Catalyst-IT Europe
 * @author       Stacey Walker <stacey@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/blocks/course_template/locallib.php');

require_login();

$id       = required_param('id', PARAM_INT);
$courseid = optional_param('c', 0, PARAM_INT);
$confirm  = optional_param('confirm', 0, PARAM_INT);
$context  = context_system::instance();

require_capability('block/course_template:edit', $context);

if (!$template = $DB->get_record('block_course_template', array('id' => $id))) {
    print_error('error:notemplate', 'block_course_template', $id);
}

// Confirmed deletion.
if ($confirm) {
    require_sesskey();

    course_template_delete_template($id);
    $url = new moodle_url('/blocks/course_template/view.php', array('course' => $courseid));
    totara_set_notification(get_string('templatedeleted', 'block_course_template'), $url, array('class' => 'notifysuccess'));
}

$heading = get_string('deletetemplate', 'block_course_template');

$url = new moodle_url('/blocks/course_template/delete.php', array('id' => $id, 'confirm' => $confirm));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title($heading);
$PAGE->set_heading($heading);
$PAGE->navbar->add(get_string('coursetemplates', 'block_course_template'));
$PAGE->navbar->add($heading);

echo $OUTPUT->header();
echo $OUTPUT->heading($heading, 2, 'main');

$confirmurl = new moodle_url('/blocks/course_template/delete.php', array('id' => $id, 'confirm' => 1, 'c' => $courseid));
$cancelurl  = new moodle_url('/blocks/course_template/view.php', array('course' => $courseid));

echo $OUTPUT->confirm(get_string('confirmdelete', 'block_course_template', s($template->name)), $confirmurl, $cancelurl);

echo $OUTPUT->footer();
