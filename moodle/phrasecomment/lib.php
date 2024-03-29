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
 * Comment is helper class to add/delete comments anywhere in moodle
 *
 * @package   comment
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


class phrasecomment {
    /**
     * there may be several comment box in one page
     * so we need a client_id to recognize them
     * @var integer
     */
    private $cid;
   // variables for phrase comments
    private $attemptid;
    private $phraseid;
    private $startnum;
    private $endnum;
	private $qid;
	private $instanceid;	
    /**
     * commentarea is used to specify different
     * parts shared the same itemid
     * @var string
     */
    private $commentarea;
    /**
     * itemid is used to associate with commenting content
     * @var integer
     */
    private $itemid;
    /**
     * this html snippet will be used as a template
     * to build comment content
     * @var string
     */
    private $template;
    /**
     * The context id for comments
     * @var int
     */
    private $contextid;
    /**
     * The context itself
     * @var stdClass
     */
    private $context;
    /**
     * The course id for comments
     * @var int
     */
    private $courseid;
    /**
     * course module object, only be used to help find pluginname automatically
     * if pluginname is specified, it won't be used at all
     * @var stdClass
     */
    private $cm;
    /**
     * The component that this comment is for. It is STRONGLY recommended to set this.
     * @var string
     */
    private $component;
    /**
     * This is calculated by normalising the component
     * @var string
     */
    private $pluginname;
    /**
     * This is calculated by normalising the component
     * @var string
     */
    private $plugintype;
    /**
     * Whether the user has the required capabilities/permissions to view comments.
     * @var bool
     */
    private $viewcap = false;
    /**
     * Whether the user has the required capabilities/permissions to post comments.
     * @var bool
     */
    private $postcap = false;
    /**
     * to costomize link text
     * @var string
     */
    private $linktext;
    /**
     * If set to true then comment sections won't be able to be opened and closed
     * instead they will always be visible.
     * @var bool
     */
    protected $notoggle = false;
    /**
     * If set to true comments are automatically loaded as soon as the page loads.
     * Normally this happens when the user expands the comment section.
     * @var bool
     */
    protected $autostart = false;
    /**
     * If set to true the total count of comments is displayed when displaying comments.
     * @var bool
     */
    protected $displaytotalcount = false;
    /**
     * If set to true a cancel button will be shown on the form used to submit comments.
     * @var bool
     */
    protected $displaycancel = false;
    /**
     * The number of comments associated with this comments params
     * @var int
     */
    protected $totalcommentcount = null;

    /**#@+
     * static variable will be used by non-js comments UI
     */
    private static $nonjs = false;
    private static $comment_itemid = null;
    private static $comment_context = null;
    private static $comment_area = null;
    private static $comment_page = null;
    private static $comment_component = null;
    /**#@-*/

