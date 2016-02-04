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
 * Language strings for Course Template block.
 *
 * @package      blocks
 * @subpackage   course_template
 * @copyright    2012 Catalyst-IT Europe
 * @author       Joby Harding <joby.harding@catalyst-eu.net>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No direct script access.
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Course Templates';

// Capabilities strings.
$string['course_template:addinstance'] = 'Add a course template block';
$string['course_template:createcourse'] = 'Create a new course from a course template';
$string['course_template:duplicatecourse'] = 'Duplicate course';
$string['course_template:edit'] = 'Create and administer course templates';
$string['course_template:import'] = 'Import a template into a course';
$string['course_template:managechannels'] = 'Manage Learning Channels';
$string['course_template:myaddinstance'] = 'Add a course template block';
$string['course_template:view'] = 'View available course templates';
$string['course_template:viewchannel'] = 'View a Learning Channel';

// Block strings.
$string['actions'] = 'Actions';
$string['activetags'] = 'Active tags';
$string['addtemplate'] = 'Add course template';
$string['alltemplates'] = 'View all course templates';
$string['basedon'] = 'based on \'{$a}\'';
$string['basedoncourse'] = 'Based on course: {$a}';
$string['basedontemplate'] = 'Based on template \'{$a}\'';
$string['channeltemplates'] = 'Channel Templates';
$string['configdescription'] = 'Define which course formats can be used to create course templates';
$string['confirmdelete'] = 'Are you sure you want to delete template \'{$a}\'?';
$string['courseformats'] = 'Course formats';
$string['coursetemplates'] = 'Course Templates';
$string['createcourse'] = 'New course from template';
$string['createdsuccessfully'] = 'Your course was created successfully';
$string['createdsuccessfully'] = 'Your course was created successfully';
$string['createtemplate'] = 'Create template';
$string['deletetemplate'] = 'Delete template';
$string['deletetemplate'] = 'Delete template';
$string['details'] = 'Details';
$string['duplicatecourse'] = 'Duplicate course';
$string['duplicatecoursesuccess'] = 'Course has been duplicated. <br> Please, edit the course below.';
$string['edittempdata'] = 'Edit template data';
$string['edittemplate'] = 'Edit course template';
$string['edittmptitle'] = 'Edit Course Template';
$string['existingtags'] = 'Existing tags';
$string['filtertemplates'] = 'Refresh templates';
$string['filtertext'] = 'Only show templates tagged with the following:';
$string['goto'] = 'Go to';
$string['importedsuccessfully'] = 'Your course was imported successfully';
$string['importedsuccessfully'] = 'Your course was imported successfully';
$string['importintocourse'] = 'Import template into course';
$string['intocourse'] = 'Import template into this course';
$string['lastmodifiedon'] = 'Last modified: {$a}';
$string['name'] = 'Course name';
$string['name_help'] = 'Please, provide a name for the new course';
$string['newcoursefromtemp'] = 'New course from template';
$string['newcoursetemplate'] = 'New course template';
$string['newtemplate'] = 'Template from this course';
$string['noimage'] = 'No image';
$string['noimage'] = 'No image';
$string['norecords'] = 'There are no templates to display';
$string['notags'] = 'There are currently no tags defined.';
$string['error:notemplates'] = 'No available course templates to create course';
$string['pagesize'] = 'Page Size';
$string['pagesize_desc'] = 'Set how many templates should be shown per page.';
$string['preview'] = 'Preview';
$string['savesuccess'] = 'Your template settings were saved successfully';
$string['screenshot'] = 'Screenshot';
$string['screenshotof'] = 'Screenshot of';
$string['screenshot_help'] = 'Screenshot files, such as images, are displayed in the list of templates together with the summary.';
$string['setchannel'] = 'Learning Channel';
$string['setchannel_desc'] = 'If checked, this course will be classed as a Learning Channel.';
$string['setchannel_help'] = 'Learning Channels are special courses in Agora that allow Subject Matter Experts (SMEs) to present selected content to Learners.';
$string['settings'] = 'Settings';
$string['tags'] = 'Tags';
$string['tagshelp'] = 'using tags';
$string['tagshelp_help'] = 'You can add multiple tags by separating each with a comma e.g. tag one, mytag, tag three etc.';
$string['templatedeleted'] = 'The template was deleted successfully';
$string['title'] = '{$a} Templates';
$string['updatetemplate'] = 'Update template';
$string['visiblename'] = 'Course formats';
$string['customcourseheading'] = 'Learning Channel heading';
$string['customcourseheading_help'] = 'Learning Channels can have custom stylised heading. If nothing is added here, the Course full name will be used.';

// Errors.
$string['error:couldnotinserttag'] = 'Could not insert tag {$a}';
$string['error:couldntgetcourse'] = 'Unable to find course with id \'{$a}\'';
$string['error:couldntgettag'] = 'Unable to find tag for id \'{$a}\'';
$string['error:couldntupdate'] = 'Could not update course template {$a}';
$string['error:createtemplatefile'] = 'Unable to create backup file for template {$a}';
$string['error:deleteinst'] = 'Unable to delete tag instances';
$string['error:deletetag'] = 'Unable to delete tags';
$string['error:extractarchive'] = 'Unable to extract archive';
$string['error:movearchive'] = 'Unable to copy restore archive';
$string['error:nameempty'] = 'Name field may not be empty.';
$string['error:nametaken'] = 'That template name is already in use. Please choose another.';
$string['error:nodirectory'] = 'No backup template directory';
$string['error:notemplate'] = 'No template record for id {$a}';
$string['error:paramrequired'] = 'Missing parameter - you must specify EITHER course or template';
$string['error:processerror'] = 'Unable to process archive file.';
$string['error:save'] = 'Unable to save template.';
$string['error:sitecourse'] = 'Site context course may NOT be used.';
