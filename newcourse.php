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

        // The following are based on the Moodle restore functionality. Specifically
        // the restore stage classes. Code below is based on __construct() and process()
        // methods of these classes found in backup/util/ui/restore_ui_stage.class.php
/*
        //
        // restore_ui_stage_confirm
        //
        $fb = get_file_packer();
        $tmpdirnewname = restore_controller::get_tempdir_name($cxt->id, $USER->id);
        $tmpdirpath =  $CFG->tempdir . '/backup/' . $tmpdirnewname . '/';
        $outcome = $fb->extract_to_pathname($CFG->tempdir . '/backup/' . $tmpcopyname, $tmpdirpath);

        if ($outcome) {
            fulldelete($tmpcopyname);
        } else {
            print_error('error:extractarchive', 'block_course_template');
        }

        //
        // restore_ui_stage_destination
        //
        $fullname = $data->fullname;
        $shortname = $data->shortname;
        $courseid = restore_dbops::create_new_course($fullname, $shortname, $data->category);
        $newcourse = $DB->get_record('course', array('id' => $courseid));

        //
        // restore_ui_stage_settings
        //
        // do nothing from this stage as we are keeping settings already defined in the .mbz package

        //
        // restore_ui_stage_schema
        //
        $info = backup_general_helper::get_backup_information($tmpdirnewname);
        $controller = new restore_controller(
            $tmpdirnewname,
            $courseid,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );

        $plan = $controller->get_plan();
        $info = $controller->get_info();
        $task = restore_factory::get_restore_course_task($info->course, $courseid);
        print_object($task->get_settings());
        $plan->add_task($task);
        print_object($newcourse);
*/
  //$blocks = backup_general_helper::get_blocks_from_path($task->get_taskbasepath);

        $stages = array(
            restore_ui::STAGE_CONFIRM,
            restore_ui::STAGE_DESTINATION,
            restore_ui::STAGE_SETTINGS,
            restore_ui::STAGE_SCHEMA,
            restore_ui::STAGE_REVIEW,
            restore_ui::STAGE_PROCESS,
            restore_ui::STAGE_COMPLETE
        );

        print_object($stages);

        //
        // Set parameters expected to come from a user's interaction
        //
        $_POST['filename'] = clean_param($tmpcopyname, PARAM_FILE);
        $_POST['stage'] = null;
        $_POST['contextid'] = null;
        $_POST['filepath'] = null;

        foreach ($stages as $stage) {
        echo "STAGE {$stage}-------------<br />";
        print_object($_POST);
        echo "...........................<br />";
            if ($stage & restore_ui::STAGE_CONFIRM + restore_ui::STAGE_DESTINATION) {
                if ($stage == restore_ui::STAGE_DESTINATION) {
                    $_POST['filepath'] = $outcome;
                    $_POST['contextid'] = $cxt->id;
                    $_POST['stage'] = $stage;
                }
                $restore = restore_ui::engage_independent_stage($stage, $cxt->id);
            } else {
                // insert required params expected from form input
                $restore = restore_ui::engage_independent_stage($stage, $cxt->id);
                if ($restore->process()) {
                    $rc = new restore_controller(
                        $restore->get_filepath(),
                        $restore->get_course_id(),
                        backup::INTERACTIVE_NO,
                        backup::MODE_GENERAL,
                        $USER->id,
                        $restore->get_target()
                    );
                }
            }

            $outcome = $restore->process();
            if (!$restore->is_independent()) {
                if ($restore->get_stage() == restore_ui::STAGE_PROCESS && !$restore->requires_substage()) {
                    try {
                        $restore->execute();
                    } catch (Exception $e) {
                        $restore->cleanup();
                        throw $e;
                    }
                } else {
                    $restore->save_controller();
                }
            }
        }
/*
        $controller = new restore_controller(
            $tmpdirnewname,
            1,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
*/


        die;
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