    /**
     * Construct function of comment class, initialise
     * class members
     * @param stdClass $options
     * @param object $options {
     *            context => context context to use for the comment [required]
     *            component => string which plugin will comment being added to [required]
     *            itemid  => int the id of the associated item (forum post, glossary item etc) [required]
     *            area    => string comment area
     *            cm      => stdClass course module
     *            course  => course course object
     *            client_id => string an unique id to identify comment area
     *            autostart => boolean automatically expend comments
     *            showcount => boolean display the number of comments
     *            displaycancel => boolean display cancel button
     *            notoggle => boolean don't show/hide button
     *            linktext => string title of show/hide button
     * }
     */
    public function __construct(stdClass $options) {
        $this->viewcap = false;
        $this->postcap = false;
        
        //setting up phrase params
        if (!empty($options->instanceid)) {
            $this->instanceid = $options->instanceid;
        } else {
            $this->instanceid = 0;
        }
        if (!empty($options->qid)) {
            $this->qid = $options->qid;
        } else {
            $this->qid = 1;
        }
        if (!empty($options->startnum)) {
            $this->startnum = $options->startnum;
        } else {
            $this->startnum = 0;
        }
        if (!empty($options->endnum)) {
            $this->endnum = $options->endnum;
        } else {
            $this->endnum = 0;
        }
        if (!empty($options->attemptid)) {
            $this->attemptid = $options->attemptid;
        } else {
            $this->attemptid = 0;
        }
        if (!empty($options->phraseid)) {
            $this->phraseid = $options->phraseid;
        } else {
            $this->phraseid = 0;
        }
        
        
        // setup client_id
        if (!empty($options->client_id)) {
            $this->cid = $options->client_id;
        } else {
            $this->cid = uniqid();
        }

        // setup context
        if (!empty($options->context)) {
            $this->context = $options->context;
            $this->contextid = $this->context->id;
        } else if(!empty($options->contextid)) {
            $this->contextid = $options->contextid;
            $this->context = get_context_instance_by_id($this->contextid);
        } else {
            print_error('invalidcontext');
        }

        if (!empty($options->component)) {
            // set and validate component
            $this->set_component($options->component);
        } else {
            // component cannot be empty
            throw new phrasecomment_exception('invalidcomponent');
        }

        // setup course
        // course will be used to generate user profile link
        if (!empty($options->course)) {
            $this->courseid = $options->course->id;
        } else if (!empty($options->courseid)) {
            $this->courseid = $options->courseid;
        } else {
            $this->courseid = SITEID;
        }

        // setup coursemodule
        if (!empty($options->cm)) {
            $this->cm = $options->cm;
        } else {
            $this->cm = null;
        }

        // setup commentarea
        if (!empty($options->area)) {
            $this->commentarea = $options->area;
        }

        // setup itemid
        if (!empty($options->itemid)) {
            $this->itemid = $options->itemid;
        } else {
            $this->itemid = 0;
        }

        // setup customized linktext
        if (!empty($options->linktext)) {
            $this->linktext = $options->linktext;
        } else {
            $this->linktext = get_string('comments');
        }

        // setup options for callback functions
        $this->comment_param = new stdClass();
        $this->comment_param->context     = $this->context;
        $this->comment_param->courseid    = $this->courseid;
        $this->comment_param->cm          = $this->cm;
        $this->comment_param->commentarea = $this->commentarea;
        $this->comment_param->itemid      = $this->itemid;

        // setup notoggle
        if (!empty($options->notoggle)) {
            $this->set_notoggle($options->notoggle);
        }

        // setup notoggle
        if (!empty($options->autostart)) {
            $this->set_autostart($options->autostart);
        }

        // setup displaycancel
        if (!empty($options->displaycancel)) {
            $this->set_displaycancel($options->displaycancel);
        }

        // setup displaytotalcount
        if (!empty($options->showcount)) {
            $this->set_displaytotalcount($options->showcount);
        }

        // setting post and view permissions
        $this->check_permissions();

        // load template
        $this->template  = html_writer::tag('div', '___picture___', array('class' => 'comment-userpicture'));
        $this->template .= html_writer::start_tag('div', array('class' => 'comment-content'));
        $this->template .= '___name___ - ';
        $this->template .= html_writer::tag('span', '___time___');
        $this->template .= html_writer::tag('div', '___content___');
        $this->template .= html_writer::end_tag('div'); // .comment-content
        if (!empty($this->plugintype)) {
            $this->template = plugin_callback($this->plugintype, $this->pluginname, 'comment', 'template', array($this->comment_param), $this->template);
        }

        unset($options);
    }

    /**
     * Receive nonjs comment parameters
     *
     * @param moodle_page $page The page object to initialise comments within
     *                          If not provided the global $PAGE is used
     */
    
    public static function init(moodle_page $page = null) {
        global $PAGE;

        if (empty($page)) {
            $page = $PAGE;
        }
        // setup variables for non-js interface
        self::$nonjs = optional_param('nonjscomment', '', PARAM_ALPHANUM);
        self::$comment_itemid  = optional_param('comment_itemid',  '', PARAM_INT);
        self::$comment_context = optional_param('comment_context', '', PARAM_INT);
        self::$comment_page    = optional_param('comment_page',    '', PARAM_INT);
        self::$comment_area    = optional_param('comment_area',    '', PARAM_ALPHAEXT);

        $page->requires->string_for_js('addcomment', 'moodle');
        $page->requires->string_for_js('deletecomment', 'moodle');
        $page->requires->string_for_js('comments', 'moodle');
        $page->requires->string_for_js('commentsrequirelogin', 'moodle');
    }

    /**
     * Sets the component.
     *
     * This method shouldn't be public, changing the component once it has been set potentially
     * invalidates permission checks.
     * A coding_error is now thrown if code attempts to change the component.
     *
     * @param string $component
     * @return void
     */
    public function set_component($component) {
        if (!empty($this->component) && $this->component !== $component) {
            throw new coding_exception('You cannot change the component of a comment once it has been set');
        }
        $this->component = $component;
        list($this->plugintype, $this->pluginname) = normalize_component($component);
    }

    /**
     * Determines if the user can view the comment.
     *
     * @param bool $value
     */
    public function set_view_permission($value) {
        $this->viewcap = (bool)$value;
    }

    /**
     * Determines if the user can post a comment
     *
     * @param bool $value
     */
    public function set_post_permission($value) {
        $this->postcap = (bool)$value;
    }

