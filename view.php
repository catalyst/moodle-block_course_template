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
require_once('tag_filter_form.php');
require_once('lib.php');
require_once("{$CFG->libdir}/tablelib.php");

require_login();
$cxt = get_context_instance(CONTEXT_SYSTEM);
require_capability('block/course_template:createcourse', $cxt);

$tagid = optional_param('tag', -1, PARAM_INT);
$delete = optional_param('delete', -1, PARAM_INT);
$confirm = optional_param('confirm', -1, PARAM_INT);
$templateid = optional_param('template', -1, PARAM_INT);

//
// Page settings
//
$PAGE->set_url('/blocks/course_template/view.php');
$PAGE->set_context(get_context_instance($cxt));
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('pluginname', 'block_course_template'));
$PAGE->set_heading(get_string('pluginname', 'block_course_template'));

//
// Handle any deletion
//
if ($delete == 1) {
    if ($confirm == 1) {
        require_capability('block/course_template:edit', $cxt);
        // delete the template
        block_course_template_delete_template($templateid);
        redirect($PAGE->url, get_string('templatedeleted', 'block_course_template'));
    }
}

// mform instance
$mform = new block_course_template_tag_filter_form(null, array('tag' => $tagid));

// tags to display (used to create query constraint)
$showtags = null;

//
// Handle any form input
//
if ($mform->is_submitted() && $mform->is_validated()) {
    if ($data = $mform->get_data()) {
        if (!empty($data->tags)) {
            // only keep tags with a value of 1
            $showtags = array_filter($data->tags, function($n){if ($n == 1) return true; return false;});
            $showtags = implode('\', \'', array_keys($showtags));
        }
    }
}

//
// Handle (if) a passed tag param
//
if ($tagid !== -1) {
    if (!$tagrawname = $DB->get_field('course_template_tag', 'name', array('id' => $tagid))) {
        print_error(get_string('error:couldntgettag', 'block_course_template', $tagid));
    } else {
        $showtags = $tagrawname;
    }
}

//
// Get records to display
//
$sql  = "FROM {$CFG->prefix}course_template tmp";

// if we have been passed some tag ids to filter by
if (!empty($showtags)) {
    $sql .= " JOIN {$CFG->prefix}course_template_tag_instance ins ON tmp.id = ins.template
              JOIN {$CFG->prefix}course_template_tag tag ON ins.tag = tag.id
         WHERE tag.name IN ('{$showtags}')";
}

// get template records - most recently modified first
$templates = $DB->get_records_sql('SELECT DISTINCT(tmp.*) ' . $sql . ' ORDER BY tmp.timemodified DESC');
$numtemps = $DB->count_records_sql('SELECT COUNT(DISTINCT tmp.*) ' . $sql);

//
// Start page output
//
echo $OUTPUT->header();

// 'namespace' CSS styles by including custom container id
echo $OUTPUT->container_start(null, 'manage_coursetemplates');


