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
//
// this file contains all the functions that aren't needed by core moodle
// but start becoming required once we're actually inside the assignment module.

require_once($CFG->dirroot . '/mod/assignment/lib.php');
require_once($CFG->libdir . '/portfolio/caller.php');

/* ---------- Giannis --------------------- */
if (is_readable($CFG->dirroot . '/local/cat/locallib.php')) {
	require_once($CFG->dirroot.'/local/cat/locallib.php');
}
/* ---------------------------------------- */

/**
 * @package   mod-assignment
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignment_portfolio_caller extends portfolio_module_caller_base {

    /**
    * the assignment subclass
    */
    private $assignment;

    /**
    * the file to include when waking up to load the assignment subclass def
    */
    private $assignmentfile;

    /**
    * callback arg for a single file export
    */
    protected $fileid;

    public static function expected_callbackargs() {
        return array(
            'id'     => true,
            'fileid' => false,
        );
    }

    public function load_data() {
        global $DB, $CFG;

        if (! $this->cm = get_coursemodule_from_id('assignment', $this->id)) {
            throw new portfolio_caller_exception('invalidcoursemodule');
        }

        if (! $assignment = $DB->get_record("assignment", array("id"=>$this->cm->instance))) {
            throw new portfolio_caller_exception('invalidid', 'assignment');
        }

        $this->assignmentfile = '/mod/assignment/type/' . $assignment->assignmenttype . '/assignment.class.php';
        require_once($CFG->dirroot . $this->assignmentfile);
        $assignmentclass = "assignment_$assignment->assignmenttype";

        $this->assignment = new $assignmentclass($this->cm->id, $assignment, $this->cm);

        if (!$this->assignment->portfolio_exportable()) {
            throw new portfolio_caller_exception('notexportable', 'portfolio', $this->get_return_url());
        }

        if (is_callable(array($this->assignment, 'portfolio_load_data'))) {
            return $this->assignment->portfolio_load_data($this);
        }

        $submission = $DB->get_record('assignment_submissions', array('assignment'=>$assignment->id, 'userid'=>$this->user->id));

        $this->set_file_and_format_data($this->fileid, $this->assignment->context->id, 'mod_assignment', 'submission', $submission->id, 'timemodified', false);
    }

    public function prepare_package() {
        global $CFG, $DB;
        if (is_callable(array($this->assignment, 'portfolio_prepare_package'))) {
            return $this->assignment->portfolio_prepare_package($this->exporter, $this->user);
        }
        if ($this->exporter->get('formatclass') == PORTFOLIO_FORMAT_LEAP2A) {
            $leapwriter = $this->exporter->get('format')->leap2a_writer();
            $files = array();
            if ($this->singlefile) {
                $files[] = $this->singlefile;
            } elseif ($this->multifiles) {
                $files = $this->multifiles;
            } else {
                throw new portfolio_caller_exception('invalidpreparepackagefile', 'portfolio', $this->get_return_url());
            }
            $baseid = 'assignment' . $this->assignment->assignment->assignmenttype . $this->assignment->assignment->id . 'submission';
            $entryids = array();
            foreach ($files as $file) {
                $entry = new portfolio_format_leap2a_file($file->get_filename(), $file);
                $entry->author = $this->user;
                $leapwriter->add_entry($entry);
                $this->exporter->copy_existing_file($file);
                $entryids[] = $entry->id;
            }
            if (count($files) > 1) {
                // if we have multiple files, they should be grouped together into a folder
                $entry = new portfolio_format_leap2a_entry($baseid . 'group', $this->assignment->assignment->name, 'selection');
                $leapwriter->add_entry($entry);
                $leapwriter->make_selection($entry, $entryids, 'Folder');
            }
            return $this->exporter->write_new_file($leapwriter->to_xml(), $this->exporter->get('format')->manifest_name(), true);
        }
        return $this->prepare_package_file();
    }

    public function get_sha1() {
        global $CFG;
        if (is_callable(array($this->assignment, 'portfolio_get_sha1'))) {
            return $this->assignment->portfolio_get_sha1($this);
        }
        return $this->get_sha1_file();
    }

    public function expected_time() {
        if (is_callable(array($this->assignment, 'portfolio_get_expected_time'))) {
            return $this->assignment->portfolio_get_expected_time();
        }
        return $this->expected_time_file();
    }

    public function check_permissions() {
        $context = get_context_instance(CONTEXT_MODULE, $this->assignment->cm->id);
        return has_capability('mod/assignment:exportownsubmission', $context);
    }

    public function __wakeup() {
        global $CFG;
        if (empty($CFG)) {
            return true; // too early yet
        }
        require_once($CFG->dirroot . $this->assignmentfile);
        $this->assignment = unserialize(serialize($this->assignment));
    }

    public static function display_name() {
        return get_string('modulename', 'assignment');
    }

    public static function base_supported_formats() {
        return array(PORTFOLIO_FORMAT_FILE, PORTFOLIO_FORMAT_LEAP2A);
    }
}

/* ------------- Giannis -------------------------- */
// Call from view_feedback() function to ouput the individual feedback on each category

