<?php

require_once($CFG->dirroot . '/local/markers/locallib.php');

global $DB, $USER;

function get_info_msg($msg) {
	return '<p><font color="#006600">' . $msg . '</font></p>';
}


class geturls_test extends UnitTestCase {

	function test_markers_get_form() {
		$statusviewform = new markers_status_view_form();
		$form = $statusviewform->get_form();	
		$this->assertTrue(is_a($form, 'MoodleQuickForm'));
	}


	function test_markers_get_user_url() {
		global $USER;
		
		//echo "i am here :)<br/>";
		//print_r($USER);
		$html = markers_get_user_url($USER->id);
		
		$text = strip_tags($html);
		$atag = strip_tags($html, '<a>'); // allow <a> tag
		// ContainsTagWithAttributes is defined on lib/simpletestlib.php
		//$this->assert(new ContainsTagWithAttributes('<a>', array()), $html);
		
		$this->assertTrue($text != $atag & $text == ($USER->firstname . ' ' . $USER->lastname));
	}
	

	function test_markers_get_user_url_error() {
		try{
			markers_get_user_url(-1);
		}
		catch (Exception $e) {
			$this->assertTrue(true);
		}	
	}

	
	function test_markers_get_course_url() {
		global $DB;
		$courses = $DB->get_records('course');
		if ($courses == null)
			$this->assertTrue(true);
		
		$course = reset($courses); // resets pointer to first element of array and return
																// its value
		$url = markers_get_course_url($course->id);
		$text = strip_tags($url);
		$atag = strip_tags($url, '<a>'); // allow <a> tag
		$this->assertTrue($text != $atag & $text == $course->fullname);

	}
	
	// it should give an exception
	function test_markers_get_course_url_error() {
		try{
			markers_get_course_url(0);
		}
		catch (Exception $e) {
			$this->assertTrue(true);
		}
	}
	
	function test_markers_get_assignment_url() {
		global $DB;
		$assignments = $DB->get_records('assignment');
		if ($assignments == null)
			$this->assertTrue(true);
		
		$ass = reset($assignments); // resets pointer to first element of array and return
																// its value
		$url = markers_get_assignment_url($ass->id);
		$text = strip_tags($url);
		$atag = strip_tags($url, '<a>'); // allow <a> tag
		$this->assertTrue($text != $atag & $text == $ass->name);

	}
	
	function test_markers_get_assignment_url_error() {
		try{
			markers_get_assignment_url(0);
		}
		catch (Exception $e) {
			$this->assertTrue(true);
		}	
	}
	
}

class statusmsg_test extends UnitTestCase {
	
	//helpful functions
	function status_msg() {
		if ($this->assign == null || $this->setup == null || $this->allmarkers == null) {
			$this->status = null;
		}
		else
		$this->status = markers_current_status_msg($this->assignment, $this->assign, $this->currentmap, $this->allmarkers, 
																				$this->setup, $this->color, $this->cid, $this->aid, $this->sid, $this->behalf,
																				$this->statusid, $this->teachermarker, $this->teacherassign);
	}
	
	function reload_this($setup, $assign, $assignment) {
		global $DB;
	
		if ($setup == null || $assign == null)
			return;
	
		$this->setup = $setup;
		$this->assignment = $assignment;
		$this->assign = $assign;
		//echo 'setupid: ' . $this->setup->id . ' assignid: ' . $this->assign->id . ' type: 0<br/>';
		$this->individualmap = $DB->get_record('markers_map', array ('setupid' => $this->setup->id, 'assignid' => $this->assign->id, 'type' => 0), '*', MUST_EXIST);
		$this->agreedmap = $DB->get_record('markers_map', array ('setupid' => $this->setup->id, 'assignid' => $this->assign->id, 'type' => 1), '*', MUST_EXIST);
		$this->allmarkers = $DB->get_records('markers_assign', array ('courseid' => $this->assign->courseid, 'studentid' => $this->assign->studentid));
		$this->currentmap = $this->individualmap;
		if ($this->allmarkers == null)
			return;
		$this->color = "#000000"; // initially black
		$this->statusid = 0;
		$this->teachermarker = false;
		$this->teacherassign = null;
		$this->cid = 0;
		$this->aid = 0;
		$this->sid = 0;
		$this->behalf = 0;			
	}
	
	// reload this object, with the details of a marker who has submitted his mark and the other markers
	// that mark together with this one has either submit their marks or not (according to submit parameter)
	// returns true if there are such info available in the DB, false otherwise
	function find_individual_markers($submit=false) {
		global $DB;
		
		$setups = $DB->get_records('markers_setup');
		foreach ($setups as $setup) {
			$assignment = $DB->get_record('assignment', array ('id' => $setup->assignmentid), '*', MUST_EXIST);
			$assigns = $DB->get_records('markers_assign', array ('courseid' => $assignment->course));
			foreach ($assigns as $assign) {
				$map = $DB->get_record('markers_map', array('setupid' => $setup->id, 'assignid' => $assign->id, 'type' => 0), '*', MUST_EXIST);
				if ($map->status != 1)
					continue;
					
				$allmarkers = $DB->get_records('markers_assign', array ('courseid' => $assign->courseid, 'studentid' => $assign->studentid));
				if ($submit)
					$found = true;
				else
					$found = false;
				foreach ($allmarkers as $other) {
					if ($other->id == $assign->id)
						continue;
					
					$thatmap = $DB->get_record('markers_map', array('setupid' => $setup->id, 'assignid' => $other->id, 'type' => 0), '*', MUST_EXIST);
					if ($submit) {

						if ($thatmap->status == 0)
							$found = false;
					}
					else {
						if ($thatmap->status == 0)
							$found = true;
					}
					
					if ($found) {
						$this->reload_this($setup, $assign, $assignment);
						return true;
					}
				}
			}			
		}
		
		// if we reach here then we couldn't find anything
		return false;
	}
	