    /**
     * check posting comments permission
     * It will check based on user roles and ask modules
     * If you need to check permission by modules, a
     * function named $pluginname_check_comment_post must be implemented
     */
    private function check_permissions() {
        $this->postcap = has_capability('moodle/comment:post', $this->context);
        $this->viewcap = has_capability('moodle/comment:view', $this->context);
        if (!empty($this->plugintype)) {
            $permissions = plugin_callback($this->plugintype, $this->pluginname, 'comment', 'permissions', array($this->comment_param), array('post'=>false, 'view'=>false));
            $this->postcap = $this->postcap && $permissions['post'];
            $this->viewcap = $this->viewcap && $permissions['view'];
        }
    }

    /**
     * Gets a link for this page that will work with JS disabled.
     *
     * @global moodle_page $PAGE
     * @param moodle_page $page
     * @return moodle_url
     */
    public function get_nojslink(moodle_page $page = null) {
        if ($page === null) {
            global $PAGE;
            $page = $PAGE;
        }

        $link = new moodle_url($page->url, array(
            'nonjscomment'    => true,
            'comment_itemid'  => $this->itemid,
            'comment_context' => $this->context->id,
            'comment_area'    => $this->commentarea,
        ));
        $link->remove_params(array('comment_page'));
        return $link;
    }

    /**
     * Sets the value of the notoggle option.
     *
     * If set to true then the user will not be able to expand and collase
     * the comment section.
     *
     * @param bool $newvalue
     */
    public function set_notoggle($newvalue = true) {
        $this->notoggle = (bool)$newvalue;
    }

    /**
     * Sets the value of the autostart option.
     *
     * If set to true then the comments will be loaded during page load.
     * Normally this happens only once the user expands the comment section.
     *
     * @param bool $newvalue
     */
    public function set_autostart($newvalue = true) {
        $this->autostart = (bool)$newvalue;
    }

    /**
     * Sets the displaycancel option
     *
     * If set to true then a cancel button will be shown when using the form
     * to post comments.
     *
     * @param bool $newvalue
     */
    public function set_displaycancel($newvalue = true) {
        $this->displaycancel = (bool)$newvalue;
    }

    /**
     * Sets the displaytotalcount option
     *
     * If set to true then the total number of comments will be displayed
     * when printing comments.
     *
     * @param bool $newvalue
     */
    public function set_displaytotalcount($newvalue = true) {
        $this->displaytotalcount = (bool)$newvalue;
    }

    /**
     * Initialises the JavaScript that enchances the comment API.
     *
     * @param moodle_page $page The moodle page object that the JavaScript should be
     *                          initialised for.
     */
    public function initialise_javascript(moodle_page $page) {

        $options = new stdClass;
        $options->client_id   = $this->cid;
        $options->commentarea = $this->commentarea;
        $options->itemid      = $this->itemid;
        $options->page        = 0;
        $options->courseid    = $this->courseid;
        $options->contextid   = $this->contextid;
        $options->component   = $this->component;
        $options->notoggle    = $this->notoggle;
        $options->autostart   = $this->autostart;
		$options->phraseid    = $this->phraseid;
		$options->attemptid	  = $this->attemptid;
		$options->startnum	  = $this->startnum;
		$options->endnum      = $this->endnum;
		$options->qid      	  = $this->qid;
		$options->instanceid  = $this->instanceid;

		$phrasecommentjs = array(
				'name'     => 'core_phrasecomment',
		        'fullpath' => '/phrasecomment/phrasecomment.js',
		        'requires' => array('base', 'io', 'node', 'json', 'yui2-animation', 'overlay'),
		        'strings'  => array( array('confirmdeletecomments', 'admin'), array('yes', 'moodle'), array('no', 'moodle'))
		);
        $page->requires->js_init_call('M.core_phrasecomment.init', array($options), true,$phrasecommentjs);

        return true;
    }

