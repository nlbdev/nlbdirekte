<?php

// utf-8 reminder: 这是一份非常间单的说明书…

$debug = false;
include('common.inc.php');

function prettyTimeToMinute($microtime) {
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
	default: $time .= "ukjent-måned";
	}
	$time .= date(" Y, G:i",(floor($microtime)+intval(date("Z",floor($microtime)))));
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
	</style>
</head>
<body style="font-family: helvetica, arial, sans-serif;">
<?php

$logname = $_REQUEST['logname'];
list($formatted_time, $userId) = explode('_',$logname);
$formatted_time_split = explode('.',$logname);
$date = $formatted_time_split[0];

$bookId = 'unknown';
$calabashlogs = array();
$pythonlogs = array();
$browser = array();

$log = array(); // all relevant log entries
$requestTimes = array(); 
// load log
if ($logFile = file(fix_directory_separators("$logdir/log_$logname.log"))) {
	foreach ($logFile as $logEntry) {
		if (empty($logEntry)) continue;
		$json = json_decode($logEntry, true);
		if (empty($json)) continue;
		$json['requestTime'] = empty($json['requestTime'])?0:isostring2microtime($json['requestTime']);
		$json['logTime'] = empty($json['logTime'])?0:isostring2microtime($json['logTime']);
		$json['eventTime'] = empty($json['eventTime'])?0:isostring2microtime($json['eventTime']);
		if (empty($json['message'])) $json['message'] = '';
		if (empty($json['language'])) $json['language'] = '';
		if (is_string($json['message'])) {
			if (preg_match('/^bookId=(\d*)$/',$json['message'],$matches)) {
				$bookId = $matches[1];
			}
			if (preg_match('/^calabashlog=(.*)$/',$json['message'],$matches)) {
				$calabashlogs[$matches[1]] = $json['requestTime'];
			}
			if (preg_match('/^pythonlog=(.*)$/',$json['message'],$matches)) {
				$pythonlogs[$matches[1]] = $json['requestTime'];
			}
		}
		if (is_array($json['message']) and array_key_exists("browser_name", $json['message'])) {
			$browser = $json['message'];
			$json['message'] = '[browser info]';
		}
		if (!in_array($json['requestTime'], $requestTimes, true)) {
			$requestTimes[] = $json['requestTime'];
		}
		if ($json['language']==='javascript')
			$json = parseJavascript($json);
		$log[] = $json;
	}
}
// load common log
if ($logFile = file(fix_directory_separators("$logdir/log_$date.log"))) {
	foreach ($logFile as $logEntry) {
		if (empty($logEntry)) continue;
		$json = json_decode($logEntry, true);
		if (count($json)) continue;
		$json['requestTime'] = empty($json['requestTime'])?0:isostring2microtime($json['requestTime']);
		$json['logTime'] = empty($json['logTime'])?0:isostring2microtime($json['logTime']);
		$json['eventTime'] = empty($json['eventTime'])?0:isostring2microtime($json['eventTime']);
		if (empty($json['message'])) $json['message'] = '';
		if (empty($json['language'])) $json['language'] = '';
		if (in_array($json['requestTime'], $requestTimes, true)) {
			if (is_string($json['message'])) {
				if (preg_match('/^bookId=(\d*)$/',$json['message'],$matches)) {
					$bookId = $matches[1];
				}
				if (preg_match('/^calabashlog=(.*)$/',$json['message'],$matches)) {
					$calabashlogs[$matches[1]] = $json['requestTime'];
				}
				if (preg_match('/^pythonlog=(.*)$/',$json['message'],$matches)) {
					$pythonlogs[$matches[1]] = $json['requestTime'];
				}
			}
			$log[] = $json;
		}
	}
}
// load python log
foreach ($pythonlogs as $pythonlog => $requestTime) {
	if ($logFile = file(fix_directory_separators("$pythonlog"))) {
		foreach ($logFile as $logEntry) {
			$json = json_decode($logEntry, true);
			$json['requestTime'] = $requestTime;
			$json['logTime'] = isostring2microtime($json['logTime']);
			$json['eventTime'] = isostring2microtime($json['eventTime']);
			$log[] = $json;
		}
	}
}
// load calabash log
foreach ($calabashlogs as $calabashlog => $requestTime) {
	if ($logFile = file(fix_directory_separators("$calabashlog"))) {
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
			
			$language = "xproc";
			$line = -1;
			
			$log[] = array(
				"eventTime" => $eventTime,
				"requestTime" => $requestTime,
				"logTime" => $logTime,
				"language" => "xproc",
				"type" => $type,
				"message" => $message,
				"file" => $file,
				"line" => -1
			);
		}
	}
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
usort($log, "logCmp");

