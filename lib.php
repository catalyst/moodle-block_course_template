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

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

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

    $tags = $DB->get_records('block_course_template_tag');

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
    $tagids = $DB->get_records('block_course_template_tag_instance', array('template' => $templateid));

    if (!empty($tagids)) {
        $tagids = array_keys($tagids);
        if (!block_course_template_delete_tag_instances($tagids)) {
            print_error(get_string('error:deleteinst', 'block_course_template'));
        }
    }

    // remove template record
    if (!$DB->delete_records('block_course_template', array('id' => $templateid))) {
        print_error(get_string('error:deletetemp', 'block_course_template', $templateid));
    }
}

/**
 * Delete tag instances. Checks whether the instance is the last instance of each tag
 * and if true also deletes the block_course_template_tag record
 *
 * @param array:integer $tagids an array of tag instance ids
 * @param transaction object
 */
function block_course_template_delete_tag_instances($instids) {
    global $CFG, $DB;

    // if we are deleting the last instance of a tag then delete the tag record also
    $countsql = "SELECT tag.id, COUNT(ins.id) FROM (SELECT t.* FROM {$CFG->prefix}block_course_template_tag t
                    JOIN {$CFG->prefix}block_course_template_tag_instance ti ON t.id = ti.tag
                    WHERE ti.id IN (" . implode(', ', $instids) . ")) tag
                 JOIN {$CFG->prefix}block_course_template_tag_instance ins ON tag.id = ins.tag
                 GROUP BY (tag.id)";

    $tagscount = $DB->get_records_sql($countsql);
    $deletetags = array();

    if (!empty($tagscount)) {
        $deletetags = array_filter($tagscount, function($n){if ($n->count == 1){return true;}else{return false;}});
        $deletetags = array_map(function($n){return $n->id;}, $deletetags);
    }

    // delete any unneeded instance records
    if (!$DB->delete_records_select('block_course_template_tag_instance', "id IN (" . implode(', ', $instids) . ")")) {
        return false;
    }
    // delete any unneeded tag records
    if (!empty($deletetags)) {
        if (!$DB->delete_records_select('block_course_template_tag', "id IN (" . implode(', ', $deletetags) . ")")) {
            return false;
        }
    }

    return true;
}

/**
 * Generate a file location.
 *
 * @param string $itemname name of the file item
 */
function block_course_template_generate_file_location($itemname) {
    global $CFG, $DB;

    // get the block context instance
    $cxt = get_context_instance(CONTEXT_SYSTEM);
    $path = "{$CFG->dataroot}/{$cxt->id}/block_course_template/screenshot/";

    return $path;
}

/**
 * Serves the course_template screenshot files
 *
 * @param object $course
 * @param object $birecord_or_cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function block_course_template_pluginfile($course, $birecord_or_cm, $context, $filearea, $args, $forcedownload) {
    global $DB;

    require_login();

    $fileareas = array('screenshot', 'backupfile');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $id = (int)array_shift($args);
    if (!$coursetemplate = $DB->get_record('block_course_template', array('id' => $id))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/block_course_template/$filearea/$id/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, true); // download MUST be forced - security!
}



/**
 * Launches an automated backup routine for the given course and associates with a given coursetemplate instance.
 *
 * @param object $course
 * @param object $course_template
 * @param int $userid
 * @return bool
 */