    /**
     * Prepare comment code in html
     * @param  boolean $return
     * @return mixed
     */
    public function output($return = true) {
        global $PAGE, $OUTPUT;
        static $template_printed;

        $this->initialise_javascript($PAGE);
		
        if (!empty(self::$nonjs)) {
            // return non js comments interface
           
            return $this->print_comments(self::$comment_page, $return, true);
        }

        $html = '';
		
        // print html template
        // Javascript will use the template to render new comments
        if (empty($template_printed) && $this->can_view()) {
            $html .= html_writer::tag('div', $this->template, array('style' => 'display:none', 'id' => 'cmt-tmpl'));
            $template_printed = true;
        }
		
        if ($this->can_view()) {
			
            // print commenting icon and tooltip
            $html .= html_writer::start_tag('div', array('class' => 'mdl-left'));
            $html .= html_writer::link($this->get_nojslink($PAGE), get_string('showcommentsnonjs'), array('class' => 'showcommentsnonjs'));
			
            if (!$this->notoggle) {
                // If toggling is enabled (notoggle=false) then print the controls to toggle
                // comments open and closed
                $countstring = '';
                if ($this->displaytotalcount) {
                    $countstring = '('.$this->count().')';
                }
                $html .= html_writer::start_tag('a', array('class' => 'comment-link', 'id' => 'comment-link-'.$this->cid, 'href' => '#'));
                $html .= html_writer::empty_tag('img', array('id' => 'comment-img-'.$this->cid, 'src' => $OUTPUT->pix_url('t/collapsed'), 'alt' => $this->linktext, 'title' => $this->linktext));
                $html .= html_writer::tag('span', $this->linktext.' '.$countstring, array('id' => 'comment-link-text-'.$this->cid));
                $html .= html_writer::end_tag('a');
            }
			
            $html .= html_writer::start_tag('div', array('id' => 'comment-ctrl-'.$this->cid, 'class' => 'comment-ctrl'));

            if ($this->autostart) {
				
                // If autostart has been enabled print the comments list immediatly
                $html .= html_writer::start_tag('ul', array('id' => 'comment-list-'.$this->cid, 'class' => 'comment-list comments-loaded'));
                $html .= html_writer::tag('li', '', array('class' => 'first'));
                $html .= $this->print_comments(0, true, false);
                $html .= html_writer::end_tag('ul'); // .comment-list
                $html .= $this->get_pagination(0);
            } else {
                $html .= html_writer::start_tag('ul', array('id' => 'comment-list-'.$this->cid, 'class' => 'comment-list'));
                $html .= html_writer::tag('li', '', array('class' => 'first'));
                $html .= html_writer::end_tag('ul'); // .comment-list
                $html .= html_writer::tag('div', '', array('id' => 'comment-pagination-'.$this->cid, 'class' => 'comment-pagination'));
            }
            

            if ($this->can_post()) {
                // print posting textarea
                
				
                $html .= html_writer::start_tag('div', array('class' => 'comment-area'));
                $html .= html_writer::start_tag('div', array('class' => 'db'));
                $html .= html_writer::tag('textarea', '', array('name' => 'content', 'rows' => 2, 'cols' => 20, 'id' => 'dlg-content-'.$this->cid));
                $html .= html_writer::end_tag('div'); // .db

                $html .= html_writer::start_tag('div', array('class' => 'fd', 'id' => 'comment-action-'.$this->cid));
                $html .= html_writer::link('#', get_string('savecomment'), array('id' => 'comment-action-post-'.$this->cid));
				if($this->can_resolve()) {	
					$html .= html_writer::start_tag('div', array('class' => 'rd', 'id' => 'comment-action-r-'.$this->cid));
					$html .= html_writer::link('#', "resolve", array('id' => 'comment-action-resolve-'.$this->cid));
					$html .= html_writer::end_tag('div');
				}
                if ($this->displaycancel) {
                    $html .= html_writer::tag('span', ' | ');
                    $html .= html_writer::link('#', get_string('cancel'), array('id' => 'comment-action-cancel-'.$this->cid));
                }

                $html .= html_writer::end_tag('div'); // .fd
                $html .= html_writer::end_tag('div'); // .comment-area
                $html .= html_writer::tag('div', '', array('class' => 'clearer'));
            }

            $html .= html_writer::end_tag('div'); // .comment-ctrl
            $html .= html_writer::end_tag('div'); // .mdl-left
        } else {
            $html = '';
        }
		
        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }
    
    
    public function can_resolve() {
		global $USER, $CFG, $DB;
		$curuser = $USER->id;
		
		$params = array();
        $sql = "SELECT c.roleid AS roleid FROM {role_assignments} c WHERE  c.userid = :userid";
        $params['contextid'] = $this->contextid;
        $params['userid'] = $curuser;
		$page = 0;
        $perpage = (!empty($CFG->commentsperpage))?$CFG->commentsperpage:15;
        $start = $page * $perpage;
		
        $rs = $DB->get_recordset_sql($sql, $params, $start, $perpage);
        $roleid = 0;
        foreach ($rs as $u) {
			
			$roleid = $u->roleid;
		}
        $rs->close();
        
		if($roleid == 4 || $roleid == 3 || $roleid == 1) {
			return true;
		}
		else {
			return false;
			}
			 
	}

