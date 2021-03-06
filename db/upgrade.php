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
 * This file keeps track of upgrades to the digitised readings block
 *
 * Sometimes, changes between versions involve alterations to database structures
 * and other major things that may break installations.
 *
 * The upgrade function in this file will attempt to perform all the necessary
 * actions to upgrade your older installation to the current version.
 *
 * If there's something it cannot do itself, it will tell you what you need to do.
 *
 * The commands in here will all be database-neutral, using the methods of
 * database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * Upgrade script for course templates
 *
 * @package    block
 * @subpackage course_template
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @copyright  2012 Catalyst IT Ltd <http://catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

function xmldb_block_course_template_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012061200) {

        // Alter table schema for block_course_template.
        $table = new xmldb_table('block_course_template');
        $field = new xmldb_field('rawname');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('created');
        $field = new xmldb_field('created', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'timecreated');
        }

        // Alter table schema for block_course_template_tag.
        $table = new xmldb_table('block_course_template_tag');
        $field = new xmldb_field('rawname');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_block_savepoint(true, 2012061200, 'course_template');
    }

    if ($oldversion < 2012061201) {
        // Bump for capabilities.
        upgrade_block_savepoint(true, 2012061201, 'course_template');
    }

    if ($oldversion < 2012061801) {
        // Bump for capabilities.
        upgrade_block_savepoint(true, 2012061801, 'course_template');
    }

    if ($oldversion < 2012061802) {
        // Bump for capabilities.
        upgrade_block_savepoint(true, 2012061802, 'course_template');
    }

    if ($oldversion < 2012061806) {
        // Bump for capabilities.
        upgrade_block_savepoint(true, 2012061806, 'course_template');
    }

    if ($oldversion < 2012062000) {
        // Bump for capabilities.
        upgrade_block_savepoint(true, 2012062000, 'course_template');
    }

    if ($oldversion < 2014040100) {
        // Change DB table name
        $table = new xmldb_table('block_course_template_tag_instance');
        if ($dbman->table_exists($table)) {
            $sql = "ALTER TABLE {block_course_template_tag_instance}
                      RENAME TO {block_course_template_tag_in}";
            $DB->execute_sql($sql);
        }
        upgrade_block_savepoint(true, 2014040100, 'course_template');
    }

    if ($oldversion < 2015020500) {
        // Move config settings from global to local
        set_config('allowedformats', $CFG->block_course_template_allowedformats, 'block_course_template');
        unset_config('block_course_template_allowedformats');

        // Add learning channel tag
        $todb = new stdClass();
        $todb->name = 'Learning channel';
        $todb->timemodified = time();

        if (!$DB->record_exists('block_course_template_tag', array('name' => $todb->name))) {
            $DB->insert_record('block_course_template_tag', $todb, false);
        }

        // Blocks savepoint reached.
        upgrade_block_savepoint(true, 2015011500, 'course_template');
    }

    if ($oldversion < 2017110805) {
        // Rename 'file' column to 'filename' because 'file' is a reserved word
        // and it was breaking core tests
        $table = new xmldb_table('block_course_template');
        if ($dbman->table_exists($table) and $dbman->field_exists($table, 'file')) {
            $sql = "ALTER TABLE {block_course_template}
                      RENAME file TO filename";
            $DB->execute($sql);
        }

        // Blocks savepoint reached.
        upgrade_block_savepoint(true, 2017110805, 'course_template');
    }

    return true;
}
