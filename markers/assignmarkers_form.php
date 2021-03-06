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


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.!!!!');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/lib/formslib.php');



/**
 * Module instance settings form
 */
class local_markers_assignmarkers_form extends moodleform {

		function hasErrors() {
			$mform = & $this->_form;
			if (!empty($mform->_errors))
				return true;
			else
				return false;
		}

    function getErrors() {
    	$mform = & $this->_form;
      return $mform->_errors;

    }


    /**
     * Defines forms elements
     */
    function definition() {

				global $CFG;

        $mform = & $this->_form;
        
				$data = & $this->_customdata;
				// add the 'choose' select
				$mform->addElement('header', 'choose', get_string('choose', 'local_markers'));
				$html = "<script type=\"text/javascript\">
							function reload() {
								var courseid = document.getElementById('course').value;

								window.location = \"assignmarkers.php?cid=\" + courseid;
							}
					 </script>";
				$html .= get_string('courseselect', 'local_markers') . ' ';	 
				$html .= "<select name=\"course\" id=\"course\" onChange=\"reload()\">";
	
				foreach ($data->courses as $course) {
					$cname = (strlen($course->fullname) > 50)? substr($course->fullname, 0, 50) . '...' : $course->fullname;
					if ($data->cid == $course->id)
						$html .= "<option value=" . $course->id . " selected>" . $cname . "</option>"; 
					else
						$html .= "<option value=" . $course->id . ">" . $cname . "</option>";
				}

				$html .= "</select>";
				
				
				$mform->addElement('html', $html);

				$mform->addElement('static', 'automaticerror', '', '');

				// automatic assignment button
				if (count($data->student) > 0 && (count($data->markers)-1) >= 2) {
					$arr = array();
					$attr['onclick'] = 'return confirm("' . get_string('removeoldassigns', 'local_markers') . '")';
					$arr[] = &$mform->createElement('submit', 'automatic', get_string('automaticassign', 'local_markers'), $attr);
					$mform->addGroup($arr, 'automaticarray', '', array(' '), false);
					$mform->closeHeaderBefore('automaticerror');
				}

				$i = 1;
				foreach ($data->student as $stud) {
					$sid = $stud->id;
					//$mform->addElement('header', 'header' . $sid, get_string('student', 'local_markers') . ' ' . $i . ': ' . $stud->firstname . ' ' . $stud->lastname);
					$mform->addElement('header', 'header' . $sid, $stud->firstname . ' ' . $stud->lastname . ', ' . $stud->email);					
					$mform->addElement('hidden', 'studentid' . $sid);
					
					$mform->setType('studentid' . $sid, PARAM_INT);
					$mform->setDefault('studentid' . $sid, 0);
					
					$mform->addElement('select', 'supervisor' . $sid, get_string('selectsupervisor', 'local_markers'), $data->markers);
					$mform->setDefault('supervisor' . $sid, -1);
					
					$mform->addElement('select', 'secondmarker' . $sid, get_string('selectsecondmarker', 'local_markers'), $data->markers);
					$mform->setDefault('secondmarker' . $sid, -1);
					
					for ($j = 1; $j <= sizeof($stud->othermarkers); $j++) {
						$mid = $stud->othermarkers[$j-1]->id;
						$markerarray = array();
						// I have to add labels for each element because labels in the group are ignored
						//$markerarray[] = &$mform->createElement('static', 'othermarkerlabel' . $sid . $j, '', get_string('othermarker', 'local_markers') . ' ' . $j);
						$markerarray[] = &$mform->createElement('select', 'othermarker' . $sid . $j, get_string('othermarker', 'local_markers') . ' ' . $j, $data->markers);
						$markerarray[] = &$mform->createElement('static', 'rolelabel' . $sid . $j, '', '   ' . get_string('role', 'local_markers') . '    ');
						$markerarray[] = &$mform->createElement('text', 'role' . $sid . $j, get_string('role', 'local_markers'), 'size="20"');
						$markerarray[] = &$mform->createElement('hidden', 'otherassignid' . $sid . $j);
						$mform->addGroup($markerarray, 'markerar' . $sid . $j, get_string('othermarker', 'local_markers') . ' ' . $j, array(''), false);
						$mform->setDefault('othermarker' . $sid . $j, $mid);
						$mform->setDefault('otherassignid' . $sid . $j, -1);
						$mform->setDefault('role' . $sid . $j, get_string('othermarker', 'local_markers'));
					}
					$i++;
				}

        //-------------------------------------------------------------------------------
        // add standard buttons
        //$this->add_action_buttons();
				$buttonarray=array();
				if (count($data->student) > 0 && (count($data->markers)-1) >= 2) {
					$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
				}
				$buttonarray[] = &$mform->createElement('cancel');
				$mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
				$mform->closeHeaderBefore('buttonar');
    }
    
