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
 * Internal library of functions for module markers
 *
 * All the markers specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod
 * @subpackage markers
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir.'/formslib.php';

/**
 * Does something really useful with the passed things
 *
 * @param array $things
 * @return object
 */
//function markers_do_something_useful(array $things) {
//    return new stdClass();
//}

class markers_status_view_form extends moodleform {
	function definition() {
	}
	
	function get_form() {
		return $this->_form;
	}
}

/*
	@ param userid gets the id of a user
	@ return html code with link to users profile
*/
function markers_get_user_url($userid, $newwindow=null) {
	global $DB, $CFG;
	
	$target = "";
	if ($newwindow != null && $newwindow = true) {
		$target = "target=\"_blank\"";
	}
	
	$user = $DB->get_record('user', array ('id' => $userid), '*', MUST_EXIST);
	$url = $CFG->wwwroot . '/user/profile.php?id=' . $userid . '&check=1';
	$html = "<a href=\"" . $url ."\"" . $target . ">" . $user->firstname . " " . $user->lastname . "</a>";
	return $html;
}

/*
	@ param cid gets the id of a course
	@ return html code with link to course view page
*/
function markers_get_course_url($cid, $newwindow=null) {
	global $DB, $CFG;
	$target = "";
	if ($newwindow != null && $newwindow = true) {
		$target = "target=\"_blank\"";
	}
	$course = $DB->get_record('course', array ('id' => $cid), '*', MUST_EXIST);
	$url = $CFG->wwwroot . '/course/view.php?id=' . $cid;
	$html = "<a href=\"" . $url ."\"" . $target . ">" . $course->fullname . "</a>";
	return $html;
}

/*
	@ param assid gets the id of an assignment
	@ return html code with link to assignment view page
*/
function markers_get_assignment_url($assid, $newwindow=null) {
	global $DB, $CFG;
	$target = "";
	if ($newwindow != null && $newwindow = true) {
		$target = "target=\"_blank\"";
	}	
	$assignment = $DB->get_record('assignment', array ('id' => $assid), '*', MUST_EXIST);
	$url = $CFG->wwwroot . '/mod/assignment/view.php?a=' . $assid;
	$html = "<a href=\"" . $url ."\"" . $target .">" . $assignment->name . "</a>";
	return $html;
}

function markers_current_status_msg($assignment, $assign, $currentmap, $allmarkers, $setup, &$color, $cid, $aid, $sid, $behalf,&$statusid, &$teachermarker, &$teacherassign) {
	global $DB, $CFG, $USER;
	
	// First of all check if the teacher/admin is also a marker
	foreach($allmarkers as $marker) {
		if ($marker->markerid == $USER->id && $behalf == 1) {
			$teachermarker = true;
			$teacherassign = $marker;
		}
	}	

	
	$submission = $DB->get_record('assignment_submissions', array ('assignment' => $assignment->id, 'userid' => $assign->studentid));	
	// Check 1: Did student submit the assignment?
	// AVOID THIS CHECK: so as the markers can provide grades even if students has not submitted anything
	/*
	if ($submission == null) {
		$color = "#D75818"; // dark orange
		$statusid = 1;
	 return get_string('waitstudentsubmit', 'local_markers');
	} */

	// may needed
	$module = $DB->get_record_select('modules', 'name = "assignment"', null, '*', MUST_EXIST);
	$cm = $DB->get_record('course_modules', array ('course' => $assign->courseid, 'module' => $module->id, 'instance' => $assignment->id), '*', MUST_EXIST);

	// Check 2: Did this marker submit a mark?
	if ($currentmap->status == 0 && ($behalf == 0 || ($behalf == 1 && $teachermarker))) {
		$color = "#FF0000"; // red

		//$correctassignid = markers_get_correct_assignid($map);
		$url = $CFG->wwwroot . '/mod/assignment/submissions.php?id=' . $cm->id . '&userid=' . $assign->studentid . '&mode=single&filter=0&offset=0&type=0'
								 . '&assignid=' . $assign->id . '&behalf=' . $behalf . '&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;

		$statusid = 2;
		$msg = get_string('waityourmark', 'local_markers');
		$status = "<a style=\"color: " . $color . "\" href=\"" . $url ."\">" . $msg . "</a>"; 
		

		return $status;
	}
	

	// Check 3: Did other markers submit their mark?
	foreach($allmarkers as $marker) {
		if ($marker->markerid == $assign->markerid && $behalf == 0)
			continue;
						
		$thatmap = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $marker->id, 'type' => 0), '*', MUST_EXIST);

		if ($thatmap->status == 0) {
		
			$color = "#D75818"; // dark orange
			$statusid = 3;
			if ($behalf == 1) {
				return get_string('waitindividualmarks', 'local_markers');
			}
			else {
				return get_string('waitothermark', 'local_markers');
			}			
		}
	}
	
	// Check 4: Did an agreed mark submitted?
	$currentmap = null;
	$url = null;
	if ($behalf == 1) { // on behalf
		
		if ($teachermarker) { // on behalf but teacher is a marker
		
			if ($teacherassign == null) { // for safety. it should never reach her
				print_error(get_string('err_status', 'local_markers'));
				die;
			}
			$currentmap = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $teacherassign->id, 'type' => 1), '*', MUST_EXIST);	
		}
		else { // we are marking on behalf and teacher is not a marker
			// So we will use the identification of supervisor to mark the assignment
			$where = 'courseid = ' . $assign->courseid . ' AND studentid = ' . $assign->studentid . ' AND role = \'' . get_string('supervisor', 'local_markers') . '\'';  
			$supervisor = $DB->get_record_select('markers_assign', $where, null,'*', MUST_EXIST);
			$currentmap = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $supervisor->id, 'type' => 1), '*', MUST_EXIST);			
		}
		
		// Calculate the url anyway (even if status = 1)
		$url = $CFG->wwwroot . '/mod/assignment/submissions.php?id=' . $cm->id . '&userid=' . $assign->studentid . '&mode=single&filter=0&offset=0&type=1'
							 . '&assignid=' . $currentmap->assignid . '&behalf=1&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;		
	}
	else { // not behalf
		$currentmap = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $assign->id, 'type' => 1), '*', MUST_EXIST);
		$url = $CFG->wwwroot . '/mod/assignment/submissions.php?id=' . $cm->id . '&userid=' . $assign->studentid . '&mode=single&filter=0&offset=0&type=1'
								 . '&assignid=' . $currentmap->assignid . '&behalf=0&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;		
	}
		
	if ($currentmap->status == 0) {
	
		if ($url == null) {// this should never happen
			print_error(get_string('err_status', 'local_markers'));
			die;
		}
		
		$color = "#FF0000"; // red
		$statusid = 4;
		$msg = get_string('waitagreedmark', 'local_markers');
		$status = "<a style=\"color: " . $color . "\" href=\"" . $url ."\">" . $msg . "</a>"; 
		return $status;
	}
	
	$color = "#008000"; // green
	$statusid = 5;
	return get_string('completed', 'local_markers');	
}