	// reload this object, with the details of a markers, such that an agreed mark between this marker and the rest of the markers
	// has either submitted or not (depending on the submit parameter)
	// returns true if details found, false otherwise
	function find_agreed_markers($submit=false) {
		global $DB;
		
		$setups = $DB->get_records('markers_setup');
		foreach ($setups as $setup) {
			$assignment = $DB->get_record('assignment', array ('id' => $setup->assignmentid));
			if ($assignment == null) {
				continue;
			}
			$assigns = $DB->get_records('markers_assign', array ('courseid' => $assignment->course));
			foreach ($assigns as $assign) {
				
				// check if all the individual marks have already submitted
				$allmarkers = $DB->get_records('markers_assign', array('courseid' => $assign->courseid, 'studentid' => $assign->studentid));
				$allsubmitted = true;
				foreach ($allmarkers as $marker) {
					$thatmap = $DB->get_record('markers_map', array('setupid' => $setup->id, 'assignid' => $marker->id, 'type' => 0));
					if ($thatmap == null) {
						continue;
					}
					if ($thatmap->status == 0) {
						$allsubmitted = false;
						break;
					}		
				}
				
				if (!$allsubmitted)
					continue;
				
				
				$map = $DB->get_record('markers_map', array('setupid' => $setup->id, 'assignid' => $assign->id, 'type' => 1));
				if ($map == null) { 
					continue;
				}
				
				if (($map->status == 1 && $submit) || ($map->status == 0 && !$submit)) {
					$this->reload_this($setup, $assign, $assignment);
					return true;
				}
				
			}
		}
		
		// if we reach here, there weren't records available
		return false;		
	}
	
	// reloads this object with the details of a teacher who is or is not a marker (according to marker parameter)
	// returns false if details not found, true otherwise
	function find_teacher_marker($marker = true) {
		global $DB;
		
		$courses = $DB->get_records('course');
		foreach ($courses as $course) {
			$teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'), '*', MUST_EXIST);
			$context = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $course->id), '*', MUST_EXIST);
			$roleassign = $DB->get_records('role_assignments', array('roleid' => $teacherrole->id, 'contextid' => $context->id));
			
			$where = 'course = ' . $course->id;
			$assignments = $DB->get_fieldset_select('assignment', 'id', $where);
			if ($assignments == null)
				continue;
			
			$setups	= $DB->get_records_list('markers_setup', 'assignmentid', $assignments);
			$setup = reset($setups); // get the first object
			$assignment = $DB->get_record('assignment', array ('id' => $setup->assignmentid), '*', MUST_EXIST);
			
