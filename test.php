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
 * Request new teamwork area
 *
 * @package    block
 * @subpackage rgu_teamwork
 * @author     Stacey Walker <stacey@catalyst-eu.net>
 * @copyright  2012 Robert Gordon University <http://rgu.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/lib/tablelib.php');

$page = optional_param('page', null, PARAM_INT);

$heading = 'Test renderer for course templates';

$url = new moodle_url('/blocks/course_template/test.php', array());
$PAGE->set_url($url);
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_pagelayout('admin');
$PAGE->set_title($heading);
$PAGE->navbar->add($heading);

$renderer = $PAGE->get_renderer('block_course_template');

echo $OUTPUT->header();
echo $OUTPUT->heading($heading, 2, 'main');

// view.php
// END view.php

echo $OUTPUT->footer();
