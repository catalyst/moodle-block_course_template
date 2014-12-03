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
 * Create and edit course templates.
 *
 * @package      blocks
 * @subpackage   course_template
 * @copyright    2012 Catalyst-IT Europe
 * @author       Joby Harding <joby.harding@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/blocks/course_template/edit_form.php');
require_once($CFG->dirroot . '/blocks/course_template/locallib.php');

require_login();

$basecourseid = optional_param('c', 0, PARAM_INT);
$currentcourse = optional_param('cc', 0, PARAM_INT);
$templateid = optional_param('t', 0, PARAM_INT);
if ($basecourseid == 1) {
    totara_set_notification(get_string('error:sitecourse', 'block_course_template'), $CFG->wwwroot);
}

if ($basecourseid === 0  && $templateid === 0) {
    totara_set_notification(get_string('error:paramrequired', 'block_course_template'), $CFG->wwwroot);
}

if ($basecourseid !== 0) {
    $redirecturl = new moodle_url('/blocks/course_template/view.php', array('c' => $basecourseid));
} else {
    $basecourseid = $DB->get_field('block_course_template', 'course', array('id' => $templateid));
    $redirecturl = new moodle_url('/blocks/course_template/view.php');
}

if ($templateid === 0) {
    $basecoursename = $DB->get_field('course', 'fullname', array('id' => $basecourseid));
    $titletxt = get_string('newcoursetemplate', 'block_course_template');
    $headingtxt = $titletxt . ' '  . get_string('basedon', 'block_course_template', $basecoursename);
} else {
    $templatename = $DB->get_field('block_course_template', 'name', array('id' => $templateid));
    $titletxt = get_string('edittemplate', 'block_course_template');
    $headingtxt = $titletxt . ' \'' . $templatename . '\'';
}

// Templates stored under system context but cap to create them is at course level.
$syscontext = get_context_instance(CONTEXT_SYSTEM);
$coursecontext = get_context_instance(CONTEXT_COURSE, $basecourseid);
require_capability('block/course_template:edit', $coursecontext);

$PAGE->set_url('/blocks/course_template/edit.php', array('c' => $basecourseid, 't' => $templateid));
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('course');

// Must call format_string() after set_context().
$titletxt = format_string($titletxt);
$headingtxt = format_string($headingtxt);

$PAGE->set_title($titletxt);
$PAGE->set_heading($titletxt);
$PAGE->navbar->add($titletxt);

$basecourse = $DB->get_record('course', array('id' => $basecourseid));

$taglist = $DB->get_records('block_course_template_tag');
if ($taglist) {
    usort($taglist,
        function($a, $b) {
            return strcmp($a->name, $b->name);
        }
    );
    $taglist = array_map(
        function($n) {
            return $n->name;
        },
        $taglist
    );
    $taglist = implode($taglist, ', ');
    $taglist = format_string($taglist);
}

$renderer = $PAGE->get_renderer('block_course_template');
$basedontext = $renderer->display_form_basedon_course($basecourse);

$mform = new course_template_edit_form(
    $PAGE->url,
    array(
        'basecourse'    => $basecourse,
        'templateid'    => $templateid,
        'taglist'       => $taglist,
        'basedontext'   => $basedontext,
        'currentcourse' => $currentcourse
    )
);

if ($mform->is_cancelled()) {
    redirect($redirecturl);
}

