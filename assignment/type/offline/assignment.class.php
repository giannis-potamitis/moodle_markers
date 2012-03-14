<?php

/* ----- Giannis --------------- */
require_once($CFG->dirroot.'/mod/assignment/locallib.php');

if (is_readable($CFG->dirroot . '/local/cat/locallib.php')) {
	require_once($CFG->dirroot.'/local/cat/locallib.php');
}
/* ----------------------------- */

/**
 * Extend the base assignment class for offline assignments
 *
 */
class assignment_offline extends assignment_base {

    function assignment_offline($cmid='staticonly', $assignment=NULL, $cm=NULL, $course=NULL) {
        parent::assignment_base($cmid, $assignment, $cm, $course);
        $this->type = 'offline';
    }

    function display_lateness($timesubmitted) {
        return '';
    }
    function print_student_answer($studentid){
        return '';//does nothing!
    }

    function prepare_new_submission($userid) {
        $submission = new stdClass();
        $submission->assignment   = $this->assignment->id;
        $submission->userid       = $userid;
        $submission->timecreated  = time(); // needed for offline assignments
        $submission->timemodified = $submission->timecreated;
        $submission->numfiles     = 0;
        $submission->data1        = '';
        $submission->data2        = '';
        $submission->grade        = -1;
        $submission->submissioncomment      = '';
        $submission->format       = 0;
        $submission->teacher      = 0;
        $submission->timemarked   = 0;
        $submission->mailed       = 0;
        return $submission;
    }

    // needed for the timemodified override
    function process_feedback() {
        global $CFG, $USER, $DB;
        require_once($CFG->libdir.'/gradelib.php');

        if (!$feedback = data_submitted() or !confirm_sesskey()) {      // No incoming data?
            return false;
        }

        ///For save and next, we need to know the userid to save, and the userid to go
        ///We use a new hidden field in the form, and set it to -1. If it's set, we use this
        ///as the userid to store
        if ((int)$feedback->saveuserid !== -1){
            $feedback->userid = $feedback->saveuserid;
        }

        if (!empty($feedback->cancel)) {          // User hit cancel button
            return false;
        }

        $grading_info = grade_get_grades($this->course->id, 'mod', 'assignment', $this->assignment->id, $feedback->userid);

        // store outcomes if needed
        $this->process_outcomes($feedback->userid);

        $submission = $this->get_submission($feedback->userid, true);  // Get or make one

        if (!$grading_info->items[0]->grades[$feedback->userid]->locked and
            !$grading_info->items[0]->grades[$feedback->userid]->overridden) {

            
            $submission->submissioncomment    = $feedback->submissioncomment_editor['text'];
            $submission->teacher    = $USER->id;
            $mailinfo = get_user_preferences('assignment_mailinfo', 0);
            if (!$mailinfo) {
                $submission->mailed = 1;       // treat as already mailed
            } else {
                $submission->mailed = 0;       // Make sure mail goes out (again, even)
            }
            $submission->timemarked = time();

            unset($submission->data1);  // Don't need to update this.
            unset($submission->data2);  // Don't need to update this.

            if (empty($submission->timemodified)) {   // eg for offline assignments
                $submission->timemodified = time();
            }
            
            /* -------- Giannis ---------------------------- */
            // Set the grade
            $submission = assignment_set_grade($this->assignment, $submission, $feedback); 
           	assignment_process_multiple_categories($this->assignment, $submission, $feedback);
            /* ---------------------------------------------- */
            
            /* ----------------- Giannis ------------- */				
						$mparam = get_markers_param($this->assignment->id);
						if ($mparam != null && $mparam->type == 0) { // individual marking
							assignment_process_multiple_markers($this->assignment, $submission, $feedback, $map);
						}
						else {
            
            $DB->update_record('assignment_submissions', $submission);

            // triger grade event
            $this->update_grade($submission);
            
            }

						/* --------------------------------------- */
            

            add_to_log($this->course->id, 'assignment', 'update grades',
                       'submissions.php?id='.$this->assignment->id.'&user='.$feedback->userid, $feedback->userid, $this->cm->id);
        }

        return $submission;

    }

}