function assignment_view_marking_details($assignment, $submission) {
	global $DB, $CFG;
	
	if (!is_readable($CFG->dirroot . '/local/cat/locallib.php')) {
		return;
	}	
	
	if ($assignment == null || $submission == null) {
		print_error('Either assignment or submission objects are null!');
		die;
	}

	$tcat = $DB->get_record('cat', array ('assignmentid' => $assignment->id));
	$categories = null;
	if ($tcat != null) {
		$categories = $DB->get_records('cat_category', array ('catid' => $tcat->id), 'priority ASC');
		
		$SQL = 'SELECT * FROM ' . $CFG->prefix . 'cat_submission WHERE ass_subid = ' . $submission->id
					 . ' AND categoryid IN (SELECT id FROM ' . $CFG->prefix . 'cat_category WHERE catid = ' . $tcat->id .')';
		
		if ($DB->get_records_sql($SQL) == null) {
			return; // Nothing more to display so return.  This means that submission has been made before the assignment
							// had been set to multiple categories so we do not have any submissions on multiple categories and subcategories
		} 
		
		echo '<div class="detailmark">';
		echo '<b><p> More Details: </p></b>';
		$num = 0;
		
		echo '<style type="text/css">
		
					table td.bordered {
						border-top: 1px solid #000000;
						border-bottom: 1px solid #000000;
						border-left: 1px solid #000000;
						border-right: 1px solid #000000;
					}
					
					table.mytable {
						position: relative;
						right: 5%;
					}
		
					</style>';
		
		echo '<table class="mytable">';
		foreach ($categories as $cat) {
			$num++;
			//$submitcat = $DB->get_record('mycat_submitcat', array ('categoryid' => $cat->id, 'ass_sub_id' => $submission->id), '*', MUST_EXIST);
			$submitcat = $DB->get_record('cat_submission', array ('categoryid' => $cat->id, 'ass_subid' => $submission->id));
			
			if ($submitcat == null) continue;
			
			echo '<tr><td class="bordered">';
			echo '<table>';
			// category
			echo '<tr>';
			echo '<td><b>' . get_string('category', 'local_cat') . ' ' . $num . ':</b></td>';
			echo '<td>' . $cat->description . '</td>';
			echo '</tr>';
			// weight
			echo '<tr>';			
			echo '<td><b>' . get_string('weight', 'local_cat') . ':</b></td>';
			echo '<td>' . round($cat->weight, 2) . '</td>';
			echo '</tr>';			
			//grade	
			echo '<tr>';								
			echo '<td><b>' . get_string('grade', 'local_cat') . ':</b></td>';
			echo '<td>' . round($submitcat->grade, 2) . ' / ' . round($cat->maxgrade, 2) . '</td>';
			echo '</tr>';
						
			// comment			
			if ($submitcat->feedback != null && $submitcat->feedback != '') {
				echo '<tr>';			
				echo '<td><b>' . get_string('comment', 'local_cat') . ':</b></td>';
				echo '<td>' . $submitcat->feedback . '</td>';
				echo '</tr>';									
			}
			
			echo '</table>';
			
			// subcategories table
			$stable = get_readonly_subcategories_table($cat->id, false, $submission->id);
			if ($stable != null) {
				//echo '<tr>';			
				//echo '<td colspan="2">' . $stable . '</td>';
				//echo '</tr>';
				echo $stable;
			}
			
			echo '</td></tr>';
			
		}
		echo '</table>';
		echo '</div>';
	}
}
/* ------------------------------------------------ */

/* ----------------- Giannis ---------------------- */
function assignment_set_grade($assignment, $submission, $feedback) {
	global $DB, $CFG;
	// check if multiple categories is set for this assignment
	//$catassign = $DB->get_record('mycat_catassign', array('assignmentid' => $assignment->id));
	if (!is_readable($CFG->dirroot . '/local/cat/locallib.php')) {
		$submission->grade = $feedback->xgrade;
	}
	else {
		$cat = $DB->get_record('cat', array('assignmentid' => $assignment->id));	
		if ($cat == null)
			$submission->grade = $feedback->xgrade;
		else
			$submission->grade = 0;
	}
	return $submission;
}
/* ------------------------------------------------ */

/* ----------------- Giannis ---------------------- */
function assignment_process_multiple_categories($assignment, $submission, $submitdata) {
	global $DB, $CFG;
	
	if (!is_readable($CFG->dirroot . '/local/cat/locallib.php')) {
		return;
	}
	
 	if (!isset($submitdata->multiplecat) || $submitdata->multiplecat != 1)
 		return;           	
  $data = cat_objectToarray($submitdata);
	// Get the number of categories
	$tcat = $DB->get_record('cat', array ('assignmentid' => $assignment->id), '*', MUST_EXIST);	

	$categories = $DB->get_records('cat_category', array ('catid' => $tcat->id));
	$count = sizeof($categories);
	$totalmark = 0;
	for ($i = 1; $i <= $count; $i++) {
		$categoryid = $data['categoryid' . $i];
		$thegrade = $data['xgrade' . $i];
		$comment = $data['comment' . $i];
		$insertdata = array ('categoryid' => $categoryid, 'grade' => $thegrade, 'feedback' => $comment, 'ass_subid' => $submission->id);		
		$oldsubmission = $DB->get_record('cat_submission', array ('categoryid' => $categoryid, 'ass_subid' => $submission->id));
		if ($oldsubmission != null) {// then we have submit a form before and now we edit it
			$insertdata['id'] = $oldsubmission->id;
			$DB->update_record('cat_submission', $insertdata);
		}
		else {// this is a new entry
			$DB->insert_record('cat_submission', $insertdata);			
		}
				
		$category = $DB->get_record('cat_category', array('id' => $categoryid), '*', MUST_EXIST);
		$scaledgrade = ($thegrade * $category->weight) / $category->maxgrade;
		$totalmark += $scaledgrade;
		
	}
	$submission->grade = $totalmark;						
}
/* ------------------------------------------------ */

