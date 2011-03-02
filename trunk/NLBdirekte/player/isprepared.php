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

# if launchTime is set, use that to put log entries in its own log
if (isset($_REQUEST['launchTime']))
	$logfile = microtimeAndUsername2logfile($_REQUEST['launchTime'],$user);

// Make sure that the patron has a profile directory
if (!is_dir("$profiles/$user"))
	mkdir("$profiles/$user");

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
									' personal-book="'.path_as_url("$profiles/$user/books/$book").'"',
									'catalog'.DIRECTORY_SEPARATOR.'prepare-catalog.xml');
	if ($pid > 0) {
		$processesFilename = str_replace("\\","/","$profiles/$user/processes.csv");
		$processesFile = fopen($processesFilename, "ab");
		if ($debug) trigger_error("appending calabash process with PID '$pid' to the processes.csv-file belonging to the user '$user'.");
		fputcsv($processesFile, array(time(), $pid));
		fclose($processesFile);
	}
	else {
		if ($debug) trigger_error("calabash process PID not determined. will be unable to determine whether it's running or not at next run.");
	}
	$progress = array(
		"ready" => false,
		"state" => "a book is being prepared",
		"progress" => 0,
		"startedTime" => time(),
		"estimatedRemainingTime" => 60
	);
	echo json_encode($progress);
	#echo '{"ready":"0", "state":"a book is being prepared", "progress":"0", "startedTime":"'+time()+'", "estimatedRemainingTime":"60"}';
}

// Book being prepared?
else if ($userHasRunningProcess) {
	if ($debug) trigger_error("a book is already being prepared; will not start a new process.");
	$progress = getProgress($user);
	$progress['ready'] = false;
	$progress['state'] = "a book is being prepared";
	echo json_encode($progress);
	#echo '{"ready":"0", "state":"a book is being prepared", "progress":"'+$progress['progress']+'", "startedTime":"'+$progress['startedTime']+'", "estimatedRemainingTime":"'+$progress['estimatedRemainingTime']+'"}';
}

// Book is ready
else {
	trigger_error("book $book is ready for user $user");
	$progress = array(
		"ready" => true,
		"state" => "a book is ready for playback"
	);
	echo json_encode($progress);
	#echo '{"ready":"1", "state":"book is ready for playback"}';
}