			foreach ($roleassign as $teacher) {
				$assigns = $DB->get_records('markers_assign', array('courseid' => $course->id, 'markerid' => $teacher->userid));

				if ($assigns != null && $marker) {
					$assign = reset($assigns);
					$this->reload_this($setup, $assign, $assignment); // use a random assignment
					return true;
				}
				
				if ($assigns == null && !$marker) {
					$this->nonmarker = $teacher->userid;
					$this->reload_this($setup, $this->assign, $assignment); // use a random assign and assignment
					return true;
				}
					
			}
		}
		
		// if we reach here, return false
		return false;
	}
	
	// reloads this object with the details of a student who has either submit or not submit an assignment (according to submit parameter)
	// returns false if details not found, true otherwise
	function find_student($submit=false) {
		global $DB;
	
		$setups = $DB->get_records('markers_setup');
		foreach ($setups as $setup) {
			$assignment = $DB->get_record('assignment', array ('id' => $setup->assignmentid), '*', MUST_EXIST);
			$assigns = $DB->get_records('markers_assign', array ('courseid' => $assignment->course));
			foreach ($assigns as $assign) {
				$submission = $DB->get_record('assignment_submissions', array ('assignment' => $assignment->id, 'userid' => $assign->studentid));
				if (($submission == null && !$submit) || ($submission != null && $submit)) { // use the first one found
					$this->reload_this($setup, $assign, $assignment);
					return true;
				}
			}
		}
		return false;
	}
	
	function setUp() {
		global $DB;
		
		$setups = $DB->get_records('markers_setup');
		if ($setups == null)
			return;
			
		$setup = reset($setups); // get first elements
		$assignment = $DB->get_record('assignment', array ('id' => $setup->assignmentid), '*', MUST_EXIST);
		$assigns = $DB->get_records('markers_assign', array ('courseid' => $assignment->course));
		if ($assigns == null)
			return;
			
		$assign = reset($assigns);
		$this->reload_this($setup, $assign, $assignment);	 
	}
	

	// the tests
	
	function test_teacher_marker() {
		global $USER;
		$found = $this->find_teacher_marker(true);
		if (!$found) {
			echo get_info_msg('test_teacher_marker(): not found details of teacher such that he/she is both a teacher and marker');
			$this->assertTrue(true);
			return;
		}
		
		$this->behalf = 1;
		
		// cheat a little bit by changing the current user into the teacher found
		$actualuser = $USER->id;
		$USER->id = $this->assign->markerid;
		$this->status_msg();
		
		$USER->id = $actualuser; // revert that hack
				
		$this->assertTrue($this->teachermarker &
											$this->teacherassign->id == $this->assign->id);
	}
	
	function test_teacher_not_marker() {
		global $USER;
		$found = $this->find_teacher_marker(false);
		if (!$found) {
			echo get_info_msg('test_teacher_marker(): not found details of teacher such that he/she is not a marker');
			$this->assertTrue(true);
			return;
		}
		
		$this->behalf = 1;
		
		// cheat a little bit by changing the current user into the teacher found
		$actualuser = $USER->id;
		$USER->id = $this->nonmarker;
		$this->status_msg();
		
		$USER->id = $actualuser; // revert that hack
		
		
		$this->assertTrue(!$this->teachermarker &
											$this->teacherassign == null);
	}
	
	function test_student_not_submit() {
		$found = $this->find_student(false);
		
		if (!$found) {
			echo get_info_msg('test_student_not_submit(): Not found a student that has not submit his assignments');
			$this->assertTrue(true);
			return;
		}
		
		$this->status_msg(); // call the function
		
		// found
		$this->assertTrue($this->status != get_string('waitstudentsubmit', 'local_markers') &
											$this->statusid != 1);
		
		
	}
	
	function test_current_marker_not_submit_mark() {
		
		$this->currentmap->status = 0; // not submitted the mark yet
		
		$this->status_msg();
		
		$status = strip_tags($this->status);
		$this->assertTrue($status == get_string('waityourmark', 'local_markers') &
												$this->color == '#FF0000' &
												$this->statusid == 2);
	}
	
	function test_current_marker_submit_mark() {
		
		$this->currentmap->status = 1; // has already submitted the mark
		
		$this->status_msg();
		
		$status = strip_tags($this->status);
		$this->assertTrue($status != get_string('waityourmark', 'local_markers') &
												$this->statusid != 2);
	}
	
	function test_other_markers_not_submit() {
		$found = $this->find_individual_markers(false);
		if (!$found) {
			echo get_info_msg('test_other_markers_not_submit(): not found details for other markers such that some of them have not submitted their mark yet');
			$this->assertTrue(true);
			return;
		}
		
		// found
		$this->status_msg();
		
		$this->assertTrue($this->status == get_string('waitothermark', 'local_markers') &
											$this->statusid == 3 &
											$this->color == '#996600');
	}
	
	function test_other_markers_submit() {
		$found = $this->find_individual_markers(true);
		if (!$found) {
			echo get_info_msg('test_other_markers_submit(): not found details for other markers such that all of them have already submitted their marks');
			$this->assertTrue(true);
			return;
		}
		
		// found
		$this->status_msg();
		
		$this->assertTrue($this->status != get_string('waitothermark', 'local_markers') &
											$this->statusid != 3);
	}
	
	function test_agreed_mark_not_submit() {
		$found = $this->find_agreed_markers(false);
		if (!$found) {
			echo get_info_msg('test_agreed_mark_not_submit(): not found details of a marker whose agreed mark has not submitted yet');
			$this->assertTrue(true);
			return;
		}
		
		// found
		$this->status_msg();
		
		$status = strip_tags($this->status);
		
		$this->assertTrue($status == get_string('waitagreedmark', 'local_markers') &
											$this->statusid == 4 &
											$this->color == '#FF0000');
				
	}
	
	function test_agreed_mark_submit() {
		$found = $this->find_agreed_markers(true);
		if (!$found) {
			echo get_info_msg('test_agreed_mark_submit(): not found details of a marker whose agreed mark has already submitted');
			$this->assertTrue(true);
			return;
		}
		
		// found
		$this->status_msg();
		
		$this->assertTrue($this->status == get_string('completed', 'local_markers') &
											$this->statusid == 5 &
											$this->color == '#008000');
				
	}
	
	function test_wait_your_mark_behalf() {
		$found = $this->find_student(true); // find a student who has submitted an assignment
		if (!$found) {
			echo get_info_msg('test_wait_your_mark_behalf(): not found any student that has submitted an assignment');
			$this->assertTrue(true);
			return;		
		}
		
		// set the inputs appropriately
		$this->behalf = 1;
		$this->teachermarker = true;
		$this->currentmap->status = 0;
		
		$this->status_msg();
		
		$status = strip_tags($this->status);
		$this->assertTrue($status == get_string('waityourmark', 'local_markers') &
												$this->color == '#FF0000' &
												$this->statusid == 2);
		
	}
	
	function test_other_markers_not_submit_behalf() {
		$found = $this->find_individual_markers(false);
		if (!$found) {
			echo get_info_msg('test_other_markers_not_submit_behalf(): not found details for other markers such that some of them have not submitted their mark yet');
			$this->assertTrue(true);
			return;
		}
		
		$this->behalf = 1;
		$this->status_msg();
		
		$this->assertTrue($this->status == get_string('waitindividualmarks', 'local_markers') &
											$this->statusid == 3 &
											$this->color == '#996600');
	}
	
}

