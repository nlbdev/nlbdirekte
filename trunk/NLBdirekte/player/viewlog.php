<?php

// utf-8 reminder: 这是一份非常间单的说明书…

$debug = false;
include('common.inc.php');

$human = isset($_REQUEST['human']) || !isset($_REQUEST['robot']);

function xml($value) {
	$value = str_replace('"','&quot;',$value);
	$value = str_replace('&','&amp;',$value);
	$value = str_replace("'",'&apos;',$value);
	$value = str_replace('<','&lt;',$value);
	$value = str_replace('>','&gt;',$value);
	return $value;
}

function row($name, $value) {
	echo utf8_encode("\t<tr>\n\t\t<td>".xml($name)."</td>\n\t\t<td>".xml($value)."</td>\n\t</tr>\n");
}

function prettyTimeToDate($microtime) {
	$time = date("j.",floor($microtime));
	switch (date("n",floor($microtime))) {
	case 1: $time .= "januar"; break;
	case 2: $time .= "februar"; break;
	case 3: $time .= "mars"; break;
	case 4: $time .= "april"; break;
	case 5: $time .= "mai"; break;
	case 6: $time .= "juni"; break;
	case 7: $time .= "juli"; break;
	case 8: $time .= "august"; break;
	case 9: $time .= "september"; break;
	case 10: $time .= "oktober"; break;
	case 11: $time .= "november"; break;
	case 12: $time .= "desember"; break;
	default: $time .= utf8_encode("ukjent-måned");
	}
	$time .= date(" Y",(floor($microtime)+intval(date("Z",floor($microtime)))));
	return $time;
}
function prettyTimeToMinute($microtime) {
	$time = prettyTimeToDate($microtime);
	$time .= date(", G:i",(floor($microtime)+intval(date("Z",floor($microtime)))));
	return $time;
}
function prettyTimeToMillisecond($microtime) {
	$time = prettyTimeToMinute($microtime);
	$time .= date(":s",(floor($microtime)+intval(date("Z",floor($microtime)))));
	$ms = preg_replace('/^0.(...).*$/','$1',($microtime - floor($microtime)));
	if ($ms == 0)
		$time .= '.000';
	else
		$time .= ".$ms";
	return $time;
}

if (!$human) {
	header('Content-Type: text/xml; charset=utf-8');
	echo utf8_encode("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
	echo utf8_encode("<html xmlns=\"http://www.w3.org/1999/xhtml\">\n");
	echo utf8_encode("<head><title>Log for robots</title></head>\n");
	echo utf8_encode("<body>\n");
} else {
	header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html>
<head>
	<title>Logg for NLBdirekte</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta charset="utf-8" />
	<style type="text/css">
		header h1 {
			color: #333;
		}
		header table {
			background-color: #DDD;
		}
		header th {
			color: #555;
			font-style: italic;
		}
		#browser_info table {
			margin-left: auto;
			margin-right: auto;
			text-align: center;
		}
		#browser_info tr.general {
			font-size: 1.1em;
		}
		#browser_info tr.technical {
			font-size: 0.5em;
		}
		#browser_info table
		{
			border-color: #DDD;
			border-width: 0 0 1px 1px;
			border-style: solid;
		}
		#browser_info td
		{
			border-color: #DDD;
			border-width: 1px 1px 0 0;
			border-style: solid;
			padding: 4px;
			background-color: #EEE;
		}
		
		#log {
			font-family: "Courier New",
			Courier,
			monospace;
		}
		#log table {
			margin-left: auto;
			margin-right: auto;
			text-align: center;
		}
		#log tr.general {
			font-size: 1.1em;
		}
		#log tr.technical {
			font-size: 0.5em;
		}
		#log table
		{
			border-color: #DDD;
			/*border-width: 0 0 1px 1px;*/
			border-style: solid;
			width: 100%;
			text-align: left;
		}
		#log td
		{
			border-color: #DDD;
			/*border-width: 1px 1px 0 0;*/
			border-style: solid;
			/*padding: 4px;*/
			background-color: #EEE;
		}
		#log td.requestDivider {
			background-color: #CCC;
			font-size: 0.75em;
			font-style: italic;
			text-align: center;
			/*padding: 0;*/
		}
		#log table td {
			border-left:solid 5px transparent;
			border-right:solid 5px transparent;
		}
		#log table th {
			border-left:solid 5px transparent;
		}
		#log table td:first-child {
			border-left:0;
		}
		#log table td:last-child {
			border-right:0;
		}
		#progress {
			text-align: center;
		}
	</style>