// Based on http://www.php.net/manual/en/function.exec.php#86329
// Returns the PID of the newly created Calabash process
function execCalabashInBackground($args, $catalog = NULL) {
	global $debug;
	global $logdir;
	global $calabashExec;
	$logfile = fix_directory_separators($logdir.'/calabash-'.date('Ymd_His').'.'.((microtime(true)*1000000)%1000000).'.txt');
	$pythonLogfile = fix_directory_separators($logdir.'/python-'.date('Ymd_His').'.'.((microtime(true)*1000000)%1000000).'.txt');
	$pythonLogArg = "python-log=\"$pythonLogfile\"";
	trigger_error("calabashlog=$logfile");
	trigger_error("pythonlog=$pythonLogfile");
	$before = array();
	$after = array();
	
	// start process
	$isWindows = (substr(php_uname(), 0, 7) == "Windows")?true:false;
	$cmd = "calabash -E org.apache.xml.resolver.tools.CatalogResolver -U org.apache.xml.resolver.tools.CatalogResolver";
	if ($isWindows){
		$cmd = "$calabashExec -E org.apache.xml.resolver.tools.CatalogResolver -U org.apache.xml.resolver.tools.CatalogResolver";
		$catalog = empty($catalog)?"":"set _JAVA_OPTIONS=-Dcom.xmlcalabash.phonehome=false -Dxml.catalog.files=$catalog -Dxml.catalog.staticCatalog=1 -Dxml.catalog.verbosity=".($debug?10:0)." &&";
		exec("tasklist /V /FO CSV", $before);
		if ($debug) {
			trigger_error("forking Windows process: '$catalog start /B $cmd $args $pythonLogArg 1>$logfile 2>&1'");
			pclose(popen("$catalog start /B $cmd $args $pythonLogArg 1>$logfile 2>&1","rb"));
		} else {
			pclose(popen("$catalog start /B $cmd $args $pythonLogArg", "rb"));
		}
		exec("tasklist /V /FO CSV", $after);
	}
	else { // Linux
		$catalog = empty($catalog)?"":"export _JAVA_OPTIONS='-Dcom.xmlcalabash.phonehome=false -Dxml.catalog.files=$catalog -Dxml.catalog.staticCatalog=1 -Dxml.catalog.verbosity=".($debug?10:0)."' &&";
		exec("ps axo pid,args", $before);
		if ($debug) {
			trigger_error("forking Linux process: '$catalog $cmd $args $pythonLogArg 1>$logfile 2>&1 &'");
			exec("$catalog $cmd $args $pythonLogArg >$logfile 2>&1 &");
		} else {
			exec("$catalog $cmd $args $pythonLogArg >/dev/null &");
		}
		exec("ps axo pid,args", $after);
	}
	
	// determine PID
	$pid = -1;
	if (!$isWindows)
		$args = exec("echo $args $pythonLogArg"); // perform expansions like "file://..." to file://... etc.
	foreach ($after as $procAfter) {
		$nameAfter = '';
		$pidAfter = 0;
		if ($isWindows) {
			// image name, pid, session name, session number, memory usage, status, username, cpu time, window title
			$line = str_getcsv($procAfter);
			$nameAfter = $line[0];
			$pidAfter = $line[1];
			if (strtolower($nameAfter) !== 'java.exe')
				continue;
		}
		else { // Linux
			preg_match('/^\s*([0-9]*)\s*(.*)$/', $procAfter, $line);
			$nameAfter = $line[2];
			$pidAfter = $line[1];
			if (strpos($nameAfter, "$cmd $args $pythonLogArg") === false) // note the === to distinguish 0 from false!
				continue;
		}

		$isNew = true;
		foreach ($before as $procBefore) {
			$nameBefore = '';
			$pidBefore = -1;
			if ($isWindows) {
				// image name, pid, session name, session number, memory usage, status, username, cpu time, window title
				$line = str_getcsv($procBefore);
				$nameBefore = $line[0];
				$pidBefore = $line[1];
			}
			else { // Linux
				// pid args
				preg_match('/^\s*([0-9]*)\s*(.*)$/', $procBefore, $line);
				$nameBefore = $line[2];
				$pidBefore = $line[1];
			}
			if ($pidAfter === $pidBefore) {
				// This is not a new process
				$isNew = false;
				break;
			}
		}
		if ($isNew) {
			if ($pid === -1) {
				if ($debug) trigger_error("execInBackground(): Found the newly started ".($isWindows?"Java":"Calabash")."-process '$nameAfter' with PID = $pidAfter");
				$pid = $pidAfter;
			}
			else {
				if ($debug) trigger_error("execInBackground(): Multiple new ".($isWindows?"Java":"Calabash")."-processes found! (found $nameAfter with PID = $pidAfter)");
				$pid = -2;
			}
		}
	}
	if ($debug) {
		switch ($pid) {
		case -2: trigger_error("execInBackground(): Warning: Unable to determine the correct PID. (Multiple new processes found)"); break;
		case -1: trigger_error("execInBackground(): Warning: Unable to determine the correct PID. (No new processes found)"); break;
		case 0: trigger_error("execInBackground(): Warning: Unable to determine the correct PID. (PID=0 identified as the process)"); break;
		default:
			if ($pid > 0) trigger_error("execInBackground(): Successfully identified the PID of the newly started Calabash process: $pid");
			else trigger_error("execInBackground(): Error: Unknown error code: $pid");
		}
	}
	return $pid;
}

