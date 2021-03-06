<?php
/*
 *	Configuration file for server-side of NLBdirekte.
 */

# bookmarks
$bookmarks_enabled = true;
$bookmarks_db_username = 'root';
$bookmarks_db_password = '';
$bookmarks_db_hostname = 'localhost';
$bookmarks_db_database = 'bookmarks';

# relative paths to general DMZ and profile storage
$shared = getcwd().'/../../../../../../media/500GB/minishare';
$profiles = getcwd().'/../profiles';

# other logfiles go in this directory
$logdir = getcwd().'/logs';

# all PHP-errors, warnings and notices are appended to this file
$logfile = $logdir.'/log_'.date("Y-m-d").'.log';

# If Calabash is not in PATH, then the full path can be specified here
# Note that spaces in the path probably won't work.
$calabashExec = "/home/jostein/xmlcalabash-0.9.44.94/calabash"; // windows full path example: "C:\\xmlcalabash-0.9.29\\calabash.bat"

# debugging
$debug = true;
#$debug = isset($debug)?$debug:true;

# default authorization function
function authorize($user, $book, $session, $redirect) {
	if (!empty($user) and !empty($book)) {
		return true;
	} else {
		if ($redirect)
			header("Location: http://websok.nlb.no/cgi-bin/mappami");
		else
			header('HTTP/1.0 401 Unauthorized');
		exit;
	}
}

?>
