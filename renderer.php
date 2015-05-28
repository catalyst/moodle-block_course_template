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
 * Renderer for Course Template block.
 *
 * @package    block
 * @subpackage course_template
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @author     Joby Harding <joby.harding@catalyst-eu.net>
 * @copyright  2012 Robert Gordon University <http://rgu.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

defined('MOODLE_INTERNAL') || die();

class block_course_template_renderer extends plugin_renderer_base {

    /**
     * Display the screenshot for the given template
     *
     * @param object template current template
     * @return string HTML
     */
    public function display_template_screenshot($template) {

        // No image default.
        $html  = html_writer::start_tag('div', array('class' => 'course-template-noimage'));
        $html .= get_string('noimage', 'block_course_template');
        $html .= html_writer::end_tag('div');

        $context = context_system::instance();
        $fs = get_file_storage();
        $file = $fs->get_file(
            $context->id,
            'block_course_template',
            'screenshot',
            $template->id,
            '/',
            $template->screenshot
        );

        if ($file) {
            $filename = $file->get_filename();
            $path = "/{$context->id}/block_course_template/screenshot/{$template->id}/$filename";
            $imageurl = file_encode_url('/pluginfile.php', $path, false);
            $screenshotstr = get_string('screenshotof', 'block_course_template', s($template->name));
            $screenshot = html_writer::tag('img', null, array('src' => $imageurl, 'class' => 'preview', 'alt' => $screenshotstr));
            $html = $screenshot;
        }

        return $html;
    }

    /**
     * Display the current templates details
     *
     * @param object template current template
     * @return string HTML
     */
    public function display_template_details($template) {
        $courseurl = new moodle_url('/course/view.php', array('id' => $template->course));
        $courselink = html_writer::link($courseurl, s($template->coursename));
        $lastmodified = userdate($template->timemodified, get_string('strftimedate'));

        $html  = html_writer::start_tag('strong');
        $html .= s($template->name);
        $html .= html_writer::end_tag('strong');
        $html .= html_writer::empty_tag('br');
        $html .= get_string('basedoncourse', 'block_course_template', $courselink);
        $html .= html_writer::empty_tag('br');
        $html .= get_string('lastmodifiedon', 'block_course_template', $lastmodified);

        $html .= html_writer::start_tag('p');
        $html .= format_text($template->description);
        $html .= html_writer::end_tag('p');

        return $html;
    }

    /**
     * Display a list of tags associated with the given template
     *
     * @param array $tags related tags for the given template
     * @return string HTML
     */
    public function display_template_tags($tags, $courseid) {
        $html = '';
        if ($tags) {
            $i = 1;
            foreach ($tags as $tag) {
                $tagurl = new moodle_url('/blocks/course_template/view.php', array('selected' => $tag->id, 'course' => $courseid));
                $html .= html_writer::link($tagurl, s($tag->name));
                if ($i < count($tags)) {
                    $html .= ', ';
                }
                $i++;
            }
        }

        return $html;
    }


    /**
     * Display associated actions with the given template
     *
     * @param object $template the current template
     * @param object $context current page context based on course
     * @param integer $courseid ID of the course we might be importing into
     * @param bool $setchannel true if the course should be a learning channel
     * @return string HTML
     */
    public function display_template_actions($template, $context, $courseid, $setchannel) {
        global $OUTPUT;

        $html = '';
        if (has_capability('block/course_template:edit', $context)) {
            $html .= $OUTPUT->action_icon(new moodle_url('/blocks/course_template/edit.php',
                                array('t' => $template->id, 'cc' => $courseid)), new pix_icon('t/edit', get_string('edit')));

            $html .= $OUTPUT->action_icon(new moodle_url('/blocks/course_template/delete.php',
                                array('id' => $template->id, 'c' => $courseid)), new pix_icon('t/delete', get_string('delete')));
        }
        if (has_capability('block/course_template:createcourse', $context)) {
            $html .= $OUTPUT->action_icon(new moodle_url('/blocks/course_template/newcourse.php',
                array('t' => $template->id, 'setchannel' => $setchannel)), new pix_icon('t/restore', get_string('new')));
        }
        if (has_capability('block/course_template:import', $context) && ($courseid && $courseid != SITEID) && $context->contextlevel == CONTEXT_COURSE) {
            $html .= $OUTPUT->action_icon(new moodle_url('/blocks/course_template/newcourse.php',
                array('t' => $template->id, 'c' => $courseid)), new pix_icon('t/restore', get_string('import')));
        }

        return $html;
    }

    /**
     * Output links to be displayed inside block.
     *
     * @param integer $courseid id of the current course.
     * @return string HTML.
     */
    public function display_block_links($courseid) {
        global $PAGE;

        $allowedformats = get_config('block_course_template', 'allowedformats');
        if (isset($allowedformats) && !empty($allowedformats)) {
            $allowedformats = explode(',', $allowedformats);
            if (!in_array($PAGE->course->format, $allowedformats)) {
                return array();
            }
        }

        // Determine context.
        if ($courseid == SITEID) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($courseid);
        }

        $tempurl = new moodle_url('/blocks/course_template/edit.php', array('c' => $courseid));
        $courseurl = new moodle_url('/blocks/course_template/newcourse.php');
        $intocourseurl = new moodle_url('/blocks/course_template/newcourse.php', array('c' => $courseid));
        $viewurl = new moodle_url('/blocks/course_template/view.php', array('course' => $courseid));

        $items = array();
        if (has_capability('block/course_template:edit', $context)) {
            $items[] = html_writer::link($tempurl, get_string('newtemplate', 'block_course_template'));
        }
        if (has_capability('block/course_template:createcourse', $context)) {
            $items[] = html_writer::link($courseurl, get_string('newcoursefromtemp', 'block_course_template'));
        }
        if (has_capability('block/course_template:import', $context)) {
            $items[] = html_writer::link($intocourseurl, get_string('intocourse', 'block_course_template'));
        }
        if (has_capability('block/course_template:view', $context)) {
            $items[] = html_writer::link($viewurl, get_string('alltemplates', 'block_course_template'));
        }

        return $items;
    }

    /**
     * Output the based on text with link to course.
     *
     * @param integer $course the course template is based on.
     * @return string HTML.
     */

    public function display_form_basedon_course($course) {
        global $CFG;

        $courselink  = html_writer::link(
            "{$CFG->wwwroot}/course/view.php?id={$course->id}",
            format_string($course->fullname)
        );
        $html  = html_writer::start_tag('p');
        $html .= get_string('basedoncourse', 'block_course_template', $courselink);
        $html .= html_writer::end_tag('p');

        return $html;
    }
}