class morelocallib_test extends UnitTestCase {
	
	function get_agreed_maps($behalf=0) {
		global $DB;
	
		$maps = $DB->get_records('markers_map', array ('type' => 1));
		foreach ($maps as $map) {
			if ($map->status == 0)
				continue;
				
				
			$currentassign = $DB->get_record('markers_assign', array('id' => $map->assignid));
			if ($currentassign == null) {
				continue;
			}
			
			if ($behalf) {
				if ($map->endmarkerid == 0)
					return $map;
			}
			else {
				if ($map->endmarkerid != 0)
					return $map;
			}
		}
		
		// if we reach here, we didn't find any map
		return null;
	}
	
	function test_get_correct_assignid_not_behalf() {
		global $DB;
		$map = $this->get_agreed_maps(0);
		if ($map == null) {
			echo get_info_msg('test_get_correct_assignid_not_behalf(): not found details for any map with not behalf');
			$this->assertTrue(true);
			return;		
		}
		
		$currentassign = $DB->get_record('markers_assign', array('id' => $map->assignid), '*', MUST_EXIST);
		$actualassign = $DB->get_record('markers_assign', array('courseid' => $currentassign->courseid, 'studentid' => $currentassign->studentid, 'markerid' => $map->endmarkerid), '*', MUST_EXIST);
		
		$assignid = markers_get_correct_assignid($map);
		
		$this->assertTrue($assignid == $actualassign->id);
		
	}
	
	function test_get_correct_assignid_behalf() {
		global $DB;
		$map = $this->get_agreed_maps(1);
		if ($map == null) {
			echo get_info_msg('test_get_correct_assignid_behalf(): not found details for any map with behalf');
			$this->assertTrue(true);
			return;		
		}
		
		$currentassign = $DB->get_record('markers_assign', array('id' => $map->assignid), '*', MUST_EXIST);
		$where = 'courseid = ' . $currentassign->courseid . ' AND studentid = ' . $currentassign->studentid . ' AND role = \'' . get_string('supervisor', 'local_markers') . '\'';  
		$actualassign = $DB->get_record_select('markers_assign', $where, null, '*', MUST_EXIST);
		
		$assignid = markers_get_correct_assignid($map);
		
		$this->assertTrue($assignid == $actualassign->id);
		
	}
	
	function test_allow_profile_view_zero_param() {
		global $USER;
	
		// change user from admin to a single one
		$actualuser = $USER->id;
		$USER->id = 3; // a non admin user
		$allow = markers_allow_profile_view(0, 1);
		
		$this->assertTrue(!$allow);
		
		echo $allow = markers_allow_profile_view(1, 0);
		
		$this->assertTrue(!$allow);
		
		echo $allow = markers_allow_profile_view(0,0);
		
		// revert user to admin
		$USER->id = $actualuser;
		
		$this->assertTrue(!$allow);
		
	}
	
	function test_allow_profile_view_same() {
		global $USER;
	
		// change user from admin to a single one
		$actualuser = $USER->id;
		$USER->id = 3; // a non admin user
		$allow = markers_allow_profile_view(3, 3);
		
		$this->assertTrue($allow);
		
		// revert user to admin
		$USER->id = $actualuser;
		
	}
	
	function test_allow_profile_view_student_marker() {
		global $DB, $USER;
		
		// change user from admin to a single one
		$actualuser = $USER->id;
		$USER->id = 3; // a non admin user
		
		$assigns = $DB->get_records('markers_assign');
		if ($assigns == null) {
			echo get_info_msg('test_allow_profile_view_student_marker(): no assigns available');
			$this->assertTrue(true);
			return;					
		}
		
		$assign = reset($assigns);
		$allow = markers_allow_profile_view($assign->markerid, $assign->studentid);
		$this->assertTrue($allow);
		
		// revert user to admin
		$USER->id = $actualuser;		
		
	}
	
	function test_allow_profile_view_same_student_markers() {
		global $DB, $USER;
		
		// change user from admin to a single one
		$actualuser = $USER->id;
		$USER->id = 3; // a non admin user
		
		$assigns = $DB->get_records('markers_assign');
		if ($assigns == null) {
			echo get_info_msg('test_allow_profile_view_same_student_markers(): no assigns available');
			$this->assertTrue(true);
			return;					
		}
		
		$assign = reset($assigns);
		
		$same = $DB->get_records('markers_assign', array('courseid' => $assign->courseid, 'studentid' => $assign->studentid));
		
		$first = null;
		$second = null;
		foreach ($same as $marker) {
			if ($first == null) {
				$first = $marker->markerid;
				continue;
			}
				
			if ($second == null) {
				$second = $marker->markerid;
				break;
			}
		}
		
		$allow = markers_allow_profile_view($first, $second);
		$this->assertTrue($allow);
		
		// revert user to admin
		$USER->id = $actualuser;		
		
	}
	
