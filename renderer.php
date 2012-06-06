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
 * Renderer for Course Templates
 *
 * @package    block
 * @subpackage course_templates
 * @author     Stacey Walker <stacey@catalyst-eu.net>
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

        $context = get_context_instance(CONTEXT_SYSTEM);
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
    public function display_template_tags($tags) {
        $html = '';
        if ($tags) {
            $i = 1;
            foreach ($tags as $tag) {
                $tagurl = new moodle_url('/blocks/course_template/view.php', array('tag' => $tag->id));
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
     * @param boolean $canedit if the user has permissions to edit/delete
     * @return string HTML
     */
    public function display_template_actions($template, $canedit=null) {
        global $OUTPUT;

        $html = '';
        if ($canedit) {
            $html .= $OUTPUT->action_icon(new moodle_url('/blocks/course_template/edit.php',
                                array('template' => $template->id)), new pix_icon('t/edit', get_string('edit')));

            $html .= $OUTPUT->action_icon(new moodle_url('/blocks/course_template/delete.php',
                                array('id' => $template->id)), new pix_icon('t/delete', get_string('delete')));
        }
        $html .= $OUTPUT->action_icon(new moodle_url('/blocks/course_template/newcourse.php',
            array('template' => $template->id)), new pix_icon('t/restore', get_string('new')));

        return $html;
    }
}
