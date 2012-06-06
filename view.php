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
 * <one-line description>
 *
 * <longer description [optional]>
 *
 * @package      blocks
 * @subpackage   course_template
 * @copyright    2012 Catalyst-IT Europe
 * @author       Joby Harding <joby.harding@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once("{$CFG->dirroot}/blocks/course_template/tag_filter_form.php");
require_once("{$CFG->dirroot}/blocks/course_template/lib.php");
require_once("{$CFG->libdir}/tablelib.php");

require_login();

$tagid      = optional_param('tag', 0, PARAM_INT);
$delete     = optional_param('d', 0, PARAM_INT);
$confirm    = optional_param('c', 0, PARAM_INT);
$templateid = optional_param('t', 0, PARAM_INT);

$syscontext = get_context_instance(CONTEXT_SYSTEM);

require_capability('block/course_template:createcourse', $syscontext);

$PAGE->set_url('/blocks/course_template/view.php');
$PAGE->set_context(get_context_instance(CONTEXT_COURSE, $PAGE->course->id));
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('pluginname', 'block_course_template'));
$PAGE->set_heading(get_string('pluginname', 'block_course_template'));

$redirecturl = new moodle_url('/block_course_template/');

$mform = new block_course_template_tag_filter_form(null, array('filtertag' => $tagid));

// Confirmed deletion?
if ($delete == 1) {
    if ($confirm == 1) {
        require_capability('block/course_template:edit', $syscontext);
        block_course_template_delete_template($templateid);
        redirect($PAGE->url, get_string('templatedeleted', 'block_course_template'));
    }
}

if ($data = $mform->get_data()) {
    if (!empty($data->tags)) {
        // only keep tags with a value of 1
        $activetags = array_filter($data->tags, function($n){if ($n == 1) return true; return false;});
        $activetags = implode('\', \'', array_keys($activetags));
    }
}

// Filter if we were a passed tag param
if ($tagid !== 0) {
    if (!$tagname = $DB->get_field('block_course_template_tag', 'name', array('id' => $tagid))) {
        redirect(get_string('error:couldntgettag', 'block_course_template', $tagid));
    } else {
        $activetags = $tagname;
    }
}

// Construct templates query
$sql  = "FROM {$CFG->prefix}block_course_template tmp";

if (isset($activetags)) {
    $sql .= " JOIN {$CFG->prefix}block_course_template_tag_instance ins ON tmp.id = ins.template
              JOIN {$CFG->prefix}block_course_template_tag tag ON ins.tag = tag.id
         WHERE tag.name IN ('{$activetags}')";
}

$templates = $DB->get_records_sql('SELECT DISTINCT tmp.id, tmp.* ' . $sql . ' ORDER BY tmp.timemodified DESC');
$numtemps = $templates ? count($templates) : 0;

echo $OUTPUT->header();
echo $OUTPUT->container_start(null, 'manage_coursetemplates');