	function test_allow_profile_view_teacher_student() {
		global $DB, $USER;
		
		// change user from admin to a single one
		$actualuser = $USER->id;
		$USER->id = 3; // a non admin user
		
		$assigns = $DB->get_records('markers_assign');
		if ($assigns == null) {
			echo get_info_msg('test_allow_profile_view_same_student_markers(): no assigns available');
			$this->assertTrue(true);
			return;					
		}
		
		$teacher = null;
		$student = null;
		$teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'), '*', MUST_EXIST);
	
		foreach ($assigns as $assign) {
			//$context = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $assign->courseid), '*', MUST_EXIST);
			$context = get_context_instance(CONTEXT_COURSE, $assign->courseid);
			$teachers = $DB->get_records('role_assignments', array('roleid' => $teacherrole->id, 'contextid' => $context->id));
			foreach ($teachers as $ateacher) {
				$try = $DB->get_record('markers_assign', array('courseid' => $assign->courseid, 'studentid' => $assign->studentid, 'markerid' => $ateacher->userid));
				if ($try == null) {
					$teacher = $ateacher->userid;
					$student = $assign->studentid;
					break;
				}
			}			
		}
		
		if ($teacher == null) {
			echo get_info_msg('test_allow_profile_view_teacher_student(): no teacher available who is not also a marker of a student');
			$this->assertTrue(true);
			return;				
		}
		$allow = markers_allow_profile_view($teacher, $student);
		$this->assertTrue($allow);
		
		// revert user to admin
		$USER->id = $actualuser;		
		
	}
	
	function get_assignment_submission($submit=true) {
		global $DB;
		
		$courses = $DB->get_records('course');
		foreach ($courses as $course) {
			$context = get_context_instance(CONTEXT_COURSE, $course->id);
			$studentrole = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
			$students = $DB->get_records('role_assignments', array('roleid' => $studentrole->id, 'contextid' => $context->id));
			$assignments = $DB->get_records('assignment', array('course' => $course->id));
			foreach ($assignments as $assignment) {
				foreach ($students as $student) {
					$submission = $DB->get_record('assignment_submissions', array('assignment' => $assignment->id, 'userid' => $student->userid));
					if (($submission == null && !$submit) || ($submission != null && $submit)) {
						$obj = new stdClass();
						$obj->assignment = $assignment;
						$obj->studentid = $student;
						return $obj;
					}
				}
			}
		}
		return null;
	}
	
	/*
	function test_get_submission_details() {
	
		$obj = $this->get_assignment_submission(true);
		if ($obj == null) {
			echo get_info_msg('test_get_submission_details(): no students with submission found');
			$this->assertTrue(true);
			return;			
		}
		$html = null;
		try {
			$html = get_submission_details($obj->assignment, $obj->studentid);
		}
		catch (Exception $e) {
			echo $e->getMessage();
		}
		$this->assertTrue($html != null);
	} */
	
	function test_is_a_marker() {
		global $DB;
	
		$assigns = $DB->get_records('markers_assign');
		if ($assigns == null) {
			echo get_info_msg('test_is_a_marker(): no assigns avaialable');
			$this->assertTrue(true);
			return;				
		}
		
		$assign = reset($assigns);
		$this->assertTrue(is_marker($assign->courseid, $assign->markerid, $assign->studentid));
	}
	
	function test_is_not_a_marker() {
		global $DB;
	
		$assigns = $DB->get_records('markers_assign');
		if ($assigns == null) {
			echo get_info_msg('test_is_not_a_marker(): no assigns avaialable');
			$this->assertTrue(true);
			return;				
		}
		
		$assign = reset($assigns);
		
		$oncourse = $DB->get_records('markers_assign', array('courseid' => $assign->courseid));
		$first = reset($oncourse);
		$second = next($oncourse);
		$this->assertTrue(!is_marker($first->courseid, $first->studentid, $second->studentid));
	}
	
	function get_assignment_for_markers($setup=true) {
		global $DB;
	
		$assignments = $DB->get_records('assignment');
		foreach ($assignments as $assignment) {
			$tsetup = $DB->get_record('markers_setup', array('assignmentid' => $assignment->id));
			if (($setup && $tsetup != null) || (!$setup && $tsetup == null))
				return $assignment; 
		}
		
		return null;
	}
	
	function test_multiple_markers_assignment() {
		global $DB;
		$assignment = $this->get_assignment_for_markers(false); // get an assignment that has not been setup for multiple markers
		
		if ($assignment == null) {
			echo get_info_msg('test_multiple_markers_assignment_true(): no assignments found that has not been setup for multiple markers');
			$this->assertTrue(true);
			return;			
		}
		
		multiple_markers_assignment($assignment->id, $assignment->course, true); // call function to set it as multiple markers
		$setup = $DB->get_record('markers_setup', array('assignmentid' => $assignment->id));
		$this->assertTrue($setup != null);
		
		multiple_markers_assignment($assignment->id, $assignment->course, false); // call function to unset multiple markers option
		$setup = $DB->get_record('markers_setup', array('assignmentid' => $assignment->id));
		print_r($setup);
		$this->assertTrue($setup == null);
		
	}
	
