<?php

# version of NLBdirekte
$version = '0.21.4';

include('config/config.inc.php'); // import users config-file

# ---- default configuration variables ----

# bookmarks
if (!isset($bookmarks_enabled)) $bookmarks_enabled = false;
if (!isset($bookmarks_db_username)) $bookmarks_db_username = 'root';
if (!isset($bookmarks_db_password)) $bookmarks_db_password = '';
if (!isset($bookmarks_db_hostname)) $bookmarks_db_hostname = 'localhost';
if (!isset($bookmarks_db_database)) $bookmarks_db_database = 'bookmarks';

# relative paths to general DMZ and profile storage
if (!isset($shared)) $shared = getcwd().'/../books';
if (!isset($profiles)) $profiles = getcwd().'/../profiles';

# other logfiles go in this directory
if (!isset($logdir)) $logdir = getcwd().'/logs';

# all PHP-errors, warnings and notices are appended to this file
if (!isset($logfile)) $logfile = $logdir.'/log_'.date("Y-m-d").'.log';

# in case Calabash is not in PATH, the absolute path can
# be given in $calabashExec
if (!isset($calabashExec)) $calabashExec = "calabash";

# debugging
if (!isset($debug)) $debug = false;

# default authorization function
if (!function_exists('authorize')) {
	function authorize($user, $book, $session) { return true; }
}

# ---- end of default configuration variables ----

# should make it easy to distinguish log entries in the log when they get intertwined
$requestTime = microtime(true);

# decoding tickets
# usage: list($userId, $bookId) = decodeTicket($ticket);
function decodeTicket($ticket) {
	global $debug;
    $ret = explode('_',str_replace(array('/',"\\"),'',$ticket));
	//if ($debug) trigger_error("ticket $ticket decoded as userId=".$ret[0].", bookId=".$ret[1]);
    return $ret;
}

function path_as_url($filepath) {
	global $debug;
	$url = 'file:';
	if (substr($filepath,0,1)!=='/')
		$url .= '/';
	$url .= str_replace(' ','%20',str_replace("\\",'/',$filepath));
	//if ($debug) trigger_error("$filepath as URL: $url");
	return $url;
}

function fix_directory_separators($filepath) {
	global $debug;
	$path = str_replace('/',DIRECTORY_SEPARATOR,str_replace("\\",'/',$filepath));
	//if ($debug) trigger_error("$filepath with fixed directory separators: $path");
	return $path;
}

function microtimeAndUsername2logfile($time,$user) {
	global $logdir;
	return $logdir.'/log_'.date("Y-m-d.H-i-s.",floor($time)).preg_replace('/^0.(...).*$/','$1',strval($time-floor($time))).'_'.$user.'.log';
}

function microtime2isostring($time,$utc=false) {
	if (!is_numeric($time))
		$time = 0;
	return date("Y-m-d",floor($time)).'T'.date("H:i:s",floor($time)).preg_replace('/0\.(...).*$/','.$1',strval($time-floor($time))).($utc?'+00:00':date('P'));
}

function isostring2microtime($iso,$utc=false) {
	// inverse of microtime2isostring
	preg_match('/^(\d+)-(\d+)-(\d+)T(\d+):(\d+):([\d\.E+-]+)([+-])(\d+):(\d+)$/',$iso,$matches);
	if (count($matches) < 10) trigger_error("Problem parsing ISO time: '$iso'".json_encode(debug_backtrace()));
	list($pattern,$year,$month,$day,$hour,$minute,$seconds,$timezoneSign,$timezoneHours,$timezoneMinutes) = $matches;
	$time = mktime($hour,$minute,floor($seconds),$month,$day,$year)+date('Z')
			- intval($timezoneSign.'1')*($timezoneHours*3600+$timezoneMinutes*60)
			+ ($seconds-floor($seconds));
	return $time;
}

