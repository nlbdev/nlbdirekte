<?php
/*
 *	isprepared.php?ticket=...
 *	Jostein Austvik Jacobsen, NLB, 2010
 */
include('common.inc.php');

header('Content-Type: application/json; charset=utf-8');

# decode JSON-log
$log = json_decode($_REQUEST['data'],true);
switch (json_last_error()) {
	// potential problem: unauthenticated users may hammer this
	// script with invalid JSON-data to fill up the logs with garbage.
	case JSON_ERROR_DEPTH:		trigger_error('json_decode - Maximum stack depth exceeded'); break;
	case JSON_ERROR_CTRL_CHAR:	trigger_error('json_decode - Unexpected control character found'); break;
	case JSON_ERROR_SYNTAX:		trigger_error('json_decode - Syntax error, malformed JSON'); break;
}

# decode ticket here
list($user, $book) = decodeTicket($log[0]['ticket']);

// Not valid request?
/*if (!(valid request)) {
	return "you are not logged in";
}*/


foreach ($log as $entry) {
	trigger_error('[javascript] ['.$entry['timestamp'].'] ['.$entry['level'].']: '.$entry['message'].' (in logger: '.$entry['logger'].' at url: '.$entry['url'].')');
}

?>