    function validation($data, $files) {
    	global $DB, $courseid;
    
    	$cus = & $this->_customdata;
    	$errors = parent::validation($data, $files);

			if (isset($data['automatic'])) {
				/*
				if ((count(m_get_non_editing_teachers($cus->cid)) + count(m_get_teachers($cus->cid))) <= 2) {
					$errors['automaticerror'] = get_string('twomarkersatleast', 'local_markers');
				} */
				
				if (count($cus->student) <= 0)  {
					$errors['automaticerror'] = get_string('nostudents', 'local_markers');
				}
				
				if ((count($cus->markers)-1) < 2) {
					$errors['automaticerror'] = get_string('moremarkers', 'local_markers');
				}
				return $errors;
			}

			$studentrole = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
			$context = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSE, 'instanceid' => $courseid));
			$roleassign = $DB->get_records('role_assignments', array('roleid' => $studentrole->id, 'contextid' => $context->id));
			$ids = array();
			foreach ($roleassign as $therole) {
				$ids[] = $therole->userid;
			}
			$thestudents = $DB->get_records_list('user', 'id', $ids, 'firstname ASC');
			foreach ($thestudents as $stud) {
				$superstr = 'supervisor' . $stud->id;
				$supervisor = $data[$superstr];
				if ($supervisor == -1) {
					$errors[$superstr] = get_string('err_selectsupervisor', 'local_markers') . $stud->firstname . ' ' . $stud->lastname;
				}
				
				$secondstr = 'secondmarker' . $stud->id;
				$secondmarker = $data[$secondstr];
				if ($secondmarker == -1) {
					$errors[$secondstr] = get_string('err_selectsecondmarker', 'local_markers') . $stud->firstname . ' ' . $stud->lastname;
				}
				
				if ($supervisor == $secondmarker && $supervisor != -1) {
					$errors[$superstr] = get_string('err_supersecondsame', 'local_markers') . $stud->firstname . ' ' . $stud->lastname;
				}
				
				// Check for any other markers
				$i = 1;
				$otherstring = 'othermarker' . $stud->id . $i;
				$groupstring = 'markerar' . $stud->id . $i;
				while(isset($data[$otherstring])) {
					$rolestr = 'role' . $stud->id . $i;
					$role = $data[$rolestr];
					if (empty($role)) {
						$errors[$groupstring] = get_string('err_emptyrole', 'local_markers');
					}
					$othermarker = $data[$otherstring];
					if ($othermarker == -1) {
						$errors[$groupstring] = '\'' . $role . '\' ' . get_string('err_selectothermarker', 'local_markers') . $stud->firstname . ' ' . $stud->lastname;
					} 
					
					if ($othermarker == $supervisor && $othermarker != -1) {
						$errors[$groupstring] = '\'' . $role . '\' ' . get_string('err_superothersame', 'local_markers') . $stud->firstname . ' ' . $stud->lastname;
					}
					
					if ($othermarker == $secondmarker && $othermarker != -1) {
						$errors[$groupstring] = '\'' . $role . '\' ' . get_string('err_secondothersame', 'local_markers') . $stud->firstname . ' ' . $stud->lastname;
					}
			
					$i++;
					$otherstring = 'othermarker' . $stud->id . $i;
					$groupstring = 'markerar' . $stud->id . $i;
				}
				
			}
			
      return $errors;
    }
    

}