/*
@param a markers_map object with type = 1
@return the id of markers_assign record of marker who did the last assess
*/
function markers_get_correct_assignid($map) {
	global $DB;
	$assign = $DB->get_record('markers_assign', array ('id' => $map->assignid), '*', MUST_EXIST);
	if ($map->endmarkerid != 0) {// the assess was not on behalf of admin/teacher
		$theassigns = $DB->get_records('markers_assign', array('courseid' => $assign->courseid, 'studentid' => $assign->studentid));
		foreach ($theassigns as $ass) {
			if ($map->endmarkerid == $ass->markerid) {
				return $ass->id;
			}
		}
		
		// it should never reach here
		print_error(get_string('err_getcorrectassid', 'local_markers'));
		die;
	}
	else { // the assess was on behalf
		$where = 'courseid = ' . $assign->courseid . ' AND studentid = ' . $assign->studentid . ' AND role = \'' . get_string('supervisor', 'local_markers') . '\'';  
		$supervisor = $DB->get_record_select('markers_assign', $where, null, '*', MUST_EXIST);
		return $supervisor->id;
	}
}


function markers_get_status_view($cid, $aid, $sid, $behalf) {
	global $USER, $DB, $CFG;
	
	$admin = false;
	$context = get_context_instance(CONTEXT_USER, $USER->id);
	if (has_capability('local/markers:admin', $context)) {
		$admin = true;
	}
	
	$allow = false;
	$courses = $DB->get_records('course', null, 'fullname ASC'); // Load all courses for the admin user
	if ($courses == null) {
		print_error(get_string('currenltynoinfo', 'local_markers'));
	}
	
	$courseids = array();
	
	if ($behalf == 1 && !$admin) {
		// Find all the courses for which this user is a teacher
		$temp = array();
		foreach ($courses as $course) {
			$context = get_context_instance(CONTEXT_COURSE, $course->id);
			if (has_capability('local/markers:editingteacher', $context)) {
				$temp[$course->id] = $course;
				$courseids[] = $course->id;
				if ($cid != 0 && $course->id == $cid) {
					$allow = true;
				}
			}
		}
		if (empty($temp) || ($cid != 0 && !$allow)) {
			print_error(get_string('norightpermissions', 'local_markers'));
			die;
		}
		
		$courses = $temp;
	}
	else if ($behalf == 0 && !$admin) {
		// Find all the courses for which this user is a marker
		$assigns = $DB->get_records('markers_assign', array ('markerid' => $USER->id));
		foreach ($assigns as $assign) {
			$courseids[] = $assign->courseid;
			if ($cid != 0 && $assign->courseid == $cid) {
				$allow = true;
			}
		}
		
		$courses = $DB->get_records_list('course', 'id', $courseids);

		if ($courses == null || ($cid != 0 && !$allow)) {
			print_error(get_string('norightpermissions', 'local_markers'));
			die;
		}
		

	}
	else if ($admin) { // the user is the admin
 
		if ($behalf == 0) {
			// The admin user should not be a marker on any course
			// He will be redirected to the privilege page so if he/she is a marker/teacher
			// will be able to see everything from there
			redirect ($CFG->wwwroot . '/local/markers/view.php?cid=' . $cid . '&aid=' . $aid . '&sid=' . $sid . '&behalf=' . 1);
		}
		else {
			foreach ($courses as $course) {
				$courseids[] = $course->id;
			}
		}
	}
	
	$form = new markers_status_view_form();
	$frm = &$form->get_form();
	
	$frm->addElement('header', 'filter', get_string('filterby', 'local_markers'));
	$html = "<script type=\"text/javascript\">
							function reload(behalf, change) {
								var courseid = document.getElementById('course').value;
								var assignmentid = document.getElementById('assignment').value;
								var studentid = document.getElementById('student').value;
								
								if (change == 1) {
									assignmentid = 0;
									studentid = 0;
								}

								window.location = \"view.php?cid=\" + courseid + \"&aid=\" + assignmentid + \"&sid=\" + studentid + \"&behalf=\" + behalf;
							}
					 </script>";		 
	
	// add the course select
	$html .= get_string('thecourse', 'local_markers') . " ";
			 
	$html .= "<select name=\"course\" id=\"course\" onChange=\"reload(". $behalf .", 1)\">";
	
	if ($courses == null)
		$html .= "<option value=0 selected>" . get_string('nocourses', 'local_markers') ."</option>";			
	else {
		if ($cid == 0)
			$html .= "<option value=0 selected>" . get_string('all', 'local_markers') ."</option>";
		else
			$html .= "<option value=0>" . get_string('all', 'local_markers') . "</option>";
	}

	foreach ($courses as $course) {
		$cname = (strlen($course->fullname) > 50)? substr($course->fullname, 0, 50) . '...' : $course->fullname;
		if ($cid == $course->id)
			$html .= "<option value=" . $course->id . " selected>" . $cname . "</option>"; 
		else
			$html .= "<option value=" . $course->id . ">" . $cname . "</option>";
	}

	$html .= "</select>";
	
	// add the assignment select
	$html .= " " . get_string('theassignment', 'local_markers') . " ";
			 
	$html .= "<select name=\"assignment\" id=\"assignment\" onChange=\"reload(". $behalf .", 0)\">";
	
	if ($cid == 0)
		$assignments = $DB->get_records_list('assignment', 'course', $courseids, 'name ASC');
	else
		$assignments = $DB->get_records('assignment', array ('course' => $cid), 'name ASC');
	
	$tempass = array();
	foreach ($assignments as $assignment) {
		$setup = $DB->get_record('markers_setup', array ('assignmentid' => $assignment->id));
		if ($setup != null) // This assignment has not been setup for multiple markers
				$tempass[] = $assignment;
	}
	$assignments = $tempass;			
	
	if (empty($assignments)/*$assignments == null*/) {
		$html .= "<option value=0 selected>" . get_string('noassignments', 'local_markers') ."</option>";			
	}
	else {
		if ($aid == 0)
			$html .= "<option value=0 selected>" . get_string('all', 'local_markers') ."</option>";
		else
			$html .= "<option value=0>" . get_string('all', 'local_markers') . "</option>";
	}
	
	foreach ($assignments as $assignment) {
		$aname = (strlen($assignment->name) > 50)? substr($assignment->name, 0, 50) . '...' : $assignment->name;
		if ($aid == $assignment->id)
			$html .= "<option value=" . $assignment->id . " selected>" . $aname . "</option>"; 
		else
			$html .= "<option value=" . $assignment->id . ">" . $aname . "</option>";
	}

	$html .= "</select>";

	
	
	// add the student select
	$html .= " " . get_string('thestudent', 'local_markers') . " ";
			 
	$html .= "<select name=\"student\" id=\"student\" onChange=\"reload(". $behalf .", 0)\">";

	// copied from my assignmarkers.php file and modified appropriately
	$studentrole = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
	$roleassign = null;
	if ($cid == 0) { 
		$allstudents = $DB->get_records('role_assignments', array('roleid' => $studentrole->id));
		$roleassign = array();
		foreach ($allstudents as $student) {
			foreach ($courses as $course) {
				$context = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $course->id), '*', MUST_EXIST);
				if ($student->contextid == $context->id) {
				
					if ($behalf == 0 && !is_marker($course->id, $USER->id, $student->userid)) {
						continue;
					}
					
					$roleassign[$student->id] = $student; // if one student appears more than once, it will be recorded  only once since I pass the id as array key
				}
			}
		}
					
	}
	else {
		$context = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $cid));
		if ($behalf == 1)
			$roleassign = $DB->get_records('role_assignments', array('roleid' => $studentrole->id, 'contextid' => $context->id));
		else
			$roleassign = $DB->get_records('markers_assign', array('courseid' => $cid, 'markerid' => $USER->id));
	}
	
	
	$ids = array();
	$thestudents = null;

	foreach ($roleassign as $therole) {
	
		if ($behalf == 0 && $cid != 0)
			$ids[] = $therole->studentid;
		else
			$ids[] = $therole->userid;
		}

	$thestudents = $DB->get_records_list('user', 'id', $ids, 'firstname ASC');
	
	if ($thestudents == null) {
		$html .= "<option value=0 selected>" . get_string('nostudents', 'local_markers') ."</option>";		
	}
	else {
		if ($aid == 0)
			$html .= "<option value=0 selected>" . get_string('all', 'local_markers') ."</option>";
		else
			$html .= "<option value=0>" . get_string('all', 'local_markers') . "</option>";
	}
	
	foreach ($thestudents as $student) {
		$sname = $student->firstname . " " . $student->lastname;
		$sname = (strlen($sname) > 30)? substr($sname, 0, 30) . '...' : $sname;
		if ($sid == $student->id)
			$html .= "<option value=" . $student->id . " selected>" . $sname . "</option>"; 
		else
			$html .= "<option value=" . $student->id . ">" . $sname . "</option>";
	}

	$html .= "</select>";
	
	
	$frm->addElement('html', $html);
	
	// Add the info
	$infoavailable = false;
	$assigns = null;
	if ($behalf != 1 && !$admin) {
		$assigns = $DB->get_records('markers_assign', array ('markerid' => $USER->id));
	}
	else { // behalf
		$where = 'courseid IN (';
		for ($i = 0; $i < count($courseids) - 1; $i++) {
			$where .= $courseids[$i] . ',';
		}
		$where .= $courseids[count($courseids)-1] . ')';
		$where .= ' AND role = "' . get_string('supervisor', 'local_markers') . '"'; // only one record we should show
		$assigns = $DB->get_records_select('markers_assign', $where);
	}
	
	foreach ($assigns as $assign) {
	
		if ($cid != 0 && $assign->courseid != $cid)
			continue;
			
		if ($sid != 0 && $assign->studentid != $sid)
			continue;
	
		$assignments = $DB->get_records('assignment', array ('course' => $assign->courseid));
		foreach ($assignments as $assignment) {
		
			if ($aid != 0 && $assignment->id != $aid)
				continue;
		
			$setup = $DB->get_record('markers_setup', array ('assignmentid' => $assignment->id));
			if ($setup == null) // This assignment has not been setup for multiple markers
				continue;
				
			$uniqueID = $assign->courseid . '.' . $assignment->id . '.' . $assign->studentid; // to be used in HTML elements
			
			$infoavailable = true;
			//$course = $DB->get_record('course', array ('id' => $assign->courseid), '*', MUST_EXIST);
			$frm->addElement('header', 'theheader', "");
			
			
			$allmarkers = $DB->get_records('markers_assign', array ('courseid' => $assign->courseid, 'studentid' => $assign->studentid));
			if ($allmarkers == null) {
				print_error('unexpectedNomarkers', 'local_markers');
				die;
			}
			
			// They are needed to redirect to the submissions.php	
			$module = $DB->get_record_select('modules', 'name = "assignment"', null, '*', MUST_EXIST);
			$cm = $DB->get_record('course_modules', array ('course' => $assign->courseid, 'module' => $module->id, 'instance' => $assignment->id), '*', MUST_EXIST);
			
			$currentmap = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $assign->id, 'type' => 0), '*', MUST_EXIST);
			// Calculate the current status
			$color = "#000000"; // initially black
			$statusid = 0;
			$teachermarker = false;
			$teacherassign = null;
			$status = markers_current_status_msg($assignment, $assign, $currentmap, $allmarkers, $setup, $color, $cid, $aid, $sid, $behalf, $statusid, $teachermarker, $teacherassign);
			
			// CSS for the first table
			$html = "<style type=\"text/css\">
									td {
										vertical-align:top;
									}
								</style>";	
			
			$html .= "<table>";
			
			$html .= "<tr><td><b>". get_string('course', 'local_markers'). "</b></td><td>" . markers_get_course_url($assign->courseid) ."</td></tr>";
			
			$html .= "<tr><td><b>". get_string('assignment', 'local_markers'). "</b></td><td>" . markers_get_assignment_url($assignment->id) ."</td></tr>";
			
			$html .= "<tr><td><b>". get_string('student', 'local_markers'). "</b></td><td>" . markers_get_user_url($assign->studentid) ."</td></tr>";
			

			if ($statusid > 1 && $assignment->assignmenttype != "offline") {
				$submission = $DB->get_record('assignment_submissions', array('assignment' => $assignment->id, 'userid' => $assign->studentid));
					if ($submission != null) {
						$output = null;
						if ($assignment->assignmenttype == "online") {
							$output = strip_tags(get_submission_details($assignment, $assign->studentid));
							$url = $CFG->wwwroot . '/mod/assignment/type/online/file.php?id=' . $cm->id . '&userid=' . $assign->studentid . '&m=1';
							$output = "<a href=\"javascript:popup('" . $url . "', 600, 400)\">" . $output . "</a>"; 					
						}
						else {
							$output = strip_tags(get_submission_details($assignment, $assign->studentid), '<a>');
						}
					}
					else {
						$output = '<font color="#D00000">' . get_string('noSubmissionYet', 'local_markers') . '</font>';
					}
				$html .= "<tr><td><b>". get_string('submission', 'local_markers'). ":" . "</b></td><td>" . $output ."</td></tr>";
			}
			
			if ($behalf == 0 || ($behalf == 1 && $teachermarker))
				$html .= "<tr><td><b>". get_string('yourrole', 'local_markers'). "</b></td><td>" . $assign->role ."</td></tr>";
				
			
			$html .= "<tr><td><b>". get_string('status', 'local_markers'). "</b></td><td><font color=" . $color .">" . $status ."</font></td></tr>";
			
			// positioning the second table inside the first one
			$top = '-6.5px';
			//if (($behalf == 0 && count($allmarkers) >= 3) || ($behalf == 1 && (($teachermarker && count($allmarkers) >= 3) || !$teachermarker)))
			//	$top = '20px';
			$html .= "<style>
									.celltable {
										position: relative;
										top:" . $top . ";
										left: -6px;
									}
									
								</style>";		
			// Other markers
			$html .= "<tr>";
			if ($behalf != 1 || ($behalf == 1 && $teachermarker))
				$html .= "<td><b>" .  get_string('othermarkers', 'local_markers') ."</b></td>";
			else
				$html .= "<td><b>" .  get_string('markers', 'local_markers') ."</b></td>";
							
			$html .= "<td><table class=\"celltable\">";
			foreach ($allmarkers as $marker) {
				if ($marker->markerid == $assign->markerid && ($behalf == 0 || ($behalf == 1 && $teachermarker)))
					continue;
					
				$html .= "<tr>";
				$html .= "<td>" . markers_get_user_url($marker->markerid) ." (" . $marker->role . ")" . "</td>";
				// find marker's status
				$markerstatus = "";
				$markermap = null;
				if ($statusid >= 3 && $statusid <= 5) {
					$markermap = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $marker->id, 'type' => 0), '*', MUST_EXIST);
					if ($markermap->status == 0) {
						if ($behalf != 1)
							$markerstatus = "(" . get_string('waitingformark', 'local_markers') . ")";
						else { // behalf
							$url = $CFG->wwwroot . '/mod/assignment/submissions.php?id=' . $cm->id . '&userid=' . $assign->studentid . '&mode=single&filter=0&offset=0&type=0'
								 . '&assignid=' . $marker->id . '&behalf=1&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;
							$msg = get_string('waitingforthemark', 'local_markers');
							$markerstatus = "<a style=\"color: " . $color . "\" href=\"" . $url ."\">" . $msg . "</a>";						
						}
					}
					else if ($behalf == 0 && $markermap->status == 1) { // either waitingagreedmark or completed
						// currently add a sinlge link to the webpage
						$url = "viewmark.php?mapid=" . $markermap->id . '&behalf=' . $behalf;
						$markerstatus = "<a href=\"javascript:popup_open('" . $url . "')\">" . get_string('viewthemark', 'local_markers') . "</a>"; 
					}
				}
				$html .= "<td>" . $markerstatus . "</td>";
				
				if ($markermap != null && $markermap->status == 1 && $behalf == 1) { // if marker submit a mark and we are on behalf show more options
					// edit mark
					$url = $CFG->wwwroot . '/mod/assignment/submissions.php?id=' . $cm->id . '&userid=' . $assign->studentid . '&mode=single&filter=0&offset=0&type=0'
								 . '&assignid=' . $marker->id . '&behalf=1&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;
					$msg = get_string('editthemark', 'local_markers');
					$edit = "<a href=\"" . $url ."\">" . $msg . "</a>";
					$html .= "<td>" . $edit . "</td>"; // edit mark
					
					// delete mark
					$url = $CFG->wwwroot . '/local/markers/deletemark.php?type=0&assignid=' . $marker->id . '&a=' . $assignment->id . '&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;
					$msg = get_string('deletethemark', 'local_markers');
					$delete = "<a href=\"" . $url ."\">" . $msg . "</a>";
					$html .= "<td>" . $delete . "</td>"; // /delete mark					
					
					// allow edit mark
					if ($markermap == null) { // this should never happen
						print_error(get_string('err_statusview', 'local_markers'));
						die;
					}
					
					$radiohtml = get_string('alloweditmark', 'local_markers') . ': ';
					// yes
					$url = $CFG->wwwroot . '/local/markers/allowedit.php?type=0&assignid=' . $marker->id . '&a=' . $assignment->id . '&allow=1' . '&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;
					
					if ($markermap->allowedit == 1)
						$radiohtml .= "<input type=\"radio\" name=\"allowedit" . $uniqueID . '.' . $marker->id . "\" value=\"yes\" checked onClick=\"window.location='". $url ."'\">"; // was $assign->id
					else
						$radiohtml .= "<input type=\"radio\" name=\"allowedit" . $uniqueID . '.' . $marker->id . "\" value=\"yes\" onClick=\"window.location='". $url ."'\">";
					$radiohtml .= ' ' . get_string('yes', 'local_markers') . ' ';
					
					// no
					$url = $CFG->wwwroot . '/local/markers/allowedit.php?type=0&assignid=' . $marker->id . '&a=' . $assignment->id . '&allow=0' . '&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;
					if ($markermap->allowedit == 0)
						$radiohtml .= "<input type=\"radio\" name=\"allowedit" . $uniqueID . '.' . $marker->id . "\" value=\"no\" checked onClick=\"window.location='". $url ."'\">";
					else
						$radiohtml .= "<input type=\"radio\" name=\"allowedit" . $uniqueID . '.' . $marker->id . "\" value=\"no\" onClick=\"window.location='". $url ."'\">";
					$radiohtml .= ' ' . get_string('no', 'local_markers');
					
					$html .= "<td>" . $radiohtml . "</td>"; // /allow edit mark
					
				}
				
				$html .= "</tr>";
			}
			$html .= "</table></td>";
			$html .= "</tr></table>";
			$frm->addElement('html', $html);
			
			// Add the view/edit links when behalf = 0
			$html = "<table><tr>";
			if ($statusid >= 3 && $statusid <= 5 && $behalf == 0) {
			
				if ($currentmap->allowedit == 1) { // edit your mark link
					$url = $CFG->wwwroot . '/mod/assignment/submissions.php?id=' . $cm->id . '&userid=' . $assign->studentid . '&mode=single&filter=0&offset=0&type=0'
								 . '&assignid=' . $assign->id . '&behalf=0&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;
					$html .= "<td><a href=\"" . $url ."\">" . get_string('editmark', 'local_markers') . "</a></td>"; // edit your mark link						
				}
				else { // add the view your mark link only if we cannot edit the mark			
			
					// view your mark link
					$url = "viewmark.php?mapid=" . $currentmap->id . '&behalf=' . $behalf;
					$html .= "<td><a href=\"javascript:popup_open('" . $url . "')\">" . get_string('viewyourmark', 'local_markers') . "</a></td>"; // view your mark link
				}

				
				if ($statusid == 5) {// status = completed
					$themap = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $assign->id, 'type' => 1), '*', MUST_EXIST);
					$correctassignid = markers_get_correct_assignid($themap);
					
					
					if ($themap->allowedit == 1) {
						$url = $CFG->wwwroot . '/mod/assignment/submissions.php?id=' . $cm->id . '&userid=' . $assign->studentid . '&mode=single&filter=0&offset=0&type=1'
								 . '&assignid=' . $correctassignid . '&behalf=0&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;
						$html .= "<td><a href=\"" . $url ."\">" . get_string('editagreedmark', 'local_markers') . "</a></td>"; // edit agreed mark link						
					}					
					else {
						$correctmap = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $correctassignid, 'type' => 1), '*', MUST_EXIST);
						$url = "viewmark.php?mapid=" . $correctmap->id . '&behalf=' . $behalf;
						$html .= "<td><a href=\"javascript:popup_open('" . $url . "')\">" . get_string('viewagreedmark', 'local_markers') . "</a></td>"; // view agreed mark link
					}

				}
			}
			$html .= "</tr></table>";
			$frm->addElement('html', $html);
			
			// Add the view/edit/delete etc links when behalf = 1
			if ($behalf == 1) {
				$html = "<table>";
				if ($teachermarker) { // add the individual links
					if ($teacherassign == null) {
						print_error(get_string('err_statusview', 'local_markers'));
						die;
					}	
					if ($statusid >= 3 && $statusid <= 5) {// then we have submitted an individual mark
						$html .= "<tr>";
						$html .= "<td><b>". get_string('yourindividualmark', 'local_markers'). ':' . "</b></td>";
						
						
						/* We can always edit mark on behalf so we do not need edit
						// view link
						$themap = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $teacherassign->id, 'type' => 0), '*', MUST_EXIST);
						$url = "viewmark.php?mapid=" . $themap->id . '&behalf=' . $behalf;
						$html .= "<td><a href=\"javascript:popup_open('" . $url . "')\">" . get_string('view', 'local_markers') . "</a></td>"; // view link */
						
						// edit link
						$url = $CFG->wwwroot . '/mod/assignment/submissions.php?id=' . $cm->id . '&userid=' . $assign->studentid . '&mode=single&filter=0&offset=0&type=0'
								 . '&assignid=' . $themap->assignid . '&behalf=1&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid; // again behalf is set to 1 as before
						$html .= "<td><a href=\"" . $url ."\">" . get_string('edit', 'local_markers') . "</a></td>"; // edit link
						
						// delete link
						$url = $CFG->wwwroot . '/local/markers/deletemark.php?type=0&assignid=' . $themap->assignid . '&a=' . $assignment->id . '&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;
						$delete = "<a href=\"" . $url ."\">" . get_string('delete', 'local_markers') . "</a>";
						$html .= "<td>" . $delete . "</td>"; // /delete link	
						
						$html .= "</tr>";
					}
				} // teachermarker
				
				if ($statusid == 5) {// add the agreed links
					$html .= "<tr>";
					$html .= "<td><b>". get_string('agreedmark', 'local_markers'). ':' . "</b></td>";
					
				
					$themap = null;
					if ($teachermarker)
						$themap = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $teacherassign->id, 'type' => 1), '*', MUST_EXIST);
					else {
						// find supervisor's map
						$where = 'courseid = ' . $assign->courseid . ' AND studentid = ' . $assign->studentid . ' AND role = \'' . get_string('supervisor', 'local_markers') . '\'';  
						$supervisor = $DB->get_record_select('markers_assign', $where, null,'*', MUST_EXIST);
						$themap = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $supervisor->id, 'type' => 1), '*', MUST_EXIST);
					}
					
					/* No view links on behalf
					// view link
					$url = "viewmark.php?mapid=" . $themap->id . '&behalf=' . $behalf;
					$html .= "<td><a href=\"javascript:popup_open('" . $url . "')\">" . get_string('view', 'local_markers') . "</a></td>"; */
						
					// edit link
					$url = $CFG->wwwroot . '/mod/assignment/submissions.php?id=' . $cm->id . '&userid=' . $assign->studentid . '&mode=single&filter=0&offset=0&type=1'
								 . '&assignid=' . $themap->assignid . '&behalf=1&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;
					$html .= "<td><a href=\"" . $url ."\">" . get_string('edit', 'local_markers') . "</a></td>"; // edit link
						
					// delete link
					$url = $CFG->wwwroot . '/local/markers/deletemark.php?type=1&assignid=' . $themap->assignid . '&a=' . $assignment->id . '&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;
					$delete = "<a href=\"" . $url ."\">" . get_string('delete', 'local_markers') . "</a>";
					$html .= "<td>" . $delete . "</td>"; // /delete link	
					
					// radio buttons (allow edit mark)
					
					$radiohtml = get_string('alloweditmark', 'local_markers') . ': ';
					// yes
					$url = $CFG->wwwroot . '/local/markers/allowedit.php?type=1&assignid=' . $themap->assignid . '&a=' . $assignment->id . '&allow=1' . '&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;
					if ($themap->allowedit == 1)
						$radiohtml .= "<input type=\"radio\" name=\"allowedit" . $uniqueID . "\" value=\"yes\" checked onClick=\"window.location='". $url ."'\">";
					else
						$radiohtml .= "<input type=\"radio\" name=\"allowedit" . $uniqueID . "\" value=\"yes\" onClick=\"window.location='". $url ."'\">";
					$radiohtml .= ' ' . get_string('yes', 'local_markers') . ' ';
					
					// no
					$url = $CFG->wwwroot . '/local/markers/allowedit.php?type=1&assignid=' . $themap->assignid . '&a=' . $assignment->id . '&allow=0' . '&rcid=' . $cid . '&raid=' . $aid . '&rsid=' . $sid;
					if ($themap->allowedit == 0)
						$radiohtml .= "<input type=\"radio\" name=\"allowedit" . $uniqueID . "\" value=\"no\" checked onClick=\"window.location='". $url ."'\">";
					else
						$radiohtml .= "<input type=\"radio\" name=\"allowedit" . $uniqueID . "\" value=\"no\" onClick=\"window.location='". $url ."'\">";
					$radiohtml .= ' ' . get_string('no', 'local_markers');
					
					$html .= "<td>" . $radiohtml . "</td>"; // /allow edit mark
					
					$html .= "</tr>";
					
				}
				$html .= "</table>";
				$frm->addElement('html', $html);
			}			
			
		}	
	}
	
	if (!$infoavailable) {
		$frm->addElement('static', 'noinfo', "",get_string('noinfo', 'local_markers'));
		$frm->closeHeaderBefore('noinfo');
	}
	
	
	return $form;
}


