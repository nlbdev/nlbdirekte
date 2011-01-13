<?php 
/*
 *	isprepared.php?ticket=...
 *	Jostein Austvik Jacobsen, NLB, 2010
 */

header('Content-Type: application/json; charset=utf-8');

# relative paths to general DMZ and profile storage
$shared = '../books';
$profiles = '../profiles';

# decode ticket here
list($user, $book) = explode('_',str_replace(array('/','\\'),'',$_REQUEST['ticket']));

// Not valid request?
/*if (!(valid request)) {
	return "you are not logged in";
}*/

// Book exists?
if (!$book or !file_exists("$shared/$book")) {
	echo '{"ready":"0", "state":"book does not exist"}';
}

// Book not ready for playback?
else if (!file_exists("$profiles/$user/books/$book/metadata.json")
	or !file_exists("$profiles/$user/books/$book/pagelist.json")
	or !file_exists("$profiles/$user/books/$book/smil.json")
	or !file_exists("$profiles/$user/books/$book/toc.json")) {
		// Is book not being prepared?
		//if (!(book preparation started)) { <-- TODO
		chdir("prepare");
		execInBackground("calabash prepare.xpl shared-book=../$shared/$book personal-book=../$profiles/$user/books/$book");
		//}
	echo '{"ready":"0", "state":"book is being prepared"}';
}

// Book is ready
else {
	echo '{"ready":"1", "state":"book is ready for playback"}';
}

// http://www.php.net/manual/en/function.exec.php#86329
function execInBackground($cmd) { 
	if (substr(php_uname(), 0, 7) == "Windows"){ 
		pclose(popen("start /B ". $cmd, "r"));	
	} 
	else { 
		exec($cmd . " > /dev/null &");	 
	} 
}

?>