?>
<header class="page-header">
	<h1>Logg for NLBdirekte</small></h1>
	<table width="100%">
		<tr>
			<th>Tilvekstnummer</th>
			<th>Tid</th>
			<th>Lånernummer</th>
		</tr>
		<tr style="text-align: center; font-size: 1.5em; font-weight: bold;">
			<td><?php echo $bookId;?><br/><div style="font-size: 0.5em; margin-left: auto; margin-right: auto; text-align: center;"><?php
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
				if (!empty($dcTitle)) echo "Tittel: $dcTitle<br/>";
				if (!empty($dcPublisher)) echo "Utgiver: $dcPublisher<br/>";
				if (!empty($dcFormat)) echo "Format: $dcFormat<br/>";
				if (!empty($dcType)) echo "Type: $dcType<br/>";
				if (!empty($dcLanguage)) echo "Språk: $dcLanguage";
				?></div></td>
			<td><nobr><?php echo prettyTimeToMinute($log[0]['requestTime']);?></nobr><br/>
			<span style="font-size: 0.5em;">varighet: <?php
				$logSpan = floor($log[count($log)-1]['requestTime']-$log[0]['requestTime']);
				$logSpanString = '';
				if ($logSpan > 3600) $logSpanString .= floor($logSpan/3600)." timer, ";
				$logSpan = $logSpan%3600;
				if ($logSpan > 60 or strlen($logSpanString)>0) echo floor($logSpan/60)." minutter og ";
				$logSpan = $logSpan%60;
				$logSpanString .= $logSpan." sekunder";
				echo $logSpanString;
			?></span></td>
			<td><?php echo $userId;?></td>
		</tr>
	</table>
</header>
<hr/>
<section id="browser_info">
	<table>
	<tr class="general">
		<td>Browser</td>
		<td><img src="img/browserlogos/<?php echo $browser['Browser'];?>.png"/> <?php echo $browser["Parent"];?></td>
	</tr>
	<tr class="general">
		<td>Platform</td>
		<td><img src="img/browserlogos/<?php echo $browser['Platform'];?>.png"/> <?php echo $browser["Platform"];?></td>
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
		<td><?php echo $browser["browser_name"];?></td>
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
<hr/>
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
					<td colspan="1" class="requestDivider"><b><nobr><?php echo $requestFile;?></nobr></b></th>
					<td colspan="2" class="requestDivider"></td>
				</tr>
				<?php
			}
			// write out logTime, eventTime, language, type, line and message
			?>
			<tr>
				<!--td><nobr><?php echo prettyTimeToMillisecond($logEntry['logTime']);?></nobr></td-->
				<td><nobr><?php echo prettyTimeToMillisecond($logEntry['eventTime']);?></nobr></td>
				<td><?php echo $logEntry['language'];?></td>
				<td><?php echo $logEntry['type'];?></td>
				<td><?php echo preg_replace('/^.*[\\/\\\\]([^\\/\\\\]*)$/','$1',$logEntry['file']);?></td>
				<td><?php echo $logEntry['line'];?></td>
				<td><pre><?php
					if (is_string($logEntry['message']))
						echo $logEntry['message'];
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

</body>
</html>
<?php
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
?>