/*
	@param $sourceid the id of user who requires to see the profile of $targetid
	@param $targetid the id of user whose profile will be sen
	@return true if sourceid user have right to see user profile of targetid
*/
function markers_allow_profile_view($sourceid, $targetid) {
	global $DB, $USER;
	
	// if the user is the admin then allow view
	$context = get_context_instance(CONTEXT_USER, $sourceid);
	if (has_capability('local/markers:admin', $context)) {
		return true;
	}
	
	if ($sourceid == 0)
		return false;
	
	if ($targetid == 0)
		return false;
		
	// if this is the same person allow view
	if ($sourceid == $targetid)
		return true;
	
	// check if source is a teacher/nonediting teacher of target
	$assign = $DB->get_records('markers_assign', array ('studentid' => $targetid, 'markerid' => $sourceid));
	if ($assign != null)
		return true;
		
	// check if source and target are markers of some common students
	$students = $DB->get_records('markers_assign');
	foreach ($students as $student) {
		$source = $DB->get_records('markers_assign', array ('studentid' => $student->studentid, 'markerid' => $sourceid));
		if ($source == null)
			continue;
			
		$target = $DB->get_records('markers_assign', array ('studentid' => $student->studentid, 'markerid' => $targetid));
		
		if ($target == null)
			continue;
			
		return true;		
	}
	
	// check if source is teacher of a course where target is a student or target is also a teacher (editing or not)
	$courses = $DB->get_records('course');
	foreach ($courses as $course) {
	
		$context = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $course->id));			
		if (has_capability('local/markers:anyteacher', $context)) {
		
			// sourceid has that capability
			$user = $DB->get_record('user', array ('id' => $targetid), '*', MUST_EXIST);
			$temp = $USER;
			$USER = $user;
			$return = false;
			if (has_capability('local/markers:anyteacher', $context)) {
				$return = true;
			}
			$USER = $temp;
			if ($return)
				return true;
		}	
		
		$studentrole = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
		$teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'), '*', MUST_EXIST);

		$students = $DB->get_records('role_assignments', array('roleid' => $studentrole->id, 'contextid' => $context->id));
		$teachers = $DB->get_records('role_assignments', array('roleid' => $teacherrole->id, 'contextid' => $context->id));
		
		$teacherflag = false;
		foreach ($teachers as $teacher) {
			if ($teacher->userid == $sourceid) {
				$teacherflag = true;
			}
		}
		
		if (!$teacherflag)
			continue;
		
		$studentflag = false;			
		foreach ($students as $student) {
			if ($student->userid == $targetid) {
				$studentflag = true;
			}
		}
		
		if ($studentflag)
			return true;
		
	}
	
	return false;
}