/* ------------------- Giannis ----------------------- */
function assignment_process_multiple_subcategories($assignment, $submission, $submitdata, $mparam) {
 	global $DB, $CFG;
 	
 	if (!is_readable($CFG->dirroot . '/local/cat/locallib.php')) {
 		return;
 	}
 	
 	$cat = $DB->get_record('cat', array ('assignmentid' => $assignment->id));
 	if ($cat == null)
 		return;
 		
 	$markers = false; // we use cat_subcat_submission table
 	if ($mparam != null && $mparam->map->type == 0) {
 		$markers = true; // we use markers_subassess table
 	}
 		
 	$ranks = $DB->get_records('cat_ranks', array('rankid' => $cat->rankid));	
 	$categories = $DB->get_records('cat_category', array('catid' => $cat->id));
 	foreach ($categories as $c) {
 		$subcategories = $DB->get_records('cat_subcat', array('categoryid' => $c->id));
 		if ($subcategories == null)
 			continue; // no subcategories on that category
 		
 		foreach ($subcategories as $subcat) {
 			
 			foreach ($ranks as $r) {
 				
 				$cell = $subcat->id . '-' . $r->id;
 				//echo 'in cell: ' . $cell . ' ';
 				if (isset($submitdata->$cell) && $submitdata->$cell == 1) { // ticked
 					
 					//echo 'TICKED' . '<br/>';
 					if ($markers && !$DB->record_exists('markers_subassess', array('mapid' => $mparam->map->id, 'subcatid' => $subcat->id, 'ranksid' => $r->id))) {
 						$DB->insert_record('markers_subassess', array('mapid' => $mparam->map->id, 'subcatid' => $subcat->id, 'ranksid' => $r->id));
 					}
 					else if (!$markers && !$DB->record_exists('cat_subcat_submission', array('ass_subid' => $submission->id, 'subcatid' => $subcat->id, 'ranksid' => $r->id))) {
						$DB->insert_record('cat_subcat_submission', array('ass_subid' => $submission->id, 'subcatid' => $subcat->id, 'ranksid' => $r->id)); 					
 					}
 				}
 				else { // not ticked
					 				
 					if ($markers && $DB->record_exists('markers_subassess', array('mapid' => $mparam->map->id, 'subcatid' => $subcat->id, 'ranksid' => $r->id))) {
 						$DB->delete_records('markers_subassess', array('mapid' => $mparam->map->id, 'subcatid' => $subcat->id, 'ranksid' => $r->id));
 					}
 					else if (!$markers && $DB->record_exists('cat_subcat_submission', array('ass_subid' => $submission->id, 'subcatid' => $subcat->id, 'ranksid' => $r->id))) {
						$DB->delete_records('cat_subcat_submission', array('ass_subid' => $submission->id, 'subcatid' => $subcat->id, 'ranksid' => $r->id)); 					
 					} 				
 				}
 				
 			}
 			
 		}
 		
 	} 	
}
/* --------------------------------------------------- */


/* --------------------- Giannis ------------------- */
/*
@return markers_setup object if multiple markers is set or null otherwise
*/
function allow_multiple_markers($assignmentid) {
	global $DB, $CFG;
	
	if (!is_readable($CFG->dirroot . '/local/markers/locallib.php')) {
		return null;
	}
	return $DB->get_record('markers_setup', array ('assignmentid' => $assignmentid));

}
/* ------------------------------------------------- */


/* --------------------- Giannis ------------------- */
/*
@return cat object if multiple categories is set or null otherwise
*/
function allow_multiple_categories($assignmentid) {
	global $DB, $CFG;

	if (!is_readable($CFG->dirroot . '/local/cat/locallib.php')) {
		return null;
	}

	return $DB->get_record('cat', array ('assignmentid' => $assignmentid));	
}
/* ------------------------------------------------- */



/* --------------------- Giannis ------------------- */
/*
@return a signle markers_map object if 'multiple markers' option is set or null otherwise
*/
function get_markers_param($assignmentid) {
	global $DB, $CFG, $USER;
	
	if (!is_readable($CFG->dirroot . '/local/markers/locallib.php')) {
		return null;
	}	
	
	$type = optional_param('type', -1, PARAM_INT);
	$assignid = optional_param('assignid', 0, PARAM_INT);
	$behalf = optional_param('behalf', 0, PARAM_INT);
	$rcid = optional_param('rcid', 0, PARAM_INT); // return course id
	$raid = optional_param('raid', 0, PARAM_INT); // return assignment id
	$rsid = optional_param('rsid', 0, PARAM_INT); // return student id
	$confirm = optional_param('confirm', 0, PARAM_INT);
	$tview = optional_param('tview', 0, PARAM_INT); // table view environment		
	$setup = allow_multiple_markers($assignmentid);
	
	if ($setup == null) 
		return null;
	
	if (($type == -1 || $type >= 2 || $assignid <= 0)) { // Check if parameters are not specified
		redirect($CFG->wwwroot . '/local/markers/view.php?behalf=' . $behalf);
	}
	
	if ($behalf < 0 || $behalf > 1)
		$behalf = 0;
	
	$obj = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $assignid, 'type' => $type), '*', MUST_EXIST);
	
	// Check If the parameters are illegal
	$assign = $DB->get_record('markers_assign', array ('id' => $assignid), '*', MUST_EXIST);
	if ($assign->markerid != $USER->id) {// then the user tries to access the page is not the actual marker
		// check if the user is allowed to mark on behalf of the actual marker
		// a) is the user an admin one ?
		$context = get_context_instance(CONTEXT_USER, $USER->id);
		if (!has_capability('local/markers:admin', $context)) { // the user is not an admin
			// check if the user is an editing teacher of the course
			$context = get_context_instance(CONTEXT_COURSE, $assign->courseid);
			if (!has_capability('local/markers:editingteacher', $context)) {
				// it is not even an editing teacher so the user will be not allowed to continue
				print_error(get_string('norightpermissions', 'local_markers'));
				die;
			}
		}
	}
	
	$final = new stdClass();
	$final->map = $obj;
	$final->behalf = $behalf;
	$final->rcid = $rcid;
	$final->raid = $raid;
	$final->rsid = $rsid;
	$final->confirm = $confirm;
	$final->tview = $tview;
	
	return $final;
}
/* ------------------------------------------------- */


/* ---------------- Giannis ----------------------- */
function assignment_map_status_complete($map) {
	global $DB, $USER, $CFG;
	
	if (!is_readable($CFG->dirroot . '/local/markers/locallib.php')) {
		return;
	}	
	
 	$map->altmarkerid = $USER->id;
 	
 	$assign = $DB->get_record('markers_assign', array ('id' => $map->assignid), '*', MUST_EXIST);
 	if ($map->type == 0) {// individual
 		if ($USER->id == $assign->markerid) // then the mark is given by the markerid
 			$map->endmarkerid = $assign->markerid;
 		else
 			$map->endmarkerid = 0; // either a teacher of course or the admin assigned the mark on behalf
 			
 		$map->status = 1; // completed
 		$DB->update_record('markers_map', $map);
 	}
 	else {// type = 1: agreeded
 		$allmarkers = $DB->get_records('markers_assign', array ('courseid' => $assign->courseid, 'studentid' => $assign->studentid));
 		$map->endmarkerid = 0; // Firstly assume that the mark has been given on behalf by admin/teacher
 		foreach ($allmarkers as $marker) {
 			if ($marker->markerid == $USER->id) {
 				$map->endmarkerid = $marker->markerid;
 				break; 
 			}
 		}
 		
 		// Update the status, altmarkerid, endmarkerid of all the markers
 		foreach ($allmarkers as $marker) {
 			$thatmap = $DB->get_record('markers_map', array ('setupid' => $map->setupid, 'assignid' => $marker->id, 'type' => 1), '*', MUST_EXIST);
 			$thatmap->status = 1;
 			$thatmap->endmarkerid = $map->endmarkerid;
 			$thatmap->altmarkerid = $map->altmarkerid;
 			$DB->update_record('markers_map', $thatmap);
 			
 			// change the allowedit field of each individual map
 			$singlemap = $DB->get_record('markers_map', array ('setupid' => $map->setupid, 'assignid' => $marker->id, 'type' => 0), '*', MUST_EXIST);
 			$singlemap->allowedit = 0;
 			$DB->update_record('markers_map', $singlemap); 			
 		}
 	}
}
/* ------------------------------------------------ */



