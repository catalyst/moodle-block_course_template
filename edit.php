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
require_once('edit_form.php');
require_once('lib.php');

require_login();

$context = get_context_instance(CONTEXT_SYSTEM);

require_capability('block/course_template:edit', $context);

$basecourseid = optional_param('c', 0, PARAM_INT);
$templateid = optional_param('t', 0, PARAM_INT);

if ($basecourseid === 0  && $templateid === 0) {
    redirect($CFG->wwwroot, get_string('error:paramrequired', 'block_course_template'));
}

if ($basecourseid !== 0) {
    $redirecturl = new moodle_url('/course/view.php', array('id' => $basecourseid));
} else {
    $basecourseid = $DB->get_field('block_course_template', 'course', array('id' => 'templateid'));
    $redirecturl = new moodle_url('/block_course_template/view.php');
}

$PAGE->set_url('/blocks/course_template/edit.php', array('c' => $basecourseid, 't' => $templateid));
$PAGE->set_context(get_context_instance(CONTEXT_COURSE, $COURSE->id));
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('edittmptitle', 'block_course_template'));
$PAGE->set_heading(get_string('edittmptitle', 'block_course_template'));

$basecourse = $DB->get_record('course', array('id' => $basecourseid));

$mform = new course_template_edit_form($PAGE->url, array('basecourse' => $basecourse, 'templateid' => $templateid));

if ($mform->is_cancelled()) {
    redirect($redirecturl);
}

// if editing an existing template
if ($templateid !== 0) {
    if (!$templaterec = $DB->get_record('block_course_template', array('id' => $templateid))) {
        redirect(get_string('error:notemplate', 'block_course_template', $templateid));
    } else {
        // format data to populate form
        $toform = clone $templaterec;
        $toform->description = array('text' => $toform->description);
        // get tags for course and compress
        $currenttags = $DB->get_records_sql("SELECT tag.rawname FROM {$CFG->prefix}block_course_template_tag_instance ins
                                                                JOIN {$CFG->prefix}block_course_template_tag tag ON ins.tag = tag.id
                                             WHERE ins.template = {$templaterec->id}");
        if (!empty($currenttags)) {
            $toform->tags = implode(
                array_map(function($n){
                            return $n->rawname;
                        },
                    $currenttags
                ),
                ', '
            );
        }

        unset($toform->created);
        unset($toform->timemodified);
        unset($toform->screenshot);
    }
}

$itemid = (isset($toform)) ? $toform->id : null;
$draftitemid = file_get_submitted_draft_itemid('screenshot');
file_prepare_draft_area($draftitemid, $context->id, 'block_course_template', 'screenshot', $itemid, array('subdirs' => 0, 'maxfiles' => 1));

$toform->screenshot = $draftitemid;

$mform->set_data($toform);

if ($data = $mform->get_data()) {

    $success = true;
    $errormsg = '';

    require_sesskey();

    // New/updated template record
    $tempobj = new stdClass();
    $tempobj->name = str_replace(' ', '_', strtolower($data->name));
    $tempobj->rawname = $data->name;
    $tempobj->description = $data->description['text'];
    $tempobj->course = $data->course;
    $tempobj->file = null;
    $tempobj->screenshot = null;
    $tempobj->created = time();
    $tempobj->timemodified = time();

    if ($templateid !== 0) {
        // update an existing record
        $tempobj->id = $templaterec->id;
        $tempobj->created = $templaterec->created;
        if (!$DB->update_record('block_course_template', $tempobj)) {
            redirect($redirecturl, get_string('error:couldntupdate', 'block_course_template'));
        }
    } else {
        // insert new record
        if (!$tempobj->id = $DB->insert_record('block_course_template', $tempobj)) {
            redirect($redirecturl, get_string('error:couldntinsert', 'block_course_template'));
        }
    }

    // Create backup .mbz file (we only do this if this is a new template record)
    // we need the template id for storage in the filesystem.
    if ($templateid === 0) {
        if (!$backupfile = course_template_create_archive($tempobj, $USER->id)) {
            // TODO delete the course_template record
            redirect($redirecturl, get_string('error:createtemplatefile', 'block_course_template', $tempobj->id));
        }

        // update the template db record to store filename
        $tempobj->file = $backupfile->get_filename();
        $DB->set_field(
            'block_course_template',
            'file',
            $tempobj->file,
            array('id' => $tempobj->id)
        );
    }

    // Screenshot
    $fs = get_file_storage();
    $draftid = file_get_submitted_draft_itemid('screenshot');

    $tempobj->screenshot = $mform->get_new_filename('screenshot');
    file_save_draft_area_files($draftid,
        $context->id,
        'block_course_template',
        'screenshot',
        $tempobj->id,
        array(
            'subdirs' => false,
            'maxfiles' => 1
        )
    );

    // update the course_template record to store filename
    $DB->set_field(
        'block_course_template',
        'screenshot',
        $tempobj->screenshot,
        array('id' => $tempobj->id)
    );

    // Tag and tag instance records
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
            if (preg_match('/^\s+$/', $tagfiltered) === 0 && $tagfiltered != '') {
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

    // Remove any instance records which are no longer required
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
        redirect($redirecturl, get_string('savesuccess', 'block_course_template'));
    }
}

echo $OUTPUT->header();
// name of the course we are basing the template on
$headingtxt = null;
if ($templateid === 0) {
    // new template
    $basecoursename = $DB->get_field('course', 'fullname', array('id' => $basecourseid));
    $headingtxt = get_string('newtemplatefrom', 'block_course_template', format_string($basecoursename));
} else {
    // edit existing tamplate
    $templatename = $DB->get_field('block_course_template', 'name', array('id' => $templateid));
    $headingtxt = get_string('edittemplate', 'block_course_template', format_string($templatename));
}
echo $OUTPUT->heading($headingtxt);

$mform->display();

echo $OUTPUT->footer();
