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
 * Library of interface functions and constants for module markers
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the markers specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    local
 * @subpackage markers
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** example constant */
//define('NEWMODULE_ULTIMATE_ANSWER', 42);

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function markers_supports($feature) {
    switch($feature) {
        default:                        return null;
    }
}


/**
 * Returns an array of users who are participanting in this markers
 *
 * Must return an array of users who are participants for a given instance
 * of markers. Must include every user involved in the instance,
 * independient of his role (student, teacher, admin...). The returned
 * objects must contain at least id property.
 * See other modules as example.
 *
 * @param int $markersid ID of an instance of this module
 * @return boolean|array false if no participants, array of objects otherwise
 */
function markers_get_participants($markersid) {
    return false;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function markers_get_extra_capabilities() {
    return array();
}


/*
	The cron function. It will mainly delete any unwanted records from the DB that are
	related with an already deleted course
*/
function local_markers_cron() {
	global $DB;
	$assigns = $DB->get_records('markers_assign');
	$del = 0;
	foreach ($assigns as $assign) {
		if (!$DB->record_exists('course', array('id' => $assign->courseid))) {	
			$DB->delete_records('markers_assign', array('id' => $assign->id));
			$del++;
		}
	}
	echo $del . ' records deleted from markers_assign table...';
	return true;
}
