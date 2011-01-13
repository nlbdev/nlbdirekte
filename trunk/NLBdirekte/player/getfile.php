<?php
/*
 *	getfile.php?ticket=...&file=...
 *	Jostein Austvik Jacobsen, NLB, 2010
 */

include('common.inc.php');

# relative paths to general DMZ and profile storage
#$shared = '../books';
#$profiles = '../profiles';

# decode ticket here
#list($user, $book) = explode('_',str_replace(array('/','\\'),'',$_REQUEST['ticket']));
list($user, $book) = decodeTicket($_REQUEST['ticket']);
$file = $_REQUEST['file'];
if ($debug) trigger_error("requested file $file");

// Not valid request?
/*if (!(valid request)) {
  return "you are not logged in";
}*/

// Book not ready for playback?
if (!file_exists("$profiles/$user/books/$book/metadata.json")
	or !file_exists("$profiles/$user/books/$book/pagelist.json")
	or !file_exists("$profiles/$user/books/$book/smil.json")
	or !file_exists("$profiles/$user/books/$book/toc.json")) {
  // Is book not being prepared?
  //if (!(book being prepared)) {
  header("Content-Type: text/plain");
  if ($debug) trigger_error("book with bookId $book is not ready");
  echo "book not ready";
  exit;
}

// Does file exist in profile area?
else if (file_exists("$profiles/$user/books/$book/$file")) {
  if ($debug) trigger_error("returning file from profile storage");
  $fullFile = "$profiles/$user/books/$book/$file";
  
  $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $fullFile);
  $size = filesize($fullFile);
  $name = basename($fullFile);
  
  header("Content-Type: $mime");
  header("Content-Description: File Transfer");
  header("Content-Length: $size");
  header("Content-Disposition: attachment; filename=$name");
  readfile($fullFile);//"$profiles/$user/books/$book/$file");
}

// Does file exist in general DMZ area?
else if (file_exists("$shared/$book/$file")) {
  if ($debug) trigger_error("returning file from shared storage");
  $fullFile = "$shared/$book/$file";
  
  $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $fullFile);
  $size = filesize($fullFile);
  $name = basename($fullFile);
  
  header("Content-Type: $mime");
  header("Content-Description: File Transfer");
  header("Content-Length: $size");
  header("Content-Disposition: attachment; filename=$name");
  readfile($fullFile);
}

else {
  if ($debug) trigger_error("file does not exist, neither in profile nor in shared storage");
  echo "file does not exist";
}

?>
