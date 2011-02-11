<?php

# version of NLBdirekte
$version = '0.14';

include('config/config.inc.php'); // import users config-file

# ---- default configuration variables ----

# relative paths to general DMZ and profile storage
if (!isset($shared)) $shared = getcwd().'/../books';
if (!isset($profiles)) $profiles = getcwd().'/../profiles';

# other logfiles go in this directory
if (!isset($logdir)) $logdir = getcwd().'/logs';

# all PHP-errors, warnings and notices are appended to this file
if (!isset($logfile)) $logfile = $logdir.'/log.txt';

# in case Calabash is not in PATH, the absolute path can
# be given in $calabashExec
if (!isset($calabashExec)) $calabashExec = "calabash";

# debugging
if (!isset($debug)) $debug = false;

# ---- end of default configuration variables ----

# should make it easy to distinguish log entries in the log when they get intertwined
$sessionUID = microtime(true);
$sessionUID = ($sessionUID%60) + ($sessionUID-floor($sessionUID));

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

# debugging (http://php.net/manual/en/function.set-error-handler.php)
error_reporting(E_ALL);
register_shutdown_function('shutdownHandler');
set_error_handler("errorHandler");
ob_start("fatalErrorHandler");
if ($debug) {
	$fd = fopen(fix_directory_separators($logfile), "a");
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
	global $logfile, $sessionUID;
	
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
	
	$fd = fopen(fix_directory_separators($logfile), "a");
	$str = "[" . date("Y-m-d H:i:s", mktime()) . "] [" . $sessionUID . "] [$type]: $errstr (at $errfile:$errline)";
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