    /**
     * Return matched comments
     *
     * @param  int $page
     * @return mixed
     */
    public function get_comments($page = '') {
        global $DB, $CFG, $USER, $OUTPUT;
        if (!$this->can_view()) {
            return false;
        }
        if (!is_numeric($page)) {
            $page = 0;
        }
        
        $params = array();
        $sql = "SELECT c.postcap AS postcap FROM {phrasecomments} c WHERE c.contextid = :contextid AND c.attemptid = :attemptid AND c.qid = :qid AND c.startnum = :startnum AND c.endnum = :endnum AND c.instanceid = :instanceid";
        $params['contextid'] = $this->contextid;
        $params['attemptid'] = $this->attemptid;
        $params['startnum'] = $this->startnum;
        $params['endnum'] = $this->endnum;
        $params['qid'] = $this->qid;
		$params['instanceid'] = $this->instanceid;	
		
        $perpage = (!empty($CFG->commentsperpage))?$CFG->commentsperpage:15;
        $start = $page * $perpage;
		
        $rs = $DB->get_recordset_sql($sql, $params, $start, $perpage);
        
        foreach ($rs as $u) {
			$this->postcap = $u->postcap;
		}
        $rs->close();
        
        $ufields = user_picture::fields('u');
        $sql = "SELECT $ufields, p.id AS cid, p.content AS ccontent, c.format AS cformat, p.timestamp AS ctimecreated
                  FROM {phrasecomments} c, {user} u, {phrase} p
                 WHERE c.contextid = :contextid AND c.attemptid = :attemptid AND c.instanceid = :instanceid AND c.startnum = :startnum AND c.endnum = :endnum AND c.qid = :qid AND c.id = p.phraseid AND p.userid = u.id AND c.commentarea = :commentarea AND c.itemid = :itemid
              ORDER BY p.timestamp ASC";
        $params['contextid'] = $this->contextid;
        $params['commentarea'] = $this->commentarea;
        $params['itemid'] = $this->itemid;
        $params['attemptid'] = $this->attemptid;
        $params['startnum'] = $this->startnum;
        $params['endnum'] = $this->endnum;
		$params['qid'] = $this->qid;
		$params['instanceid'] = $this->instanceid;
		
        $comments = array();
        $formatoptions = array('overflowdiv' => true);
        $rs = $DB->get_recordset_sql($sql, $params, $start, $perpage);
        foreach ($rs as $u) {
            $c = new stdClass();
            $c->id          = $u->cid;
            $c->content     = $u->ccontent;
            $c->format      = $u->cformat;
            $c->timecreated = $u->ctimecreated;
            $url = new moodle_url('/user/view.php', array('id'=>$u->id, 'course'=>$this->courseid));
            $c->profileurl = $url->out();
            $c->fullname = fullname($u);
            $c->time = userdate($c->timecreated, get_string('strftimerecent', 'langconfig'));
            $c->content = format_text($c->content, $c->format, $formatoptions);
            $c->avatar = $OUTPUT->user_picture($u, array('size'=>18));

            $candelete = $this->can_delete($c->id);
            if (($USER->id == $u->id) || !empty($candelete)) {
                $c->delete = true;
            }
            $comments[] = $c;
        }
        $rs->close();

        if (!empty($this->plugintype)) {
            // moodle module will filter comments
            $comments = plugin_callback($this->plugintype, $this->pluginname, 'comment', 'display', array($comments, $this->comment_param), $comments);
        }

        return $comments;
    }

    /**
     * Returns the number of comments associated with the details of this object
     *
     * @global moodle_database $DB
     * @return int
     */
    public function count() {
        global $DB;
        if ($this->totalcommentcount === null) {
			$params = array();
			$sql = "SELECT id AS id FROM {phrasecomments} c WHERE c.contextid = :contextid AND c.instanceid = :instanceid AND c.attemptid = :attemptid AND c.qid = :qid AND c.startnum = :startnum AND c.endnum = :endnum";
			$params['contextid'] = $this->contextid;
			$params['attemptid'] = $this->attemptid;
			$params['startnum'] = $this->startnum;
			$params['endnum'] = $this->endnum;
			$params['qid'] = $this->qid;
			$params['instanceid'] = $this->instanceid;
			
			$page =0;
			$perpage = (!empty($CFG->commentsperpage))?$CFG->commentsperpage:15;
			$start = $page * $perpage;
        
			$rs = $DB->get_recordset_sql($sql, $params, $start, $perpage);
            $qid = 1;
			foreach ($rs as $u) {
				$qid  = $u->id;
			}
			$rs->close();
			
            $this->totalcommentcount = $DB->count_records('phrase', array('phraseid' => $qid));
		}
		return $this->totalcommentcount;
	}