# debugging (http://php.net/manual/en/function.set-error-handler.php)
error_reporting(E_ALL);
register_shutdown_function('shutdownHandler');
set_error_handler("errorHandler");
ob_start("fatalErrorHandler");
if ($debug) {
	logMessage(array(
		"eventTime" => microtime2isostring(microtime(true)),
		"language" => "php",
		"type" => E_NOTICE,
		"message" => "requestFile=".$_SERVER['SCRIPT_NAME'],
		"file" => __FILE__,
		"line" => __LINE__
	));
}
function shutdownHandler() {
    $error = error_get_last();
    errorHandler($error['type'],$error['message'],$error['file'],$error['line']);
}
function fatalErrorHandler($buffer) {
	if (preg_match("/(error<\/b>:)(.+)(<br)/", $buffer, $regs) ) {
		$err = preg_replace("/<.*?>/","",$regs[2]);
		trigger_error($err);
		return "ERROR CAUGHT check log file";
	}
	return $buffer;
}
function errorHandler($errno, $errstr, $errfile, $errline)
{
	$eventTime = microtime(true);
	global $logfile, $requestTime;
	
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }
	
	$type = '';
    switch ($errno) {
		case E_ERROR: $type = 'E_ERROR'; break;
		case E_WARNING: $type = 'E_WARNING'; break;
		case E_PARSE: $type = 'E_PARSE'; break;
		case E_NOTICE: $type = 'E_NOTICE'; break;
		case E_CORE_ERROR: $type = 'E_CORE_ERROR'; break;
		case E_CORE_WARNING: $type = 'E_CORE_WARNING'; break;
		case E_COMPILE_ERROR: $type = 'E_COMPILE_ERROR'; break;
		case E_COMPILE_WARNING: $type = 'E_COMPILE_WARNING'; break;
		case E_USER_ERROR: $type = 'E_USER_ERROR'; break;
		case E_USER_WARNING: $type = 'E_USER_WARNING'; break;
		case E_USER_NOTICE: $type = 'E_USER_NOTICE'; break;
		case E_STRICT: $type = 'E_STRICT'; break;
		case E_RECOVERABLE_ERROR: $type = 'E_RECOVERABLE_ERROR'; break;
		case E_DEPRECATED: $type = 'E_DEPRECATED'; break;
		case E_USER_DEPRECATED: $type = 'E_USER_DEPRECATED'; break;
		case E_ALL: $type = 'E_ALL'; break;
		default: $type = "(unknown error type: $errno)";
	}
	
	logMessage(array(
		"eventTime" => microtime2isostring($eventTime),
		"requestTime" => microtime2isostring($requestTime),
		"logTime" => microtime2isostring(microtime(true)),
		"language" => "php",
		"type" => $type,
		"message" => preg_replace("/\r?\n/", "<br/>", $errstr),
		"file" => $errfile,
		"line" => $errline
	));
	
	if ($errno !== E_WARNING and $errno !== E_NOTICE and
		$errno !== E_USER_WARNING and $errno !== E_USER_NOTICE and
		$errno !== E_DEPRECATED and $errno !== E_USER_DEPRECATED and
		$errno !== E_STRICT and $errno !== E_RECOVERABLE_ERROR and
		$errno !== E_CORE_WARNING and $errno !== E_COMPILE_WARNING and
		$errno !== E_PARSE and $errno !== E_ALL)
		die("$type: $errstr (at $errfile:$errline)");
	
    /* Don't execute PHP internal error handler */
    return true;
}
function logMessage($log) {
	global $logfile, $requestTime;
	
	if (!isset($log['eventTime'])) $log['eventTime'] = microtime2isostring(microtime(true));
	$log['requestTime'] = microtime2isostring($requestTime);
	$log['logTime'] = microtime2isostring(microtime(true));
	if (!isset($log['language'])) $log['language'] = utf8_encode('unknown');
	if (!isset($log['type'])) $log['type'] = utf8_encode('UNKNOWN');
	if (!isset($log['message'])) $log['message'] = utf8_encode('');
	else if (is_string($log['message'])) $log['message'] = utf8_encode(preg_replace("/\r?\n/", "<br/>", $log['message']));
	if (!isset($log['file'])) $log['file'] = utf8_encode(__FILE__);
	if (!isset($log['line'])) $log['line'] = __LINE__;
	
	$log_string = json_encode($log);
	$json_err = "";
	$json_errstr = "";
	switch (json_last_error()) {
		case JSON_ERROR_DEPTH:			$json_err = 'JSON_ERROR_DEPTH'; $json_errstr = 'json_encode - The maximum stack depth has been exceeded'; break;
		case JSON_ERROR_CTRL_CHAR:		$json_err = 'JSON_ERROR_CTRL_CHAR'; $json_errstr = 'json_encode - Control character error, possibly incorrectly encoded'; break;
		case JSON_ERROR_STATE_MISMATCH:	$json_err = 'JSON_ERROR_STATE_MISMATCH'; $json_errstr = 'json_encode - Invalid or malformed JSON'; break;
		case JSON_ERROR_SYNTAX:			$json_err = 'JSON_ERROR_SYNTAX'; $json_errstr = 'json_encode - Syntax error'; break;
	}
	if (!empty($json_errstr)) {
		// log JSON error as JSON manually
		$fd = fopen(fix_directory_separators($logfile), "a");
		fwrite($fd, '['.
			'"eventTime":"'.microtime2isostring(microtime(true)).'",'.
			'"requestTime":"'.microtime2isostring($requestTime).'",'.
			'"logTime":"'.microtime2isostring(microtime(true)).'",'.
			'"language":"php",'.
			'"type":"'.$json_err.'",'.
			'"message":"'.preg_replace("/\r?\n/", "<br/>", $json_errstr).'",'.
			'"file":"'.__FILE__.'",'.
			'"line":"'.__LINE__.'"'.
		']'."\n");
		fclose($fd);
		
		// try to manually construct the desired JSON output
		$log_string = utf8_encode('['.
			'"eventTime": "'.microtime2isostring($eventTime).'",'.
			'"requestTime": "'.microtime2isostring($requestTime).'",'.
			'"logTime": "'.microtime2isostring(microtime(true)).'",'.
			'"language": "php",'.
			'"type": "'.$type.'",'.
			'"message": "'.preg_replace("/\r?\n/", "<br/>", strval($log['message'])).'",'.
			'"file": "'.$errfile.'",'.
			'"line": "'.$errline.'"'.
		']');
	}
	
	$log_string = preg_replace('/\r?\n/', '', $log_string);
	$fd = fopen(fix_directory_separators($logfile), "a");
	fwrite($fd, $log_string."\n");
	fclose($fd);
}
?>