/* ----------------- Giannis ---------------------- */
function assignment_process_multiple_markers($assignment, $submission, $submitdata, $map) {
	global $DB, $CFG;
	
	if (!is_readable($CFG->dirroot . '/local/markers/locallib.php')) {
		return;
	}		
	
	$oldmap = $map; // initially oldmap = map
	if ($map->type == 1) {// agreeded
		require_once($CFG->dirroot . '/local/markers/locallib.php');
		$oldassignid = markers_get_correct_assignid($map);
		$oldmap = $DB->get_record('markers_map', array ('setupid' => $map->setupid, 'assignid' => $oldassignid, 'type' => 1), '*', MUST_EXIST);
	}
	
 	if (!isset($submitdata->multiplecat) || $submitdata->multiplecat != 1) { // no multiple categories
 		// check if we are on update mode
 		$recordexist = $DB->get_record('markers_assess', array ('mapid' => $oldmap->id, 'categoryid' => -1));
 		if ($recordexist != null) { // need to update
 			$recordexist->grade = $submitdata->xgrade;
 			$recordexist->feedback = $submitdata->submissioncomment_editor['text'];
 			$recordexist->mapid = $map->id; // update the map id anyway
 			$DB->update_record('markers_assess', $recordexist);
 		}
 		else { // new record
 			$DB->insert_record('markers_assess', array ('mapid' => $map->id, 'categoryid' => -1, 'grade' => $submitdata->xgrade, 'feedback' => $submitdata->submissioncomment_editor['text']));
 		}
 		
		assignment_map_status_complete($map);
 		
 		return;
 	}
 	
	if (!is_readable($CFG->dirroot . '/local/cat/locallib.php')) {
		return;
	} 	
 	
 	// Else multiple categories            	
  $data = cat_objectToarray($submitdata);
	// Get the number of categories
	//$catassign = $DB->get_record('mycat_catassign', array ('assignmentid' => $assignment->id), '*', MUST_EXIST);
	$tcat = $DB->get_record('cat', array ('assignmentid' => $assignment->id), '*', MUST_EXIST);
	$categories = $DB->get_records('cat_category', array ('catid' => $tcat->id));
	$count = sizeof($categories);
	$totalmark = 0;
	
	for ($i = 1; $i <= $count; $i++) {
		$categoryid = $data['categoryid' . $i];
		$thegrade = $data['xgrade' . $i];
		$comment = $data['comment' . $i];
		//$insertdata = array ('mapid' => $map->id, 'categoryid' => $categoryid, 'grade' => $thegrade, 'feedback' => $comment);
		$recordexist = $DB->get_record('markers_assess', array ('mapid' => $oldmap->id, 'categoryid' => $categoryid));
		if ($recordexist != null) {// then we have submit a form before and now we edit it
			$recordexist->mapid = $map->id; // update the map id anyway
			$recordexist->categoryid = $categoryid;
			$recordexist->grade = $thegrade;
			$recordexist->feedback = $comment;
			$DB->update_record('markers_assess', $recordexist);
		}
		else // this is a new entry
			$DB->insert_record('markers_assess', array ('mapid' => $map->id, 'categoryid' => $categoryid, 'grade' => $thegrade, 'feedback' => $comment));
		
		//$totalmark += $thegrade;
		$category = $DB->get_record('cat_category', array('id' => $categoryid), '*', MUST_EXIST);
		$scaledgrade = ($thegrade * $category->weight) / $category->maxgrade;
		$totalmark += $scaledgrade;		
	}
	
	// Update the total mark on markers_assess
	
	//Check if we are on edit mode
	$recordexist = $DB->get_record('markers_assess', array ('mapid' => $oldmap->id, 'categoryid' => -1));
	if ($recordexist != null) {
		$recordexist->grade = $totalmark;
		$recordexist->feedback = $submitdata->submissioncomment_editor['text'];
		$recordexist->mapid = $map->id; // update map id anyway
		$DB->update_record('markers_assess', $recordexist);
	}
	else {
		$DB->insert_record('markers_assess', array ('mapid' => $map->id, 'categoryid' => -1, 'grade' => $totalmark, 'feedback' => $submitdata->submissioncomment_editor['text']));
	}
	
	assignment_map_status_complete($map);					
}
/* ------------------------------------------------ */


/* -------------- Giannis ---------------------- */
/* tview specifies whether or not we marked the submission using the table view enviroment of moodle (tview = 1)
	 or the local/markers/view.php environment (tview = 0)*/
function assignment_markers_redirect($courseid, $behalf, $rcid, $raid, $rsid, $tview=0, $cmid=0) {
	global $CFG, $DB;
	
	if (!is_readable($CFG->dirroot . '/local/markers/locallib.php')) {
		return;
	}	
	
	if ($tview == 1) {
		redirect($CFG->wwwroot . '/mod/assignment/submissions.php?id=' . $cmid);
	}
	else {
		redirect($CFG->wwwroot . '/local/markers/view.php?cid=' . $rcid . '&aid=' . $raid . '&sid=' . $rsid . '&behalf=' . $behalf);
	}
}
	
/* ------------------------------------------------ */

/* ------------- Giannis ------------------ */
/*
	 $categoryid and $markers(multiple markers available) are compulsory
	 if $markers == true then:
	 														$setup != null
	 														$assign != null
	 														$type != null
	 														
	 else												$submissionid != null
	 														 
	 @param $viewmark - true if the function is called from local/markers/viewmark.php														 
*/

