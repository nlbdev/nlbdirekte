<?php
/*
 *	isprepared.php?ticket=...
 *	Jostein Austvik Jacobsen, NLB, 2010
 */
include('common.inc.php');

if (isset($_REQUEST['callback']) and !empty($_REQUEST['callback']))
	header('Content-Type: application/javascript; charset=utf-8');
else
	header('Content-Type: application/json; charset=utf-8');

list($user, $book) = decodeTicket($_REQUEST['ticket']);
authorize($user,$book,isset($_REQUEST['session'])?$_REQUEST['session']:'',false);

// use launchTime to put log entries in its own log
$logfile = microtimeAndUsername2logfile(isset($_REQUEST['launchTime'])?$_REQUEST['launchTime']:0,$user);

// Make sure that the patron has a profile directory
if (!is_dir("$profiles/$user"))
	mkdir("$profiles/$user");

// So we don't have to run this twice...
$userHasRunningProcess = isProcessing($user, $book);

// Book exists?
if (!$book or !file_exists(fix_directory_separators("$shared/$book"))) {
	// does not check whether the whole book exists, just whether the folder exists!
	// (prepare-process will fail and start over if isprepared.php is called before the entire book is copied)
	global $debug;
	if ($debug) trigger_error("book with bookId $book does not exist in the location ".fix_directory_separators("$shared/$book"));
	$result = array(
		"ready" => false,
		"state" => "DOES_NOT_EXIST",
		"message" => "The book does not exist in storage."
	);
	echo json_or_jsonp(prepareStruct($result));
	trigger_error("JSON-PROGRESS:".json_encode($result));
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
		fputcsv($processesFile, array(time(), $pid, isset($_REQUEST['launchTime'])?$_REQUEST['launchTime']:0, $book));
		fclose($processesFile);
	}
	else {
		if ($debug) trigger_error("calabash process PID not determined. will be unable to determine whether it's running or not at next run.");
	}
	$progress = array(
		"ready" => false,
		"state" => "STARTED",
		"message" => "The book started preparing.",
		"progress" => 0,
		"startedTime" => time()
	);
	echo json_or_jsonp(prepareStruct($progress));
	trigger_error("JSON-PROGRESS:".json_encode($progress));
}

// Book being prepared?
else if ($userHasRunningProcess) {
	if ($debug) trigger_error("a book is already being prepared; will not start a new process.");
	$progress = getProgress($user, $book);
	$progress['ready'] = false;
	$progress['state'] = "PREPARING";
	$progress['message'] = "The book is being prepared.";
	echo json_or_jsonp(prepareStruct($progress));
	trigger_error("JSON-PROGRESS:".json_encode($progress));
}

