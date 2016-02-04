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
 * Local library functions for Course Template block.
 *
 * @package      blocks
 * @subpackage   course_template
 * @copyright    2012 Catalyst-IT Europe
 * @author       Joby Harding <joby.harding@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// No direct script access.
defined('MOODLE_INTERNAL') || die();

/**
 * Delete a course template and related tag instances
 *
 * @param integer $templateid the id of the template to delete
 */
function course_template_delete_template($templateid) {
    global $DB;

    // Delete tags first.
    $tagids = $DB->get_records('block_course_template_tag_in', array('template' => $templateid));

    if (!empty($tagids)) {
        $tagids = array_keys($tagids);
        if (!course_template_delete_tag_instances($tagids)) {
            print_error(get_string('error:deleteinst', 'block_course_template'));
        }
    }

    // Remove template record.
    if (!$DB->delete_records('block_course_template', array('id' => $templateid))) {
        print_error(get_string('error:deletetemp', 'block_course_template', $templateid));
    }
}

/**
 * Delete tag instances. Checks whether the instance is the last instance of each tag
 * and if true also deletes the block_course_template_tag record.
 *
 * @param array:integer $tagids an array of tag instance ids.
 * @param transaction object.
 */
function course_template_delete_tag_instances($instids) {

    global $CFG, $DB;

    list($insql, $params) = $DB->get_in_or_equal($instids);

    // If we are deleting the last instance of a tag then delete the tag record also.
    $countsql = "SELECT tag.id, COUNT(ins.id) AS count FROM (SELECT t.* FROM {$CFG->prefix}block_course_template_tag t
                    JOIN {$CFG->prefix}block_course_template_tag_in ti ON t.id = ti.tag
                    WHERE ti.id {$insql}) tag
                 JOIN {$CFG->prefix}block_course_template_tag_in ins ON tag.id = ins.tag
                 GROUP BY (tag.id)";

    $tagscount = $DB->get_records_sql($countsql, $params);

    $deletetags = array();

    if ($tagscount) {
        $deletetags = array_filter(
            $tagscount,
            function($n) {
                if ($n->count == 1) {
                    return true;
                }
                return false;
            }
        );
    }
    $deletetags = array_keys($deletetags);

    // Delete any unneeded instance records.
    if (!$DB->delete_records_select('block_course_template_tag_in', "id {$insql}", $params)) {
        return false;
    }

    // Delete any unneeded tag records.
    if (!empty($deletetags)) {
        list($tagsinsql, $tagsparams) = $DB->get_in_or_equal($deletetags);
        if (!$DB->delete_records_select('block_course_template_tag', "id {$tagsinsql}", $tagsparams)) {
            return false;
        }
    }

    return true;
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
    global $CFG, $DB;

    $course = $DB->get_record('course', array('id' => $coursetemplate->course));
    $config = course_template_get_settings();
    $admin = get_admin();
    if (!$admin) {
        return false;
    }

    $bc = new backup_controller(
        backup::TYPE_1COURSE,
        $course->id,
        backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO,
        backup::MODE_AUTOMATED,
        $admin->id
    );

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

        // Set the default filename.
        $format = $bc->get_format();
        $type = $bc->get_type();
        $id = $bc->get_id();
        $users = $bc->get_plan()->get_setting('users')->get_value();
        $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
        $backupfile = backup_plan_dbops::get_default_backup_filename(
            $format,
            $type,
            $id,
            $users,
            $anonymised,
            true
        );
        $bc->get_plan()->get_setting('filename')->set_value($backupfile);

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

            // Create a template name in the form coursetemplate_<id>_<courseid>_<datestamp>.mbz.
            $filename  = 'coursetemplate_';
            $filename .= $coursetemplate->id . '_';
            $filename .= $course->id . '_';
            $filename .= $coursetemplate->timecreated;
            $filename .= '.mbz';

            // File API copy to location.
            $cxt = context_system::instance();
            $fs = get_file_storage();

            $fileinfo = array(
                'contextid' => $cxt->id,
                'component' => 'block_course_template',
                'filearea' => 'backupfile',
                'itemid' => $coursetemplate->id,
                'filepath' => '/',
                'filename' => $filename,
                'timecreated' => $coursetemplate->timecreated,
            );

            // Delete any current template
            $oldfile = $fs->get_file(
                $fileinfo['contextid'],
                $fileinfo['component'],
                $fileinfo['filearea'],
                $fileinfo['itemid'],
                $fileinfo['filepath'],
                $fileinfo['filename']
            );
            if ($oldfile) {
                $oldfile->delete();
            };

            // Create a copy of the file in the course_template location.
            $templatefile = $fs->create_file_from_pathname($fileinfo, "{$dir}/{$backupfile}");

            if ($templatefile && $storage === 1) {
                //$file->delete();
            }
        }
    } catch (backup_exception $e) {
        return false;
    }

    $bc->destroy();
    unset($bc);

    return (isset($templatefile)) ? $templatefile : false;
}