/* 
 A function for outputting the submission of a student
 @param assignment object
 @param userid the id of the user we want to see his/her submission
 @return html code for outputting the submission */
function get_submission_details($assignment, $userid) {
	global $DB, $CFG, $PAGE;
	require_once($CFG->dirroot . '/mod/assignment/lib.php');
	require_once($CFG->libdir.'/plagiarismlib.php');
	//$PAGE->requires->js('/mod/assignment/assignment.js');
	$course = $DB->get_record('course', array ('id' => $assignment->course), '*', MUST_EXIST);
	$module = $DB->get_record_select('modules', 'name = "assignment"', null, '*', MUST_EXIST);
	$cm = $DB->get_record('course_modules', array ('course' => $course->id, 'module' => $module->id, 'instance' => $assignment->id), '*', MUST_EXIST);
	// Copied from /mod/assignment/submissions.php: Load up the required assignment code
	require_once($CFG->dirroot.'/mod/assignment/type/'.$assignment->assignmenttype.'/assignment.class.php');
	$assignmentclass = 'assignment_'.$assignment->assignmenttype;
	$assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);
	return $assignmentinstance->print_student_answer($userid);
}

/*
	Indicates whether or not a user is a marker of a student in a course
	 
	@param courseid The id of the course we are interested in
	@param markerid The id of the marker we are interested in
	@param studentid The id of the student we are interested in
	@return true if marker is indeed a marker of the student in that course, false otherwise.
*/
function is_marker($courseid, $markerid, $studentid) {
	global $DB;
	$thatassign = $DB->get_record('markers_assign', array ('courseid' => $courseid, 'studentid' => $studentid, 'markerid' => $markerid));
	if ($thatassign == null)
		return false;
		
	return true;
}

