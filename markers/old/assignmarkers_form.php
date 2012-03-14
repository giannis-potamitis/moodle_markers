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

    /**
     * Defines forms elements
     */
    function definition() {

				global $CFG;

        $mform = & $this->_form;
        
				$data = & $this->_customdata;

				for ($i = 1; $i <= sizeof($data->student); $i++) {
					$mform->addElement('header', 'header' . $i, get_string('student', 'local_markers') . ' ' . $i . ': ' . $data->student[$i-1]->firstname . ' ' . $data->student[$i-1]->lastname);
					$mform->addElement('hidden', 'studentid' . $i);
					
					$mform->setType('studentid' . $i, PARAM_INT);
					$mform->setDefault('studentid' . $i, 0);
					
					$mform->addElement('select', 'supervisor' . $i, get_string('selectsupervisor', 'local_markers'), $data->markers);
					$mform->setDefault('supervisor' . $i, -1);
					
					$mform->addElement('select', 'secondmarker' . $i, get_string('selectsecondmarker', 'local_markers'), $data->markers);
					$mform->setDefault('secondmarker' . $i, -1);
					
					for ($j = 1; $j <= sizeof($data->student[$i-1]->othermarkers); $j++) {
						$markerarray = array();
						$markerarray[] = &$mform->createElement('select', 'othermarker' . $i . $j, get_string('othermarker', 'local_markers') . ' ' . $j, $data->markers);
						$markerarray[] = &$mform->createElement('text', 'role' . $i . $j, get_string('role', 'local_markers'), 'size="20"');
						$mform->addGroup($markerarray, 'markerar', '', array(''), false);
						$mform->setDefault('othermarker' . $i . $j, $data->student[$i-1]->othermarkers[$j-1]->marker->id);
						$mform->setDefault('role' . $i . $j, get_string('othermarker', 'local_markers'));
					}
				}

        //-------------------------------------------------------------------------------
        // add standard buttons
        //$this->add_action_buttons();
				$buttonarray=array();
				$buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
				$buttonarray[] = &$mform->createElement('reset', 'resetbutton', get_string('revert'));
				$buttonarray[] = &$mform->createElement('cancel');
				$mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
				$mform->closeHeaderBefore('buttonar');
    }
}