/**
 * Return the config settings for course_tempate backup used in place of system settings.
 *
 * @return object
 */
function course_template_get_settings() {
    global $CFG;

    // General backup settings.
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

    // Automated backup settings.
    $config->backup_auto_weekdays = 0000000;
    $config->backup_auto_hour = 0;
    $config->backup_auto_minute = 0;
    $config->backup_auto_storage = 1;   // This vaule to specify directory.
    $config->backup_auto_keep = 1;      // Only keep one backup.
    $config->backup_auto_users = 0;
    $config->backup_auto_role_assignments = 0;
    $config->backup_auto_activities = 1;
    $config->backup_auto_blocks = 1;
    $config->backup_auto_filters = 1;
    $config->backup_auto_comments = 0;
    $config->backup_auto_userscompletion = 0;
    $config->backup_auto_logs = 0;
    $config->backup_auto_histories = 0;
    $config->backup_auto_active = 2;    // This value for 'manual' backups.
    $backupdir = get_config('backup', 'backup_auto_destination');
    $config->backup_auto_destination = isset($backupdir) ? $backupdir : "{$CFG->dataroot}/temp/backup/";

    return $config;
}

/**
 * Duplicate a course and copy all its classifications.
 *
 * @param int $courseid The id number of the course being copied.
 * @param string $fullname The fullname for the new course.
 * @param string $shortname The shortname for the new course.
 * @param int $categoryid The category id number for the new course.
 * @return object $newcourse The new course object.
 */
function course_template_duplicate_course($courseid, $fullname, $shortname, $categoryid) {
    global $CFG, $DB, $USER;

    require_once($CFG->dirroot.'/admin/tool/topics/lib.php');
    require_once($CFG->dirroot.'/local/content/lib.php');
    require_once($CFG->dirroot.'/local/courseprovider/lib.php');
    require_once($CFG->dirroot.'/local/search/lib.php');

    try {
        $transaction = $DB->start_delegated_transaction();
        // Duplicate the course.
        $newcourse = core_course_external::duplicate_course($courseid, $fullname, $shortname, $categoryid);
        // Get the new course object.
        $newcourse = get_course($newcourse['id']);

        // Set additional data for the new course.
        $topics = tool_topics_get_course_topics($courseid);
        tool_topics_save_course_topics($topics, $newcourse->id);

        // Save languages
        $languages = local_content_get_course_language_ids($courseid);
        local_content_set_course_languages($newcourse->id, $languages);

        // Save course providers
        $courseproviders = local_courseprovider_get_course_provider_ids($courseid);
        local_courseprovider_save_course_providers($newcourse->id, $courseproviders);

        // Save course locations
        $locations = local_search_get_course_locations($courseid);
        local_search_save_course_locations($newcourse->id, $locations);

        // Save course content formats
        $contentformats = local_search_get_course_contentformats($courseid);
        local_search_save_course_contentformats($newcourse->id, $contentformats);

        // Learning time.
        $newcourse->learningtime = $DB->get_field('course', 'learningtime', array('id' => $courseid));
        // Content type.
        $newcourse->contenttype = $DB->get_field('course', 'contenttype', array('id' => $courseid));
        // Author.
        $newcourse->author = $USER->id;

        update_course($newcourse);

        $transaction->allow_commit();

        return $newcourse;

    } catch (Exception $e) {
        //extra cleanup steps
        $transaction->rollback($e); // rethrows exception
    }
}