/*
	A helpful function used on detelemark.php. Mainly for re-using code and not re-write it
*/
function markers_delete_individual_mark($setup, $assignid) {
	global $DB;
	
	$map = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $assignid, 'type' => 0), '*', MUST_EXIST);
	$map->status = 0;
	$map->allowedit = 1;
	$map->endmarkerid = 0;
	$map->altmarkerid = 0;
	$DB->update_record('markers_map', $map);
	$DB->delete_records('markers_assess', array('mapid' => $map->id));
}

/*
	A helpful function used on detelemark.php. Mainly for re-using code and not re-write it
*/
function markers_delete_agreed_mark($setup, $assignid, $assignment) {
	global $DB;

	$theassign = $DB->get_record('markers_assign', array('id' => $assignid), '*', MUST_EXIST);
	$assigns = $DB->get_records('markers_assign', array('courseid' => $assignment->course, 'studentid' => $theassign->studentid));
	if ($assigns == null) {
		print_error(get_string('unexpectederroroccured', 'local_markers'));
		die;
	}


	foreach ($assigns as $assign) {
		$map = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $assign->id, 'type' => 1), '*', MUST_EXIST);
		$map->status = 0;
		$map->allowedit = 1;
		$map->endmarkerid = 0;
		$map->altmarkerid = 0;
		$DB->update_record('markers_map', $map);
		$DB->delete_records('markers_assess', array('mapid' => $map->id));
	}
	
	// Update rest tables
	$submission = $DB->get_record('assignment_submissions', array('assignment' => $assignment->id, 'userid' => $theassign->studentid), '*', MUST_EXIST);
	$submission->grade = -1;
	$submission->submissioncomment = "";
	$submission->teacher = 0;
	$submission->timemarked = 0;
	$DB->update_record('assignment_submissions', $submission);
	
	// if the assignment was a multiple category then remove all the marked categories
	//$DB->delete_records('mycat_submitcat', array('ass_sub_id' => $submission->id));
	$DB->delete_records('cat_submission', array('ass_subid' => $submission->id));
	$DB->delete_records('cat_subcat_submission', array('ass_subid' => $submission->id));	
	
	$where = 'courseid=' . $assignment->course . ' AND itemtype="mod" AND itemmodule="assignment" AND iteminstance=' . $assignment->id;
	$item = $DB->get_record_select('grade_items', $where, null, '*', MUST_EXIST);
	$grade = $DB->get_record('grade_grades', array('itemid' => $item->id, 'userid' => $theassign->studentid), '*', MUST_EXIST);
	$grade->rawgrade = null;
	$grade->finalgrade = null;
	$grade->feedback = null;
	$grade->timemodified = null;
	$grade->usermodified = null;
	$DB->update_record('grade_grades', $grade);
}

