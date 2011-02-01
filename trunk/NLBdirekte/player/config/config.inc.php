<?php
/*
 *	Configuration file for server-side of NLBdirekte.
 */

# relative paths to general DMZ and profile storage
$shared = getcwd().'/../books';
$profiles = getcwd().'/../profiles';

# other logfiles go in this directory
$logdir = getcwd().'/logs';

# all PHP-errors, warnings and notices are appended to this file
$logfile = $logdir.'/log.txt';

# If Calabash is not in PATH, then the full path can be specified here
# Note that spaces in the path probably won't work.
$calabashExec = "calabash"; // full path example: "C:\\xmlcalabash-0.9.29\\calabash.bat"

# debugging
$debug = true;

?>