function get_readonly_subcategories_table($categoryid, $markers, $submissionid=null,$setup=null, $assign=null, $type=-1, $viewmark = false) {

			global $DB, $CFG;
			
			if (!is_readable($CFG->dirroot . '/local/cat/locallib.php')) {
				return;
			}	
			
			if ($markers) {
				if (!is_readable($CFG->dirroot . '/local/markers/locallib.php')) {
					if ($submissionid != null) {
						$markers = false; // treat it as if there were no markers from the beginning
					}
					else {
						return; // nothing can be done. Perhaps wrong inputs
					}
				}
			}					
			
			if (($markers == true && ($setup == null || $assign == null || $type == -1)) ||
					($markers == false && $submissionid == null)) {
				print_error(get_string('err_get_readonly_subcategories_table', 'local_cat'));
				die;
			}
				
			$subcategories = $DB->get_records('cat_subcat', array('categoryid' => $categoryid));
			if ($subcategories == null)
				return null;			
			
			$category = $DB->get_record('cat_category', array('id' => $categoryid), '*', MUST_EXIST);
			$cat = $DB->get_record('cat', array('id' => $category->catid), '*', MUST_EXIST);
			if ($cat->rankid == 0) {
				$sql = 'SELECT * FROM ' . $CFG->prefix . 'cat_rank WHERE name="' . get_string('rank_default', 'local_cat') . '" AND nextpriority = 7 AND courseid = 0'; 
				$default = $DB->get_records_sql($sql);
				if ($default != null) {
					$rank = reset($default);
					$rankid = $rank->id;
				} else {
					$rankid = set_default_ranks();
				}
				$cat->rankid = $rankid;
				$DB->update_record('cat', $cat);
				 
			}			
			$ranks = $DB->get_records('cat_ranks', array('rankid' => $cat->rankid));	
			
			// css
			if ($markers && !$viewmark) {
				$html = '<style type="text/css">
				
									table.subtable2 {
										position: relative;
										margin-left: auto;
										margin-right: auto;
									}
								
									table.table td.subtablecell table.subtable2 td {
										border-left: 0px solid #000000;
										border-top: 0px solid #000000;
										border-bottom: 0px solid #000000;
										border-right: 0px solid #000000;								
									}
								
									table.table td.subtablecell table.subtable2 td.special {	
										border-left: 1px solid #0000FF;
										border-top: 1px solid #0000FF;
										border-bottom: 1px solid #0000FF;
										border-right: 1px solid #0000FF;
										align: center;
									}
								</style>';							
			}
			else if (!$markers || ($markers && $viewmark)){
				$html = '<style type="text/css">
				
									table.subtable2 {
										position: relative;
										margin-left: auto;
										margin-right: auto;
									}
								
									table.subtable2 td {
										border-left: 0px solid #000000;
										border-top: 0px solid #000000;
										border-bottom: 0px solid #000000;
										border-right: 0px solid #000000;								
									}
								
									table.subtable2 td.special {	
										border-left: 1px solid #0000FF;
										border-top: 1px solid #0000FF;
										border-bottom: 1px solid #0000FF;
										border-right: 1px solid #0000FF;
										align: center;
									}
								</style>';				
			}
														
			$html .= '<table class="subtable2">';
			$html .= '<tr>';
			$html .= '<td></td>'; // the blank cell
			foreach ($ranks as $r) {
				$html .= '<td>';
				$html .= '<b>' . $r->description . '</b>';
				$html .= '</td>';
			}
			$html .= '</tr>';	
			foreach ($subcategories as $subcat) {
				$html .= '<tr>';
				$html .= '<td>';
				$html .= '<b>' . $subcat->description . '</b>';
				$html .= '</td>';
				foreach ($ranks as $r) {
					$color = "#FFFFFF"; // white
					$visibility = "hidden";
					
					$exists = false;
					
					if ($markers) { // multiple markers available
						$map = $DB->get_record('markers_map', array('setupid' => $setup->id, 'assignid' => $assign->id, 'type' => $type), '*', MUST_EXIST);
						$exists = $DB->record_exists('markers_subassess', array('mapid' => $map->id, 'subcatid' => $subcat->id, 'ranksid' => $r->id));						
					}
					else { // no multiple markers
						$exists = $DB->record_exists('cat_subcat_submission', array('ass_subid' => $submissionid, 'subcatid' => $subcat->id, 'ranksid' => $r->id));					
					} 

					if ($exists) {
						$color = "#FFFF00";
						$visibility = "visible";
					}
								
					$html .= '<td bgcolor=' . $color . ' class="special">';

					// add the tick symbol
					$url = $CFG->wwwroot . '/pix/i/tick_green_big.gif';
					$html .= '<center>';
					$html .= '<img src="' . $url . '" style="visibility: ' . $visibility . '"/>';
					$html .= '</center>';
											
					$html .= '</td>';						
				}
				
				$html .= '</tr>';
				
			}
			
			$html .= '</table>';
			
			return $html;	
			
		}
/* ---------------------------------------- */    
    


