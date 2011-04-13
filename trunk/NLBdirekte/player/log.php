<?php
/*
 *	log.php?data=...
 *	Jostein Austvik Jacobsen, NLB, 2010
 */
include('common.inc.php');

header('Content-Type: application/json; charset=utf-8');

# decode JSON-log
$log = json_decode($_REQUEST['data'],true);
switch (json_last_error()) {
	// potential problem: unauthenticated users may hammer this
	// script with invalid JSON-data to fill up the logs with garbage.
	case JSON_ERROR_DEPTH:			trigger_error('json_decode - The maximum stack depth has been exceeded'); break;
	case JSON_ERROR_CTRL_CHAR:		trigger_error('json_decode - Control character error, possibly incorrectly encoded'); break;
	case JSON_ERROR_STATE_MISMATCH:	trigger_error('json_decode - Invalid or malformed JSON'); break;
	case JSON_ERROR_SYNTAX:			trigger_error('json_decode - Syntax error'); break;
}

list($user, $book) = decodeTicket($log[0]['ticket']);
authorize($user,$book,isset($_REQUEST['session'])?$_REQUEST['session']:'');

# if launchTime is set, use that to put log entries in its own log
if (isset($log[0]['launchTime']))
	$logfile = microtimeAndUsername2logfile($log[0]['launchTime'],$user);

foreach ($log as $entry) {
	logMessage(array(
		"eventTime" => microtime2isostring($entry['timestamp']/1000.),
		"language" => "javascript",
		"type" => $entry['level'],
		"message" => preg_replace("/\r?\n/", "<br/>", $entry['message']),
		"file" => preg_replace('/^([^?#]*).*/','$1',$entry['url']),
		"line" => -1
	));
}

?>
