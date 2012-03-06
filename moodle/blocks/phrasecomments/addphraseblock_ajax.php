<?php

define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once($CFG->libdir.'/pagelib.php');
require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->dirroot . '/phrasecomment/lib.php');

global $PAGE, $DB, $USER;

$contextid = optional_param('contextid', SYSCONTEXTID, PARAM_INT);
$action    = optional_param('action', '', PARAM_ALPHA);

//ToDO: Check for permissions to create a block

list($context, $course, $cm) = get_context_info_array($contextid);

// generate a new Page
//$page = new moodle_page();



$PAGE->set_url('/phrasecomment/addphraseblock_ajax.php');

require_course_login($course, true, $cm);
$PAGE->set_context($context);
if (!empty($cm)) {
    $PAGE->set_cm($cm, $course);
} else if (!empty($course)) {
    $PAGE->set_course($course);
}

if (!confirm_sesskey()) {
    $error = array('error'=>get_string('invalidsesskey', 'error'));
    die(json_encode($error));
}

$startnum  = optional_param('start',    '',  PARAM_INT);
$endnum    = optional_param('end',    '',  PARAM_INT);
$qid       = optional_param('qid',    '',  PARAM_INT);

$args->startnum  = $startnum;
$args->endnum   =  $endnum;
$args->qid   	=  $qid;

echo $OUTPUT->header(); // send headers

switch ($action) {
	case 'addblock':  
        $newcmt = new stdClass;       
        $newcmt->userid    = $USER->id;
		$newcmt->qid      = $qid;
		$newcmt->startnum = $startnum;
        $newcmt->endnum  = $endnum;
          

        $cmt_id = $DB->insert_record('tempo', $newcmt);


        $page = new moodle_page();
        $page->set_context(get_context_instance_by_id($contextid));      
		$page->blocks->add_blocks(array(BLOCK_POS_LEFT=>array(),BLOCK_POS_RIGHT=>array('phrasecomments')),'mod-quiz-*');
        $page->blocks->load_blocks();
         

      /*$ablocks = $page->blocks->get_blocks_for_region(BLOCK_POS_RIGHT); print_r($ablocks);
        foreach($ablocks as $ablock) {
            $ablock->config->qid=$qid;
            $ablock->config->startOffset=$startnum;
            $ablock->config->endOffset=$endnum;
            print_r($ablock);echo "as";
            $ablock->instance_config_commit();
        }*/
		break;
	default:
}
	
if (!isloggedin()) {
    // tell user to log in to view comments
    echo json_encode(array('error'=>'require_login'));
}
// ignore request
die;