</head>
<body style="font-family: helvetica, arial, sans-serif;">
<?php }

if (isset($_REQUEST['type'])) {
	switch ($_REQUEST['type']) {
		case 'session': echoSessionLog(); break;
		case 'day': echoDayLog(); break;
		case 'calabash': echoCalabashLog(); break;
		case 'python': echoPythonLog(); break;
		default: echo '<p><em>Unknown log type: '.utf8_encode($_REQUEST['type']).'.</em></p>';
	}
} else {
	echo '<p><em>Log type not defined.</em></p>';
}

function echoSessionLog() {
	global $logdir, $human;
	
	$logname = $_REQUEST['logname'];
	list($formatted_time, $userId) = explode('_',$logname);
	$formatted_time_split = explode('.',$logname);
	$date = $formatted_time_split[0];

	$pythonlogs = array();
	$calabashlogs = array();
	$bookId = 'unknown';
	$browser = array();
	
	$log = array(); // all relevant log entries
	$requestTimes = array();
	
	// load session log
	if ($logFile = file(fix_directory_separators("$logdir/log_$logname.log"))) {
		appendJsonLog($logFile, $log, $requestTimes, true, $pythonlogs, $calabashlogs, $bookId, $browser);
	}
	
	// load common log entries belonging to the session log entries (only checks the current day)
	if ($human and $logFile = file(fix_directory_separators("$logdir/log_$date.log"))) {
		appendJsonLog($logFile, $log, $requestTimes, false, $pythonlogs, $calabashlogs, $bookId, $browser);
	}
	
	// load python logs
	if ($human) foreach ($pythonlogs as $pythonlog => $requestTime) {
		$beforeCount = count($log);
		if ($logFile = file(fix_directory_separators("$pythonlog"))) {
			appendJsonLog($logFile, $log);
		}
		for ($i = $beforeCount; $i < count($log); $i++) {
			$log[$i]['requestTime'] = $requestTime;
		}
	}
	
	// load calabash logs
	if ($human) foreach ($calabashlogs as $calabashlog => $requestTime) {
		$beforeCount = count($log);
		if ($logFile = file(fix_directory_separators("$calabashlog"))) {
			appendCalabashLog($logFile, $log);
		}
		for ($i = $beforeCount; $i < count($log); $i++) {
			$log[$i]['requestTime'] = $requestTime;
		}
	}
	
	usort($log, "logCmp");
	
	if ($human) {
	?><header class="page-header">
		<h1>Logg for NLBdirekte</h1>
	</header>
	<hr/><?php
	}
	
	if (!$human) echo "<table>\n";
	echoSessionHeader($userId, $bookId, $log);
	if ($human) {
		echo '<hr/>';
		echo '<table><tr><td>';
	}
	echoBrowserInfo($browser);
	if ($human) echo '</td><td>';
	$progress = progressList($log);
	echoProgress($progress);
	if ($human) {
		echo '</td></tr></table>';
		echo '<hr/>';
	} else echo "</table>\n";
	if ($human) echoLog($log);
}

function echoDayLog() {
	global $logdir;
	
	$logname = "log_".$_REQUEST['logname'].".log";
	$log = array();
	
	// load log
	if ($logFile = file(fix_directory_separators("$logdir/$logname"))) {
		appendJsonLog($logFile, $log);
	}
	
	usort($log, "logCmp");
	
	?><header class="page-header">
		<h1>Logg for NLBdirekte</h1>
	</header>
	<hr/><?php
	echo '<hr/>';
	echoLog($log);
}

function echoCalabashLog() {
	global $logdir;
	
	$logname = $_REQUEST['logname'];
	$calabashlog = array();
	
	// load calabash log
	if ($logFile = file(fix_directory_separators("$logdir/$logname"))) {
		appendCalabashLog($logFile, $calabashlog);
	}
	
	usort($log, "logCmp");
	
	?><header class="page-header">
		<h1>Logg for NLBdirekte</h1>
	</header>
	<hr/><?php
	echo '<hr/>';
	echoLog($calabashlog);
}

