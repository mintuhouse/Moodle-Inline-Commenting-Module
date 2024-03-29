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
 * comment_manager is helper class to manage moodle comments in admin page (Reports->Comments)
 *
 * @package   comment
 * @copyright  2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_manager {

    /**
     * The number of comments to display per page
     * @var int
     */
    private $perpage;

    /**
     * Constructs the comment_manage object
     */
    public function __construct() {
        global $CFG;
        $this->perpage = $CFG->commentsperpage;
    }

    /**
     * Return comments by pages
     *
     * @global moodle_database $DB
     * @param int $page
     * @return array An array of comments
     */
    function get_comments($page) {
        global $DB;

        if ($page == 0) {
            $start = 0;
        } else {
            $start = $page * $this->perpage;
        }
        $comments = array();

        $sql = "SELECT c.id, c.contextid, c.itemid, c.commentarea, c.userid, c.content, u.firstname, u.lastname, c.timecreated
                  FROM {phrase} c
                  JOIN {user} u
                       ON u.id=c.userid
              ORDER BY c.timecreated ASC";
        $rs = $DB->get_recordset_sql($sql, null, $start, $this->perpage);
        $formatoptions = array('overflowdiv' => true);
        foreach ($rs as $item) {
            // Set calculated fields
            $item->fullname = fullname($item);
            $item->time = userdate($item->timecreated);
            $item->content = format_text($item->content, FORMAT_MOODLE, $formatoptions);
            // Unset fields not related to the comment
            unset($item->firstname);
            unset($item->lastname);
            unset($item->timecreated);
            // Record the comment
            $comments[] = $item;
        }
        $rs->close();

        return $comments;
    }

    /**
     * Records the course object
     *
     * @global moodle_page $PAGE
     * @global moodle_database $DB
     * @param int $courseid
     * @return void
     */
    private function setup_course($courseid) {
        global $PAGE, $DB;
        if (!empty($this->course)) {
            // already set, stop
            return;
        }
        if ($courseid == $PAGE->course->id) {
            $this->course = $PAGE->course;
        } else if (!$this->course = $DB->get_record('course', array('id' => $courseid))) {
            $this->course = null;
        }
    }

    /**
     * Sets up the module or block information for a comment
     *
     * @global moodle_database $DB
     * @param stdClass $comment
     * @return bool
     */
    private function setup_plugin($comment) {
        global $DB;
        $this->context = get_context_instance_by_id($comment->contextid);
        if (!$this->context) {
            return false;
        }
        switch ($this->context->contextlevel) {
            case CONTEXT_BLOCK:
                if ($block = $DB->get_record('block_instances', array('id' => $this->context->instanceid))) {
                    $this->plugintype = 'block';
                    $this->pluginname = $block->blockname;
                } else {
                    return false;
                }
                break;
            case CONTEXT_MODULE:
                $this->plugintype = 'mod';
                $this->cm = get_coursemodule_from_id('', $this->context->instanceid);
                $this->setup_course($this->cm->course);
                $this->modinfo = get_fast_modinfo($this->course);
                $this->pluginname = $this->modinfo->cms[$this->cm->id]->modname;
                break;
        }
        return true;
    }

    /**
     * Print comments
     * @param int $page
     * @return boolean return false if no comments available
     */
    public function print_comments($page = 0) {
        global $OUTPUT, $CFG, $OUTPUT, $DB;

        $count = $DB->count_records('phrase');
        $comments = $this->get_comments($page);
        if (count($comments) == 0) {
            echo $OUTPUT->notification(get_string('nocomments', 'moodle'));
            return false;
        }

        $table = new html_table();
        $table->head = array (
            html_writer::checkbox('selectall', '', false, get_string('selectall'), array('id'=>'comment_select_all', 'class'=>'comment-report-selectall')),
            get_string('author', 'search'),
            get_string('content'),
            get_string('action')
        );
        $table->align = array ('left', 'left', 'left', 'left');
        $table->attributes = array('class'=>'generaltable commentstable');
        $table->data = array();

        $link = new moodle_url('/phrasecomment/index.php', array('action' => 'delete', 'sesskey' => sesskey()));
        foreach ($comments as $c) {
            $this->setup_plugin($c);
            if (!empty($this->plugintype)) {
                $context_url = plugin_callback($this->plugintype, $this->pluginname, 'comment', 'url', array($c));
            }
            $checkbox = html_writer::checkbox('comments', $c->id, false);
            $action = html_writer::link(new moodle_url($link, array('commentid' => $c->id)), get_string('delete'));
            if (!empty($context_url)) {
                $action .= html_writer::empty_tag('br');
                $action .= html_writer::link($context_url, get_string('commentincontext'), array('target'=>'_blank'));
            }
            $table->data[] = array($checkbox, $c->fullname, $c->content, $action);
        }
        echo html_writer::table($table);
        echo $OUTPUT->paging_bar($count, $page, $this->perpage, $CFG->wwwroot.'/phrasecomment/index.php');
        return true;
    }

    /**
     * Delete a comment
     *
     * @param int $commentid
     * @return bool
     */
    public function delete_comment($commentid) {
        global $DB;
        if ($DB->record_exists('phrase', array('id' => $commentid))) {
            $DB->delete_records('phrase', array('id' => $commentid));
            return true;
        }
        return false;
    }
    /**
     * Delete comments
     *
     * @param string $list A list of comment ids separated by hyphens
     * @return bool
     */
    public function delete_comments($list) {
        global $DB;
        $ids = explode('-', $list);
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($DB->record_exists('phrase', array('id' => $id))) {
                $DB->delete_records('phrase', array('id' => $id));
            }
        }
        return true;
    }
	$block_phrasecommentjs = array(
			'name'     => 'core_phrasecomment',
            'fullpath' => '/phrasecomment/phrasecomment.js',
            'requires' => array('base', 'io', 'node', 'json', 'yui2-animation', 'overlay'),
            'strings'  => array(array('confirmdeletecomments', 'admin'), array('yes', 'moodle'), array('no', 'moodle'))
    );

}
