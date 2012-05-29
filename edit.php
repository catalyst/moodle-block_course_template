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
* For most people, just lists the course categories
* Allows the admin to create, delete and rename course categories
*
* @copyright 1999 Martin Dougiamas  http://dougiamas.com
* @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
* @package course
*/

require_once('../../config.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once('edit_template_form.php');
require_once('lib.php');

require_login();
$cxt = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/course_template:edit', $cxt);

$basecourseid = optional_param('course', -1, PARAM_INT);
$templateid = optional_param('template', -1, PARAM_INT);
$referer = optional_param('referer', null, PARAM_TEXT);

if ($basecourseid === -1  && $templateid === -1) {
    print_error(get_string('error:paramrequired', 'block_course_template'));
}

if ($referer === null) {
    $referer = get_referer(false);
}

//
// Page settings
//
$PAGE->set_url('/blocks/course_template/edit.php');
$PAGE->set_context(get_context_instance(CONTEXT_COURSE, $COURSE->id));
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('edittmptitle', 'block_course_template'));
$PAGE->set_heading(get_string('edittmptitle', 'block_course_template'));

// if a template id was passed then get corresponding record
if ($templateid !== -1) {
    if (!$currentrec = $DB->get_record('block_course_template', array('id' => $templateid))) {
        print_error(get_string('error:notemplate', 'block_course_template', $templateid));
    } else {
        // set base course id
        $basecourseid = $currentrec->course;
        // format data to populate form
        $toform = clone $currentrec;
        $toform->description = array('text' => $toform->description);
        // get tags for course and compress
        $currenttags = $DB->get_records_sql("SELECT tag.rawname FROM {$CFG->prefix}block_course_template_tag_instance ins
                                                                JOIN {$CFG->prefix}block_course_template_tag tag ON ins.tag = tag.id
                                             WHERE ins.template = {$currentrec->id}");
        if (!empty($currenttags)) {
            $toform->tags = implode(array_map(function($n){return $n->rawname;}, $currenttags), ', ');
        }

        unset($toform->created);
        unset($toform->timemodified);
        unset($toform->screenshot);
    }
}

//
// Navigation
//
// set navigation (so that breadcrumb is generated correctly)
$coursenode = $PAGE->navigation->find($basecourseid, navigation_node::TYPE_COURSE);
$nodetitle = ($templateid !== -1) ? get_string('updatetemplate', 'block_course_template') : get_string('createtemplate', 'block_course_template');
$editnode = $coursenode->add($nodetitle);
$editnode->make_active();

// mform instance
$mform = new block_course_template_add_template_form(null, array('basecourseid' => $basecourseid, 'templateid' => $templateid, 'referer' => $referer));

//$tmpid = (isset($currentrec)) ? $currentrec->id : null;     // the template id (if we have one)
if (!isset($toform)) {
    $toform = new stdClass();
    $toform->id = null;
}

$draftitemid = file_get_submitted_draft_itemid('screenshot');
file_prepare_draft_area($draftitemid, $cxt->id, 'block_course_template', 'screenshot', $toform->id, array('subdirs' => 0, 'maxfiles' => 1));
// set draft id
$toform->screenshot = $draftitemid;
// repopulate form
$mform->set_data($toform);

//
// Handle form input
//
if ($mform->is_submitted() && $mform->is_validated()) {
    if ($data = $mform->get_data()) {

        $success = true;
        $errormsg = '';

        require_sesskey();

        //
        // Template record
        //
        $tempobj = new stdClass();
        $tempobj->file = null;
        $tempobj->name = str_replace(' ', '_', strtolower($data->name));
        $tempobj->rawname = $data->name;
        $tempobj->description = $data->description['text'];
        $tempobj->course = $data->course;
        $tempobj->screenshot = null;
        $tempobj->created = time();
        $tempobj->timemodified = time();

        if ($templateid !== -1) {
            // update existing record
            $tempobj->id = $currentrec->id;
            $tempobj->created = $currentrec->created;
            if (!$DB->update_record('block_course_template', $tempobj)) {
                print_error(get_string('error:couldntupdate', 'block_course_template'));
            }
        } else {
            // insert new record
            if (!$tempobj->id = $DB->insert_record('block_course_template', $tempobj)) {
                print_error(get_string('error:couldntinsert', 'block_course_template'));
            }
        }

        //
        // Create backup .mbz file
        //
        // we only do this if this is a new template record
        if ($templateid === -1) {
            $course = $DB->get_record('course', array('id' => $tempobj->course));
            if (!$backupfile = block_course_template_create_template_file($tempobj, $USER->id)) {
                print_error(get_string('error:createtemplatefile', 'block_course_template', $tempobj->id));
            }
        }
        // update the template db record to store filename
        $tempobj->file = $backupfile->get_filename();

        $DB->set_field(
            'block_course_template',
            'file',
            $tempobj->file,
            array('id' => $tempobj->id)
        );

        //
        // Screenshot file
        //
        // save the image file
        $fs = get_file_storage();
        $draftid = file_get_submitted_draft_itemid('screenshot');

        $tempobj->screenshot = $mform->get_new_filename('screenshot');
        file_save_draft_area_files($draftid,
            $cxt->id,
            'block_course_template',
            'screenshot',
            $tempobj->id,
            array(
                'subdirs' => false,
                'maxfiles' => 1
            )
        );

        // update the course_template record to store file id
        $DB->set_field(
            'block_course_template',
            'screenshot',
            $tempobj->screenshot,
            array('id' => $tempobj->id)
        );

        //
        // Tag and tag instance records
        //
        // get the existing tag instances for this template - we'll need these later...
        $oldtags = $DB->get_records('block_course_template_tag_instance', array('template' => $tempobj->id));
        // this to store the tag instances we'll create for values submitted in the form
        $currenttags = array();

        // save tags
        if (!empty($data->tags)) {
            $tags = array_map(function($n){return trim($n);}, explode(',', $data->tags));
            foreach ($tags as $tag) {
                // TODO robust filtering form malicious input
                $tagfiltered = strtolower(preg_replace('/\s+/', ' ', $tag));
                // does the tag contain something sensible?
                if (preg_match('/^\s+$/', $tagfiltered) == 0 && $tagfiltered != '') {
                    // insert tag into database if it doesn't already exist
                    $tagobj = new stdClass();
                    $tagobj->name = preg_replace('/\s/', '_', $tagfiltered);
                    $tagobj->rawname = ucfirst($tagfiltered);
                    $tagobj->timemodified = time();

                    // insert a tag record
                    if (!$tagobj->id = $DB->get_field('block_course_template_tag', 'id',  array('rawname' => $tagobj->rawname))) {
                        if (!$tagobj->id = $DB->insert_record('block_course_template_tag', $tagobj)) {
                            $success = false;
                            $errormsg = get_string('error:couldnotinserttag', 'block_course_template', $tagobj->rawname);
                        }
                    }

                    // create a tag instance record
                    $instobj = new stdClass();
                    $instobj->tag = $tagobj->id;
                    $instobj->template = $tempobj->id;
                    $instobj->timemodified = time();

                    if (!$instobj->id = $DB->get_field('block_course_template_tag_instance', 'id', array('tag' => $instobj->tag, 'template' => $instobj->template))) {
                        if (!$instobj->id = $DB->insert_record('block_course_template_tag_instance', $instobj)) {
                            $success = false;
                            $errormsg = get_string('error:couldnotinserttag', 'block_course_template', $tagobj->rawname);
                        }
                    }
                    // keep hold of the tag instance id
                    $currenttags[] = $instobj->id;
                }
            } // END $tags as $tag
        }

        //
        // Remove any instance records which are no longer required
        //
        $oldtags = array_keys($oldtags);
        // any old tag instances which are not in the current tags array need deleting
        $deleteins = array_diff($oldtags, $currenttags);
        if (!empty($deleteins)) {
            $success = $success && block_course_template_delete_tag_instances($deleteins);
            $error = get_string('error:deleteinst', 'block_course_template');
        }

        if (!$success) {
            print_error($error);
        } else {
            redirect($referer, get_string('savesuccess', 'block_course_template'));
        }
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
// name of the course we are basing the template on
$headingtxt = null;
if ($templateid === -1) {
    // new template
    $basecoursename = $DB->get_field('course', 'fullname', array('id' => $basecourseid));
    $headingtxt = get_string('newtemplatefrom', 'block_course_template', format_string($basecoursename));
} else {
    // edit existing tamplate
    $templatename = $DB->get_field('block_course_template', 'name', array('id' => $templateid));
    $headingtxt = get_string('edittemplate', 'block_course_template', format_string($templatename));
}
echo $OUTPUT->heading($headingtxt);

// render the form
$mform->display();

echo $OUTPUT->footer();