function echoPythonLog() {
	global $logdir;
	
	$logname = $_REQUEST['logname'];
	$pythonlog = array();
	
	// load python log
	if ($logFile = file(fix_directory_separators("$logdir/$logname"))) {
		appendJsonLog($logFile, $pythonlog);
	}
	
	usort($log, "logCmp");
	
	?><header class="page-header">
		<h1>Logg for NLBdirekte</h1>
	</header>
	<hr/><?php
	echo '<hr/>';
	echoLog($pythonlog);
}

// logFile: filehandle
// log: array to append log entries to
// requestTimes: array to append unique requestTimes to
// newRequestTimes: if true; append all logs and new requestTimes.
//					if false; only append logs with matching requestTime.
// pythonlogs: references to python logs are appended here
// calabashlogs: references to calabash logs are appended here
// bookId: if set, the book id (if found) are assigned to this parameter
// browser: browserinfo are put into this array if set
function appendJsonLog($logFile, &$log, &$requestTimes, $newRequestTimes, &$pythonlogs, &$calabashlogs, &$bookId, &$browser) {
	foreach ($logFile as $logEntry) {
		if (empty($logEntry)) continue;
		$json = json_decode($logEntry, true);
		if (empty($json)) continue;
		$json['requestTime'] = empty($json['requestTime'])?0:isostring2microtime($json['requestTime']);
		$json['logTime'] = empty($json['logTime'])?0:isostring2microtime($json['logTime']);
		$json['eventTime'] = empty($json['eventTime'])?0:isostring2microtime($json['eventTime']);
		if (empty($json['message'])) $json['message'] = '';
		if (empty($json['language'])) $json['language'] = '';
		if (!isset($newRequestTimes) or $newRequestTimes or in_array($json['requestTime'], $requestTimes, true)) {
			if (is_string($json['message'])) {
				if (isset($bookId) and preg_match('/^bookId=(\d*)$/',$json['message'],$matches)) {
					$bookId = $matches[1];
				}
				if (is_array($calabashlogs) and preg_match('/^calabashlog=(.*)$/',$json['message'],$matches)) {
					$calabashlogs[$matches[1]] = $json['requestTime'];
				}
				if (is_array($pythonlogs) and preg_match('/^pythonlog=(.*)$/',$json['message'],$matches)) {
					$pythonlogs[$matches[1]] = $json['requestTime'];
				}
			}
			if (isset($browser) and is_array($json['message']) and array_key_exists("browser_name", $json['message'])) {
				$browser = $json['message'];
				$json['message'] = '[browser info]';
			}
			if (isset($requestTimes) and (!isset($newRequestTimes) or $newRequestTimes) and !in_array($json['requestTime'], $requestTimes, true)) {
				$requestTimes[] = $json['requestTime'];
			}
			if ($json['language']==='javascript')
				$json = parseJavascript($json);
			$log[] = $json;
		}
	}
}

// logFile: filehandle
// log: array to append log entries to
function appendCalabashLog($logFile, &$log) {
	for ($i = 0; $i < count($logFile); $i++) {
		$logLine = $logFile[$i];
		if (!(preg_match('/^\d+\.\w+\.\d+\s+\d+:\d+:\d+.*$/',$logLine)))
			continue;
		preg_match('/^(\d+)\.(\w+)\.(\d+)\s+(\d+):(\d+):(\d+)\s+([^\s]+)\s+(.+)$/',$logLine,$matches);
		$isostringUTC = $matches[3]."-";
		switch ($matches[2]) {
		case 'jan': $isostringUTC .= '01'; break;
		case 'feb': $isostringUTC .= '02'; break;
		case 'mar': $isostringUTC .= '03'; break;
		case 'apr': $isostringUTC .= '04'; break;
		case 'may': $isostringUTC .= '05'; break;
		case 'jun': $isostringUTC .= '06'; break;
		case 'jul': $isostringUTC .= '07'; break;
		case 'aug': $isostringUTC .= '08'; break;
		case 'sep': $isostringUTC .= '09'; break;
		case 'oct': $isostringUTC .= '10'; break;
		case 'nov': $isostringUTC .= '11'; break;
		case 'dec': $isostringUTC .= '12'; break;
		default: $isostringUTC .= '00';
		}
		$isostringUTC .= "-".$matches[1]."T".$matches[4].":".$matches[5].":".$matches[6]."+00:00";
		
		$logTime = isostring2microtime($isostringUTC)-date("Z");
		$eventTime = isostring2microtime($isostringUTC)-date("Z");
		$file = preg_replace('/^.*\.(.*)$/','$1',$matches[7]);
		$type = $matches[8];
		
		$message = "";
		for ($i++; $i < count($logFile); $i++) {
			if (preg_match('/^([A-Z]+):\s+(.*)$/',$logFile[$i],$messageMatches)) {
				$type = $messageMatches[1];
				$message .= $messageMatches[2];
			} else {
				$message .= $logFile[$i];
			}
			if ($i < count($logFile)-1 and (preg_match('/^\d+\.\w+\.\d+\s+\d+:\d+:\d+.*$/',$logFile[$i+1]) or !preg_match('/\w/',$logFile[$i+1]))) {
				break;
			}
		}
		
		$log[] = array(
			"eventTime" => $eventTime,
			"requestTime" => $requestTime,
			"logTime" => $logTime,
			"language" => "xproc",
			"type" => $type,
			"message" => $message,
			"file" => $file,
			"line" => $line
		);
	}
}

