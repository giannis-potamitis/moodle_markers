<?php

class block_markeradmin extends block_base {
    public function init() {
        $this->title = get_string('markeradmin', 'block_markeradmin');
    }    
		
		/* Get a nav item image with a string*/
    public function get_nav_item() {
			global $CFG;
			$url = $CFG->wwwroot . "/pix/i/navigationitem.png";
			return '<img src="' . $url .'" alt=""/>'; 
		}
    
    public function get_content() {
    	global $DB, $USER, $CFG;
    
			if (!is_readable($CFG->dirroot . '/local/markers/locallib.php')) {
				$this->content         =  new stdClass;
    		$this->content->text   = '';
    		$this->content->footer = '';
    		return $this->content;
			}    
    
    	if ($this->content !== null) {
      	return $this->content;
    	}
 
 
 			$courses = $DB->get_records('course');
 			
 			$html = '';
 			
 			
 			$assignmarkers = false;
 			$privilegemode = false;
 			$status = false;
 			foreach ($courses as $course) {
 				$where = 'course=' . $course->id;
 				$a_ids = $DB->get_fieldset_select('assignment', 'id', $where); // the assignment ids
 				$setups = $DB->get_records_list('markers_setup', 'assignmentid', $a_ids);
 				
 				if ($setups == null) // this course does not have any courses with multiple markers
 					continue;

 			
 				// check if the user is admin
 				$admin = false;
 				$context = get_context_instance(CONTEXT_USER, $USER->id);
 				if (has_capability('local/markers:admin', $context))
 					$admin = true;
 					
 				// check if the user is a teacher
 				$teacher = false;
 				$context = get_context_instance(CONTEXT_COURSE, $course->id);
 				if (has_capability('local/markers:editingteacher', $context))
 					$teacher = true;
 					
 				// check if the user is a marker on that course
 				$marker = false;
 				$assign = $DB->get_records('markers_assign', array('courseid' => $course->id, 'markerid' => $USER->id));
 				if ($assign != null)
 					$marker = true;
 					
 				if (($admin || $teacher) && !$assignmarkers) {
 					// add the assignmarkers link
 					$url = $CFG->wwwroot . '/local/markers/assignmarkers.php?cid=' . $course->id; 
 					$html .= $this->get_nav_item() . ' ' . '<a href="' . $url . '">' . get_string('assignmarkers', 'block_markeradmin') . '</a><br/>';
 					$assignmarkers = true;
 				}
 				
 				if (($admin || $teacher) && !$privilegemode) {
 					// add the privilege mode (behalf = 1)
 					$url = $CFG->wwwroot . '/local/markers/view.php?behalf=1'; 
 					$html .= $this->get_nav_item() . ' ' . '<a href="' . $url . '">' . get_string('privilegemode', 'block_markeradmin') . '</a><br/>';
 					$privilegemode = true; 				
 				}
 				
 				if ($marker && !$status && !$teacher && !$admin) {
 					// add the general status
 					$url = $CFG->wwwroot . '/local/markers/view.php?behalf=0'; 
 					$html .= $this->get_nav_item() . ' ' . '<a href="' . $url . '">' . get_string('generalstatus', 'block_markeradmin') . '</a><br/>';
 					$status = true;  				
 				}

 			}
 			$this->content         =  new stdClass;
    	$this->content->text   = $html;
    	$this->content->footer = '';
 			
    	return $this->content;
  }
  
  public function instance_allow_config() {
  	return true;
	}
	

} 
