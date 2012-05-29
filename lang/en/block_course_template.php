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
 * Strings for component 'block_activity_modules', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   block_course_templates
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// no direct script access
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Course Template';
 // capabilities strings
$string['course_template:edit'] = 'Create and administer course templates';
$string['course_template:createcourse'] = 'Create a new course from a course template';

// block strings
$string['template'] = 'Template';
$string['addtemplate'] = 'Add course template';
$string['newcoursefromtemp'] = 'New course from template';
$string['alltemplates'] = 'View all templates';
$string['importintocourse'] = 'Import into course';
$string['settings'] = 'Settings';
$string['courseformats'] = 'Course formats';
$string['cseformat:visiblename'] = 'Course formats';
$string['cseformat:description'] = 'Define which course formats can be used to create course templates';
$string['cseformat:description'] = 'Define which course formats can be used to create course templates';
$string['edittmptitle'] = 'Edit Course Template';
$string['newtemplatefrom'] = 'New course template based on \'{$a}\'';
$string['edittemplate'] = 'Edit course template \'{$a}\'';
$string['savesuccess'] = 'Your template settings were saved successfully';
$string['activetags'] = 'Active tags';
$string['filtertemplates'] = 'Refresh templates';
$string['filtertext'] = 'Only show templates tagged with the following:';
$string['notags'] = 'There are currently no tags defined.';
$string['norecords'] = 'There are no templates to display';
$string['preview'] = 'Preview';
$string['tags'] = 'Tags';
$string['actions'] = 'Actions';
$string['lastmodified'] = 'Last modified';
$string['goto'] = 'Go to';
$string['edittempdata'] = 'Edit template data';
$string['newcourse'] = 'New course';
$string['details'] = 'Details';
$string['createtemplate'] = 'Create template';
$string['updatetemplate'] = 'Update template';
$string['existingtags'] = 'Existing tags';
$string['tagshelp'] = 'using tags';
$string['tagshelp_help'] = 'You can add multiple tags by separating each with a comma e.g. tag one, mytag, tag three etc.';
$string['basedoncourse'] = 'Based on course';
$string['screenshot'] = 'Screenshot';
$string['createcourse'] = 'New course from template';
$string['basedontemplate'] = 'Based on template \'{$a}\'';
$string['newtemplate'] = 'Template from this course';
$string['notemplates'] = 'No available course templates to create course';
$string['confirmdelete'] = 'Are you sure you want to delete template \'{$a}\'?';
$string['templatedeleted'] = 'The template was deleted successfully';
$string['screenshotof'] = 'Screenshot of';

// errors
$string['error:notemplate'] = 'No template record for id {$a}';
$string['error:couldnotinserttag'] = 'Could not insert tag {$a}';
$string['error:couldntupdate'] = 'Could not update course template {$a}';
$string['error:paramrequired'] = 'Missing parameter - you must specify EITHER course or template';
$string['error:couldntgettag'] = 'Unable to find tag for id \'{$a}\'';
$string['error:couldntgetcourse'] = 'Unable to find course with id \'{$a}\'';
$string['error:deleteinst'] = 'Unable to delete tag instances';
$string['error:deletetag'] = 'Unable to delete tags';
$string['error:deleteinst'] = 'Unable to delete template {$a}';
$string['error:createtemplatefile'] = 'Unable to create backup file for template {$a}';
$string['error:movearchive'] = 'Unable to copy restore archive';
$string['error:extractarchive'] = 'Unable to extract archive';
$string['error:nametaken'] = 'That template name is already in use. Please choose another.';