// sort logs by requestTime, then logTime, then eventTime
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

function echoSessionHeader($userId, $bookId, $log) {
	global $human;
	$logSpan = $log[count($log)-1]['requestTime']-$log[0]['requestTime'];
	if ($human) {
	?>
	<header>
		<table width="100%">
			<tr>
				<th>Tilvekstnummer</th>
				<th>Tid</th>
				<th>Lånernummer</th>
			</tr>
			<tr style="text-align: center; font-size: 1.5em; font-weight: bold;">
				<td><?php echo utf8_encode($bookId);?><br/><div style="font-size: 0.5em; margin-left: auto; margin-right: auto; text-align: center;"><?php
					$dcLanguage = '';
					$dcTitle = '';
					$dcPublisher = '';
					$dcFormat = '';
					$dcType = '';
					if ($dcFile = file("http://128.39.10.81/cgi-bin/hentdynamisk.htmc?mode=dc&tnr=$bookId")) {
						foreach ($dcFile as $dcLine) {
							if (preg_match('/meta\s+name="(.*)"\s+content="(.*)"/',$dcLine,$matches)) {
								switch ($matches[1]) {
								case "dc:language": $dcLanguage = $matches[2]; break;
								case "dc:title": $dcTitle = $matches[2]; break;
								case "dc:publisher": $dcPublisher = $matches[2]; break;
								case "dc:format": $dcFormat = $matches[2]; break;
								case "dc:type": $dcType = $matches[2]; break;
								}
							}
						}
					}
					if (!empty($dcTitle)) echo utf8_encode("Tittel: $dcTitle<br/>");
					if (!empty($dcPublisher)) echo utf8_encode("Utgiver: $dcPublisher<br/>");
					if (!empty($dcFormat)) echo utf8_encode("Format: $dcFormat<br/>");
					if (!empty($dcType)) echo utf8_encode("Type: $dcType<br/>");
					if (!empty($dcLanguage)) echo utf8_encode("Språk: $dcLanguage");
					?></div></td>
				<td><nobr><?php echo prettyTimeToMinute($log[0]['requestTime']);?></nobr><br/>
				<span style="font-size: 0.5em;">varighet: <?php
					$logSpan = floor($logSpan);
					$logSpanString = '';
					if ($logSpan > 3600) $logSpanString .= floor($logSpan/3600)." timer, ";
					$logSpan = $logSpan%3600;
					if ($logSpan > 60 or strlen($logSpanString)>0) echo floor($logSpan/60)." minutter og ";
					$logSpan = $logSpan%60;
					$logSpanString .= $logSpan." sekunder";
					echo $logSpanString;
				?></span></td>
				<td><?php echo utf8_encode($userId);?></td>
			</tr>
		</table>
	</header>
	<?php
	} else {
		row('bookId', $bookId, '');
		row('userId', $userId, '');
		row('duration', $logSpan, '');
	}
}