function processIsRunning($pid) {
	global $debug;
	if (!isset($pid)) {
		if ($debug) trigger_error("processIsRunning(): Missing PID-argument.");
		return false;
	}
	
	// get list of all processes and their PIDs
	$processes = array();
	if (substr(php_uname(), 0, 7) == "Windows") {
		if ($debug) trigger_error("processIsRunning(): Fetching list of running Windows processes: 'tasklist /V /FO CSV'");
		exec("tasklist /V /FO CSV", $processes);
	}
	else {
		if ($debug) trigger_error("processIsRunning(): Fetching list of running Linux processes: 'ps axo pid,args'");
		exec("ps axo pid,args", $processes);
	}
	
	// Inspect the list of processes and look for $pid among the PIDs
	foreach ($processes as $process) {
		$processName = '';
		$processPID = -1;
		if (substr(php_uname(), 0, 7) == "Windows") {
			// Example line: "python.exe","2072","Console","1","6540 K","Running","NLB\jostein","0:00:00","123456"
			$line = str_getcsv($process);
			$processName = $line[0];
			$processPID = $line[1];
		}
		else { // Linux
			preg_match('/^\s*([0-9]*)\s*(.*)$/', $process, $line);
			$processName = $line[2];
			$processPID = $line[1];
		}
		if ($processPID === $pid) {
			if ($debug) trigger_error("processIsRunning(): ".$processName." has PID '".$processPID."' which is the PID that we're looking for. Good!");
			return true;
		}
	}
	return false; // process not found
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

function getProgress($user) {
	global $debug;
	global $profiles;
	global $logfile;
	$progressLogs = array();
	$pythonLogs = array();
	if ($file = file(fix_directory_separators("$logfile"))) {
		foreach ($file as $logEntry) {
			$json = json_decode($logEntry, true);
			$json['requestTime'] = isostring2microtime($json['requestTime']);
			$json['logTime'] = isostring2microtime($json['logTime']);
			$json['eventTime'] = isostring2microtime($json['eventTime']);
			if (is_string($json['message']) and preg_match('/^pythonlog=(.*)$/',$json['message'],$matches)) {
				$pythonLogs[$matches[1]] = $json['requestTime'];
			}
			
		}
	}
	if (count($pythonLogs)==0) return array("progress"=>0, "startedTime"=>time(), "estimatedRemainingTime"=>60);
	foreach ($pythonLogs as $pythonlog => $requestTime) {
		if ($file = file(fix_directory_separators("$pythonlog"))) {
			foreach ($file as $logEntry) {
				$json = json_decode($logEntry, true);
				if ($json['type'] == 'PROGRESS') {
					$json['requestTime'] = $requestTime;
					$json['logTime'] = isostring2microtime($json['logTime']);
					$json['eventTime'] = isostring2microtime($json['eventTime']);
					$progressLogs[] = $json;
				}
			}
		}
	}
	if (count($progressLogs)==0) return array("progress"=>0, "startedTime"=>time(), "estimatedRemainingTime"=>60);
	// sort logs by requestTime, then logTime
	function logCmp($a, $b) {
		if ($a['requestTime'] == $b['requestTime']) {
			if ($a['logTime'] == $b['logTime']) {
				if ($a['eventTime'] == $b['eventTime']) {
					return 0;
				} else {
					return ($a['eventTime'] < $b['eventTime']) ? -1 : 1;
				}
			} else {
				return ($a['logTime'] < $b['logTime']) ? -1 : 1;
			}
		}
		return ($a['requestTime'] < $b['requestTime']) ? -1 : 1;
	}
	usort($progressLogs, "logCmp");
	if (preg_match('/^.*:(.*)%$/', $progressLogs[count($progressLogs)-1]['message'], $matches)) {
		$progress = array(
			"progress" => $matches[1],
			"startedTime" => $progressLogs[count($progressLogs)-1]['requestTime'],
			"estimatedRemainingTime" => ($progressLogs[count($progressLogs)-1]['logTime']-$progressLogs[count($progressLogs)-1]['requestTime'])*(100./$matches[1]-1.)
		);
		if ($debug) trigger_error(json_encode($progress));
		return $progress;
	} else {
		trigger_error("Unable to parse progress: ".$progressLogs[count($progressLogs)-1]['message']);
		return array("progress"=>0, "startedTime"=>time(), "estimatedRemainingTime"=>60);
	}
}
?>