	/*
	function test_multiple_markers_assignment_false() {
		global $DB;
		//$assignment = $this->get_assignment_for_markers(true); // get an assignment that has been setup for multiple markers
		
		if ($this->assignment == null) {
			echo get_info_msg('test_multiple_markers_assignment_false(): no assignments found that has been setup for multiple markers');
			$this->assertTrue(true);
			return;			
		}
		
		multiple_markers_assignment($this->assignment->id, false); // call function to unset multiple markers option
		$setup = $DB->get_record('markers_setup', array('assignmentid' => $assignment->id));
		$this->assertTrue($setup == null);
	} */
	
	
	
	
	function test_markers_get_status_view_no_behalf_permission() {
		global $USER, $DB;
		
		$courses = $DB->get_records('course');
		$students = null;
		foreach ($courses as $course) {
			$students = m_get_students($course->id);
			if ($students != null) {
				break;
			}
		}
		
		if ($students == null) {
			echo get_info_msg('test_markers_get_status_view_no_behalf_permission(): no students found on any course');
			$this->assertTrue(true);
			return;						
		}				
		
		$admin = $USER;
		
		$student = reset($students);
		
		$USER = $student;			
		
		$exception = false;
		try {
			$html = markers_get_status_view(0, 0, 0, 1);
		}
		catch (Exception $e) {
			echo get_info_msg('Exception: ' . $e->getMessage());
			$exception = true;
		}
		
		$USER = $admin;
		
		$this->assertTrue($exception);		
	}
	
	function test_markers_get_status_view_behalf_permission() {
		global $USER, $DB;
		
		$courses = $DB->get_records('course');
		$teachers = null;
		foreach ($courses as $course) {
			$teachers = m_get_teachers($course->id);
			if ($teachers != null) {
				break;
			}
		}
		
		if ($teachers == null) {
			echo get_info_msg('test_markers_get_status_view_behalf_permission(): no teachers found on any course');
			$this->assertTrue(true);
			return;						
		}
		
		$admin = $USER;
		
		$teacher = reset($teachers);
		
		$USER = $teacher;	
		
		$exception = false;
		
		try {
			$html = markers_get_status_view(0, 0, 0, 1);
		}
		catch (Exception $e) {
			echo get_info_msg('Exception: ' . $e->getMessage());
			$exception = true;
		}
		
		$USER = $admin;
		
		$this->assertTrue(!$exception);
	}
	
	function test_markers_get_status_view_marker_permission() {
		global $USER, $DB;
		
		$admin = $USER;
		
		$courses = $DB->get_records('course');
		$marker = null;
		foreach ($courses as $course) {
			$markers = $DB->get_records('markers_assign', array('courseid' => $course->id));
			$context = get_context_instance(CONTEXT_COURSE, $course->id);
			foreach ($markers as $m) {
				$user = $DB->get_record('user', array('id' => $m->markerid), '*', MUST_EXIST);
				$USER = $user;
				if (!has_capability('local/markers:editingteacher', $context)) {
					$marker = $user;
					break;
				}
			}
			
		}
		
		if ($marker == null) {
			echo get_info_msg('test_markers_get_status_view_marker_permission(): no marker found on any course who is not an editingteacher');
			$this->assertTrue(true);
			return;						
		}	
		
		// assertion 1
		$exception = false;
		
		try {
			$html = markers_get_status_view(0, 0, 0, 0);
		}
		catch (Exception $e) {
			echo get_info_msg('Exception: ' . $e->getMessage());
			$exception = true;
		}
		
		$this->assertTrue(!$exception);
		
		// assertion 2
		$exception = false;
		
		try {
			$html = markers_get_status_view(0, 0, 0, 1);
		}
		catch (Exception $e) {
			echo get_info_msg('Exception: ' . $e->getMessage());
			$exception = true;
		}
		
		$this->assertTrue($exception);
		
		
		$USER = $admin;
	}
	
