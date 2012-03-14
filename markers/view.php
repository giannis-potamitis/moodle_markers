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




if (isguestuser()) {
			print_error(get_string('norightpermissions', 'local_markers'));
	die;
}

$cid = optional_param('cid', 0, PARAM_INT); // course id
$aid = optional_param('aid', 0, PARAM_INT); // assignment id
$sid = optional_param('sid', 0, PARAM_INT); // student id
$behalf = optional_param('behalf', 0, PARAM_INT); // if 0 then the page is accessed by a marker
																									// if 1 then the page is accessed by admin/teacher

if ($cid < 0 || $aid < 0 || $sid < 0) {
	print_error(get_string('nonegative', 'local_markers'));
	die;
}

if ($cid > 0) {
	$course = $DB->get_record('course', array('id' => $cid), '*', MUST_EXIST);
}

if ($aid > 0) {
	$assignment = $DB->get_record('assignment', array('id' => $aid), '*', MUST_EXIST);
}

if ($cid > 0 && $aid > 0) {
	$cm = get_coursemodule_from_instance('assignment', $aid, $cid);
	require_login($cid, true, $cm);
	$context = get_context_instance(CONTEXT_MODULE, $cm->id);
}
else if ($cid > 0) {
	$context = get_context_instance(CONTEXT_COURSE, $cid);
	require_login($cid);
}
else if ($aid > 0) {
	$cm = get_coursemodule_from_instance('assignment', $aid, $assignment->course);
	require_login($assignment->course, true, $cm);
	$context = get_context_instance(CONTEXT_MODULE, $cm->id);
}
else {
	require_login();
	$context = get_context_instance(CONTEXT_SYSTEM);
}

//$context = get_context_instance(CONTEXT_SYSTEM);
//$context = get_context_instance(CONTEXT_USER, $USER->id);

$url = new moodle_url('/local/markers/view.php', array('cid' => $cid, 'aid' => $aid, 'sid' => $sid, 'behalf' => $behalf));

/// Print the page header
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(format_string(get_string('markerstatus', 'local_markers')));
$PAGE->set_heading(format_string(get_string('markerstatus', 'local_markers')));
$PAGE->set_pagelayout('mydashboard');

// Set the navbar
if ($cid > 0 || $aid > 0) {
	$PAGE->navbar->add(get_string('markerstatus', 'local_markers'), $url);	
}
else  {
	$PAGE->navbar->ignore_active();
	$PAGE->navbar->add(get_string('markerstatus', 'local_markers'), $url);
}

// Taken from http://stackoverflow.com/questions/5660700/javascript-to-open-popup-window-and-disable-parent-window
// and improved to achieved further tasks.
$html = "<script type=\"text/javascript\">
					var popupWin=null;
					var refresh = false;
					var thehtml = null;
					function popup_open(url)
					{ 
						thehtml = document.getElementById('mform1').innerHTML; // currently not used
						document.getElementById('mform1').innerHTML = \"" . get_string('viewingmark', 'local_markers') . "\";
						popupWin = window.open(url,\"popup\",\"directories=no, status=no, location=no, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, width=850, height=600,top=0,left=0\");
						refresh = true;

					}
					function parent_disable() {
						if(popupWin && !popupWin.closed)
							popupWin.focus();
							
							
						if (popupWin != null && popupWin.closed && refresh == true) {
							//document.getElementById('mform1').innerHTML = thehtml;
							window.location.reload(true);
							refresh = false;
						}	
						
					}
					function popup(url, thewidth, theheight) {
						window.open(url,\"popup\",\"directories=no, status=no, location=no, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, width=\" + thewidth + \", height=\" + theheight +\",top=0,left=0\");
					}
			</script>" ;

$html .= "<body onFocus=\"parent_disable();\" onclick=\"parent_disable();\"></body>";
echo $html;

	
// Output starts here
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('markerstatus', 'local_markers'));

$theform = markers_get_status_view($cid, $aid, $sid, $behalf);
	
$theform->display();

// Finish the page
echo $OUTPUT->footer();