function multiple_markers_assignment($assignmentid, $course, $multiple=true) {
	global $DB;

	if ($multiple == true) {
		// check if multiple markers has already been defined
		$setup = $DB->get_record('markers_setup', array ('assignmentid' => $assignmentid));
		if ($setup != null)
			return; // no need to change anything
			
		$setupid = $DB->insert_record('markers_setup', array('assignmentid' => $assignmentid), true);
		//$assignment = $DB->get_record('assignment', array('id' => $assignmentid), '*', MUST_EXIST);
		$assigns = $DB->get_records('markers_assign', array('courseid' => $course));
		foreach ($assigns as $assign) {
			$object = new stdClass();
			$object->setupid = $setupid;
			$object->assignid = $assign->id;
			$object->type = 0;
			$object->status = 0;
			$object->endmarkerid = 0;
			$object->altmarkerid = 0;
			$object->allowedit = 1;
			$DB->insert_record('markers_map', $object);
			
			$object->type = 1;
			$DB->insert_record('markers_map', $object);
		}
	}
	else { // multiple == false
		// check if multiple markers has already been defined
		$setup = $DB->get_record('markers_setup', array ('assignmentid' => $assignmentid));
		if ($setup == null)
			return; // no need to change anything	
			
		$maps = $DB->get_records('markers_map', array ('setupid' => $setup->id));
		foreach ($maps as $map) {
			$DB->delete_records('markers_assess', array('mapid' => $map->id));
		}
		$DB->delete_records('markers_map', array ('setupid' => $setup->id));
		$DB->delete_records('markers_setup', array('assignmentid' => $assignmentid));	
	}
}

