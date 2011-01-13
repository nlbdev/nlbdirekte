<?php 
/*
 *	isprepared.php?ticket=...
 *	Jostein Austvik Jacobsen, NLB, 2010
 */
include('common.inc.php');

header('Content-Type: application/json; charset=utf-8');

# relative paths to general DMZ and profile storage
#$shared = '../books';
#$profiles = '../profiles';

# decode ticket here
#list($user, $book) = explode('_',str_replace(array('/','\\'),'',$_REQUEST['ticket']));
list($user, $book) = decodeTicket($_REQUEST['ticket']);
$user = 'jostein';
$book = '613757';

// Not valid request?
/*if (!(valid request)) {
	return "you are not logged in";
}*/

// Book exists?
if (!$book or !file_exists("$shared/$book")) {
	global $debug;
	if ($debug) trigger_error("book with bookId $book does not exist in the location $shared/$book");
	echo '{"ready":"0", "state":"book does not exist"}';
}

// Book not ready for playback?
else if (!file_exists("$profiles/$user/books/$book/metadata.json")
	or !file_exists("$profiles/$user/books/$book/pagelist.json")
	or !file_exists("$profiles/$user/books/$book/smil.json")
	or !file_exists("$profiles/$user/books/$book/toc.json")) {
		// Is book not being prepared?
		//if (!(book preparation started)) { <-- TODO (how about a database table with running processes?)
		if ($debug) trigger_error("preparing book $book for user $user");
		chdir("prepare");
		execInBackground("calabash prepare.xpl shared-book=../$shared/$book personal-book=../$profiles/$user/books/$book",
						 $logdir.DIRECTORY_SEPARATOR.'calabash-'.date('Ymd_His').'.'.((microtime(true)*1000000)%1000000).'.txt');
		//}
	echo '{"ready":"0", "state":"book is being prepared"}';
}

// Book is ready
else {
	trigger_error("book $book is ready for user $user");
	echo '{"ready":"1", "state":"book is ready for playback"}';
}

// http://www.php.net/manual/en/function.exec.php#86329
function execInBackground($cmd, $logfile) {
	global $debug;
	global $logdir;
	if (!isset($logfile))
		$logfile = $logdir.DIRECTORY_SEPARATOR.'log-'.date('Ymd_His').'.'.((microtime(true)*1000000)%1000000).'.txt';
	if (substr(php_uname(), 0, 7) == "Windows"){ 
		if ($debug) trigger_error("forking Windows process: 'start /B $cmd 1>$logfile 2>&1'");
		pclose(popen("start /B $cmd 1>$logfile 2>&1", "r"));	
	} 
	else { 
		if ($debug) trigger_error("forking Linux (or MacOS?) process: '$cmd 1>$logfile 2>&1 &'");
		exec("$cmd >$logfile 2>&1 &");
	} 
}

?>
