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

// No direct script access
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class course_template_edit_form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        $mform =& $this->_form;

        extract($this->_customdata);

        // Prevent file attachments
        $editoroptions = array(
            'maxfiles' => 0,
            'maxbytes' => 0,
            'subdirs' => 0,
            'changeformat' => 0,
            'context' => null,
            'noclean' => 0,
            'trusttext' => 0
        );

        $mform->addElement('header', 'detailsheading', get_string('details', 'block_course_template'));

        $basedontxt  = html_writer::start_tag('p');
        $basedontxt .= get_string('basedoncourse', 'block_course_template') . ' ';
        $basedontxt .= html_writer::link("{$CFG->wwwroot}/course/view.php?id={$basecourse->id}", format_string($basecourse->fullname));
        $basedontxt .= html_writer::end_tag('p');
        $mform->addElement('html', $basedontxt);

        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', 'client');

        $mform->addElement('editor', 'description', get_string('description'), $editoroptions);
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('filepicker', 'screenshot', get_string('screenshot', 'block_course_template'), null, array('maxbytes' => get_max_upload_file_size($CFG->maxbytes), 'accepted_types' => 'image'));

        $mform->addElement('header', 'tagsheading', get_string('tags'));

        $tagtxt = html_writer::tag('p', get_string('existingtags', 'block_course_template') . ': ' . block_course_template_get_tag_list());
        $mform->addElement('html', $tagtxt);

        $mform->addElement('text', 'tags', get_string('tags'));
        $mform->setType('tags', PARAM_TEXT);
        $mform->addHelpButton('tags', 'tagshelp', 'block_course_template');

        $mform->addElement('hidden', 'c', $basecourse->id);
        $mform->addElement('hidden', 't', $templateid);

        $actiontxt = ($templateid !== -1) ? get_string('updatetemplate', 'block_course_template') : get_string('createtemplate', 'block_course_template');
        $this->add_action_buttons(true, $actiontxt);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $templateid = $data['t'];

        // Template name must be unique
        $likefragment = $DB->sql_like('name', ':tagname', false);
        $likeparams = array('tagname' => '%' . $data['name'] . '%');

        if ($templateid == 0) {
            $existingtag = $DB->get_field_select('block_course_template_tag', 'id', $likefragment, $likeparams);
            if ($existingtag) {
                $errors['name'] = get_string('error:nametaken', 'templated');
            }
        } else {
            $templaterec = $DB->get_record('block_course_template', array('id' => $templateid));

            // If template name has been altered also check
            if (strtolower($templaterec->name) != strtolower($data['name'])) {
                $existingtag = $DB->get_field_select('block_course_template_tag', 'id', $likefragment, $likeparams);
                if ($existingtag) {
                    $errors['name'] = get_string('error:nametaken', 'templated');
                }
            }
        }

        return $errors;
    }
}