/*
	@ param cid  course id
	@ return an array of all teachers (editing teachers) on that course
*/
function m_get_teachers($cid) {
	global $DB;
	
	$teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'), '*', MUST_EXIST);
	$context = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $cid), '*', MUST_EXIST);
	$where = 'roleid=' . $teacherrole->id . ' AND contextid=' . $context->id;
	$ids = $DB->get_fieldset_select('role_assignments', 'userid', $where);
	return $DB->get_records_list('user', 'id', $ids);
}

/*
	@ param cid  course id
	@ return an array of all non-editing teachers on that course
*/
function m_get_non_editing_teachers($cid) {
	global $DB;
	
	$teacherrole = $DB->get_record('role', array('shortname' => 'teacher'), '*', MUST_EXIST);
	$context = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $cid), '*', MUST_EXIST);
	$where = 'roleid=' . $teacherrole->id . ' AND contextid=' . $context->id;
	$ids = $DB->get_fieldset_select('role_assignments', 'userid', $where);
	return $DB->get_records_list('user', 'id', $ids);
}

/*
	@ param cid course id
	@ return an array of all students on that course
*/
function m_get_students($cid) {
	global $DB;
	
	$studentrole = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
	$context = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $cid), '*', MUST_EXIST);
	$where = 'roleid=' . $studentrole->id . ' AND contextid=' . $context->id;
	$ids = $DB->get_fieldset_select('role_assignments', 'userid', $where);
	return $DB->get_records_list('user', 'id', $ids);
}