//
// Handle any deletion
//
if ($delete == 1) {
    if ($confirm != 1) {
        require_capability('block/course_template:edit', $cxt);
        // confirm ('delete' code at top of script due to redirect() needing to be called before page output begins)
        $confirmurl = new moodle_url($PAGE->url, array('delete' => 1, 'confirm' => 1, 'template' => $templateid));
        $cancelurl = new moodle_url($PAGE->url);
        if (!$templatename = $DB->get_field('course_template', 'name', array('id' => $templateid))) {
            print_error('error:notemplate', 'block_course_template', $templateid);
        }
        echo $OUTPUT->confirm(get_string('confirmdelete', 'block_course_template', $templatename), $confirmurl, $cancelurl);
    }
} else {

    //
    // Generate table
    //
    // render the form
    $mform->display();

    // display table
    $table = new flexible_table('course_templates');
    $table->set_attribute('width', '100%');
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('cellpadding', '3');
    $table->set_attribute('id', 'course_templates');
    $table->set_attribute('class', 'generaltable generalbox');

    $table->define_columns(array('preview', 'details', 'tags', 'actions'));
    $table->define_headers(array(get_string('preview', 'block_course_template'), get_string('details', 'block_course_template'), get_string('tags', 'block_course_template'), get_string('actions', 'block_course_template')));
    $table->define_baseurl("{$CFG->wwwroot}/blocks/course_template/manage.php");

    $table->pageable(true);
    $table->setup();
    // show 4 templates per page
    $table->pagesize(4, $numtemps);
    // set some col widths / styles
    $table->column_style('preview', 'width', '206px');
    $table->column_style('preview', 'text-align', 'center');
    $table->column_style('tags', 'max-width', '14em');
    $table->column_style('actions', 'width', '5em');

    // generate entries
    if (!empty($templates)) {
        foreach ($templates as $temp) {
            $details  = html_writer::start_tag('ul');

            //
            // Details column
            //
            // details header $details .= html_writer::start_tag('li');
            $details .= html_writer::tag('b', format_string($temp->name));
            $details .= html_writer::end_tag('li');

            // details based on
            $course   = format_string($DB->get_field('course', 'fullname', array('id' => $temp->course)));
            $details .= html_writer::start_tag('li');
            $details .= get_string('basedoncourse', 'block_course_template') . ': ';
            $details .= html_writer::tag('a', $course, array('href' => "{$CFG->wwwroot}/course/view.php?id={$temp->course}", 'title' => get_string('goto', 'block_course_template') . ' ' . $course));

            // last modified
            $details .= html_writer::start_tag('li');
            $details .= get_string('lastmodified', 'block_course_template') . ': ';
            $details .= userdate($temp->timemodified, '%A, %d %B %Y');
            $details .= html_writer::end_tag('li');

            // description
            $details .= html_writer::start_tag('li');
            $details .= format_text(get_string('description') . ': ' . $temp->description);
            $details .= html_writer::end_tag('li');

            $details .= html_writer::end_tag('ul');

            //
            // Preview column
            //
            $preview = html_writer::tag('img', null, array('src' => '//TODO', 'class' => 'preview'));

            //
            // Tags column
            //
            $tags = '';
            // get tags associated with this template
            $tagsql = "SELECT tag.* FROM {$CFG->prefix}course_template_tag_instance ins
                                    JOIN {$CFG->prefix}course_template_tag tag ON ins.tag = tag.id
                                    WHERE ins.template = {$temp->id}
                                    ORDER BY tag.name";

            $tagrecs = $DB->get_records_sql($tagsql);

            if (!empty($tagrecs)) {
                $tagslist = implode(', ', array_map(function($n){
                        $link = html_writer::link(new moodle_url(me(), array('tag' => $n->id)), format_string($n->rawname));
                        return $link;
                    },
                    $tagrecs
                ));
                $tags = $tagslist;
            }

            //
            // Actions column
            //
            $actions = '';
            $canedit = has_capability('block/course_template:edit', $cxt);

            // edit icon
            if ($canedit) {
                $actions  .= $OUTPUT->action_icon(new moodle_url('/blocks/course_template/edit.php',
                                    array('template' => $temp->id)),
                                    new pix_icon('t/edit', get_string('edittempdata', 'block_course_template')));

                // delete icon
                $actions .= $OUTPUT->action_icon(new moodle_url('/blocks/course_template/view.php',
                                    array('template' => $temp->id, 'delete' => 1)),
                                    new pix_icon('t/delete', get_string('delete')));
            }

            // new course from template
            $actions .= $OUTPUT->action_icon(new moodle_url('/backup/restorefile.php',
                                array('template' => $temp->id)),
                                new pix_icon('t/restore', get_string('newcourse', 'block_course_template')));

            $table->add_data(array($preview, $details, $tags, $actions));
        }
    }

    // output the table
    $table->print_html();
}

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
