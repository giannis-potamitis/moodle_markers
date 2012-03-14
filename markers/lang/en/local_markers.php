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
 * English strings for markers
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage markers
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Multiple Markers';
$string['student'] = 'Student';
$string['selectsupervisor'] = 'Supervisor';
$string['selectsecondmarker'] = 'Second Marker';
$string['supervisor'] = 'Supervisor';
$string['secondmarker'] = 'Second Marker';
$string['role'] = 'Role';
$string['othermarker'] = 'Other Marker';
$string['assignmarkertitle'] = 'Allocate markers to students';
$string['assignmarkersheading'] = 'Allocate markers to students';
$string['notallhavemarkers'] = 'Some enrolled students have not been allocated a ' . $string['supervisor'] . ' or a ' . $string['secondmarker'] . '!'; 
$string['markers:markerenrolment'] = 'Assign supervisor and second Marker to students';
$string['allhavemarkers'] = 'All enrolled students have been allocated a ' . $string['supervisor'] . ' and a ' . $string['secondmarker'] .'!';
$string['err_selectsupervisor'] = 'Select a ' . $string['supervisor'] .' for ';
$string['err_selectsecondmarker'] = 'Select a ' . $string['secondmarker'] .' for ';
$string['err_supersecondsame'] = $string['supervisor'] . ' and ' . $string['secondmarker'] . ' cannot be the same person for ';
$string['err_selectothermarker'] = 'cannot be empty. Either select a marker from the list or delete that marker for ';
$string['err_superothersame'] = 'and ' . $string['supervisor'] . ' cannot be the same person for ';
$string['err_secondothersame'] = 'and ' . $string['secondmarker'] . ' cannot be the same person for ';
$string['err_emptyrole'] = 'Role cannot be empty';
$string['submitmark'] = 'Submit';
$string['feedbackfrommarkers'] = 'Feedback from your markers';
$string['waitstudentsubmit'] = 'Awaiting student\'s submission';
$string['course'] = 'Course:';
$string['assignment'] = 'Assignment:';
$string['student'] = 'Student:';
$string['yourrole'] = 'Your Role:';
$string['waityourmark'] = 'Awaiting your grade';
$string['waitothermark'] = 'Awaiting other grades';
$string['waitagreedmark'] = 'Awaiting agreed grade';
$string['completed'] = 'Completed';
$string['status'] = 'Status:';
$string['othermarkers'] = 'Other Markers:';
$string['markers'] = 'Markers:';
$string['waitingformark'] = 'awaiting grade';
$string['viewmark'] = '(view grade)';
$string['editmark'] = 'Update your grade';
$string['editagreedmark'] = 'Update agreed grade';
$string['markerstatus'] = 'Marker Status';
$string['filterby'] = 'Filter by';
$string['thecourse'] = 'Course';
$string['theassignment'] = 'Assignment';
$string['noinfo'] = 'No available information';
$string['norightpermissions'] = 'You do not have the right permissions to access this page';
$string['viewindividualmark'] = 'View Grade';
$string['themarker'] = 'Marker';
$string['therole'] = 'Role';
$string['allowviewmark'] = 'Please note that you won\'t be able to further update your grade if you proceed on viewing other grades.';
$string['viewingmark'] = 'Viewing grade in progress. Please close popup window to return back';
$string['grade'] = 'Grade';
$string['feedback'] = 'Feedback';
$string['category'] = 'Category';
$string['total'] = 'Total';
$string['generalfeedback'] = 'General Feedback';
$string['close'] = 'Close';
$string['thestudent'] = 'Student';
$string['nostudents'] = 'no students available';
$string['noassignments'] = 'no assignments available';
$string['all'] = 'all';
$string['nonegative'] = 'Page parameters cannot be negative';
$string['viewyourmark'] = 'View your grade';
$string['viewagreedmark'] = 'View agreed grade';
$string['agreedmark'] = 'Agreed Grade';
$string['submission'] = 'Submission';
$string['markers:editingteacher'] = 'Privilege access in the status view page of multiple markers is allowed only to editing teachers of a course';
$string['markers:admin'] = 'This capability defines an admin user. So everyone else should be prohibited from it';
$string['currenltynoinfo'] = 'There are currently no information available for accessing this page';
$string['nocourses'] = 'no courses available';
$string['noallowedit'] = 'Sorry, you are not allowed to submit changes';
$string['err_getcorrectassid'] = 'Unexpected error on get_correct_assignid. Please contact the administrator.';
$string['details'] = 'Details';
$string['waitindividualmarks'] = 'Awaiting individual grades';
$string['err_status'] = 'Unexpected error on markers_current_status_msg. Please contact the administrator.';
$string['err_statusview'] = 'Unexpected error on markers_get_status_view. Please contact the administrator.';
$string['viewthemark'] = 'View grade';
$string['waitingforthemark'] = 'Awaiting grade';
$string['editthemark'] = 'Update grade';
$string['deletethemark'] = 'Delete grade';
$string['alloweditmark'] = 'Allow update grade';
$string['yes'] = 'yes';
$string['no'] = 'no';
$string['yourindividualmark'] = 'Your individual grade';
$string['view'] = 'View';
$string['edit'] = 'Update';
$string['delete'] = 'Delete';
$string['agreedmark'] = 'Agreed grade';
$string['agreedmarkby'] = 'Agreed grade by';
$string['onbehalfof'] = 'On behalf of';
$string['actualmarkers'] = 'the actual markers';
$string['submittedby'] = 'Submitted by';
$string['onbehalfof2'] = 'on behalf of';
$string['wrongparameters'] = 'Wrong parameters';
$string['unexpectederroroccured'] = 'Unexpected error occured';
$string['assformMarkers'] = 'Markers';
$string['allowmultiplemarkers'] = 'Allow multiple markers';
$string['choose'] = 'Choose';
$string['courseselect'] = 'Course';
$string['unexpectedNomarkers'] = 'Unexpected error on markers_get_status_view(): no markers found';
$string['confirmdeletemark'] = 'You are about to delete that grade. Press ok if you really want to.';
$string['confirmagreedmark'] = 'Please note that you won\'t be able to further update your individual grade if you proceed on submitting the agreed grade.';
$string['tviewMarkers'] = 'Markers';
$string['tviewGrade'] = 'Grade';
$string['tviewUpdate'] = 'Update';
$string['tviewAwaitingOthers'] = 'Awaiting other grades';
$string['tviewAgreedGrade'] = 'Agreed Grade';
$string['tviewAgreedUpdate'] = 'Agreed Update';
$string['tviewCompleted'] = 'Completed';
$string['tviewMore'] = 'More Details';
$string['tviewFor'] = 'for';
$string['noSubmissionYet'] = 'No submission yet';
$string['sMarker'] = 'Marker';
$string['sFeedback'] = 'Feedback';
$string['sRole'] = 'Role';
$string['markers:anyteacher'] = 'Any Teacher';
$string['markers:student'] = 'Student';
$string['automaticassign'] = 'Automatic Allocation';
$string['removeoldassigns'] = 'Please note that this will remove any old allocations to markers and will re-allocate.  This means that any individual grades given by markers will be lost.  Final – agreed – grades will not be lost.';
$string['twomarkersatleast'] = 'At least two markers (editing and/or non-editing teachers) must be enrolled in the course to proceed';
$string['nomarkerfound'] = 'Unexpected error: no any marker found';
$string['allocatemarkers'] = 'Allocate Markers';
$string['nostudents'] = 'There are no students enrolled in this course';
$string['moremarkers'] = 'At least two markers (editing and/or non-editing teachers) must be enrolled in this course';
$string['fromlist'] = 'Please select from the list';