/* ---------------- Giannis ---------------------- */
function assignment_get_mark_table($map, $categoryid) {
	global $DB, $CFG;
	
	if (!is_readable($CFG->dirroot . '/local/markers/locallib.php')) {
		return '';
	}	
	
	$currentassign = $DB->get_record('markers_assign', array ('id' => $map->assignid), '*', MUST_EXIST);
	$assigns = $DB->get_records('markers_assign', array('courseid' => $currentassign->courseid, 'studentid' => $currentassign->studentid));
	
	$html ="<style>
						#mymarks {
							#width: 500px;
							width: 85%;
							margin: 0px auto;
							border-collapse: collapse;
							border: 1px solid #000000;
							position: relative;
							right: -10%;
 						}
						#mymarks .rowa {
							background: #BFC7FF;
							height: 20px;
							text-align:center;
						}
						#mymarks .rowb {
							background: #D9F9FC;
							height: 20px;
							text-align:center;
						}
						#mymarks .cell {
							padding: 0px;
							border: 1px solid #000000;
						}
						#mymarks .titles {
							background: #E5E5E5;
							height: 20px;
							font-weight:bold;
							text-align:center;
						}
						
						.textcell {
							#width: 10%;
						}
						
						
						table.table td.subtablecell {
							#width: 10%;
							#border: 0;
							text-align: left;
						}
						
						table.table td {
							border-top: 1px solid #000000;
							border-bottom: 1px solid #000000;
							#border-left: 1px solid #000000;
							#border-right: 1px solid #000000;
							white-space: nowrap;
						}
						
						table.table td.commentcell {
							white-space: normal;
						}

						
					</style>";
	
	$html .= "<table class=\"table\" id=\"mymarks\" cellspacing=\"0\" cellpadding=\"0\" align=\"left\">";
	
	
	$html .= "<tr class=\"titles\">";
	$html .= "<td>Marker</td>"; // if i say <td class=cell> on all cells I will have borders for the whole table
	$html .= "<td>Role</td>";
	$html .= "<td>Grade</td>";
	$html .= "<td>Comment</td>";
	
	$setup = $DB->get_record('markers_setup', array ('id' => $map->setupid), '*', MUST_EXIST);
	$assignment = $DB->get_record('assignment', array('id' => $setup->assignmentid), '*', MUST_EXIST);	
	$submission = $DB->get_record('assignment_submissions', array ('assignment' => $assignment->id, 'userid' => $currentassign->studentid), '*', MUST_EXIST);
	
	if (!is_readable($CFG->dirroot . '/local/cat/locallib.php')) {
		$subcategories = null;
	}
	else {		
		$subcategories = $DB->get_records('cat_subcat', array('categoryid' => $categoryid));
	}
	
	// label for subcategories' table
	if ($subcategories != null) {
		$html .= "<td>Sub-categories</td>";
	}
	
	$html .= "</tr>";
	$classname = "rowa";
	$sum = 0;
	$count = 0;
	
	
	$outof = round($assignment->grade, 2);
	if ($categoryid != -1) {
		$category = $DB->get_record('cat_category', array('id' => $categoryid), '*', MUST_EXIST);
		$outof = round($category->maxgrade, 2);
	}	
	
	foreach ($assigns as $assign) {
		$thatmap = $DB->get_record('markers_map', array('setupid' => $map->setupid, 'assignid' => $assign->id, 'type' => 0), '*', MUST_EXIST);
		$thatassess = $DB->get_record('markers_assess', array ('mapid' => $thatmap->id, 'categoryid' => $categoryid), '*', MUST_EXIST);
		$marker = $DB->get_record('user', array ('id' => $assign->markerid), '*', MUST_EXIST);
		
		$html .= "<tr class=" . $classname .">";
		$html .= "<td class='textcell'>" . $marker->firstname . " " . $marker->lastname ."</td>";
		$html .= "<td class='textcell'>" . $assign->role ."</td>";
		$html .= "<td class='textcell'>" . round($thatassess->grade, 2) . ' / ' . $outof . "</td>";
		
		$sum += $thatassess->grade;
		$count++;
		
		$comment = strip_tags($thatassess->feedback);		
		$comment = (strlen($comment) > 100) ? substr($comment,0,100).'...' : $comment;
		
		$html .= "<td class='commentcell'>" . $comment ."</td>";
		
		// add the subcategories' table
		if ($subcategories != null) {
			$stable = get_readonly_subcategories_table($categoryid, true, null,$setup, $assign, 0);					
			$html .= "<td class='subtablecell'>" . $stable . "</td>";
		}
		
		$html .= "</tr>";
		
		if ($classname = "rowa")
			$classname = "rowb";
		else
			$classname = "rowa";
	}
	
	$average = $sum/$count;
	
	$html .= "<tr class=\"titles\">";
	$html .= "<td></td>";
	$html .= "<td></td>";
	$html .= "<td></td>";
	if ($subcategories != null) {	
		$html .= "<td></td>";
	}
	$html .= "<td style='text-align: right'>Average: " . $average . ' / ' . $outof . "</td>";
	
	$html .= "</tr>"; 
	
	$html .= "</table>";
	//$html .= "<br/><br/><br/><br/><br/><br/><br/><br/><br/>";
	return $html;
}
/* ----------------------------------------------- */

/* -------------- Giannis ----------------------- */
function assignment_output_markers($studentid, $courseid, $assignmentid, $date) {
	global $DB, $OUTPUT, $CFG;	

	if (!is_readable($CFG->dirroot . '/local/markers/locallib.php')) {
		return null;
	}
	
	$html = "";
	$assigns = $DB->get_records('markers_assign', array ('courseid' => $courseid, 'studentid' => $studentid));
	require_once($CFG->dirroot . '/local/markers/locallib.php');
	
	if ($assigns != null) {
		$ass = reset($assigns); // reset the pointer to the first element and get its value
		$setup = $DB->get_record('markers_setup', array('assignmentid' => $assignmentid), '*', MUST_EXIST);
		$map = $DB->get_record('markers_map', array ('setupid' => $setup->id, 'assignid' => $ass->id, 'type' => 1), '*', MUST_EXIST);
		if ($map->endmarkerid == 0 && $map->altmarkerid > 0) {// marked from behalf	
			$html .= "<tr><center>" . get_string('submittedby', 'local_markers') . ' ' . markers_get_user_url($map->altmarkerid) . ', ' . get_string('onbehalfof2', 'local_markers') . ': ' ."</center></tr>";
		}
	}
	
	foreach ($assigns as $marker) {
		$user = $DB->get_record('user', array('id' => $marker->markerid), '*', MUST_EXIST);
		$html .= "<tr>";
		$html .= "<td class=\"left picture\">";
		$html .= $OUTPUT->user_picture($user);
		$html .= "</td>";
		$html .= "<td class=\"topic\">";
    $html .= "<div class=\"from\">";
    $html .= "<div class=\"fullname\">" . markers_get_user_url($marker->markerid) . " (" . $marker->role . ")" ."</div>";
    $html .= "</td>";
    $html .= "</tr>";
	}
	
	$html .= "<tr><td></td>";
	$html .= "<td>";
	$html .= "<div class=\"time\">" . $date . "</div>";
	$html .= "</td>";
	$html .= "</tr>";
	return $html;
}
/* ---------------------------------------------- */

