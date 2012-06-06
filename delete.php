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
require_once($CFG->dirroot.'/blocks/course_template/lib.php');

require_login();

$id      = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

require_capability('block/course_template:edit', get_context_instance(CONTEXT_SYSTEM));

if (!$template = $DB->get_record('block_course_template', array('id' => $id))) {
    print_error('error:notemplate', 'block_course_template', $id);
}

// Confirmed deletion.
if ($confirm) {
    block_course_template_delete_template($id);
    redirect('/blocks/course_template/view.php', get_string('templatedeleted', 'block_course_template'));
    exit;
}

$heading = get_string('deletetemplate', 'block_course_template');

$url = new moodle_url('/blocks/course_template/delete.php', array('id' => $id, 'confirm' => $confirm));
$PAGE->set_url($url);
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_pagelayout('course');
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

echo $OUTPUT->header();
echo $OUTPUT->heading($heading, 2, 'main');

$confirmurl = new moodle_url('/blocks/course_template/delete.php', array('id' => $id, 'confirm' => 1));
$cancelurl  = new moodle_url('/blocks/course_template/view.php');

echo $OUTPUT->confirm(get_string('confirmdelete', 'block_course_template', s($template->name)), $confirmurl, $cancelurl);

echo $OUTPUT->footer();
