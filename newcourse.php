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
 * @package      blocks
 * @subpackage   course_template
 * @copyright    2012 Catalyst-IT Europe
 * @author       Joby Harding <joby.harding@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('course_form.php');
require_once('lib.php');
require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

require_login();

$templateid = optional_param('t', 0, PARAM_INT);
$courseid = optional_param('c', 0, PARAM_INT);
$context = get_context_instance(CONTEXT_SYSTEM);

require_capability('block/course_template:createcourse', $context);

$referer = optional_param('referer', null, PARAM_TEXT);
if ($referer === null) {
   $referer = get_referer(false);
}

$numtemps = $DB->count_records('block_course_template');
if ($numtemps < 1) {
    redirect(get_referer(), get_string('notemplates', 'block_course_template'));
}

if ($courseid === 0) {
    $insert = false;
} else {
    if ($courseid == 1) {
        redirect($referer, get_string('error:sitecourse', 'block_course_template'));
    }
    $insert = true;
}

$headingstr = $insert != 1 ? get_string('newcoursefromtemp', 'block_course_template') : get_string('importintocourse', 'block_course_template');

$PAGE->set_url('/blocks/course_template/newcourse.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($headingstr);
$PAGE->set_heading($headingstr);

$mform = new block_course_template_course_form(
    null,
    array(
        'template' => $templateid,
        'referer' => $referer,
        'courseid' => $courseid
    )
);

if ($mform->is_cancelled()) {
    redirect($referer);
}

if ($data = $mform->get_data()) {

    if (!$coursetemplate = $DB->get_record('block_course_template', array('id' => $data->template))) {
        print_error(get_string('error:notemplate', 'block_course_template', $data->template));
    }

    $fs = get_file_storage();
    $restorefile = $fs->get_file_by_hash(sha1("/$context->id/block_course_template/backupfile/$coursetemplate->id/$coursetemplate->file"));

    $tmpcopyname = md5($coursetemplate->file);
    if (!$tmpcopy = $restorefile->copy_content_to($CFG->tempdir . '/backup/' . $tmpcopyname)) {
        print_error('error:movearchive', 'block_course_template');
    }

    if (!$insert) {
        $courseid = restore_dbops::create_new_course($data->fullname, $data->shortname, $data->category);
    }

    $fb = get_file_packer();
    $tmpdirnewname = restore_controller::get_tempdir_name($context->id, $USER->id);
    $tmpdirpath =  $CFG->tempdir . '/backup/' . $tmpdirnewname . '/';
    $outcome = $fb->extract_to_pathname($CFG->tempdir . '/backup/' . $tmpcopyname, $tmpdirpath);

    if ($outcome) {
        fulldelete($tmpcopyname);
    } else {
        print_error('error:extractarchive', 'block_course_template');
    }

    $tempdestination = $tmpdirpath;
    if (!file_exists($tempdestination) || !is_dir($tempdestination)) {
        print_error('error:nodirectory');
    }

    $restoretarget = $insert != 1 ? backup::TARGET_NEW_COURSE : backup::TARGET_EXISTING_ADDING;

    $rc = new restore_controller($tmpdirnewname, $courseid, backup::INTERACTIVE_YES, backup::MODE_IMPORT, $USER->id, $restoretarget);

    if (!$insert) {
        $plan = $rc->get_plan();
        $tasks = $plan->get_tasks();

        foreach ($tasks as &$task) {
            if (!($task instanceof restore_root_task)) {
                $settings = $task->get_settings();
                foreach ($settings as &$setting) {
                    $name = $setting->get_ui_name();

                    switch ($name) {
                        case 'setting_course_course_fullname' :
                            $setting->set_value($data->fullname);
                            break;
                        case 'setting_course_course_shortname' :
                            $setting->set_value($data->shortname);
                            break;
                        case 'setting_course_course_id' :
                            $setting->set_value($data->idnumber);
                            break;
                        case 'setting_course_course_startdate' :
                            $setting->set_value($data->startdate);
                            break;
                    }
                }
            }
        }
    }

    if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
        $rc->convert();
    }

    $rc->finish_ui();

    $rc->execute_precheck();

    $rc->execute_plan();

    fulldelete($tempdestination);

    if (!$insert) {
        $updaterec = new stdClass();
        $updaterec->id = $courseid;
        $updaterec->idnumber = $data->idnumber;
        $DB->update_record('course', $updaterec);
    }

    if ($insert) {
        $message = get_string('importedsuccessfully', 'block_course_template');
    } else {
        $message = get_string('createdsuccessfully', 'block_course_template');
    }

    redirect(new moodle_url('/course/view.php', array('id' => $courseid)), $message);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($headingstr);

$mform->display();

echo $OUTPUT->footer();