class marker {
	public $markerid;
	public $supervisor; // an array of userids with key the userid too. It contains all ids of students
							 // where this marker is a supervisor to them
	public $secondmarker; // similar with supervisor but this is for second marker
	
	function __construct($mid) {
		$this->markerid = $mid;
		$supervisor = array();
		$secondmarker = array();
	}
}

define('SUPERVISOR', 0);
define('SECONDMARKER', 1);

define('MAX_INTEGER', 2147483647); // the maximum integer

/*
	@param $studentid - int the id of the student we wish to find marker to
	@param $type - either SUPERVISOR or SECONDMARKER
	@param $markers - array of marker objects, key the markerid
	@return int - the id of the less loaded marker. That is the marker who is responsible for the least students
*/
function less_loaded_marker($studentid, $type, $markers) {
	$min = MAX_INTEGER;
	$minuserid = -1;
	foreach ($markers as $marker) {
		if (($type == SUPERVISOR && isset($marker->secondmarker[$studentid]))
				|| ($type == SECONDMARKER && isset($marker->supervisor[$studentid]))) {
					continue;
				}
				
		$load = ($type == SUPERVISOR) ? count($marker->supervisor) : count($marker->secondmarker);
		if ($load < $min) {
			$min = $load;
			$minuserid = $marker->markerid;
		}
	}
	
	return $minuserid;
}

function delete_assigns($courseid, $studentid) {
	global $DB;
	$assigns = $DB->get_records('markers_assign', array('courseid' => $courseid, 'studentid' => $studentid));
	foreach ($assigns as $assign) {
		$maps = $DB->get_records('markers_map', array('assignid' => $assign->id));
		foreach ($maps as $map) {
			$DB->delete_records('markers_assess', array('mapid' => $map->id));
			$DB->delete_records('markers_subassess', array('mapid' => $map->id));
		}
		$DB->delete_records('markers_map', array('assignid' => $assign->id));
	}
	$DB->delete_records('markers_assign', array('courseid' => $courseid, 'studentid' => $studentid));
}
