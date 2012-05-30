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
 * <one-line descriptionet
 *
 * <longer description [optional]>
 *
 * @package      <package name>
 * @subpackage   <subpackage name>
 * @copyright    2012 Catalyst-IT Europe
 * @author       Joby Harding <joby.harding@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('new_course_form.php');
require_once('lib.php');
require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once('coursetemplate_restore_ui_stage.class.php');

$referer = optional_param('referer', null, PARAM_TEXT);
if ($referer === null) {
   $referer = get_referer(false);
}

require_login();
$cxt = get_context_instance(CONTEXT_SYSTEM);

require_capability('block/course_template:createcourse', $cxt);

$templateid = optional_param('template', -1, PARAM_INT);

// if there aren't any templates then redirect
$numtemps = $DB->count_records('block_course_template');
if ($numtemps < 1) {
    redirect(get_referer(), get_string('notemplates', 'block_course_template'));
}

//
// Page settings
//
$PAGE->set_url('/blocks/course_template/newcourse.php');
$PAGE->set_context($cxt);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('newcourse', 'block_course_template'));
$PAGE->set_heading(get_string('newcoursefromtemp', 'block_course_template'));

// mform instance
$mform = new block_course_template_new_course_form(null, array('template' => $templateid, 'referer' => $referer));

//
// Handle form input
//
if ($mform->is_submitted() && $mform->is_validated()) {

    if ($data = $mform->get_data()) {
        require_sesskey();

        // get course_template record
        if (!$coursetemplate = $DB->get_record('block_course_template', array('id' => $data->template))) {
            print_error(get_string('error:notemplate', 'block_course_template', $data->template));
        }

        // get the restore file
        $fs = get_file_storage();
        $restorefile = $fs->get_file_by_hash(sha1("/$cxt->id/block_course_template/backupfile/$coursetemplate->id/$coursetemplate->file"));

        // copy file into the temp directory for extraction
        $tmpcopyname = md5($coursetemplate->file);
        if (!$tmpcopy = $restorefile->copy_content_to($CFG->tempdir . '/backup/' . $tmpcopyname)) {
            print_error('error:movearchive', 'block_course_template');
        }

        /////////////////////
        //                 //
        // Restore process //
        //                 //
        /////////////////////

        //*********************
        $courseid = restore_dbops::create_new_course($data->fullname, $data->shortname, $data->category);
        $newcourse = $DB->get_record('course', array('id' => $courseid));
        // set the start date

        $fb = get_file_packer();
        $tmpdirnewname = restore_controller::get_tempdir_name($cxt->id, $USER->id);
        $tmpdirpath =  $CFG->tempdir . '/backup/' . $tmpdirnewname . '/';
        $outcome = $fb->extract_to_pathname($CFG->tempdir . '/backup/' . $tmpcopyname, $tmpdirpath);

        if ($outcome) {
            fulldelete($tmpcopyname);
        } else {
            print_error('error:extractarchive', 'block_course_template');
        }

        // sanity check - make sure extracted directory is there
        $tempdestination = $tmpdirpath;
        if (!file_exists($tempdestination) || !is_dir($tempdestination)) {
            print_error('error:nodirectory'); // shouldn't happen ever
        }

        $restoretarget = backup::TARGET_NEW_COURSE;

        $rc = new restore_controller($tmpdirnewname, $courseid, backup::INTERACTIVE_YES, backup::MODE_IMPORT, $USER->id, $restoretarget);
        $plan = $rc->get_plan();
        $tasks = $plan->get_tasks();

        // iterate through the tasks in order to change course details
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
                        case 'setting_course_course_startdate' :
                            $setting->set_value($data->startdate);
                            break;
                    }
                }
            }
        }

        // Convert the backup if required.... it should NEVER happed
        if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
            $rc->convert();
        }

        // Mark the UI finished.
        $rc->finish_ui();
        // Execute prechecks
        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                fulldelete($tempdestination);

                echo $OUTPUT->header();
                echo $renderer->precheck_notices($precheckresults);
                echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id'=>$course->id)));
                echo $OUTPUT->footer();
                die();
            }
        } else {
            if ($restoretarget == backup::TARGET_CURRENT_DELETING || $restoretarget == backup::TARGET_EXISTING_DELETING) {
                restore_dbops::delete_course_content($courseid);
            }
            // Execute the restore
            $rc->execute_plan();
        }

        // Delete the temp directory now
        fulldelete($tempdestination);

        //***********************

    }
}

//
// Form cancelled
//
if ($mform->is_cancelled()) {
    redirect($referer);
}

//
// Start page output
//
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('newcoursefromtemp', 'block_course_template'));

// render the form
$mform->display();

echo $OUTPUT->footer();
