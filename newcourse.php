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
require_once($CFG->dirroot . '/backup/controller/restore_controller.class.php');

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

        print_object($data);

        // get course_template record
        if (!$coursetemplate = $DB->get_record('block_course_template', array('id' => $data->coursetemplate))) {
            print_error(get_string('error:notemplate', 'block_course_template', $data->coursetemplate));
        }
        // get the restore file
        $fs = get_file_storage();
        $restorefile = $fs->get_file(
            $cxt->id,               // context id
            'course_template',      // location
            'backupfile',           // area
            $coursetemplate->id,    // item id (id of associated course_template record)
            '/',                    // filepath
            $coursetemplate->file   // filename
        );

        // copy file into the temp directory for extraction
        if (!$tmpcopy = $restorefile->copy_content_to($CFG->tempdir . '/backup/' . md5($coursetemplate->file))) {
            print_error('error:movearchive', 'block_course_template');
        }

        //
        // Restore process
        //
        $fb = get_file_packer();
        $tempdirnewname = restore_controller::get_tempdir_name($cxt->id, $USER->id);
        print_object($tmpcopy->get_filename());
        //$extractdir = $fb->extract_to_pathname();


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