// Handle any deletion
if ($delete == 1) {
    if ($confirm != 1) {
        require_capability('block/course_template:edit', $syscontext);
        // confirm ('delete' code at top of script due to redirect() needing to be called before page output begins)
        $confirmurl = new moodle_url($PAGE->url, array('d' => 1, 'c' => 1, 't' => $templateid));
        $cancelurl = new moodle_url($PAGE->url);
        if (!$templatename = $DB->get_field('block_course_template', 'name', array('id' => $templateid))) {
            print_error('error:notemplate', 'block_course_template', $templateid);
        }
        echo $OUTPUT->confirm(get_string('confirmdelete', 'block_course_template', $templatename), $confirmurl, $cancelurl);
    }
} else {

    $mform->display();

    $table = new flexible_table('block-course-template-table-' . $PAGE->course->id);
    $table->set_attribute('width', '100%');
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('cellpadding', '3');
    $table->set_attribute('id', 'block_course_template');
    $table->set_attribute('class', 'generaltable generalbox');
    $table->define_columns(array('preview', 'details', 'tags', 'actions'));
    $table->define_headers(array(get_string('preview', 'block_course_template'), get_string('details', 'block_course_template'), get_string('tags', 'block_course_template'), get_string('actions', 'block_course_template')));
    $table->define_baseurl("{$CFG->wwwroot}/blocks/course_template/view.php");
    $table->column_class('preview', 'screenshot');
    $table->column_class('tags', 'tags');
    $table->column_class('actions', 'actions');

    $table->setup();
    $table->pagesize(1, $numtemps);
    $startpage = $table->get_page_start();
    $pagecount = $table->get_page_size();

    // Generate entries
    if (!empty($templates)) {
        foreach ($templates as $temp) {
            $details  = html_writer::start_tag('ul');

            $details .= html_writer::start_tag('li');
            $details .= html_writer::tag('b', format_string($temp->name));
            $details .= html_writer::end_tag('li');

            $course   = format_string($DB->get_field('course', 'fullname', array('id' => $temp->course)));
            $details .= html_writer::start_tag('li');
            $details .= get_string('basedoncourse', 'block_course_template') . ': ';
            $details .= html_writer::tag('a', $course, array('href' => "{$CFG->wwwroot}/course/view.php?id={$temp->course}", 'title' => get_string('goto', 'block_course_template') . ' ' . $course));

            $details .= html_writer::start_tag('li');
            $details .= get_string('lastmodified', 'block_course_template') . ': ';
            $details .= userdate($temp->timemodified, '%A, %d %B %Y');
            $details .= html_writer::end_tag('li');

            $details .= html_writer::start_tag('li');
            $details .= format_text(get_string('description') . ': ' . $temp->description);
            $details .= html_writer::end_tag('li');

            $details .= html_writer::end_tag('ul');

            $imageurl = '';

            if (!empty($temp->screenshot)) {

                $fs = get_file_storage();
                $file = $fs->get_file(
                    $syscontext->id,
                    'block_course_template',
                    'screenshot',
                    $temp->id,
                    '/',
                    $temp->screenshot
                );

                $filename = $file->get_filename();
                $path  = '/' . $syscontext->id . '/block_course_template/screenshot/';
                $path .= $temp->id . '/' . $filename;
                $file = $fs->get_file_by_hash(sha1($path));
                $imageurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', $path, false);
            }

            $preview = html_writer::tag('img', null, array('src' => $imageurl, 'class' => 'preview', 'alt' => get_string('screenshotof', 'block_course_template') . ' ' . $temp->name));

            // Tags column
            $tags = '';

            // Get tags associated with this template
            $tagsql = "SELECT tag.* FROM {$CFG->prefix}block_course_template_tag_instance ins
                                    JOIN {$CFG->prefix}block_course_template_tag tag ON ins.tag = tag.id
                                    WHERE ins.template = {$temp->id}
                                    ORDER BY tag.name";

            $tagrecs = $DB->get_records_sql($tagsql);

            if (!empty($tagrecs)) {
                $tagslist = implode(', ', array_map(function($n){
                        $link = html_writer::link(new moodle_url(me(), array('tag' => $n->id)), format_string($n->name));
                        return $link;
                    },
                    $tagrecs
                ));
                $tags = $tagslist;
            }

            // Actions column
            $actions = '';
            $canedit = has_capability('block/course_template:edit', $syscontext);

            // Edit icon
            if ($canedit) {
                $actions  .= $OUTPUT->action_icon(new moodle_url('/blocks/course_template/edit.php',
                                    array('t' => $temp->id)),
                                    new pix_icon('t/edit', get_string('edittempdata', 'block_course_template')));

                // Delete icon
                $actions .= $OUTPUT->action_icon(new moodle_url('/blocks/course_template/view.php',
                                    array('t' => $temp->id, 'd' => 1)),
                                    new pix_icon('t/delete', get_string('delete')));
            }

            // New course from template
            $actions .= $OUTPUT->action_icon(new moodle_url('/blocks/course_template/newcourse.php',
                                array('t' => $temp->id)),
                                new pix_icon('t/restore', get_string('newcourse', 'block_course_template')));

            $table->add_data(array($preview, $details, $tags, $actions));
        }
    }

    // Output the table
    $table->print_html();
}

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
