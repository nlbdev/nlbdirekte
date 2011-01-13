<?php 
/*
 *	isprepared.php?ticket=...
 *	Jostein Austvik Jacobsen, NLB, 2010
 */
include('common.inc.php');

header('Content-Type: application/json; charset=utf-8');

# decode ticket here
list($user, $book) = decodeTicket($_REQUEST['ticket']);

// Not valid request?
/*if (!(valid request)) {
	return "you are not logged in";
}*/

// Book exists?
if (!$book or !file_exists(realpath("$shared/$book"))) {
	global $debug;
	if ($debug) trigger_error("book with bookId $book does not exist in the location ".realpath("$shared/$book"));
	echo '{"ready":"0", "state":"book does not exist"}';
}

// Book not ready for playback?
else if (!file_exists(realpath("$profiles/$user/books/$book/metadata.json"))
	or !file_exists(realpath("$profiles/$user/books/$book/pagelist.json"))
	or !file_exists(realpath("$profiles/$user/books/$book/smil.json"))
	or !file_exists(realpath("$profiles/$user/books/$book/toc.json"))) {
		// Is book not being prepared?
		//if (!(book preparation started)) { <-- TODO (how about a database table with running processes?)
		if ($debug) trigger_error("preparing book $book for user $user");
		trigger_error('#### '.$logfile.'/calabash-'.date('Ymd_His').'.'.((microtime(true)*1000000)%1000000).'.txt');
		chdir("prepare");
		execInBackground('calabash prepare.xpl'.
						 ' shared-book="'.path_as_url("$shared/$book").'"'.
						 ' personal-book="'.path_as_url("$profiles/$user/books/$book").'"',
						 fix_directory_separators($logdir.'/calabash-'.date('Ymd_His').'.'.((microtime(true)*1000000)%1000000).'.txt'));
		/*execInBackground('calabash prepare.xpl'.
						 ' shared-book="file:/'.str_replace('\\','/',$shared.$dir.$book).'"'.
						 ' personal-book="file:/'.str_replace('\\','/',$profiles.$dir.$user.$dir.'books'.$dir.$book).'"',
						 $logdir.$dir.'calabash-'.date('Ymd_His').'.'.((microtime(true)*1000000)%1000000).'.txt');*/
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
	if (!isset($logfile)) {
		$logfile = realpath("$logdir/log-".date('Ymd_His').'.'.((microtime(true)*1000000)%1000000).'.txt');
		fix_directory_separators($logfile);
	}
	if (substr(php_uname(), 0, 7) == "Windows"){
		if ($debug) {
			trigger_error("forking Windows process: 'start /B $cmd 1>$logfile 2>&1'");
			pclose(popen("start /B $cmd 1>$logfile 2>&1", "r"));
		} else {
			pclose(popen("start /B $cmd", "r"));
		}
	} 
	else {
		if ($debug) {
			trigger_error("forking Linux (or MacOS?) process: '$cmd 1>$logfile 2>&1 &'");
			exec("$cmd >$logfile 2>&1 &");
		} else {
			exec("$cmd >/dev/null &");
		}
	}
}

?>
