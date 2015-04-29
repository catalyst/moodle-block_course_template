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

$selected = optional_param('selected', 0, PARAM_SEQUENCE);
$page     = optional_param('page', 0, PARAM_INT);
$courseid = optional_param('course', 0, PARAM_INT);
$hidefilter = optional_param('hidefilter', 0, PARAM_BOOL); // Hide the filter form for Learning Channels.

$course = null;
if (!$courseid || $courseid == SITEID) {
    $context = context_system::instance();
} else {
    $course = get_course($courseid);
    $context = context_course::instance($courseid);
}

require_capability('block/course_template:view', $context);

// Set page size for the template listing.
$pagesize = empty(get_config('block_course_template', 'pagesize')) ? '4' : get_config('block_course_template', 'pagesize');

$urlparams = array(
    'page' => $page,
    'selected' => $selected,
    'course' => $courseid,
    'hidefilter' => $hidefilter,
);
$url = new moodle_url('/blocks/course_template/view.php', $urlparams);
$PAGE->set_url($url);
$PAGE->set_context($context);
if ($course) {
    $PAGE->set_course($course);
}
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('coursetemplates', 'block_course_template'));
$PAGE->set_heading(get_string('coursetemplates', 'block_course_template'));
$PAGE->navbar->add(get_string('coursetemplates', 'block_course_template'));
$PAGE->navbar->add(get_string('alltemplates', 'block_course_template'));

// Tags form.
$tagsql = '';
$tagparams = array();
$tags =  $DB->get_records('block_course_template_tag', null, 'name ASC');
$params = array(
    'tags' => $tags,
    'selected' => $selected,
    'course' => $courseid,
    'page' => $page,
);
$mform = new block_course_template_tag_filter_form(null, $params);
if ($data = $mform->get_data()) {
    if (isset($data->tags) && is_array($data->tags)) {

        // Only keep tags with a value of 1.
        $activetags = array_keys($data->tags, 1);
        if (!empty($activetags)) {
            list($tagsql, $tagparams) = $DB->get_in_or_equal(array_values($activetags), SQL_PARAMS_NAMED, 'tag_');
        }
        $selected = implode(',', $activetags);
        $url = new moodle_url('/blocks/course_template/view.php', array('page' => $page, 'selected' => $selected, 'course' => $courseid));
        $PAGE->set_url($url);
    }
} else if ($selected) {
    list($tagsql, $tagparams) = $DB->get_in_or_equal(explode(',', $selected), SQL_PARAMS_NAMED, 'tag_');
}

// Templates listing.
if (!empty($tagsql) && !empty($tagparams)) {
    $totalcount = $DB->count_records_sql("
        SELECT COUNT(*)
        FROM (
        SELECT DISTINCT ct.id FROM {block_course_template} ct
        JOIN {block_course_template_tag_in} ti ON ti.template = ct.id
        WHERE ti.tag {$tagsql}
        ) AS temp", $tagparams);
    $templatesql = "SELECT ct.*, c.fullname AS coursename
        FROM {block_course_template} ct
        JOIN {block_course_template_tag_in} ti ON ti.template = ct.id
        JOIN {course} c ON c.id = ct.course
        WHERE ti.tag {$tagsql}
        GROUP BY ct.id, coursename
        ORDER BY ct.timemodified DESC";
} else {
    $totalcount = $DB->count_records_sql("SELECT COUNT(ct.id)
        FROM {block_course_template} ct
        JOIN {course} c ON c.id = ct.course");
    $templatesql = "SELECT ct.*, c.fullname AS coursename
        FROM {block_course_template} ct
        JOIN {course} c ON c.id = ct.course
        ORDER BY ct.name";
}

$renderer = $PAGE->get_renderer('block_course_template');

echo $OUTPUT->header();

// Display tags form.
if ($tags && !$hidefilter) {
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

$table->pagesize($pagesize, $totalcount);

$templates = $DB->get_records_sql($templatesql, $tagparams, $table->get_page_start(), $table->get_page_size());
if ($templates) {
    foreach ($templates as $template) {
        $tags = $DB->get_records_sql("SELECT id, name
            FROM {block_course_template_tag}
            WHERE id IN (
                SELECT tag
                FROM {block_course_template_tag_in}
                WHERE template = ?
            )", array($template->id));

        $row = array();
        $row[] = $renderer->display_template_screenshot($template);
        $row[] = $renderer->display_template_details($template);
        $row[] = $renderer->display_template_tags($tags, $courseid);
        $row[] = $renderer->display_template_actions($template, $context, $courseid, $hidefilter);

        $table->add_data($row);
    }
}

$table->print_html();

echo $OUTPUT->footer();