function echoBrowserInfo($browser) {
	if ($human) {
	?><section id="browser_info">
		<table>
		<tr class="general">
			<td>Browser</td>
			<td><img src="img/logsymbols/<?php echo utf8_encode($browser['Browser']);?>.png"/> <?php echo isset($browser["Parent"])?utf8_encode($browser["Parent"]):'';?></td>
		</tr>
		<tr class="general">
			<td>Platform</td>
			<td><img src="img/logsymbols/<?php echo utf8_encode($browser['Platform']);?>.png"/> <?php echo isset($browser["Platform"])?utf8_encode($browser["Platform"]):'';?></td>
		</tr>
		<tr class="general">
			<td>Architecture</td>
			<td><?php echo $browser["Win64"]?'64-bit':($browser["Win32"]?'32-bit':($browser["Win16"]?'16-bit':'unknown'));?></td>
		</tr>
		<tr class="general">
			<td>Supports JavaScript</td>
			<td><?php echo $browser["JavaScript"]?'yes':'no';?></td>
		</tr>
		<tr class="general">
			<td>CSS Version</td>
			<td><?php echo $browser["CssVersion"];?></td>
		</tr>
		<tr class="general">
			<td>Supports background sounds</td>
			<td><?php echo $browser["BackgroundSounds"]?'yes':'no';?></td>
		</tr>
		<tr class="general">
			<td>Is mobile device</td>
			<td><?php echo $browser["isMobileDevice"]?'yes':'no';?></td>
		</tr>
		<!--
		<tr class="technical">
			<td>User Agent</td>
			<td><?php echo utf8_encode($browser["browser_name"]);?></td>
		</tr>
		<tr class="technical">
			<td>Browser is alpha version</td>
			<td><?php echo $browser["Alpha"]?'yes':'no';?></td>
		</tr>
		<tr class="technical">
			<td>Browser is beta version</td>
			<td><?php echo $browser["Beta"]?'yes':'no';?></td>
		</tr>
		<tr class="technical">
			<td>Supports frames</td>
			<td><?php echo $browser["Frames"]?'yes':'no';?></td>
		</tr>
		<tr class="technical">
			<td>Supports iframes</td>
			<td><?php echo $browser["IFrames"]?'yes':'no';?></td>
		</tr>
		<tr class="technical">
			<td>Supports tables</td>
			<td><?php echo $browser["Tables"]?'yes':'no';?></td>
		</tr>
		<tr class="technical">
			<td>Supports cookies</td>
			<td><?php echo $browser["Cookies"]?'yes':'no';?></td>
		</tr>
		<tr class="technical">
			<td>Supports JavaApplets</td>
			<td><?php echo $browser["JavaApplets"]?'yes':'no';?></td>
		</tr>
		<tr class="technical">
			<td>Supports CSS</td>
			<td><?php echo $browser["supportsCSS"]?'yes':'no';?></td>
		</tr>
		<tr class="technical">
			<td>Supports CDF</td>
			<td><?php echo $browser["CDF"]?'yes':'no';?></td>
		</tr>
		<tr class="technical">
			<td>Supports VBScript</td>
			<td><?php echo $browser["VBScript"]?'yes':'no';?></td>
		</tr>
		<tr class="technical">
			<td>Supports ActiveXControls</td>
			<td><?php echo $browser["ActiveXControls"]?'yes':'no';?></td>
		</tr>
	<?php if ($browser["isBanned"]) { ?>
		<tr class="technical">
			<td>Banned by Craig Keith</td>
			<td><?php echo $browser['isBanned']?'yes':'no';?></td>
		</tr>
	<?php } ?>
		<tr class="technical">
			<td>Is syndication reader</td>
			<td><?php echo $browser["isSyndicationReader"]?'yes':'no';?></td>
		</tr>
		<tr class="technical">
			<td>Is crawler</td>
			<td><?php echo $browser["Crawler"]?'yes':'no';?></td>
		</tr>
	<?php if ($browser["AOL"]) { ?>
		<tr class="technical">
			<td>AOL branded browser</td>
			<td><?php echo $browser['AOL']?'yes':'no';?></td>
		</tr>
		<tr class="technical">
			<td>AOL version</td>
			<td><?php echo $browser['aolVersion'];?></td>
		</tr>
	<?php } ?>
		-->
		</table>
	</section>
	<?php } else {
		foreach ($browser as $key => $value) {
			row('browser.'.$key, $value, '');
		}
	}
}

