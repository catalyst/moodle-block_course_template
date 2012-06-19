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
 * Show all course templates in tabular format.
 *
 * @package      blocks
 * @subpackage   course_template
 * @copyright    2012 Catalyst-IT Europe
 * @author       Joby Harding <joby.harding@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/blocks/course_template/tag_filter_form.php');
require_once($CFG->dirroot . '/blocks/course_template/locallib.php');
require_once($CFG->libdir . '/tablelib.php');

require_login();

$tag      = optional_param('tag', 0, PARAM_INT);
$page     = optional_param('page', 0, PARAM_INT);
$courseid = optional_param('c', 0, PARAM_INT);

$course = null;
if (!$courseid || $courseid == SITEID) {
    $context = get_context_instance(CONTEXT_SYSTEM);
} else {
    $course = $DB->get_record('course', array('id' => $courseid));
    $context = get_context_instance(CONTEXT_COURSE, $courseid);
}

require_capability('block/course_template:view', $context);

$url = new moodle_url('/blocks/course_template/view.php', array('page' => $page, 'tag' => $tag, 'c' => $courseid));
$PAGE->set_url($url);
$PAGE->set_context($context);
if ($course) {
    $PAGE->set_pagelayout('course');
    $PAGE->set_course($course);
} else {
    $PAGE->set_pagelayout('admin');
}
$PAGE->set_title(get_string('pluginname', 'block_course_template'));
$PAGE->set_heading(get_string('pluginname', 'block_course_template'));
$PAGE->navbar->add(get_string('alltemplates', 'block_course_template'));

$renderer = $PAGE->get_renderer('block_course_template');
echo $OUTPUT->header();

// Tags form.
$tagsql = '';
$tagparams = array();
$tags =  $DB->get_records('block_course_template_tag');
$mform = new block_course_template_tag_filter_form(null, array('tags' => $tags, 'filtertag' => $tag, 'course' => $courseid));
if ($data = $mform->get_data()) {
    if (isset($data->tags) && is_array($data->tags)) {

        // Only keep tags with a value of 1.
        $activetags = array_keys($data->tags, 1);
        if (!empty($activetags)) {
            list($tagsql, $tagparams) = $DB->get_in_or_equal(array_values($activetags), SQL_PARAMS_NAMED, 'tag_');
        }
    }
} else if ($tag) {
    $tagsql = " = {$tag}";
    $tagparams['filtertag'] = $tag;
}

// Templates listing.
if (!empty($tagsql) && !empty($tagparams)) {
    $totalcount = $DB->count_records_sql("SELECT COUNT(ct.id)
        FROM {block_course_template} ct
        JOIN {block_course_template_tag_instance} ti ON ti.template = ct.id
        WHERE ti.tag {$tagsql}", $tagparams);
    $templatesql = "SELECT ct.*, c.fullname AS coursename
        FROM {block_course_template} ct
        JOIN {block_course_template_tag_instance} ti ON ti.template = ct.id
        JOIN {course} c ON c.id = ct.course
        WHERE ti.tag {$tagsql}
        ORDER BY ct.timemodified DESC";
} else {
    $totalcount = $DB->count_records_sql("SELECT COUNT(*) FROM {block_course_template}");
    $templatesql = "SELECT ct.*, c.fullname AS coursename
        FROM {block_course_template} ct
        JOIN {course} c ON c.id = ct.course
        ORDER BY ct.timemodified DESC";
}

// Display tags form.
if ($tags) {
    $mform->display();
}

// Display the listings table.
$table = new flexible_table('block-course-template');
$table->set_attribute('id', 'block-course-template');
$table->set_attribute('class', 'generaltable generalbox');

$table->define_columns(array('preview', 'details', 'tags', 'actions'));
$table->define_headers(array(
    get_string('screenshot'),
    get_string('details', 'block_course_template'),
    get_string('tags'),
    get_string('actions'),
));
$table->define_baseurl($url);

$table->column_class('preview', 'preview');
$table->column_class('details', 'details');
$table->column_class('tags', 'tags');
$table->column_class('actions', 'actions');

$table->setup();

$table->pagesize(COURSE_TEMPLATES_PAGESIZE, $totalcount);

$templates = $DB->get_records_sql($templatesql, $tagparams, $table->get_page_start(), $table->get_page_size());
if ($templates) {
    foreach ($templates as $template) {
        $tags = $DB->get_records_sql("SELECT id, name
            FROM {block_course_template_tag}
            WHERE id IN (
                SELECT tag
                FROM {block_course_template_tag_instance}
                WHERE template = ?
            )", array($template->id));

        $row = array();
        $row[] = $renderer->display_template_screenshot($template);
        $row[] = $renderer->display_template_details($template);
        $row[] = $renderer->display_template_tags($tags, $courseid);
        $row[] = $renderer->display_template_actions($template, $context, $courseid);

        $table->add_data($row);
    }
}

$table->print_html();

echo $OUTPUT->footer();
