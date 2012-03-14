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
 * Prints a particular instance of mycat
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage mycat
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');


require_login();

if (isguestuser()) {
	print_error(get_string('norightpermissions', 'local_markers'));
	die;
}

$assignid = required_param('assignid', PARAM_INT);
$type = required_param('type', PARAM_INT);
$a = required_param('a', PARAM_INT);
$allow = required_param('allow', PARAM_INT);
$raid = optional_param('raid', 0, PARAM_INT);
$rcid = optional_param('rcid', 0, PARAM_INT);
$rsid = optional_param('rsid', 0, PARAM_INT);


if ($assignid <= 0 || $type < 0 || $type > 1 || $a <= 0 || $raid < 0 || $rcid < 0 || $rsid < 0 || $allow < 0 || $allow > 1) {
	print_error(get_string('wrongparameters', 'local_markers'));
	die;
}

$assignment = $DB->get_record('assignment', array('id' => $a), '*', MUST_EXIST);
$context = get_context_instance(CONTEXT_COURSE, $assignment->course);
if (!has_capability('local/markers:editingteacher', $context)) {// if the user is not an editing teacher or admin then..
	print_error(get_string('norightpermissions', 'local_markers'));
	die;
}


$context = get_context_instance(CONTEXT_USER, $USER->id);
/// Print the page header
$PAGE->set_context($context);
$PAGE->set_url('/local/markers/allowedit.php?assignid=' . $assignid . '&type=' . $type . '&a=' . $a . '&rcid=' . $rcid .'&raid=' . $raid . '&rsid=' . $rsid);

$setup = $DB->get_record('markers_setup', array ('assignmentid' => $a), '*', MUST_EXIST);
if ($type == 0) { // individual
	$map = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $assignid, 'type' => $type), '*', MUST_EXIST);
	$map->allowedit = $allow;
	$DB->update_record('markers_map', $map);
}
else { // agreed
	$theassign = $DB->get_record('markers_assign', array('id' => $assignid), '*', MUST_EXIST);
	$assigns = $DB->get_records('markers_assign', array('courseid' => $assignment->course, 'studentid' => $theassign->studentid));
	if ($assigns == null) {
		print_error(get_string('unexpectederroroccured', 'local_markers'));
		die;
	}
	
	foreach ($assigns as $assign) {
		$map = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $assign->id, 'type' => $type), '*', MUST_EXIST);
		$map->allowedit = $allow;
		$DB->update_record('markers_map', $map);
	}
}
redirect($CFG->wwwroot . '/local/markers/view.php?cid=' . $rcid .'&aid=' . $raid . '&sid=' . $rsid . '&behalf=1');