function echoLog($log) {
	?>
	<section id="log">
		<table>
			<tr>
				<!--th>logTime</th-->
				<th>eventTime</th>
				<th>language</th>
				<th>severity</th>
				<th>file</th>
				<th>line</th>
				<th>message</th>
			</tr>
			<?php
			$newLogGroup = true;
			// for each group with equal requestTime or adjacent javascript-entries
			$requestFile = "";
			for ($i = 0; $i < count($log); $i++) {
				$logEntry = $log[$i];
				if (preg_match('/^requestFile=(.*)$/',$logEntry['message'],$matches)) {
					$requestFile = $matches[1];
					continue;
				}
				if ($newLogGroup) {
					// write out thin grey divider with requestTime from first entry
					?>
					<tr>
						<td colspan="1" class="requestDivider"><b><nobr><?php echo prettyTimeToMillisecond($logEntry['requestTime']);?></nobr></b></th>
						<td colspan="2" class="requestDivider"></td>
						<td colspan="1" class="requestDivider"><b><nobr><?php echo utf8_encode($requestFile);?></nobr></b></th>
						<td colspan="2" class="requestDivider"></td>
					</tr>
					<?php
				}
				// write out logTime, eventTime, language, type, line and message
				?>
				<tr>
					<!--td><nobr><?php echo prettyTimeToMillisecond($logEntry['logTime']);?></nobr></td-->
					<td><nobr><?php echo prettyTimeToMillisecond($logEntry['eventTime']);?></nobr></td>
					<td><?php echo utf8_encode($logEntry['language']);?></td>
					<td><?php echo utf8_encode($logEntry['type']);?></td>
					<td><?php echo utf8_encode(preg_replace('/^.*[\\/\\\\]([^\\/\\\\]*)$/','$1',$logEntry['file']));?></td>
					<td><?php echo utf8_encode(xml($logEntry['line']));?></td>
					<td><pre><?php
						if (is_string($logEntry['message']))
							echo utf8_encode(xml($logEntry['message']));
						else
							var_dump($logEntry['message']);
					?></td>
				</tr>
				<?php
				
				if ($i+1 < count($log)) {
					if ($log[$i+1]['requestTime'] == $logEntry['requestTime']) {
						$newLogGroup = false;
					} else if ($logEntry['language'] == 'javascript') {
						if ($log[$i+1]['language'] == 'javascript') {
							$newLogGroup = false;
						} else if (preg_match('/^requestFile=.*$/',$log[$i+1]['message']) and $i+2 < count($log) and $log[$i+2]['language'] == 'javascript') {
							$newLogGroup = false;
						} else {
							$newLogGroup = true;
						}
					} else {
						$newLogGroup = true;
					}
				}
			}
			?>
		</table>
	</section>
	<?php
}

function parseJavascript($json) {
	$messagelines = explode("<br/>",$json['message']);
	foreach ($messagelines as $line) {
		preg_match('/onerror\(["\'].*["\'],["\'](.*)["\'],(.*)\)/',$line,$matches);
		if (count($matches)>1) {
			$json['line'] = $matches[2];
			$json['file'] = $matches[1];
			break;
		} else {
			if (preg_match('/^.*@(http[^?#]*).*:(.*)$/',$line,$matches)) {
				$json['line'] = $matches[2];
				$json['file'] = $matches[1];
			}
		}
	}
	
	return $json;
}

function progressList($log) {
	$progress = array();
	foreach ($log as $entry) {
		preg_match('/^JSON-PROGRESS:(.*)$/',$entry['message'],$matches);
		if (count($matches)>1) {
			$progress[] = json_decode($matches[1]);
			$progress[count($progress)-1]->requestTime = $entry['requestTime'];
		}
	}
	return $progress;
}

function echoProgress($progress) {
	global $human;
	if ($human) {
	echo '<table id="progress">';
	echo utf8_encode('<tr><th>Tid</th><th>Fremdrift</th><th>Gjenstående</th></tr>');
	foreach ($progress as $p) {
		echo '<tr><td>';
		echo round($p->requestTime-$progress[0]->requestTime,3);
		echo '</td><td>';
		echo round($p->progress,3);
		echo '</td><td>';
		echo round($p->estimatedRemainingTime,3);
		echo '</td></tr>';
	}
	echo '</table>';
	}
}

echo "</body>\n";
echo "</html>\n";