function course_template_create_archive($coursetemplate, $userid) {
    global $DB;

    $course = $DB->get_record('course', array('id' => $coursetemplate->course));
    $config = block_course_template_get_settings();

    $bc = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_AUTOMATED, $userid);

    try {

        $settings = array(
            'users' => 'backup_auto_users',
            'role_assignments' => 'backup_auto_role_assignments',
            'activities' => 'backup_auto_activities',
            'blocks' => 'backup_auto_blocks',
            'filters' => 'backup_auto_filters',
            'comments' => 'backup_auto_comments',
            'completion_information' => 'backup_auto_userscompletion',
            'logs' => 'backup_auto_logs',
            'histories' => 'backup_auto_histories'
        );
        foreach ($settings as $setting => $configsetting) {
            if ($bc->get_plan()->setting_exists($setting)) {
                $bc->get_plan()->get_setting($setting)->set_value($config->{$configsetting});
            }
        }

        // Set the default filename
        $format = $bc->get_format();
        $type = $bc->get_type();
        $id = $bc->get_id();
        $users = $bc->get_plan()->get_setting('users')->get_value();
        $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
        $bc->get_plan()->get_setting('filename')->set_value(backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised));

        $bc->set_status(backup::STATUS_AWAITING);

        $outcome = $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $dir = $config->backup_auto_destination;
        $storage = (int)$config->backup_auto_storage;
        if (!file_exists($dir) || !is_dir($dir) || !is_writable($dir)) {
            $dir = null;
        }
        if (!empty($dir) && $storage !== 0) {

            // create a template name in the form coursetemplate_<id>_<courseid>_<datestamp>.mbz
            $filename  = 'coursetemplate_';
            $filename .= $coursetemplate->id . '_';
            $filename .= $course->id . '_';
            $filename .= $coursetemplate->created;
            $filename .= '.mbz';

            //
            // File API copy to location
            //
            $cxt = get_context_instance(CONTEXT_SYSTEM);
            $fs = get_file_storage();

            $fileinfo = array(
                'contextid' => $cxt->id,
                'component' => 'block_course_template',
                'filearea' => 'backupfile',
                'itemid' => $coursetemplate->id,
                'filepath' => '/',
                'filename' => $filename
            );

            // create a copy of the file in the course_template location
            $templatefile = $fs->create_file_from_storedfile($fileinfo, $file);

            if ($templatefile && $storage === 1) {
                // delete the file created by backup system
                $file->delete();
            }
        }
        $outcome = true;
    } catch (backup_exception $e) {
        print_error(get_string('error:createbackupfile', 'block_course_template', $coursetemplate->id));
        $outcome = false;
    }

    $bc->destroy();
    unset($bc);

    return (isset($templatefile)) ? $templatefile : false;
}

/**
 * Return the config settings for course_tempate backup
 *
 * @return object
 */
function block_course_template_get_settings() {
    global $CFG;

    //
    // General backup settings
    //
    $config = new stdClass();
    $config->backup_general_users = 0;
    $config->backup_general_users_locked = 0;
    $config->backup_general_users_anonymize = 0;
    $config->backup_general_users_anonymize_locked = 0;
    $config->backup_general_role_assignments = 0;
    $config->backup_general_role_assignments_locked = 0;
    $config->backup_general_activities = 1;
    $config->backup_general_activities_locked = 0;
    $config->backup_general_blocks = 1;
    $config->backup_general_blocks_locked = 0;
    $config->backup_general_filters = 1;
    $config->backup_general_filters_locked = 0;
    $config->backup_general_comments = 0;
    $config->backup_general_comments_locked = 0;
    $config->backup_general_userscompletion = 0;
    $config->backup_general_userscompletion_locked = 0;
    $config->backup_general_logs = 0;
    $config->backup_general_logs_locked = 0;
    $config->backup_general_histories = 0;
    $config->backup_general_histories_locked = 0;

    //
    // Automated backup settings
    //
    $config->backup_auto_weekdays = 0000000;
    $config->backup_auto_hour = 0;
    $config->backup_auto_minute = 0;
    $config->backup_auto_storage = 1;   // this vaule to specify directory
    $config->backup_auto_keep = 1;      // only keep one backup
    $config->backup_auto_users = 0;
    $config->backup_auto_role_assignments = 0;
    $config->backup_auto_activities = 1;
    $config->backup_auto_blocks = 1;
    $config->backup_auto_filters = 1;
    $config->backup_auto_comments = 0;
    $config->backup_auto_userscompletion = 0;
    $config->backup_auto_logs = 0;
    $config->backup_auto_histories = 0;
    $config->backup_auto_active = 2;    // this value for 'manual' backups
    $config->backup_auto_destination = "{$CFG->dataroot}/temp/backup/";

    return $config;
}
