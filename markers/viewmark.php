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

$mapid = required_param('mapid', PARAM_INT);
$behalf = optional_param('behalf', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$map = $DB->get_record('markers_map', array ('id' => $mapid), '*', MUST_EXIST);
$assign = $DB->get_record('markers_assign', array ('id' => $map->assignid), '*', MUST_EXIST);
$setup = $DB->get_record('markers_setup', array ('id' => $map->setupid), '*', MUST_EXIST);

if (!markers_allow_profile_view($USER->id, $assign->markerid)) {
	print_error(get_string('norightpermissions', 'local_markers'));
	die;
}

$context = get_context_instance(CONTEXT_USER, $USER->id);

/// Print the page header
$PAGE->set_context($context);
$PAGE->set_url('/local/markers/viewmark.php?mapid=' . $mapid);
if ($map->type == 1)
	$PAGE->set_title(format_string(get_string('agreedmark', 'local_markers')));
else
	$PAGE->set_title(format_string(get_string('viewindividualmark', 'local_markers') . ": " . strip_tags(markers_get_user_url($assign->markerid))));

$PAGE->navbar->ignore_active();
	
// Output starts here
echo $OUTPUT->header();

if ($map->type == 1) {
	if ($map->endmarkerid == 0)
		echo $OUTPUT->heading(get_string('submittedby', 'local_markers') . ' ' . strip_tags(markers_get_user_url($map->altmarkerid)) . '<br/>(' . 
													get_string('onbehalfof', 'local_markers') . ' ' . get_string('actualmarkers', 'local_markers') . ')');
	else
		echo $OUTPUT->heading(get_string('submittedby', 'local_markers') . ' ' . strip_tags(markers_get_user_url($map->altmarkerid)));
}
else {
	if ($map->endmarkerid == 0)
		echo $OUTPUT->heading(get_string('submittedby', 'local_markers') . ' ' . strip_tags(markers_get_user_url($map->altmarkerid)) . '<br/>(' .
													get_string('onbehalfof', 'local_markers') . ' ' . strip_tags(markers_get_user_url($assign->markerid)) . ' )');
	else
		echo $OUTPUT->heading(get_string('submittedby', 'local_markers') . ' ' . strip_tags(markers_get_user_url($assign->markerid)));	

}

$theform = new markers_status_view_form();
$frm = $theform->get_form();

/*
$html = "<table>";
$html .= "<tr><td><b>" . get_string('themarker', 'local_markers') .  ":" . "</b></td>";
$html .= "<td>" . markers_get_user_url($assign->markerid) . "</td></tr>";

$html .= "<tr><td><b>" . get_string('role', 'local_markers') .  ":" . "</b></td>";
$html .= "<td>" . $assign->role . "</td></tr>";

$html .= "<tr><td><b>" . get_string('student', 'local_markers') . "</b></td>";
$html .= "<td>" . markers_get_user_url($assign->studentid, true) . "</td></tr>";

$html .= "<tr><td><b>" . get_string('thecourse', 'local_markers') . ":" . "</b></td>";
$html .= "<td>" . markers_get_course_url($assign->courseid, true) . "</td></tr>";

$html .= "<tr><td><b>" . get_string('theassignment', 'local_markers') . ":" . "</b></td>";
$html .= "<td>" . markers_get_assignment_url($setup->assignmentid, true) . "</td></tr>";

$html .= "</table>";
*/

if ($behalf == 0 && ($USER->id != $assign->markerid) && $map->type == 0) {
	$currentassign = $DB->get_record('markers_assign', array ('courseid' => $assign->courseid, 'studentid' => $assign->studentid, 'markerid' => $USER->id), '*', MUST_EXIST);
	$currentmap = $DB->get_record('markers_map', array('setupid' => $map->setupid, 'assignid' => $currentassign->id, 'type' => $map->type), '*', MUST_EXIST);
	if ($currentmap->allowedit == 1 && $confirm == 0) { // if the user can still edit the individual mark

		$msg = get_string('allowviewmark', 'local_markers');
		$html = "<script type=\"text/javascript\">
								function confirmMe() {
									var answer = confirm(\"$msg\");
									if (!answer) {
										window.close();
									}
									else {
										window.location = \"viewmark.php?mapid=\" + \"$mapid\" + \"&behalf=\" + \"$behalf\" + \"&confirm=1\";
									}
								}
								
								window.onload = confirmMe(); 
					</script>";
		echo $html;
		
	}
	
	if ($confirm == 1) {
		// it cannot edit the mark anymore
		$currentmap->allowedit = 0;
		$DB->update_record('markers_map', $currentmap);
	}
}

$assignment = $DB->get_record('assignment', array ('id' => $setup->assignmentid), '*', MUST_EXIST);

if (!is_readable($CFG->dirroot . '/local/cat/locallib.php')) {
	$tcat = null;
}
else {
	$tcat = $DB->get_record('cat', array ('assignmentid' => $setup->assignmentid));
}

if ($tcat == null) { // no multiple categories

	if ($map->type == 0) {// individual mark
		$assess = $DB->get_record('markers_assess', array ('mapid' => $map->id, 'categoryid' => -1), '*', MUST_EXIST);
		$html = "<table>";
		$html .= "<tr><td><b>" . get_string('grade', 'local_markers') . ":" . "</b></td><td>" . round($assess->grade, 2) . " / " . round($assignment->grade, 2) ."</td></tr>";
		$html .= "<tr><td><b>" . get_string('feedback', 'local_markers') . ":" . "</b></td><td>" . strip_tags($assess->feedback) . "</td></tr>";
		$html .= "</table>";
		$frm->addElement('html', $html);
	}
	else { // agreeded/final mark
		$submission = $DB->get_record('assignment_submissions', array ('assignment' => $setup->assignmentid, 'userid' => $assign->studentid), '*', MUST_EXIST);
		$html = "<table>";
		$html .= "<tr><td><b>" . get_string('grade', 'local_markers') . ":" . "</b></td><td>" . round($submission->grade, 2) . " / " . round($assignment->grade, 2) ."</td></tr>";
		$html .= "<tr><td><b>" . get_string('feedback', 'local_markers') . ":" . "</b></td><td>" . strip_tags($submission->submissioncomment) . "</td></tr>";
		$html .= "</table>";
		$frm->addElement('html', $html);		
	}
}
else { // multiple categories
	$categories = $DB->get_records('cat_category', array ('catid' => $tcat->id), 'priority ASC');
	if ($map->type == 0) {// individual mark
		$html = "<table>";
		$total = $DB->get_record('markers_assess', array ('mapid' => $map->id, 'categoryid' => -1), '*', MUST_EXIST);
		$html .= "<tr><td><b>" . get_string('total', 'local_markers') . ":" . "</b></td><td>" . round($total->grade, 2) . " / " . round($assignment->grade, 2) ."</td></tr>";
		$html .= "<tr><td><b>" . get_string('generalfeedback', 'local_markers') . ":" . "</b></td><td>" . strip_tags($total->feedback) . "</td></tr>";
		$html .= "</table>";
		$frm->addElement('html', $html);
		foreach ($categories as $cat) {
			$assess = $DB->get_record('markers_assess', array ('mapid' => $map->id, 'categoryid' => $cat->id), '*', MUST_EXIST);
			$frm->addElement('header', 'cat' . $cat->id, get_string('category', 'local_markers') . ': ' . $cat->description . ' (' . get_string('theweight', 'local_cat') . ': ' . round($cat->weight, 2) . ')');
			$html = "<table>";
			$html .= "<tr><td><b>" . get_string('grade', 'local_markers') . ":" . "</b></td><td>" . round($assess->grade, 2) . " / " . round($cat->maxgrade, 2) ."</td></tr>";
			$html .= "<tr><td><b>" . get_string('feedback', 'local_markers') . ":" . "</b></td><td>" . strip_tags($assess->feedback) . "</td></tr>";
			$subcategories = $DB->get_records('cat_subcat', array('categoryid' => $cat->id));
			if ($subcategories != null) {
				require_once($CFG->dirroot . '/mod/assignment/locallib.php');
				$stable = get_readonly_subcategories_table($cat->id, true, null,$setup, $assign, 0, true);
				$html .= "<td colspan='2'>" . $stable . "</td>";  
			}
			$html .= "</table>";
			$frm->addElement('html', $html);			
		}
	}
	else { // agreeded mark
		$html = "<table>";
		$submission = $DB->get_record('assignment_submissions', array ('assignment' => $setup->assignmentid, 'userid' => $assign->studentid), '*', MUST_EXIST);
		$html .= "<tr><td><b>" . get_string('total', 'local_markers') . ":" . "</b></td><td>" . round($submission->grade, 2) . " / " . round($assignment->grade, 2) ."</td></tr>";
		$html .= "<tr><td><b>" . get_string('generalfeedback', 'local_markers') . ":" . "</b></td><td>" . strip_tags($submission->submissioncomment) . "</td></tr>";
		$html .= "</table>";
		$frm->addElement('html', $html);
		foreach ($categories as $cat) {
			$submitcat = $DB->get_record('cat_submission', array ('categoryid' => $cat->id, 'ass_subid' => $submission->id), '*', MUST_EXIST);
			$frm->addElement('header', 'cat' . $cat->id, get_string('category', 'local_markers') . ': ' . $cat->description);
			$html = "<table>";
			$html .= "<tr><td><b>" . get_string('grade', 'local_markers') . ":" . "</b></td><td>" . round($submitcat->grade, 2) . " / " . round($cat->maxgrade, 2) ."</td></tr>";
			$html .= "<tr><td><b>" . get_string('feedback', 'local_markers') . ":" . "</b></td><td>" . strip_tags($submitcat->feedback) . "</td></tr>";
			$subcategories = $DB->get_records('cat_subcat', array('categoryid' => $cat->id));
			if ($subcategories != null) {
				require_once($CFG->dirroot . '/mod/assignment/locallib.php');
				$stable = get_readonly_subcategories_table($cat->id, false, $submission->id);
				$html .= "<td colspan='2'>" . $stable . "</td>";  
			}			
			$html .= "</table>";
			$frm->addElement('html', $html);			
		}		
	}
}

// a small hack to close the header
$frm->addElement('static', 'closeheader', '', '');
$frm->closeHeaderBefore('closeheader');

$html = "<center>";
$html .= "<button name=\"button\" onclick=\"window.close()\">" . get_string('close', 'local_markers') . "</button>"; 
$html .= "</center>";
$frm->addElement('html', $html);

	
$theform->display();