/* ------------- Giannis ------------------------ */
// Used on update an assignment instance
function remove_multiple_categories($assignmentid, $data) {
	global $DB, $CFG;

	if (!is_readable($CFG->dirroot . '/local/cat/locallib.php')) {
		return null;
	}	
	
	$cat = $DB->get_record('cat', array('assignmentid' => $assignmentid));
	if ($cat == null)
		return null; // case: an assignment with no multiple categories before and again
								 // no multiple categories now
	
	
	// case: Before it was multiple categories but no now	
	$assignment = $DB->get_record('assignment', array('id' => $assignmentid), '*', MUST_EXIST);
	
	$maxgrade = $data['grade'];
	$grade_item = grade_item::fetch(array('courseid' => $assignment->course, 'itemtype' => 'mod', 'itemmodule' => 'assignment', 'iteminstance' => $assignment->id));
	if ($grade_item == false) {
		print_error(get_string('unexpectederror', 'local_cat'));
		die;
	}	
	$oldmaxgrade = $assignment->grade;

	$submissions = $DB->get_records('assignment_submissions', array('assignment' => $assignmentid));
	if ($submissions != null) {
		foreach($submissions as $submission) {
			if ($submission->grade == -1)
				continue;
				

			$grade_grade = grade_grade::fetch(array('itemid' => $grade_item->id, 'userid' => $submission->userid));			
			if ($grade_grade == false) {
				print_error(get_string('unexpectederror', 'local_cat'));
				die;
			}
			
			$newgrade = ($submission->grade * $maxgrade) / $oldmaxgrade;
			$submission->grade = $newgrade;
			
			$grade_grade->rawgrade = $newgrade;
			$grade_grade->finalgrade = $newgrade;
			$grade_grade->rawgrademax = $maxgrade;
			$grade_grade->update();
			
			$DB->update_record('assignment_submissions', $submission);
		}
						
	}

	$assignment->grade = $maxgrade;
	$DB->update_record('assignment', $assignment);	
	$grade_item->grademax = $maxgrade;
	$grade_item->update();
			

	
	
	// delete the necessary records
	$categories = $DB->get_records('cat_category', array('catid' => $cat->id));
	foreach ($categories as $category) {
		$subcats = $DB->get_records('cat_subcat', array('categoryid' => $category->id));
		foreach ($subcats as $subcat) {
			$DB->delete_records('cat_subcat_submission', array('subcatid' => $subcat->id));
		}
		$DB->delete_records('cat_subcat', array('categoryid' => $category->id));
		$DB->delete_records('cat_submission', array('categoryid' => $category->id));		
	}
	
	$DB->delete_records('cat_category', array('catid' => $cat->id));
	$DB->delete_records('cat', array('assignmentid' => $assignmentid));
	return true;
}
/* ---------------------------------------------- */

