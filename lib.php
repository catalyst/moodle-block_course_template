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
 * Course template
 *
 * @package      plugin
 * @copyright    2012 Catalyst-IT Europe
 * @author       Joby Harding <joby.harding@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// no direct script access
defined('MOODLE_INTERNAL') || die();

/**
 * Return an associative array containing course formats for settings checkboxes
 *
 * @return array
 */
function block_course_template_get_courseformats() {
    $formatarray = array();

    $formats = get_plugin_list('format');
    if (!empty($formats)) {
        foreach ($formats as $key => $value) {
            $formatarray[$key] = ucfirst($key);
        }
    }

    return $formatarray;
}

/**
 * Used with array_map function to reassign array values. For any of the standard course format types return 1.
 *
 * @param mixed $n
 * @return array
 */
function block_course_template_get_formatsdefaults($n) {
    switch ($n) {
        case 'Scorm'  :
        case 'Social' :
        case 'Topics' :
        case 'Weeks'  :
            return 1;
            break;
        default :
            return 0;
            break;
    }
}

/**
 * Return an array of existing (block_course_template_tag) tag terms.
 *
 * @return array array of tag records or empty array
 */
function block_course_template_get_tags() {
    global $DB;

    $tags = $DB->get_records('course_template_tag');

    if (!empty($tags)) {
        return $tags;
    }

    return array();
}

/**
 * Return a string of tag names.
 *
 * @return string comma separated list of tag names or message indicating there are no tags yet
 */
function block_course_template_get_tag_list() {
    $tags = block_course_template_get_tags();
    if (!empty($tags)) {
        // sort alphabetically
        usort($tags, function($a, $b){return strcmp($a->name, $b->name);});
        // convert array into CSV string
        return format_string(implode(array_map(function($n){return $n->rawname;}, $tags), ', '));
    }

    return get_string('notags', 'block_course_template');
}

/**
 * Delete a course template and related tag instances
 *
 * @param integer $templateid the id of the template to delete
 */
function block_course_template_delete_template($templateid) {
    global $DB;

    // delete tags first
    $tagids = $DB->get_records('course_template_tag_instance', array('template' => $templateid));

    if (!empty($tagids)) {
        $tagids = array_keys($tagids);
        if (!block_course_template_delete_tag_instances($tagids)) {
            print_error(get_string('error:deleteinst', 'block_course_template'));
        }
    }

    // remove template record
    if (!$DB->delete_records('course_template', array('id' => $templateid))) {
        print_error(get_string('error:deletetemp', 'block_course_template', $templateid));
    }
}

/**
 * Delete tag instances. Checks whether the instance is the last instance of each tag
 * and if true also deletes the course_template_tag record
 *
 * @param array:integer $tagids an array of tag instance ids
 * @param transaction object
 */
function block_course_template_delete_tag_instances($instids) {
    global $CFG, $DB;

    // if we are deleting the last instance of a tag then delete the tag record also
    $countsql = "SELECT tag.id, COUNT(ins.id) FROM (SELECT t.* FROM {$CFG->prefix}course_template_tag t
                    JOIN {$CFG->prefix}course_template_tag_instance ti ON t.id = ti.tag
                    WHERE ti.id IN (" . implode(', ', $instids) . ")) tag
                 JOIN {$CFG->prefix}course_template_tag_instance ins ON tag.id = ins.tag
                 GROUP BY (tag.id)";

    $tagscount = $DB->get_records_sql($countsql);
    $deletetags = array();

    if (!empty($tagscount)) {
        $deletetags = array_filter($tagscount, function($n){if ($n->count == 1){return true;}else{return false;}});
        $deletetags = array_map(function($n){return $n->id;}, $deletetags);
    }

    // delete any unneeded instance records
    if (!$DB->delete_records_select('course_template_tag_instance', "id IN (" . implode(', ', $instids) . ")")) {
        return false;
    }
    // delete any unneeded tag records
    if (!empty($deletetags)) {
        if (!$DB->delete_records_select('course_template_tag', "id IN (" . implode(', ', $deletetags) . ")")) {
            return false;
        }
    }

    return true;
}
