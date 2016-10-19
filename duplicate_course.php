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
 * Create a new course from a course template.
 *
 * @package      block_course_template
 * @copyright    Catalyst IT Europe
 * @author       Botond Hegedus <botond.hegedus@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/blocks/course_template/duplicate_course_form.php');
require_once($CFG->dirroot . '/blocks/course_template/locallib.php');
require_once($CFG->dirroot . '/course/externallib.php');

// Get the course ID.
$courseid = required_param('courseid', PARAM_INT);
// Set referer.
$pagetype = required_param('pagetype', PARAM_URL);
// User must be logged in.
require_login();
// If course === site error.
if ($courseid === SITEID) {
    print_error('error:sitecourse', 'block_course_template');
}
// Set context vars.
$coursecontext = context_course::instance($courseid);
// Check course creation capability.
require_capability('block/course_template:duplicatecourse', $coursecontext);
// Get existing course from DB.
$course = get_course($courseid);
// Set $PAGE vars.
$PAGE->set_context($coursecontext);
$headingstr  = get_string('duplicatecourse', 'block_course_template') . " '" . format_string($course->fullname) . "'";
$PAGE->set_url('/blocks/course_template/duplicate_course.php', array('courseid' => $courseid, 'pagetype' => $pagetype));
$PAGE->set_pagelayout('admin');
$PAGE->set_course($course);
$PAGE->set_title($headingstr);
$PAGE->set_heading($headingstr);
$PAGE->navbar->add(get_string('coursetemplates', 'block_course_template'));
$PAGE->navbar->add($headingstr);
// Call the form.
if (!empty($_POST['submitbutton'])) {
    require_sesskey();

    $courseid = required_param('courseid', PARAM_INT);
    $fullname = required_param('fullname', PARAM_TEXT);
    $shortname = required_param('shortname', PARAM_TEXT);
    $categoryid = required_param('category', PARAM_INT);
    $visibility = required_param('visibility', PARAM_TEXT);
    $enrolmentcopy = required_param('enrolment', PARAM_TEXT);
    $categoryctx = context_coursecat::instance($course->category);

    try {
        // Assign the user a role that can do a course restore.
        role_assign(1, $USER->id, $categoryctx->id);
        // Duplicate old course.
        $newcourse = course_template_duplicate_course($courseid, $fullname, $shortname, $categoryid, (int)$visibility, (int)$enrolmentcopy);
        // Unassign the role now.
        role_unassign(1, $USER->id, $categoryctx->id);
        // Then assign teacher role in the new course so user can access it.
        role_assign(3, $USER->id, context_course::instance($newcourse->id));
        // Output notification and redirect to edit page.
        totara_set_notification(get_string('duplicatecoursesuccess', 'block_course_template'),
            new moodle_url('/course/edit.php', array('id' => $newcourse->id)),
            array('class' => 'notifysuccess'));
    } catch (Exception $e) {
        print_error($e->getMessage());
    }

} else {
    $mform = new duplicate_course_form(null, array('course' => $course, 'pagetype' => $pagetype));
    if ($mform->is_cancelled()) {
        redirect($CFG->wwwroot.'/course/'.$pagetype.'.php?id='.$courseid);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->heading($headingstr);

    $mform->display();

    echo $OUTPUT->footer();
}