    /**
     * Returns HTML to display a pagination bar
     *
     * @global stdClass $CFG
     * @global core_renderer $OUTPUT
     * @param int $page
     * @return string
     */
    public function get_pagination($page = 0) {
        global $CFG, $OUTPUT;
        $count = $this->count();
        $perpage = (!empty($CFG->commentsperpage))?$CFG->commentsperpage:15;
        $pages = (int)ceil($count/$perpage);
        if ($pages == 1 || $pages == 0) {
            return html_writer::tag('div', '', array('id' => 'comment-pagination-'.$this->cid, 'class' => 'comment-pagination'));
        }
        if (!empty(self::$nonjs)) {
            // used in non-js interface
            return $OUTPUT->paging_bar($count, $page, $perpage, $this->get_nojslink(), 'comment_page');
        } else {
            // return ajax paging bar
            $str = '';
            $str .= '<div class="comment-paging" id="comment-pagination-'.$this->cid.'">';
            for ($p=0; $p<$pages; $p++) {
                if ($p == $page) {
                    $class = 'curpage';
                } else {
                    $class = 'pageno';
                }
                $str .= '<a href="#" class="'.$class.'" id="comment-page-'.$this->cid.'-'.$p.'">'.($p+1).'</a> ';
            }
            $str .= '</div>';
        }
        return $str;
    }

    /**
     * Add a new comment
     *
     * @global moodle_database $DB
     * @param string $content
     * @return mixed
     */
    public function add($content, $format = FORMAT_MOODLE) {
        global $CFG, $DB, $USER, $OUTPUT;
        if (!$this->can_post()) {
            throw new phrasecomment_exception('nopermissiontocomment');
        }
        $now = time();
        
        
        
        $this->totalcommentcount = $DB->count_records('phrasecomments', array('contextid' => $this->contextid, 'instanceid' => $this->instanceid, 'attemptid' => $this->attemptid, 'qid' => $this->qid, 'startnum' => $this->startnum, 'endnum' =>$this->endnum));
        if(!$this->totalcommentcount) {
			$newcmt1 = new stdClass; 
			$newcmt1->contextid    = $this->contextid;
			$newcmt1->commentarea  = $this->commentarea;
			$newcmt1->itemid       = $this->itemid;
			$newcmt1->format       = $format;
			$newcmt1->attemptid = $this->attemptid;
			$newcmt1->startnum = $this->startnum;
			$newcmt1->endnum = $this->endnum;
			$newcmt1->postcap = $this->postcap;
			$newcmt1->qid = $this->qid;
			$newcmt1->instanceid = $this->instanceid;
			$this->phraseid = $DB->insert_record('phrasecomments', $newcmt1);
			 
		}
		else {
			$params = array();
			$sql = "SELECT id AS id FROM {phrasecomments} c WHERE c.contextid = :contextid AND c.instanceid = :instanceid AND c.attemptid = :attemptid AND c.qid = :qid AND c.startnum = :startnum AND c.endnum = :endnum";
			$params['contextid'] = $this->contextid;
			$params['attemptid'] = $this->attemptid;
			$params['startnum'] = $this->startnum;
			$params['endnum'] = $this->endnum;
			$params['qid'] = $this->qid;
			$params['instanceid'] = $this->instanceid;
			$page = 0;
			$perpage = (!empty($CFG->commentsperpage))?$CFG->commentsperpage:15;
			$start = $page * $perpage;
        
			$rs = $DB->get_recordset_sql($sql, $params, $start, $perpage);
            
			foreach ($rs as $u) {
				$this->phraseid  = $u->id;
			}
			$rs->close();
		}      
        // have to add only if it doesn't already exists
        
        $newcmt = new stdClass;       
        $newcmt->contextid    = $this->contextid;
		$newcmt->content      = $content;
		$newcmt->userid       = $USER->id;
        $newcmt->timestamp  = $now;
        $newcmt->phraseid = $this->phraseid;
        
        $cmt_id = $DB->insert_record('phrase', $newcmt);
        
        if (!empty($cmt_id)) {
			$newcmt->commentarea  = $this->commentarea;
			$newcmt->itemid       = $this->itemid;
			$newcmt->format       = $format;
            $newcmt->id = $cmt_id;
            $newcmt->time = userdate($now, get_string('strftimerecent', 'langconfig'));
            $newcmt->fullname = fullname($USER);
            $url = new moodle_url('/user/view.php', array('id' => $USER->id, 'course' => $this->courseid));
            $newcmt->profileurl = $url->out();
            $newcmt->content = format_text($newcmt->content, $format, array('overflowdiv'=>true));
            $newcmt->avatar = $OUTPUT->user_picture($USER, array('size'=>16));
            return $newcmt;
        } else {
            throw new phrasecomment_exception('dbupdatefailed');
        }
        
        
    }

