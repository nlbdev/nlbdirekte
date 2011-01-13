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

// So we don't have to run this twice...
$userHasRunningProcess = isProcessing($user);

// Book exists?
if (!$book or !file_exists(fix_directory_separators("$shared/$book"))) {
	global $debug;
	if ($debug) trigger_error("book with bookId $book does not exist in the location ".fix_directory_separators("$shared/$book"));
	echo '{"ready":"0", "state":"book does not exist"}';
}

// Book not being prepared or not ready for playback at all?
else if (!$userHasRunningProcess and (
		   !file_exists(fix_directory_separators("$profiles/$user/books/$book/metadata.json"))
		or !file_exists(fix_directory_separators("$profiles/$user/books/$book/pagelist.json"))
		or !file_exists(fix_directory_separators("$profiles/$user/books/$book/smil.json"))
		or !file_exists(fix_directory_separators("$profiles/$user/books/$book/toc.json"))
								  ) ) {
	if ($debug) trigger_error("preparing book $book for user $user");
	chdir("prepare");
	$pid = execCalabashInBackground('prepare.xpl'.
									' shared-book="'.path_as_url("$shared/$book").'"'.
									' personal-book="'.path_as_url("$profiles/$user/books/$book").'"');
	if (!is_dir("$profiles/$user"))
		mkdir("$profiles/$user");
	$processesFilename = str_replace("\\","/","$profiles/$user/processes.csv");
	$processesFile = fopen($processesFilename, "ab");
	if ($debug) trigger_error("appending calabash process with PID '$pid' to the processes.csv-file belonging to the user '$user'.");
	fputcsv($processesFile, array(time(), $pid));
	fclose($processesFile);
	echo '{"ready":"0", "state":"a book is being prepared"}';
}

// Book being prepared?
else if ($userHasRunningProcess) {
	if ($debug) trigger_error("a book is already being prepared; will not start a new process.");
	echo '{"ready":"0", "state":"a book is being prepared"}';
}

// Book is ready
else {
	trigger_error("book $book is ready for user $user");
	echo '{"ready":"1", "state":"book is ready for playback"}';
}

// Based on http://www.php.net/manual/en/function.exec.php#86329
// Returns the PID of the newly created Calabash process
function execCalabashInBackground($args, $logfile = NULL) {
	global $debug;
	global $logdir;
	if (!isset($logfile)) {
		$logfile = fix_directory_separators($logdir.'/calabash-'.date('Ymd_His').'.'.((microtime(true)*1000000)%1000000).'.txt');
	}
	if (substr(php_uname(), 0, 7) == "Windows"){
		$before = array();
		exec("tasklist /V /FO CSV", $before);
		if ($debug) {
			trigger_error("forking Windows process: 'start /B calabash $args 1>$logfile 2>&1'");
			pclose(popen("start /B calabash $args 1>$logfile 2>&1","rb"));
			//pclose(popen("start /B $cmd 1>$logfile 2>&1", "rb"));
		} else {
			pclose(popen("start /B calabash $args", "rb"));
		}
		$after = array();
		exec("tasklist /V /FO CSV", $after);
		$pid = -1;
		foreach ($after as $procAfter) {
			list($imageNameAfter, $pidAfter, $sessionNameAfter, $sessionNumberAfter,
				 $memUsageAfter, $statusAfter, $userNameAfter, $cpuTimeAfter, $windowTitleAfter) = str_getcsv($procAfter);
			if (strtolower($imageNameAfter) !== 'java.exe')
				continue;

			$isNew = true;
			foreach ($before as $procBefore) {
				list($imageNameBefore, $pidBefore, $sessionNameBefore, $sessionNumberBefore,
					 $memUsageBefore, $statusBefore, $userNameBefore, $cpuTimeBefore, $windowTitleBefore) = str_getcsv($procBefore);
				if ($pidAfter === $pidBefore) {
					// This is not a new java-process
					$isNew = false;
					break;
				}
			}
			if ($isNew) {
				if ($pid === -1) {
					if ($debug) trigger_error("execInBackground(): Found the newly started Java-process '$imageNameAfter' with PID = $pidAfter");
					$pid = $pidAfter;
				}
				else {
					if ($debug) trigger_error("execInBackground(): Multiple new Java-processes found! (found $imageNameAfter with PID = $pidAfter)");
					$pid = -2;
				}
			}
		}
		if ($debug) {
			switch ($pid) {
			case -2: trigger_error("execInBackground(): Warning: Unable to determine the correct PID. (Multiple new processes found)"); break;
			case -1: trigger_error("execInBackground(): Warning: Unable to determine the correct PID. (No new processes found)"); break;
			case 0: trigger_error("execInBackground(): Warning: Unable to determine the correct PID. (System Idle Process (PID=0) identified as the process)"); break;
			default:
				if ($pid > 0) trigger_error("execInBackground(): Successfully identified the PID of the newly started Calabash process: $pid");
				else trigger_error("execInBackground(): Error: Unknown error code: $pid");
			}
		}
		return $pid;
	}
	else {
		if ($debug) {
			trigger_error("forking Linux (or MacOS?) process: 'calabash $args 1>$logfile 2>&1 &'");
			exec("calabash $args >$logfile 2>&1 &");
		} else {
			exec("calabash $args >/dev/null &");
		}
	}
}

