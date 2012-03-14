<?php

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->libdir.'/plagiarismlib.php');

/* --------- Giannis ----------- */
require_once("locallib.php");
/* ----------------------------- */

$id   = optional_param('id', 0, PARAM_INT);          // Course module ID
$a    = optional_param('a', 0, PARAM_INT);           // Assignment ID
$mode = optional_param('mode', 'all', PARAM_ALPHA);  // What mode are we in?
$download = optional_param('download' , 'none', PARAM_ALPHA); //ZIP download asked for?

$url = new moodle_url('/mod/assignment/submissions.php');
if ($id) {
    if (! $cm = get_coursemodule_from_id('assignment', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $assignment = $DB->get_record("assignment", array("id"=>$cm->instance))) {
        print_error('invalidid', 'assignment');
    }

    if (! $course = $DB->get_record("course", array("id"=>$assignment->course))) {
        print_error('coursemisconf', 'assignment');
    }
    $url->param('id', $id);
} else {
    if (!$assignment = $DB->get_record("assignment", array("id"=>$a))) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", array("id"=>$assignment->course))) {
        print_error('coursemisconf', 'assignment');
    }
    if (! $cm = get_coursemodule_from_instance("assignment", $assignment->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
    $url->param('a', $a);
}

if ($mode !== 'all') {
    $url->param('mode', $mode);
}

/* --------------- Giannis ------------------- */
if (is_readable($CFG->dirroot . '/local/markers/locallib.php') && allow_multiple_markers($assignment->id)) {
	$type = optional_param('type', -1, PARAM_INT);
	$assignid = optional_param('assignid', 0, PARAM_INT);
	$behalf = optional_param('behalf', 0, PARAM_INT);
	$rcid = optional_param('rcid', 0, PARAM_INT); // return course id
	$raid = optional_param('raid', 0, PARAM_INT); // return assignment id
	$rsid = optional_param('rsid', 0, PARAM_INT); // return student id
	$confirm = optional_param('confirm', 0, PARAM_INT);
	$tview = optional_param('tview', 0, PARAM_INT); // table view environment
	$url->param('type', $type);
	$url->param('assignid', $assignid);
	$url->param('behalf', $behalf);
	$url->param('rcid', $rcid);
	$url->param('raid', $raid);
	$url->param('rsid', $rsid);
	$url->param('confirm', $confirm);
	$url->param('tview', $tview);
	
	if ($behalf == 1) {
		// If user claims that can access this page in a privilege mode
		// then we have to check it first
		$context = get_context_instance(CONTEXT_USER, $USER->id);
		if (!has_capability('local/markers:admin', $context)) { // if the user is not admin
			$context = get_context_instance(CONTEXT_COURSE, $course->id);
		 	if (!has_capability('local/markers:editingteacher', $context)) {
				// the user is not an editing teacher either
				print_error(get_string('norightpermissions', 'local_markers'));
				die;
			}
		}
	}
}

/* ------------------------------------------- */

$PAGE->set_url($url);
require_login($course->id, false, $cm);

require_capability('mod/assignment:grade', get_context_instance(CONTEXT_MODULE, $cm->id));

$PAGE->requires->js('/mod/assignment/assignment.js');

/// Load up the required assignment code
require($CFG->dirroot.'/mod/assignment/type/'.$assignment->assignmenttype.'/assignment.class.php');
$assignmentclass = 'assignment_'.$assignment->assignmenttype;
$assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);

if($download == "zip") {
    $assignmentinstance->download_submissions();
} else {
    $assignmentinstance->submissions($mode);   // Display or process the submissions
}