// Book is ready
else {
	trigger_error("book $book is ready for user $user");
	$progress = array(
		"progress" => 100,
		"estimatedRemainingTime" => 0,
		"ready" => true,
		"state" => "READY",
		"message" => "The book is ready for playback."
	);
	echo json_or_jsonp(prepareStruct($progress));
	trigger_error("JSON-PROGRESS:".json_encode($progress));
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
			if (strpos($nameAfter, "$cmd $args") === false) { // note the === to distinguish 0 from false!
				continue;
			}
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
				trigger_error("execInBackground(): Warning: found multiple new ".($isWindows?"Java":"Calabash")."-processes; using the one with the highest PID! (found $nameAfter with PID = $pidAfter)");
				$pid = max($pid,$pidAfter);
			}
		}
	}
	if ($debug) {
		switch ($pid) {
		//case -2: trigger_error("execInBackground(): Warning: Unable to determine the correct PID. (Multiple new processes found)"); break;
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
	global $processIsRunning_processes;
	
	if (!isset($pid)) {
		if ($debug) trigger_error("processIsRunning(): Missing PID-argument.");
		return false;
	}
	
	// get list of all processes and their PIDs
	$processes = array();
	if (isset($processIsRunning_processes)) {
		$processes = $processIsRunning_processes;
	} else {
		if (substr(php_uname(), 0, 7) == "Windows") {
			if ($debug) trigger_error("processIsRunning(): Fetching list of running Windows processes: 'tasklist /V /FO CSV'");
			exec("tasklist /V /FO CSV", $processes);
		}
		else {
			if ($debug) trigger_error("processIsRunning(): Fetching list of running Linux processes: 'ps axo pid,args'");
			exec("ps axo pid,args", $processes);
		}
	}
	
	// Inspect the list of processes and look for $pid among the PIDs
	foreach ($processes as $process) {
		$processName = '';
		$processPID = -1;
		if (substr(php_uname(), 0, 7) == "Windows") {
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

function isProcessing($user, $book) {
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
			$launchTime = $csvLine[2];
			$bookNr = $csvLine[3];
			// Processes older than six hours are ignored, since PIDs can be reused.
			// Six hours is chosen as something that is definately greater than the
			// assumed time it takes to process the biggest books.
			if ($launchTime > 0 and $launchTime < time() - 60*60*6) {
				if ($debug) trigger_error("isProcessing(): There's over six hours since user '$user' was running the process with PID '$pid'; ignoring it.");
				continue;
			}
			if (processIsRunning($pid)) {
				if ($debug) trigger_error("isProcessing(): Found a running process for user '$user' with PID = '$pid'.");
				$stillRunning["$time"] = array("$pid","$launchTime","$bookNr");
			}
			else if ($debug) trigger_error("isProcessing(): Found a process that was not running for user '$user' with PID = '$pid'.");
		}
		fclose($processesFile);
	}
	$processesFile = fopen($processesFilename, "wb");
	fwrite($processesFile,'');
	$thisBookCount = 0;
	foreach ($stillRunning as $time => $procData) {
		if ($debug) trigger_error("isProcessing(): process with PID '".$procData[0]."' is still running for user '$user'; saving it back to the processing.csv-file.");
		fputcsv($processesFile, array($time, $procData[0], $procData[1], $procData[2]));
		if (!isset($book) or $procData[2] == $book or $procData[2] == 0)
			$thisBookCount++;
	}
	fclose($processesFile);
	if ($thisBookCount === 0) {
		if ($debug) trigger_error("isProcessing(): No process for book $book is running. ".count($stillRunning)." processes running in total.");
		return false;
	} else {
		if ($debug) trigger_error("isProcessing(): $thisBookCount out of a total of ".count($stillRunning)." running process".((count($stillRunning)<=1)?"":"es")." are processing the book $book.");
		return true;
	}
}

function getProgress($user, $book) {
	global $debug;
	global $profiles;
	global $logfile;
	$launchTime = isset($_REQUEST['launchTime'])?$_REQUEST['launchTime']:time();
	$logfiles = array();
	$processesFilename = str_replace("\\","/","$profiles/$user/processes.csv");
	if (file_exists($processesFilename)) {
		$processesFile = fopen($processesFilename, "rb");
		while (($csvLine = fgetcsv($processesFile, 10000)) !== false) {
			if ($csvLine[3] == $book) {
				$logfiles[] = microtimeAndUsername2logfile($csvLine[2],$user);
				if ($csvLine[2] < $launchTime)
					$launchTime = $csvLine[2];
			}
		}
		fclose($processesFile);
	}
	if (count($logfiles)==0) {
		if ($debug) {
			$debugString = "getProgress($user,$book): No logfiles found. $processesFilename:\n";
			if (!file_exists($processesFilename)) {
				$debugString .= "does not exist";
			} else {
				$processesFile = fopen($processesFilename, "rb");
				$debugString .= fread($fh, filesize($processesFilename))."\n";
				fclose($processesFile);
			}
			trigger_error($debugString);
		}
		return array("progress"=>0);
	}
	$progressLogs = array();
	$pythonLogs = array();
	foreach ($logfiles as $logfilename) {
		if ($file = file(fix_directory_separators($logfilename))) {
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
	}
	if (count($pythonLogs)==0) {
		if ($debug)
			trigger_error("getProgress($user,$book): No python logs found in log(s) (".implode(' , ',$logfiles).")");
		return array("progress"=>0);
	}
	foreach ($pythonLogs as $pythonlog => $requestTime) {
		if (file_exists(fix_directory_separators("$pythonlog")) and $file = file(fix_directory_separators("$pythonlog"))) {
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
	if (count($progressLogs)==0) {
		if ($debug)
			trigger_error("getProgress($user,$book): No progress logs found in python log(s) (".implode(' , ',$pythonLogs).")\ndate(\"U\")=".date("U")." , \$requestTime=$requestTime");
		$ret = array("progress"=>min(10,(date("U")-$requestTime)/2.), "estimatedRemainingTime"=>-1, "timeSpent"=>date("U")-$requestTime);
		if ($ret['timeSpent'] > 0 and $ret['progress'] > 0.1)
			$ret['estimatedRemainingTime'] = $ret['timeSpent']/($ret['progress']/100.) - $ret['timeSpent'];
			if ($debug)
				trigger_error("getProgress($user,$book): Setting estimatedRemainingTime to $estimatedRemainingTime (timeSpent=".$ret['timeSpent']." , progress=".$ret['progress'].")");
		return $ret;
	}
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
	$logTimeOfFirstProgressAboveZero;
	$progressOfLast6090Progress; // "6090"="outside of the range [60,90>"
	$timeOfLast6090Progress;
	$progressLogCount = count($progressLogs);
	$startTime = floatval($progressLogs[$progressLogCount-1]['requestTime']);
	foreach ($progressLogs as $progressLog) {
		if ($progressLog['startTime'] != $progressLogs[$progressLogCount-1]['startTime'])
			continue;
		if (preg_match('/^.*:(.*)%$/', $progressLog['message'], $matches)) {
			if (floatval($matches[1]) >= 0.001) {
				$logTimeOfFirstProgressAboveZero = $progressLog['logTime'];
				break;
			}
		}
	}
	foreach (array_reverse($progressLogs) as $progressLog) {
		if ($progressLog['startTime'] != $progressLog[$progressLogCount-1]['startTime'])
			continue;
		if (preg_match('/^.*:(.*)%$/', $progressLog['message'], $matches)) {
			if (floatval($matches[1])/100. < 0.6 or floatval($matches[1])/100. >= 0.9) {
				$progressOfLast6090Progress = floatval($matches[1])/100.;
				$timeOfLast6090Progress = $progressLog['logTime'];
				break;
			}
		}
	}
	/*
	  x0: last progress not in range [60%,90%>
	  x1: estimated current progress from 0.0 to 1.0 (0%-100%)
	  t0: time in seconds corresponding to x0, counted since start time
	  t1: current time in seconds since start time
	  t2: estimated total time / end time in seconds counted since start time
	*/
	$x0 = min(0.9,max(0.6,$progressOfLast6090Progress));
	$t0 = max(0,$timeOfLast6090Progress - $startTime);
	$t1 = max(0,date("U") - $startTime);
	$t2 =  (
		($x0 < 0.01) ? -1 : (
		($x0 < 0.10) ? (0.5*$t0/$x0) : (
		($x0 < 0.95) ? (-3*$t0/log(1-$x0)) : (
			       (max(1,min(10,$t1/$t0)) + $t0)
		))));
	$x1 = min(1, $t1/$t2);
	if ($debug) {
		trigger_error("getProgress($user,$book): Estimating progress and remaining time using formula.\nprogressOfLast6090Progress=$progressOfLast6090Progress , timeOfLast6090Progress=$timeOfLast6090Progress , date(\"U\")=".date("U")." , x0=$x0 , t0=$t0 , t1=$t1 , t2=$t2 , x1=$x1");
	}
	return array("progress"=>min(99,$x1*90+10), "startedTime"=>floor($startTime), "estimatedRemainingTime"=>max(-1,$t2-$t1), "timeSpent"=>max(0,$t1));
}

function json_or_jsonp($structure) {
	if (isset($_REQUEST['callback']) and !empty($_REQUEST['callback']))
		return $_REQUEST['callback'].'('.json_encode($structure).')';
	else
		return json_encode($structure);
}

function prepareStruct($struct) {
	$fixedStruct = array(
		"ready" => (isset($struct['ready'])?$struct['ready']:false),
		"state" => (isset($struct['state'])?$struct['state']:""),
		"progress" => (isset($struct['progress'])?$struct['progress']:0),
		"startedTime" => (isset($struct['startedTime'])?$struct['startedTime']:time()),
		"estimatedRemainingTime" => (isset($struct['estimatedRemainingTime'])?$struct['estimatedRemainingTime']:-1),
		"message" => (isset($struct['message'])?$struct['message']:"")
	);
	$fixedStruct['timeSpent'] = (isset($struct['timeSpent'])?$struct['timeSpent']:(time()-$fixedStruct['startedTime']));
	return $fixedStruct;
}
?>
