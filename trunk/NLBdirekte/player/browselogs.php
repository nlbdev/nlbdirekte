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
	$time .= preg_replace('/^0.(...).*$/','.$1',($microtime - floor($microtime)));
	return $time;
}

header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html>
<head>
	<title>Logger for NLBdirekte</title>
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
		
		#logs table {
			margin-left: auto;
			margin-right: auto;
			text-align: center;
		}
		#logs tr.general {
			font-size: 1.1em;
		}
		#logs tr.technical {
			font-size: 0.5em;
		}
		#logs table
		{
			border-color: #DDD;
			border-width: 0 0 1px 1px;
			border-style: solid;
			width: 100%;
			text-align: left;
		}
		#logs td
		{
			border-color: #DDD;
			border-width: 1px 1px 0 0;
			border-style: solid;
			padding: 4px;
			background-color: #EEE;
		}
		#logs td.requestDivider {
			background-color: #CCC;
			font-size: 0.75em;
			font-style: italic;
			text-align: center;
			padding: 0;
		}
	</style>
</head>
<body style="font-family: helvetica, arial, sans-serif;">

<header class="page-header">
	<h1>Logger for NLBdirekte</small></h1>
</header>

<table id="logs">
	<tr>
		<th>Tid</th>
		<th>Lånernummer</th>
		<th></th>
	</tr>
	<?php
	// open this directory 
	$logDirectoryHandle = opendir(fix_directory_separators($logdir));
	$logFiles = array();
	while ($logFilename = readdir($logDirectoryHandle)) {
		if (preg_match('/log_(\d\d\d\d)-(\d\d)-(\d\d).(\d\d)-(\d\d)-(\d\d.\d\d\d)_(.*).log$/',$logFilename,$matches)) {
			$logFiles[] = array(
				"filename" => $logFilename,
				"time" => isostring2microtime($matches[1]."-".$matches[2]."-".$matches[3]."T".$matches[4].":".$matches[5].":".$matches[6]."+00:00")-date("Z"),
				"user" => $matches[7]
			);
		}
	}
	closedir($logDirectoryHandle);
	
	// sorter logg på requestTime, så logTime
	function logCmp($a, $b) {
		if ($a['time'] == $b['time']) {
			return 0;
		} else {
			return ($a['time'] > $b['time']) ? -1 : 1;
		}
	}
	usort($logFiles, "logCmp");
	
	foreach ($logFiles as $logFile) {
		?><tr>
			<td><?php echo prettyTimeToMillisecond($logFile['time']);?></td>
			<td><?php echo $logFile['user'];?></td>
			<td><a href="viewlog.php?logname=<?php echo preg_replace('/.*log_(\d\d\d\d-\d\d-\d\d.\d\d-\d\d-\d\d.\d\d\d_.*).log$/','$1',$logFile['filename']);?>">Vis logg</a></td>
		</tr><?php
	}
	?>
</table>

</body>
</html>