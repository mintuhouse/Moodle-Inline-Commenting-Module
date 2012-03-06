<?php

define('AJAX_SCRIPT', true);

require_once('../config.php');
require_once($CFG->dirroot . '/phrasecomment/lib.php');

$contextid = optional_param('contextid', SYSCONTEXTID, PARAM_INT);
$action    = optional_param('action', '', PARAM_ALPHA);


