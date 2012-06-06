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
 * Settings for Course Template block.
 *
 * @package      blocks
 * @subpackage   course_template
 * @copyright    2012 Catalyst-IT Europe
 * @author       Joby Harding <joby.harding@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No direct script access thankyou
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/course_template/lib.php');

// Navigation nodes
$ADMIN->add(
    'courses',
    new admin_category(
        'coursetemplate',
        get_string('pluginname', 'block_course_template')
    )
);

$ADMIN->add(
    'coursetemplate',
    new admin_externalpage(
        'newcoursefromtemp',
        get_string('newcoursefromtemp', 'block_course_template'),
        $CFG->wwwroot . '/blocks/course_template/newcourse.php'
    )
);

$ADMIN->add(
    'coursetemplate',
    new admin_externalpage(
        'viewallcoursetemp',
        get_string('alltemplates', 'block_course_template'),
        $CFG->wwwroot . '/blocks/course_template/import.php'
    )
);

$hassiteconfig = has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

// Site config settings
if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'block_course_template',
        get_string('pluginname', 'block_course_template') . ' ' . get_string('settings', 'block_course_template')
    );

    $ADMIN->add('coursetemplate', $settings);

    $formats = get_plugin_list('format');
    if (!empty($formats)) {
        foreach ($formats as $key => $value) {
            $formats[$key] = ucfirst($key);
        }
    }

    $defaultformats = array('scorm', 'social', 'topics', 'weeks');
    $configdefaults = array();
    if (!empty($defaultformats)) {
        foreach ($formats as $key => $value) {
            $configdefaults[$key] = in_array($key, $defaultformats) ? 1 : 0;
        }
    }

    $settings->add(
        new admin_setting_configmulticheckbox(
            'block_course_template/' . 'allowcourseformats',
            get_string('visiblename', 'block_course_template'),
            get_string('configdescription', 'block_course_template'),
            $configdefaults,
            $formats
        )
    );
}
