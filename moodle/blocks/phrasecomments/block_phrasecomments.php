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
 * The comments block
 *
 * @package   block
 * @subpackage comments
 * @copyright 2009 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Obviously required
require_once($CFG->dirroot . '/phrasecomment/lib.php');

class block_phrasecomments extends block_base {
    
    public $qid     = 0;
    public $startnum   = 0;
    public $endnum     = 0;
    
    function init() {
        $this->title = get_string('pluginname', 'block_phrasecomments');        
    }

    function specialization() {
        // require js for commenting
        phrasecomment::init();
        if (!empty($this->config->title)) {
            $this->title = $this->config->title;
        } else {
            $this->config->title = 'Inline Comments';
        }         
        if (empty($this->config->qid)) $this->config->qid = '0';
        
    }
    function applicable_formats() {
        return array('mod-quiz' => true);
    }

    function instance_allow_multiple() {
        return true;
    }
	
	function instance_allow_config() {
		return true;
	}

    function get_content() {
        global $CFG, $PAGE, $USER, $DB;
        if (!$CFG->usecomments) {
            $this->content->text = '';
            if ($this->page->user_is_editing()) {
                $this->content->text = get_string('disabledcomments');
            }
            return $this->content;
        }
        if ($this->content !== NULL) {
            return $this->content;
        }
        if (empty($this->instance)) {
            return null;
        }
        //echo $this->instance->id;
        $this->content->footer = '';
        $this->content->text = '';
        list($context, $course, $cm) = get_context_info_array($PAGE->context->id);
        
        $display = -1;
        $pageurl = (string)$PAGE->url;
        $review = "review.php";
        $attempt = "attempt=";
        $aid = -1;
        $totalcommentcount = $DB->count_records('phrasecomments', array('instanceid' => $this->instance->id));
        if(!$totalcommentcount) {
        $params = array();
        $sql = "SELECT c.qid AS qid, c.startnum AS startnum, c.endnum AS endnum FROM {tempo} c WHERE c.userid = :userid";
        $params['userid'] = $USER->id;
		$page = 0;
        $perpage = (!empty($CFG->commentsperpage))?$CFG->commentsperpage:15;
        $start = $page * $perpage;
		
        $rs = $DB->get_recordset_sql($sql, $params, $start, $perpage);
        
        foreach ($rs as $u) {
			$this->qid = $u->qid;
            $this->startnum = $u->startnum;
            $this->endnum = $u->endnum;
		}
        $rs->close();

        $DB->delete_records('tempo', array('userid'=>$USER->id));
	}
	else {
		$params = array();
        $sql = "SELECT c.qid AS qid, c.startnum AS startnum, c.endnum AS endnum FROM {phrasecomments} c WHERE c.instanceid = :instanceid";
        $params['instanceid'] = $this->instance->id;
		$page = 0;
        $perpage = (!empty($CFG->commentsperpage))?$CFG->commentsperpage:15;
        $start = $page * $perpage;
		
        $rs = $DB->get_recordset_sql($sql, $params, $start, $perpage);
        
        foreach ($rs as $u) {
			$this->qid = $u->qid;
            $this->startnum = $u->startnum;
            $this->endnum = $u->endnum;
		}
        $rs->close();
	}
        
      //  echo $this->config;
        
        if(!stristr($pageurl,$review) === FALSE && !stristr($pageurl,$attempt) === FALSE) {
			$display = 1;
			
			$and = "&";
			if(stristr($pageurl,$and) === FALSE) {
			//and absent..directly get attemptid
				$pos = strpos($pageurl,$attempt);
				$pos = $pos + 8;
				$aid = substr($pageurl,$pos);
				
				
			}
			else {
				$pos = strpos($pageurl,$attempt);
				$pos = $pos + 8;
				$pos1 = strpos($pageurl,$and);
				$pos1 = $pos1 - $pos;
				$aid = substr($pageurl,$pos,$pos1);
				
			}
		}

        $args = new stdClass;
        $args->context   = $PAGE->context;
        $args->course    = $course;
        $args->area      = 'page_phrasecomments';
        $args->itemid    = 0;
        $args->component = 'block_phrasecomments';
        $args->linktext  = get_string('showcomments');
        $args->notoggle  = true;
        $args->autostart = true;
        $args->displaycancel = false;
        $args->startnum 	= $this->startnum;
        $args->endnum 		= $this->endnum;
        $args->attemptid 	= $aid;
        $args->qid 			= $this->qid;
		$args->instanceid   = $this->instance->id;
    
        $this->title="Question ".$this->qid." [".$this->startnum."-".$this->endnum."]";       

        $comment = new phrasecomment($args);
        $comment->set_view_permission(true);

        $this->content = new stdClass();
        if($PAGE->context->contextlevel == 70 && $display==1) {
			
			$this->content->text = $comment->output(true);
		}
		else {
			$this->content->text = '';
		}
	
        $this->content->footer = '';
        return $this->content;
    }
}
