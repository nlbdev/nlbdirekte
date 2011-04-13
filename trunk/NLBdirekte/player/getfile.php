<?php
/*
 *	getfile.php?ticket=...&file=...
 *	Jostein Austvik Jacobsen, NLB, 2010
 */

include('common.inc.php');

list($user, $book) = decodeTicket($_REQUEST['ticket']);
authorize($user,$book,isset($_REQUEST['session'])?$_REQUEST['session']:'');

# if launchTime is set, use that to put log entries in its own log
if (isset($_REQUEST['launchTime']))
	$logfile = microtimeAndUsername2logfile($_REQUEST['launchTime'],$user);

$file = $_REQUEST['file'];
if ($debug) trigger_error("requested file $file");

// Book not ready for playback?
if (!file_exists(fix_directory_separators("$profiles/$user/books/$book/metadata.json"))
	or !file_exists(fix_directory_separators("$profiles/$user/books/$book/pagelist.json"))
	or !file_exists(fix_directory_separators("$profiles/$user/books/$book/smil.json"))
	or !file_exists(fix_directory_separators("$profiles/$user/books/$book/toc.json"))) {
  // Is book not being prepared?
  //if (!(book being prepared)) {
  header("Content-Type: text/plain");
  if ($debug) trigger_error("book with bookId $book is not ready");
  echo "book not ready";
  exit;
}

// Does file exist in profile area?
else if (file_exists(fix_directory_separators("$profiles/$user/books/$book/$file"))) {
  if ($debug) trigger_error("returning file from profile storage");
  $fullFile = fix_directory_separators("$profiles/$user/books/$book/$file");
  
  $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $fullFile);
  $size = filesize($fullFile);
  $name = basename($fullFile);
  
  header("Content-Type: $mime");
  header("Content-Description: File Transfer");
  header("Content-Length: $size");
  header("Content-Disposition: attachment; filename=$name");
  readfile($fullFile);
}

// Does file exist in general DMZ area?
else if (file_exists(fix_directory_separators("$shared/$book/$file"))) {
  if ($debug) trigger_error("returning file from shared storage");
  $fullFile = fix_directory_separators("$shared/$book/$file");
  
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
