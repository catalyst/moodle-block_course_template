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

if ($ADMIN->fulltree) {

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

    // Teamwork Owner can assign from roles.
    $settings->add(new admin_setting_configmultiselect('block_course_template_allowedformats',
        get_string('visiblename', 'block_course_template'), get_string('configdescription', 'block_course_template'),
        $defaultformats, $formats));
}
