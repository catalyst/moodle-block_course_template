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

// no direct script access thankyou
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/course_template/lib.php');

//
// Insert course templates actions into admin menu tree
//
// Course templates category (directory)
$coursetmps = array(
    'key'    => 'coursetemplates',
    'title' => get_string('pluginname', 'block_course_template')
);

// Import template into course
$coursefromtmp = array(
    'key'    => 'newcoursefromtemp',
    'title' => get_string('newcoursefromtemp', 'block_course_template'),
    'url'    => $CFG->wwwroot . '/blocks/course_template/newcourse.php'
);

// Import template into course
$importtmp = array(
    'key'    => 'importcoursetemp',
    'title' => get_string('importintocourse', 'block_course_template'),
    'url'    => $CFG->wwwroot . '/blocks/course_template/import.php'
);

// View all templates
$mantmps = array(
    'key'    => 'viewallcoursetemp',
    'title' => get_string('alltemplates', 'block_course_template'),
    'url'    => $CFG->wwwroot . '/blocks/course_template/view.php'
);

// Settings
$tmpsettings = array(
    'key'    => 'block_course_template',
    'title' => get_string('pluginname', 'block_course_template') . ' ' . get_string('settings', 'block_course_template'),
    'options' => array(
        'courseformats' => array (
            'name'        => 'allowcourseformats',
            'visiblename' => get_string('cseformat:visiblename', 'block_course_template'),
            'description' => get_string('cseformat:description', 'block_course_template'),
            'defaults'    => array_map('block_course_template_get_formatsdefaults', block_course_template_get_courseformats()),
            'choices'     => block_course_template_get_courseformats()
        )
    )
);

//
// Insert nav items into Site admin tree
//
$ADMIN->add('courses', new admin_category($coursetmps['key'], $coursetmps['title']));
$ADMIN->add($coursetmps['key'], new admin_externalpage($coursefromtmp['key'], $coursefromtmp['title'], $coursefromtmp['url']));
$ADMIN->add($coursetmps['key'], new admin_externalpage($mantmps['key'], $mantmps['title'], $mantmps['url']));

//
// Configuration settings page
//
if ($hassiteconfig) {
    $settings = new admin_settingpage($tmpsettings['key'], $tmpsettings['title']);
    $ADMIN->add($coursetmps['key'], $settings);

    // course format settings
    extract($tmpsettings['options']);
    $settings->add(new admin_setting_configmulticheckbox('block_course_template/' . $courseformats['name'], $courseformats['visiblename'], $courseformats['description'], $courseformats['defaults'], $courseformats['choices']));
}