/* ------------- Giannis ------------------------ */
// Used on add and update an assignment instance
function update_multiple_categories ($assignmentid, $data){
	global $DB, $CFG;
	
	if (!is_readable($CFG->dirroot . '/local/cat/locallib.php')) {
		return;
	}	
	
	require_once($CFG->libdir . '/grade/grade_item.php');
	require_once($CFG->libdir . '/grade/grade_grade.php');
	
	$i = 1;
	$cat = $DB->get_record('cat', array('assignmentid' => $assignmentid));
	$assignment = $DB->get_record('assignment', array('id' => $assignmentid), '*', MUST_EXIST);
	$grade_item = grade_item::fetch(array('courseid' => $assignment->course, 'itemtype' => 'mod', 'itemmodule' => 'assignment', 'iteminstance' => $assignment->id));
	if ($grade_item == false) {
		print_error(get_string('unexpectederror', 'local_cat'));
		die;
	}	
	
		
	if ($cat == null) {
		$id = $DB->insert_record('cat', array('assignmentid' => $assignment->id, 'total' => 0, 'nextpriority' => '1', 'rankid' => $data['rank']));		
		$cat = $DB->get_record('cat', array('id' => $id), '*', MUST_EXIST);
	}
	else {
		$cat->rankid = $data['rank'];
		$DB->update_record('cat', $cat);
	}
	
	
	$totalweight = 0;	
	while (true) {
		$txtdesc =  'catdescription' . $i;
		$txtweight = 'catweight' . $i;
		$txtmax = 'catmaxgrade' . $i;
		$txtremove = 'remove' . $i;
		$txtpriority = 'priority' . $i;
		$txtcategoryid = 'catid' . $i;
		
		$newid = -1; // use when we add a new category
		
		if (!isset($data[$txtdesc]))
			break;

			
		$desc = $data[$txtdesc];
		$weight = $data[$txtweight];
		$max = $data[$txtmax];
		$remove = $data[$txtremove];
		$priority = $data[$txtpriority];
		$categoryid = $data[$txtcategoryid];
		
	
		if ($remove == 1 && $categoryid != -1) {
			// check if there are assignment_submissions on this assignment
			$submissions = $DB->get_records('assignment_submissions', array('assignment' => $assignmentid));
			$category = $DB->get_record('cat_category', array('id' => $categoryid), '*', MUST_EXIST);			
			if ($submissions != null) { // yes there are				
				
				foreach ($submissions as $submission) {
					if ($submission->grade == -1) {
						continue; // student has submit something but that something has not be marked yet
					}
					// I do not use MUST_EXIST because we may have submitted a mark for this submission, before set it as multiple categories one
					$cat_submission = $DB->get_record('cat_submission', array('categoryid' => $category->id, 'ass_subid' => $submission->id));
					if ($cat_submission != null) {
						$value = ($cat_submission->grade * $category->weight) / $category->maxgrade;						
						$submission->grade = $submission->grade - $value;						
						$DB->update_record('assignment_submissions', $submission); // update only submission for now
					} // if csubmit == null then this submission has not been done under multiple categories option and we will consider it in the end
					
					$subcats = $DB->get_records('cat_subcat', array('categoryid' => $category->id));
					foreach ($subcats as $subcat) {
						$DB->delete_records('cat_subcat_submission', array('subcatid' => $subcat->id, 'ass_subid' => $submission->id));
					}
					
					$DB->delete_records('cat_submission', array('categoryid' => $category->id, 'ass_subid' => $submission->id));
					
											
				}

			}
			
			$DB->delete_records('cat_subcat', array('categoryid' => $category->id));
					
			$DB->delete_records('cat_category', array('id' => $category->id));
		}
		else if ($remove == 0) {
	
			$totalweight += $weight;
			
			// in the following case we deal with categories that have already specified before SO
			// here we ALWAYS have multiple categories from before
			if ($categoryid != -1) { // already exists			
				$old = $DB->get_record('cat_category', array('id' => $categoryid), '*', MUST_EXIST);
				if ($old->weight != $weight || $old->maxgrade != $max) {
					$submissions = $DB->get_records('assignment_submissions', array('assignment' => $assignment->id));
					if ($submissions != null) { // we have some submissions
						foreach ($submissions as $submission) {
							if ($submission->grade == -1)
								continue; // not mark submitted yet
							
							// I do not use MUST_EXIST because we may have submitted a mark for this submission, before set it as multiple categories one									
							$csubmit = $DB->get_record('cat_submission', array('categoryid' => $old->id, 'ass_subid' => $submission->id));
							if ($csubmit != null) {								
								$k = ($csubmit->grade * $old->weight) / $old->maxgrade; // old-grade-weighted mark
								$newgrade = ($max * $csubmit->grade) / $old->maxgrade; // new grade
								$newk = ($newgrade * $weight) / $max;
																													
								$submission->grade -= $k;
								$submission->grade += $newk;
												
								$DB->update_record('assignment_submissions', $submission); // update submission
							
								$csubmit->grade = $newgrade;
								$DB->update_record('cat_submission', $csubmit); // update submission
														
							} // if csubmit == null then this submission has not been done under multiple categories option and we will consider it in the end
						}
					}

				}
				
				// update values anyway because description and priority might also change
				$old->weight = $weight;
				$old->maxgrade = $max;
				$old->description = $desc;
				$old->priority = $priority;
				$DB->update_record('cat_category', $old); // update that category too				
			}
			else { // category not exists	
				$newid = $DB->insert_record('cat_category', array('catid' => $cat->id, 'description' => $desc, 'maxgrade' => $max, 'weight' => $weight, 'priority' => $priority));
				$submissions = $DB->get_records('assignment_submissions', array('assignment' => $assignment->id));
				if ($submissions != null) { // we have some submissions
					foreach ($submissions as $submission) {
						if ($submission->grade == -1)
							continue; // not mark submitted yet
						
													
						$csubmit = $DB->get_records('cat_submission', array('ass_subid' => $submission->id));
						if ($csubmit != null) { // we have submitted categorized marks before so that indicates this submissions has been made under multiple categories option
							$grade = 0.4 * $max; // give the 40% of maximum grade on that category
							$k =	($grade * $weight) / $max; // the grade-weighted mark
							$DB->insert_record('cat_submission', array('categoryid' => $newid, 'ass_subid' => $submission->id, 'grade' => $grade, 'feedback' => ''));
							$submission->grade += $k;
							$DB->update_record('assignment_submissions', $submission); // update submission
						
						
						} // if csubmit == null then this submission has not been done under multiple categories option and we will consider it in the end
						
					}
				}
			}
		}
		
		$j = 1;

		
		while (true) {
		
			$txtsubdesc = 'cat' . $i . 'subdesc' . $j;
			$txtsubid = 'cat' . $i . 'subid' . $j;
			$txtsubpriority = 'cat' . $i . 'priority' . $j;
			$txtsubremove = 'cat' . $i . 'remove' . $j;		
		
			if (!isset($data[$txtsubdesc]))
				break;
					
			$subdesc = $data[$txtsubdesc];
			$subid = $data[$txtsubid];
			$subpriority = $data[$txtsubpriority];
			$subremove = $data[$txtsubremove];
			
			if ($subremove == 1 && $subid != -1) {
				$DB->delete_records('cat_subcat_submission', array('subcatid' => $subid)); // remove any records there might be
				$DB->delete_records('cat_subcat', array('id' => $subid)); // remove that sub-category
			}
			else if ($subremove == 0) {
				if ($subid != -1) { // already exists
					$DB->update_record('cat_subcat', array('id' => $subid, 'description' => $subdesc, 'priority' => $subpriority));
				}
				else { // a new one
					$cid = $categoryid;
					if ($categoryid == -1)
						$cid = $newid;
					
					$DB->insert_record('cat_subcat', array('description' => $subdesc, 'priority' => $subpriority, 'categoryid' => $cid));	
					
				}
			}
			$j++;
		}
		$i++;	
			
	} // end of category loop
	
	$oldtotalgrade = $assignment->grade;
	$assignment->grade = $totalweight;
	$DB->update_record('assignment', $assignment);
	
	$grade_item->grademax = $totalweight;
	$grade_item->update(); // update db
	
	$cat->total = $totalweight;
	$DB->update_record('cat', $cat);	
	
	
	$submissions = $DB->get_records('assignment_submissions', array ('assignment' => $assignment->id));
	if ($submissions != null) {
		foreach ($submissions as $submission) {
			if ($submission->grade == -1)
				continue;
				
			$mulcats = true; // multiple categories
			$submitcats = $DB->get_records('cat_submission', array('ass_subid' => $submission->id));
			if ($submitcats == null) { // this submission has marked when the assignment did not have
																// multiple categories and it still marks individually without multiple categories
				// but we need to scale the mark accordingly
				$newgrade = ($submission->grade * $totalweight) / $oldtotalgrade;
				$submission->grade = $newgrade;
				$DB->update_record('assignment_submissions', $submission);													
			}
			$grade_grade = grade_grade::fetch(array('itemid' => $grade_item->id, 'userid' => $submission->userid));
			if ($grade_grade == false) {
				print_error(get_string('unexpectederror', 'local_cat'));
				die;
			}
			$grade_grade->rawgrade = $submission->grade; // update also grade_grade fields
			$grade_grade->finalgrade = $submission->grade;
			$grade_grade->rawgrademax = $totalweight;
			$grade_grade->update();						
		}
	}
} // end of function

/* ---------------------------------------------- */