    /**
     * delete by context, commentarea and itemid
     * @param stdClass|array $param {
     *            contextid => int the context in which the comments exist [required]
     *            commentarea => string the comment area [optional]
     *            itemid => int comment itemid [optional]
     * }
     * @return boolean
     */
    public function delete_comments($param) {
        global $DB;
        $param = (array)$param;
        if (empty($param['contextid'])) {
            return false;
        }
        $DB->delete_records('phrase', $param);
        return true;
    }



	/**
     * Delete page_comments in whole course, used by course reset
     *
     * @param stdClass $context course context
     */
    public function reset_course_page_comments($context) {
        global $DB;
        $contexts = array();
        $contexts[] = $context->id;
        $children = get_child_contexts($context);
        foreach ($children as $c) {
            $contexts[] = $c->id;
        }
        list($ids, $params) = $DB->get_in_or_equal($contexts);
        $DB->delete_records_select('comments', "commentarea='page_comments' AND contextid $ids", $params);
    }

    /**
     * Delete a comment
     *
     * @param  int $commentid
     * @return mixed
     */
    public function delete($commentid) {
        global $DB, $USER;
        $candelete = has_capability('moodle/comment:delete', $this->context);
        if (!$comment = $DB->get_record('phrase', array('id'=>$commentid))) {
            throw new phrasecomment_exception('dbupdatefailed');
        }
        if (!($USER->id == $comment->userid || !empty($candelete))) {
            throw new phrasecomment_exception('nopermissiontocomment');
        }
        $DB->delete_records('phrase', array('id'=>$commentid));
        return true;
    }
    
    public function resolve() {
		global $DB;
		$params = array();
		//	$sql = "SELECT id AS id FROM {phrasecomments} c WHERE c.contextid = :contextid AND c.attemptid = :attemptid AND c.startnum = :startnum AND c.endnum = :endnum";
			$params['contextid'] = $this->contextid;
			$params['attemptid'] = $this->attemptid;
			$params['startnum'] = $this->startnum;
			$params['endnum'] = $this->endnum;
			$params['qid'] = $this->qid;
			$params['instanceid'] = $this->instanceid;
        
			$rs = $DB->get_record('phrasecomments', $params, '*', IGNORE_MULTIPLE);
            $rs->postcap = 0;
		
		$DB->update_record('phrasecomments', $rs);
	}