function processIsRunning($pid) {
	global $debug;
	if (!isset($pid)) {
		if ($debug) trigger_error("processIsRunning(): Missing PID-argument.");
		return false;
	}
	if (substr(php_uname(), 0, 7) == "Windows") {
		if ($debug) trigger_error("processIsRunning(): Fetching list of running Windows processes: 'tasklist /V /FO CSV'");
		$processes = array();
		exec("tasklist /V /FO CSV", $processes);
		foreach ($processes as $process) {
			// Example line: "python.exe","2072","Console","1","6ÿ540 K","Running","NLB\jostein","0:00:00","123456"
			list($imageName, $processPID, $sessionName, $sessionNumber, $memUsage, $status, $userName, $cpuTime, $windowTitle) = str_getcsv($process);
			if ($processPID === $pid) {
				if ($debug) trigger_error("processIsRunning(): ".$imageName." has PID '".$processPID."' which is the PID that we're looking for. Good!");
				return true;
			}
		}
		return false; // process not found
	}
	else {
		trigger_error("TODO: processIsRunning(\$processName) not yet implemented for Linux; returning true.");
		return true;
	}
}

function isProcessing($user) {
	global $debug;
	global $profiles;
	if (!isset($user)) {
		if ($debug) trigger_error("isProcessing(): Missing user-argument.");
		return true; // note that it returns true (it's safer that way)
	}
	$processesFilename = str_replace("\\","/","$profiles/$user/processes.csv");
	$stillRunning = array();
	if (file_exists($processesFilename)) {
		$processesFile = fopen($processesFilename, "rb");
		while (($csvLine = fgetcsv($processesFile, 1000)) !== false) {
			$time = $csvLine[0];
			$pid = $csvLine[1];
			// Processes older than six hours are ignored, since PIDs can be reused.
			// Six hours is chosen as something greater than the assumed time it
			// takes to process the biggest books.
			if ($time < time() - 60*60*6) {
				if ($debug) trigger_error("isProcessing(): There's over six hours since user '$user' was running the process with PID '$pid'; ignoring it.");
				continue;
			}
			if (processIsRunning($pid)) {
				if ($debug) trigger_error("isProcessing(): Found a running process for user '$user' with PID = '$pid'.");
				$stillRunning["$time"] = "$pid";
			}
			else if ($debug) trigger_error("isProcessing(): Found a process that was not running for user '$user' with PID = '$pid'.");
		}
		fclose($processesFile);
	}
	$processesFile = fopen($processesFilename, "wb");
	fwrite($processesFile,'');
	foreach ($stillRunning as $time => $pid) {
		if ($debug) trigger_error("isProcessing(): process with PID '$pid' is still running for user '$user'; saving it back to the processing.csv-file.");
		fputcsv($processesFile, array($time, $pid));
	}
	fclose($processesFile);
	if (count($stillRunning) === 0) {
		if ($debug) trigger_error("isProcessing(): No process is running.");
		return false;
	} else {
		if ($debug) trigger_error("isProcessing(): ".count($stillRunning)." process".((count($stillRunning)<=1)?"":"es")." are running.");
		return true;
	}
}

?>