// Get existing template data to repopulate form.
if ($templateid !== 0) {

    if (!$templaterec = $DB->get_record('block_course_template', array('id' => $templateid))) {
        totara_set_notification(get_string('error:notemplate', 'block_course_template', $templateid), $redirecturl);
    } else {
        // Format data to populate form.
        $toform = clone $templaterec;
        $toform->description = array('text' => $toform->description);
        // Get tags for course and compress.
        $currenttags = $DB->get_records_sql("SELECT tag.name FROM {block_course_template_tag_in} ins
                                                                JOIN {block_course_template_tag} tag ON ins.tag = tag.id
                                             WHERE ins.template = ?", array($templaterec->id));
        if ($currenttags) {
            $toform->tags = array_map(
                function($n) {
                    return $n->name;
                },
                $currenttags
            );

            $toform->tags = implode($toform->tags, ', ');
        }

        unset($toform->timecreated);
        unset($toform->timemodified);
        unset($toform->screenshot);

        $itemid = (isset($toform)) ? $toform->id : null;
        $draftitemid = file_get_submitted_draft_itemid('screenshot');

        file_prepare_draft_area(
            $draftitemid,
            $syscontext->id,
            'block_course_template',
            'screenshot',
            $itemid,
            array(
                'subdirs' => 0,
                'maxfiles' => 1
            )
        );

        $toform->screenshot = $draftitemid;

        $mform->set_data($toform);
    }
}

if ($data = $mform->get_data()) {

    $transaction = $DB->start_delegated_transaction();
    $success = true;
    $errormsg = '';

    require_sesskey();

    // New/updated template record.
    $tempobj = new stdClass();
    $tempobj->name = $data->name;
    $tempobj->description = $data->description['text'];
    $tempobj->course = $basecourseid;
    $tempobj->file = null;
    $tempobj->screenshot = null;
    $tempobj->timecreated = time();
    $tempobj->timemodified = time();

    if ($templateid !== 0) {
        // Update an existing record.
        $tempobj->id = $templaterec->id;
        $tempobj->timecreated = $templaterec->timecreated;
        if (!$DB->update_record('block_course_template', $tempobj)) {
            totara_set_notification(get_string('error:couldntupdate', 'block_course_template'), $redirecturl);
        }
    } else {
        // Insert new record.
        if (!$tempobj->id = $DB->insert_record('block_course_template', $tempobj)) {
            totara_set_notification(get_string('error:couldntinsert', 'block_course_template'), $redirecturl);
        }
    }

    // Create backup .mbz file (we only do this if this is a new template record)
    // we need the template id for storage in the filesystem.
    if ($templateid === 0) {
        if (!$backupfile = course_template_create_archive($tempobj, $USER->id)) {
            $DB->delete_records('block_course_template', array('id' => $tempobj->id));
            totara_set_notification(get_string('error:createtemplatefile', 'block_course_template', $tempobj->id), $redirecturl);
        }

        // Update the template db record to store filename.
        $tempobj->file = $backupfile->get_filename();
        $DB->set_field(
            'block_course_template',
            'file',
            $tempobj->file,
            array('id' => $tempobj->id)
        );
    }

    // Screenshot.
    $fs = get_file_storage();
    $draftid = file_get_submitted_draft_itemid('screenshot');

    // Delete any existing files.
    $existingfiles = $fs->get_area_files(
        $syscontext->id,
        'block_course_template',
        'screenshot',
        $tempobj->id
    );

    if (!empty($existingfiles)) {
        foreach ($existingfiles as $file) {
            $file->delete();
        }
    }

    $tempobj->screenshot = $mform->get_new_filename('screenshot');
    file_save_draft_area_files($draftid,
        $syscontext->id,
        'block_course_template',
        'screenshot',
        $tempobj->id,
        array(
            'subdirs' => false,
            'maxfiles' => 1
        )
    );

    // Update the course_template record to store filename.
    $DB->set_field(
        'block_course_template',
        'screenshot',
        $tempobj->screenshot,
        array('id' => $tempobj->id)
    );

    // Tag and tag instance records.
    $oldtags = $DB->get_records('block_course_template_tag_in', array('template' => $tempobj->id));

    // This to store the tag instances we'll create for values submitted in the form.
    $currenttags = array();

    // Save tags.
    if (!empty($data->tags)) {

        $tags = explode(',', $data->tags);
        $tags = array_map(
            function($n) {
                return trim($n);
            },
            $tags
        );

        foreach ($tags as $tag) {

            $tagfiltered = strtolower(preg_replace('/\s+/', ' ', $tag));

            if (preg_match('/^\s+$/', $tagfiltered) === 0 && $tagfiltered != '') {

                // Insert tag into database if it doesn't already exist.
                $tagobj = new stdClass();
                $tagobj->name = preg_replace('/\s/', '_', $tagfiltered);
                $tagobj->name = ucfirst($tagfiltered);
                $tagobj->timemodified = time();

                $tagobj->id = $DB->get_field('block_course_template_tag', 'id', array('name' => $tagobj->name));
                if (!$tagobj->id) {
                    if (!$tagobj->id = $DB->insert_record('block_course_template_tag', $tagobj)) {
                        $success = false;
                        $errormsg = get_string('error:couldnotinserttag', 'block_course_template', $tagobj->name);
                    }
                }

                // Create a tag instance record.
                $instobj = new stdClass();
                $instobj->tag = $tagobj->id;
                $instobj->template = $tempobj->id;
                $instobj->timemodified = time();
                $instobj->id = $DB->get_field(
                    'block_course_template_tag_in',
                    'id',
                    array(
                        'tag' => $instobj->tag,
                        'template' => $instobj->template
                    )
                );

                if (!$instobj->id) {
                    if (!$instobj->id = $DB->insert_record('block_course_template_tag_in', $instobj)) {
                        $success = false;
                        $errormsg = get_string('error:couldnotinserttag', 'block_course_template', $tagobj->name);
                    }
                }

                $currenttags[] = $instobj->id;
            }
        }
    }

    $oldtags = array_keys($oldtags);

    // Any old tag instances which are not in the current tags array need deleting.
    $deleteins = array_diff($oldtags, $currenttags);
    if (!empty($deleteins)) {
        $success = $success && course_template_delete_tag_instances($deleteins);
        $error = get_string('error:deleteinst', 'block_course_template');
    }

    if (!$success) {
        totara_set_notification(get_string('error:save', 'block_course_template'), $redirecturl);
    } else {
        $transaction->allow_commit();
        totara_set_notification(get_string('savesuccess', 'block_course_template'), $redirecturl, array('class' => 'notifysuccess'));
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading($headingtxt);

$mform->display();

echo $OUTPUT->footer();
