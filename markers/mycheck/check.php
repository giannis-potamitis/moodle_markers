<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot . '/local/markers/locallib.php');

	$setups = $DB->get_records('markers_setup');
	$errors = 0;
	foreach ($setups as $setup) {
		echo '=================== SETUPID: ' . $setup->id . '===============================================<br/>';
		$assignment = $DB->get_record('assignment', array ('id' => $setup->assignmentid));
		if ($assignment == null) {
			echo 'NO ASSIGNMENT with id: ' . $setup->assignmentid . ' setupid: ' . $setup->id . '<br/>';
			$errors++;
			continue;
		}
		echo 'ASSIGNMENT DETAILS: ID: ' . $assignment->id . ' NAME: ' . markers_get_assignment_url($assignment->id) . ' COURSE: ' . $assignment->course . '<br/><br/>';
		
		$assigns = $DB->get_records('markers_assign', array ('courseid' => $assignment->course));
		foreach ($assigns as $assign) {
			$map = $DB->get_record('markers_map', array('setupid' => $setup->id, 'assignid' => $assign->id, 'type' => 0));
			if ($map == null) {
					echo 'no record: setupid: ' . $setup->id . ' assignid: ' . $assign->id . ' type: 0 course: ' . $assign->courseid . ' student: ' . markers_get_user_url($assign->studentid) . ' marker: ' . markers_get_user_url($assign->markerid) . ' role: ' . $assign->role . '<br/>'; 
				$errors++;
			}
				
			
			$map = $DB->get_record('markers_map', array('setupid' => $setup->id, 'assignid' => $assign->id, 'type' => 1));
			if ($map == null) {
					echo 'no record: setupid: ' . $setup->id . ' assignid: ' . $assign->id . ' type: 1 course: ' . $assign->courseid . ' student: ' . markers_get_user_url($assign->studentid) . ' marker: ' . markers_get_user_url($assign->markerid) . ' role: ' . $assign->role . '<br/>'; 
				$errors++;
			}
				
						
		}
		echo '==================================================================================================<br/><br/><br/><br/>';
	}
	
	echo '<br/><br/>ERRORS: ' . $errors . '<br/>FINISH<br/>';
	$total = $errors;
	/*
	$result = true;
	if (! $DB->delete_records('markers_setup', array('assignmentid' => 58364))) {
		$result = false;
	}
	
	echo ($result ? 'TRUE ' : 'FALSE ') . $result . '<br/>';*/
	

	
	echo '<br/><br>';
	echo 'SECOND CHECK: OLD RECORDS?<br/>';
	$errors = 0;
	$maps = $DB->get_records('markers_map');
	foreach ($maps as $map) {
		echo '=============================== MAPID: ' . $map->id . '======================<br/>';
		echo 'Current: setupid: ' . $map->setupid . ' assignid: ' . $map->assignid . ' type: ' . $map->type . '<br/>';
		
		$setup = $DB->get_record('markers_setup', array('id' => $map->setupid));
		if ($setup == null) {
			echo 'MISSING setupid: ' . $map->setupid . '<br/>';
			$DB->delete_records('markers_map', array('id' => $map->id));
			$errors++;
		}
		
		$assign = $DB->get_record('markers_assign', array('id' => $map->assignid));
		if ($assign == null) {
			echo 'MISSING assignid: ' . $map->assignid . '<br/>';
			$DB->delete_records('markers_map', array('id' => $map->id));
			$errors++;
		}
		
				
		echo '=======================================================================================<br/><br/><br/>';
	}
	
		echo '<br/><br/>ERRORS: ' . $errors . '<br/>FINISH<br/>'; 
		
		$total += $errors;
		echo '<br/>TOTAL ERRORS: ' . $total . '<br/>';
		
		