	function test_markers_get_status_view_specific_course_permission() {
		global $DB, $USER, $CFG;
		$admin = $USER;	
	
		// first assertion
		$courses = $DB->get_records('course');
		$student = null;
		$selcourse = null;
		foreach ($courses as $course) {
			$students = m_get_students($course->id);
			$context = get_context_instance(CONTEXT_COURSE, $course->id);
			foreach ($students as $s) {
				$USER = $s;
				if (!has_capability('local/markers:editingteacher', $context)) {
					$student = $s;
					$selcourse = $course;
					break;
				}
			}
		}
		
		if ($student == null) {
			echo get_info_msg('test_markers_get_status_view_specific_course_permission(): no student found on any course who is not an editingteacher');
			$this->assertTrue(true);
			return;						
		}
		
		$exception = false;
		
		try {
			$html = markers_get_status_view($selcourse->id, 0, 0, 0);
		}
		catch (Exception $e) {
			echo get_info_msg('Exception: ' . $e->getMessage());
			$exception = true;
		}
		
		$this->assertTrue($exception);
		
		
		// second assertion		
		$courses = $DB->get_records('course');
		$marker = null;
		$selcourse = null;
		foreach ($courses as $course) {
			$markers = $DB->get_records('markers_assign', array('courseid' => $course->id));
			$context = get_context_instance(CONTEXT_COURSE, $course->id);
			foreach ($markers as $m) {
				$user = $DB->get_record('user', array('id' => $m->markerid), '*', MUST_EXIST);
				$USER = $user;
				if (!has_capability('local/markers:editingteacher', $context)) {
					$marker = $user;
					$selcourse = $course;
					break;
				}
			}
			
		}
		
		if ($marker == null) {
			echo get_info_msg('test_markers_get_status_view_specific_course_permission(): no marker found on any course who is not an editingteacher');
			$this->assertTrue(true);
			return;						
		}
		
		$exception = false;
		
		try {
			$html = markers_get_status_view($selcourse->id, 0, 0, 0);
		}
		catch (Exception $e) {
			echo get_info_msg('Exception: ' . $e->getMessage());
			$exception = true;
		}
		
		$this->assertTrue(!$exception);
		
		// third assertion
		
		// find an assignment which allows multiple markers on that course
		$SQL = "SELECT a.* from " . $CFG->prefix . "assignment a, " . $CFG->prefix . "markers_setup s WHERE course=" . $selcourse->id
					. " AND s.assignmentid = a.id";
		$assignments = $DB->get_records_sql($SQL);
		if ($assignments == null) {
			echo get_info_msg('test_markers_get_status_view_specific_course_permission(): no assignment found with multiple markers on that specific course');
			$this->assertTrue(true);
			return;			
		}
		$assignment = reset($assignments);
		
		// find a student of this marker
		$students = $DB->get_records('markers_assign', array ('courseid' => $selcourse->id, 'markerid' => $USER->id));
		if ($students == null) {
			echo get_info_msg('test_markers_get_status_view_specific_course_permission(): no students found for that marker on that course');
			$this->assertTrue(true);
			return;				
		}
		
		$student = reset($students);
		
		try {
			$html = markers_get_status_view($selcourse->id, $assignment->id, $student->studentid, 0);
		}
		catch (Exception $e) {
			echo get_info_msg('Exception: ' . $e->getMessage());
			$exception = true;
		}		
		
		$theone = $this->return_first_table_view($html);
		$my = $this->extract_array_from_table($theone);		
		
		$cname = str_replace(' ', '', $selcourse->fullname);

		$aname = str_replace(' ', '', $assignment->name);
		
		$stud = $DB->get_record('user', array ('id' => $student->studentid), '*', MUST_EXIST);
		$sname = $stud->firstname . $stud->lastname;
		
		
		$mrole = str_replace(' ', '', $student->role);
		
		$setup = $DB->get_record('markers_setup', array('assignmentid' => $assignment->id), '*', MUST_EXIST);
		
		$map = $DB->get_record('markers_map', array('setupid' => $setup->id, 'assignid' => $student->id, 'type' => 1), '*', MUST_EXIST);
		$status = true;
		if ($map->status == 1) {
			$status = get_string('completed', 'local_markers') == $my['status'];
		}
		else {
			$status = get_string('completed', 'local_markers') != $my['status'];		
		}
		
		
		
		$this->assertTrue($cname == $my['course'] &
											$aname == $my['assignment'] &
											$sname == $my['student'] &
											$mrole == $my['yourrole'] &
											$status);
											
		
		
		$USER = $admin;	
	}
	
	function extract_array_from_table($html) {
		global $CFG;

		require_once($CFG->dirroot . '/local/markers/tblextractor.php');
		$tbl = new tableExtractor(); 
		$tbl->source = $html;
		$extract = $tbl->extractTable();
		$my = array();
		$keys = array_keys(reset($extract));
		$k = strip_tags(strtolower(str_replace(':', '', reset($keys))));
		$f = next($keys);
		$my[$k] = $f;
		foreach ($extract as $ext) {
			$first = strip_tags(strtolower(str_replace(':', '', reset($ext))));
			$second = strip_tags(next($ext));
			$my[$first] = $second;
		}
		return $my;			
	}

	function return_first_table_view($mform) {
		$_form = $mform->get_form();
		$elements = $_form->_elements;
		$sel = array();
		foreach ($elements as $el) {
			if ($el instanceof HTML_QuickForm_html)
				$sel[] = $el->_text;
			}
			$theone = reset($sel); // the first one is the filter by
			$theone = next($sel);
			return $theone;
	}
	
	function test_m_get_teachers_correct_parameter() {
		global $DB;
		
		$courses = $DB->get_records('course');
		if ($courses == null) {
			echo get_info_msg('test_m_get_teachers_correct_parameter(): no courses found');
			$this->assertTrue(true);
			return;					
		}
		
		$course = reset($courses);
		$ex = false;
		
		try {
			$teachers = m_get_teachers($course->id);
		}
		catch (Exception $e){
			$ex = true;
		}
		
		$this->assertTrue(!$ex);
	}
	
	function test_m_get_teachers_wrong_parameter() {
		$ex = false;
		
		try {
			$teachers = m_get_teachers(-1);
		}
		catch (Exception $e){
			$ex = true;
		}
		
		$this->assertTrue($ex);
	}
	
	function test_m_get_students_correct_parameter() {
		global $DB;
		
		$courses = $DB->get_records('course');
		if ($courses == null) {
			echo get_info_msg('test_m_get_students_correct_parameter(): no courses found');
			$this->assertTrue(true);
			return;					
		}
		
		$course = reset($courses);
		$ex = false;
		
		try {
			$students = m_get_students($course->id);
		}
		catch (Exception $e){
			$ex = true;
		}
		
		$this->assertTrue(!$ex);
	}
	
	function test_m_get_students_wrong_parameter() {
		$ex = false;
		
		try {
			$students = m_get_students(-1);
		}
		catch (Exception $e){
			$ex = true;
		}
		
		$this->assertTrue($ex);
	}					
}