    /**
     * Print comments
     *
     * @param int $page
     * @param boolean $return return comments list string or print it out
     * @param boolean $nonjs print nonjs comments list or not?
     * @return mixed
     */
    public function print_comments($page = 0, $return = true, $nonjs = true) {
        global $DB, $CFG, $PAGE;

        if (!$this->can_view()) {
            return '';
        }
		
        $html = '';
        if (!(self::$comment_itemid == $this->itemid &&
            self::$comment_context == $this->context->id &&
            self::$comment_area == $this->commentarea)) {
            $page = 0;
        }
        
        $comments = $this->get_comments($page);
		
        $html = '';
        if ($nonjs) {
            $html .= html_writer::tag('h3', get_string('comments'));
            $html .= html_writer::start_tag('ul', array('id' => 'comment-list-'.$this->cid, 'class' => 'comment-list'));
        }
        // Reverse the comments array to display them in the correct direction
        foreach (array_reverse($comments) as $cmt) {
            $html .= html_writer::tag('li', $this->print_comment($cmt, $nonjs), array('id' => 'comment-'.$cmt->id.'-'.$this->cid));
        }
        if ($nonjs) {
            $html .= html_writer::end_tag('ul');
            $html .= $this->get_pagination($page);
        }
        if ($nonjs && $this->can_post() && $this->can_post1()) {
            // Form to add comments
            $html .= html_writer::start_tag('form', array('method' => 'post', 'action' => new moodle_url('/phrasecomment/comment_post.php')));
            // Comment parameters
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'contextid', 'value' => $this->contextid));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'action',    'value' => 'add'));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'area',      'value' => $this->commentarea));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'component', 'value' => $this->component));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'itemid',    'value' => $this->itemid));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'courseid',  'value' => $this->courseid));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'attemptid',  'value' => $this->attemptid));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'phraseid',  'value' => $this->phraseid));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'startnum',  'value' => $this->startnum));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'endnum',  'value' => $this->endnum));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'qid',  'value' => $this->qid));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'instanceid',  'value' => $this->instanceid));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey',   'value' => sesskey()));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'returnurl', 'value' => $PAGE->url));
            // Textarea for the actual comment
            $html .= html_writer::tag('textarea', '', array('name' => 'content', 'rows' => 2));
            // Submit button to add the comment
            $html .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('submit')));
            $html .= html_writer::end_tag('form');
        }
        if ($return) {
            return $html;
        } else {
            echo $html;
        }
    }

    /**
     * Returns an array containing comments in HTML format.
     *
     * @global core_renderer $OUTPUT
     * @param stdClass $cmt {
     *          id => int comment id
     *          content => string comment content
     *          format  => int comment text format
     *          timecreated => int comment's timecreated
     *          profileurl  => string link to user profile
     *          fullname    => comment author's full name
     *          avatar      => string user's avatar
     *          delete      => boolean does user have permission to delete comment?
     * }
     * @param bool $nonjs
     * @return array
     */
    public function print_comment($cmt, $nonjs = true) {
        global $OUTPUT;
        $patterns = array();
        $replacements = array();

        if (!empty($cmt->delete) && empty($nonjs)) {
            $deletelink  = html_writer::start_tag('div', array('class'=>'comment-delete'));
            $deletelink .= html_writer::start_tag('a', array('href' => '#', 'id' => 'comment-delete-'.$this->cid.'-'.$cmt->id));
            $deletelink .= $OUTPUT->pix_icon('t/delete', get_string('delete'));
            $deletelink .= html_writer::end_tag('a');
            $deletelink .= html_writer::end_tag('div');
            $cmt->content = $deletelink . $cmt->content;
        }
        $patterns[] = '___picture___';
        $patterns[] = '___name___';
        $patterns[] = '___content___';
        $patterns[] = '___time___';
        $replacements[] = $cmt->avatar;
        $replacements[] = html_writer::link($cmt->profileurl, $cmt->fullname);
        $replacements[] = $cmt->content;
        $replacements[] = userdate($cmt->timecreated, get_string('strftimerecent', 'langconfig'));

        // use html template to format a single comment.
        return str_replace($patterns, $replacements, $this->template);
    }

    /**
     * Revoke validate callbacks
     *
     * @param stdClass $params addtionall parameters need to add to callbacks
     */
    protected function validate($params=array()) {
        foreach ($params as $key=>$value) {
            $this->comment_param->$key = $value;
        }
        $validation = plugin_callback($this->plugintype, $this->pluginname, 'comment', 'validate', array($this->comment_param), false);
        if (!$validation) {
            throw new phrasecomment_exception('invalidcommentparam');
        }
    }

    /**
     * Returns true if the user is able to view comments
     * @return bool
     */
    public function can_view() {
        $this->validate();
        return !empty($this->viewcap);
    }

    /**
     * Returns true if the user can add comments against this comment description
     * @return bool
     */
    public function can_post() {
        $this->validate();
        return isloggedin() && !empty($this->postcap);
    }

    public function can_post1() {
        $params = array();
        $sql = "SELECT c.attemptid AS attemptid FROM {phrasecomments} c WHERE c.instanceid = :instanceid";
		$params['instanceid'] = $this->instanceid;	
		$page = 0;
        $perpage = (!empty($CFG->commentsperpage))?$CFG->commentsperpage:15;
        $start = $page * $perpage;
		
        $rs = $DB->get_recordset_sql($sql, $params, $start, $perpage);
        $atid = 0;
        foreach ($rs as $u) {
			$atid = $u->attemptid;
		}
        $rs->close();
        if($atid == $this->attemptid) {
            return true;
        }
        else {
            return false;
        }    
    }

    /**
     * Returns true if the user can delete this comment
     * @param int $commentid
     * @return bool
     */
    public function can_delete($commentid) {
        $this->validate(array('commentid'=>$commentid));
        return has_capability('moodle/comment:delete', $this->context);
    }

    /**
     * Returns the component associated with the comment
     * @return string
     */
    public function get_compontent() {
        return $this->component;
    }

    /**
     * Returns the context associated with the comment
     * @return stdClass
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Returns the course id associated with the comment
     * @return int
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * Returns the course module associated with the comment
     *
     * @return stdClass
     */
    public function get_cm() {
        return $this->cm;
    }

    /**
     * Returns the item id associated with the comment
     *
     * @return int
     */
    public function get_itemid() {
        return $this->itemid;
    }

    /**
     * Returns the comment area associated with the commentarea
     *
     * @return stdClass
     */
    public function get_commentarea() {
        return $this->commentarea;
    }
}

class phrasecomment_exception extends moodle_exception {
}
