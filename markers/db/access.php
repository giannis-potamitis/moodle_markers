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
 * Capability definitions for the markers module
 *
 * The capabilities are loaded into the database table when the module is
 * installed or updated. Whenever the capability definitions are updated,
 * the module version number should be bumped up.
 *
 * The system has four possible values for a capability:
 * CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
 *
 * It is important that capability names are unique. The naming convention
 * for capabilities that are specific to modules and blocks is as follows:
 *   [mod/block]/<plugin_name>:<capabilityname>
 *
 * component_name should be the same as the directory name of the mod or block.
 *
 * Core moodle capabilities are defined thus:
 *    moodle/<capabilityclass>:<capabilityname>
 *
 * Examples: mod/forum:viewpost
 *           block/recent_activity:view
 *           moodle/site:deleteuser
 *
 * The variable name for the capability definitions array is $capabilities
 *
 * @package    mod
 * @subpackage markers
 * @copyright  2011 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

/***************************** remove these comment marks and modify the code as needed
    'mod/markers:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'guest' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
    ),

    'mod/markers:submit' => array(
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'student' => CAP_ALLOW
        )
    ),
******************************/	
	'local/markers:markerenrolment' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_COURSE,
		'legacy' => array(
			'guest' => CAP_PROHIBIT,
			'student' => CAP_PROHIBIT,
			'teacher' => CAP_PROHIBIT, // teacher = non-editing teacher
      'editingteacher' => CAP_ALLOW,
      'manager' => CAP_ALLOW,
      'coursecreator'  => CAP_ALLOW
		)
	),
	
	'local/markers:editingteacher' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_COURSE,
		'legacy' => array(
			'guest' => CAP_PROHIBIT,
			'student' => CAP_PROHIBIT,
			'teacher' => CAP_PROHIBIT, // teacher = non-editing teacher
      'editingteacher' => CAP_ALLOW,
      'manager' => CAP_PROHIBIT,
      'coursecreator'  => CAP_PROHIBIT
		)
	),
	
	'local/markers:admin' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_USER,
		'legacy' => array(
			'guest' => CAP_PROHIBIT,
			'student' => CAP_PROHIBIT,
			'teacher' => CAP_PROHIBIT, // teacher = non-editing teacher
      'editingteacher' => CAP_PROHIBIT,
      'manager' => CAP_PROHIBIT,
      'coursecreator'  => CAP_PROHIBIT,
      'user' => CAP_PROHIBIT,
      'frontpage' => CAP_PROHIBIT
		)
	),
	
		'local/markers:student' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_COURSE,
		'legacy' => array(
			'guest' => CAP_PROHIBIT,
			'student' => CAP_ALLOW,
			'teacher' => CAP_PROHIBIT, // teacher = non-editing teacher
      'editingteacher' => CAP_PROHIBIT,
      'manager' => CAP_PROHIBIT,
      'coursecreator'  => CAP_PROHIBIT
		)
	),
	
	'local/markers:anyteacher' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_COURSE,
		'legacy' => array(
			'guest' => CAP_PROHIBIT,
			'student' => CAP_PROHIBIT,
			'teacher' => CAP_ALLOW, // teacher = non-editing teacher
      'editingteacher' => CAP_ALLOW,
      'manager' => CAP_PROHIBIT,
      'coursecreator'  => CAP_PROHIBIT
		)
	)		
		

);