require_once($CFG->dirroot . '/mod/assignment/locallib.php');

class assignmentlocallib_test extends UnitTestCase {

	function test_assignment_view_marking_details_null_param() {
		$ex = false;
		try {
			assignment_view_marking_details(null, null);
		}
		catch (Exception $e) {
			$ex = true;
		}
		
		$this->assertTrue($ex);
	}
	
	function test_allow_multiple_markers() {
		global $DB;
		$setups = $DB->get_records('markers_setup');
		if ($setups == null) {
			echo get_info_msg('test_allow_multiple_markers(): no assignments with multiple markers found');
			$this->assertTrue(true);
			return;				
		}
		
		$setup = reset($setups);
		
		$result = allow_multiple_markers($setup->assignmentid);
		$this->assertTrue($result != null);
	}
	
	function test_not_allow_multiple_markers() {
		global $DB, $CFG;
		
		$sql = 'SELECT * FROM ' .  $CFG->prefix . 'assignment
						WHERE id NOT IN (SELECT assignmentid FROM ' . $CFG->prefix . 'markers_setup)';
						
		$assignments = $DB->get_records_sql($sql);
		
		if ($assignments == null) {
			echo get_info_msg('test_not_allow_multiple_markers(): no assignments with no multiple markers found');
			$this->assertTrue(true);
			return;				
		}
		
		$ass = reset($assignments);
		
		$result = allow_multiple_markers($ass->id);
		$this->assertTrue($result == null);
	}
	
	function test_get_markers_param() {
		global $DB, $USER, $CFG;
		$assigns = $DB->get_records('markers_assign');
		
		if ($assigns == null) {
			echo get_info_msg('test_get_markers_param(): no markers_assign found');
			$this->assertTrue(true);
			return;					
		}
		
		// first assertion
		$assign = reset($assigns); // get the first one
		//$assignments = $DB->get_records('assignment', array('course' => $assign->courseid));
		
		$sql = 'SELECT * FROM ' . $CFG->prefix . 'assignment 
						WHERE course =' . $assign->courseid . ' AND id IN (SELECT assignmentid FROM ' . $CFG->prefix . 'markers_setup)'; 
		
		$assignments = $DB->get_records_sql($sql);
		if ($assignments == null) {
			echo get_info_msg('test_get_markers_param(): no assignments found with multiple markers for the course specified');
			$this->assertTrue(true);
			return;			
		}
	
		$assignment = reset($assignments);
		
		$setup = $DB->get_record('markers_setup', array ('assignmentid' => $assignment->id), '*', MUST_EXIST);
		
		$_POST['type'] = 0;
		$_POST['assignid'] = $assign->id;
		$return = get_markers_param($assignment->id);
		
		$map = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $assign->id, 'type' => 0), '*', MUST_EXIST);
		
		$predict = new stdClass();
		$predict->map = $map;
		$predict->behalf = 0;
		$predict->rcid = 0;
		$predict->raid = 0;
		$predict->rsid = 0;
		$predict->confirm = 0;
		$predict->tview = 0;
		
		$this->assertTrue($return == $predict);
		
		// second assertion
		$students = m_get_students($assign->courseid);
		if ($students == null) {
			echo get_info_msg('test_get_markers_param(): no students found for the course specified');
			$this->assertTrue(true);
			return;			
		}
		
		$admin = $USER;
		$student = reset($students);
		$USER = $student;
		$ex = false;
		try {
			$return = get_markers_param($assignment->id);		
		}
		catch(Exception $e) {
			$ex = true;
		}
		
		$USER = $admin;
		
		$this->assertTrue($ex);
	}
	
	function test_assignment_process_multiple_markers_error() {
		$ex = false;
		
		$map = new stdClass();
		$map->assignid = -1;
		$map->setupid = -1;
		$map->type = -1;
		$map->id = -1;
		$submitcat = new stdClass();
		$submitcat->multiplecat = -1;
		$submitcat->xgrade = -1;
		$submitcat->submissioncomment_editor= -1;
		try {
			assignment_process_multiple_markers(null, null, $submitcat, $map);
		}
		catch (Exception $e) {
			$ex = true;
		}
		
		$this->assertTrue($ex);
	}
	
	function test_assignment_get_mark_table_error() {
		$map = new stdClass();
		$map->assignid = -1;
		$ex = false;
		try {
			assignment_get_mark_table($map, -1);
		}
		catch (Exception $e) {
			$ex = true;
		}
		
		$this->assertTrue($ex);
	}
	
	function test_assignment_get_mark_table() {
		global $DB, $CFG;
		

		$sql = 'SELECT * FROM '. $CFG->prefix . 'markers_map 
						WHERE type = 1 AND id IN (SELECT mapid FROM ' . $CFG->prefix . 'markers_assess WHERE categoryid=-1)';
		
		$maps = $DB->get_records_sql($sql);
		if ($maps == null) {
			echo get_info_msg('test_assignment_get_mark_table(): could not find records on markers_map that has categoryid=-1 on markers_assess');
			$this->assertTrue(true);
			return;					
		}
		$map = reset($maps);
		$html = null;
		$html = assignment_get_mark_table($map, -1);
		
		$this->assertTrue($html != null);
	}

}

