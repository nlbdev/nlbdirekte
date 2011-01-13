<?php
# version of NLBdirekte
$version = 0.2;

# relative paths to general DMZ and profile storage
$shared = '../books';
$profiles = '../profiles';

# other logfiles go in this directory
$logdir = getcwd().DIRECTORY_SEPARATOR.'logs';

# all PHP-errors, warnings and notices are appended to this file
$logfile = $logdir.DIRECTORY_SEPARATOR.'log.txt';

# debugging
$debug = true;

# ---- end of configurable variables ----

# decoding tickets
# usage: list($userId, $bookId) = decodeTicket($ticket);
function decodeTicket($ticket) {
	global $debug;
    $ret = explode('_',str_replace(array('/','\\'),'',$ticket));
	if ($debug) trigger_error("ticket $ticket decoded as userId=".$ret[0].", bookId=".$ret[1]);
    return $ret;
}

# debugging (http://php.net/manual/en/function.set-error-handler.php)
error_reporting(E_ALL);
register_shutdown_function('shutdownHandler');
set_error_handler("errorHandler");
ob_start("fatalErrorHandler");
if ($debug) {
	$fd = fopen($logfile, "a");
	fwrite($fd, "\n");
	fclose($fd);
	trigger_error("---- ".$_SERVER['SCRIPT_NAME']." ----");
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
	global $logfile;
	
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
	
	$fd = fopen($logfile, "a");
	$str = "[" . date("Y-m-d H:i:s", mktime()) . "] " . "$type: $errstr (at $errfile:$errline)";
	fwrite($fd, $str . "\n");
	fclose($fd);
	
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
?>
