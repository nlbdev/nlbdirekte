<?php

// utf-8 reminder: 这是一份非常间单的说明书…

$debug = false;
include('common.inc.php');

$includeBrowserInfo = true;
$logsPerPage = 50;
$human = isset($_REQUEST['human']) || !isset($_REQUEST['robot']);

function xml($value) {
	$value = str_replace('"','&quot;',$value);
	$value = str_replace('&','&amp;',$value);
	$value = str_replace("'",'&apos;',$value);
	$value = str_replace('<','&lt;',$value);
	$value = str_replace('>','&gt;',$value);
	return $value;
}

function row($time, $user, $log) {
	echo utf8_encode("\t<tr>\n\t\t<td>".xml($time)."</td>\n\t\t<td>".xml($user)."</td>\n\t\t<td>".xml($log)."</td>\n\t</tr>\n");
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
	default: $time .= "ukjent-måned";
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
	$time .= preg_replace('/^0.(...).*$/','.$1',($microtime - floor($microtime)));
	return $time;
}

if ($human) {
	header('Content-Type: text/html; charset=utf-8');
	echo "<!doctype html>\n";
	echo "<html>\n";
} else {
	header("Content-Type: text/xml; charset=utf-8");
	echo utf8_encode("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
	echo utf8_encode("<html xmlns=\"http://www.w3.org/1999/xhtml\">\n");
}
?><head>
	<title>Logger for NLBdirekte</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta charset="utf-8" />
<?php if ($human) { ?>
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
		th.breadcrumb {
			text-align: right;
		}
	</style>
<?php } ?>
</head>
<body style="font-family: helvetica, arial, sans-serif;">
<?php if ($human) { ?>
<header class="page-header">
	<h1>Logger for NLBdirekte</small></h1>
</header>
<?php }
echo "<table".($human?'id="logs"':'').">\n";

	// open this directory 
	$logDirectoryHandle = opendir(fix_directory_separators($logdir));
	$logFiles = array();
	$files = 0;
	while (false !== ($logFilename = readdir($logDirectoryHandle))) {
		if (preg_match('/log_(\d\d\d\d)-(\d\d)-(\d\d).(\d\d)-(\d\d)-(\d\d.\d\d\d)_(.*).log$/',$logFilename,$matches)) {
			$logFiles[] = array(
				"filename" => $logFilename,
				"time" => isostring2microtime($matches[1]."-".$matches[2]."-".$matches[3]."T".$matches[4].":".$matches[5].":".$matches[6]."+00:00")-date("Z"),
				"user" => $matches[7],
				"type" => "session"
			);
			$files++;
		}
		else if (preg_match('/log_(\d\d\d\d)-(\d\d)-(\d\d).log$/',$logFilename,$matches)) {
			$logFiles[] = array(
				"filename" => $logFilename,
				"time" => isostring2microtime($matches[1]."-".$matches[2]."-".$matches[3]."T00:00:00+00:00")-date("Z"),
				"type" => "day"
			);
			$files++;
		}
		else if (preg_match('/python-(\d\d\d\d)(\d\d)(\d\d)_(\d\d)(\d\d)(\d\d).*.txt$/',$logFilename,$matches)) {
			$logFiles[] = array(
				"filename" => $logFilename,
				"time" => isostring2microtime($matches[1]."-".$matches[2]."-".$matches[3]."T".$matches[4].":".$matches[5].":".$matches[6]."+00:00")-date("Z"),
				"type" => "python"
			);
			$files++;
		}
		else if (preg_match('/calabash-(\d\d\d\d)(\d\d)(\d\d)_(\d\d)(\d\d)(\d\d).*.txt$/',$logFilename,$matches)) {
			$logFiles[] = array(
				"filename" => $logFilename,
				"time" => isostring2microtime($matches[1]."-".$matches[2]."-".$matches[3]."T".$matches[4].":".$matches[5].":".$matches[6]."+00:00")-date("Z"),
				"type" => "calabash"
			);
			$files++;
		}
	}
	closedir($logDirectoryHandle);
	
	// sort log on time
	function logCmp($a, $b) {
		if ($a['time'] == $b['time']) {
			return 0;
		} else {
			return ($a['time'] > $b['time']) ? -1 : 1;
		}
	}
	usort($logFiles, "logCmp");
	
	$pages = ceil(count($logFiles)/$logsPerPage);
	$page = max(0,min((isset($_REQUEST['page'])?$_REQUEST['page']:0),$pages));
//	if ($human) {
		$logFiles = array_splice($logFiles, $logsPerPage*$page, ($page<$pages?$logsPerPage:(count($logFiles)%$logsPerPage)));
//	}
	if ($human) { ?>
		<tr>
			<th><?php echo "Type" ?></th>
			<th><?php echo "Tid" ?></th>
			<th><?php echo "Lånernummer" ?></th>
			<?php
			echo '<th colspan="4" class="breadcrumb">Side: ';
			if ($page < $pages-10)
				echo "<a href='browselogs.php?page=0'>&lt;&lt;</a> ";
			for ($p = max(0,$page-9); $p < $pages and $p <= $page+9; $p++) {
				if ($p == $page) {
					echo " <span class='current-page'><big>".($pages-$p)."</big></span> ";
				} else if (abs($p-$page)<10) {
					echo " <a href='browselogs.php?page=$p'>".($pages-$p)."</a> ";
				}
			}
			if ($page > 10)
				echo " <a href='browselogs.php?page=".($pages-1)."'>&gt;&gt;</a>";
			echo "</th>\n";
		echo "</tr>\n";
	}
	foreach ($logFiles as $logFile) {
		if (!$human and $logFile['type'] != "session") continue;
		if (!$human and strlen($_REQUEST['time']) > 0 and strpos(date('c',$logFile['time']),$_REQUEST['time']) !== 0) continue;
		if (!$human) row(date('c',$logFile['time']),
				 isset($logFile['user'])?$logFile['user']:'',
				 'http://'.$_SERVER['SERVER_NAME'].preg_replace('/[^\/]+#?[^#\/]*$/','',$_SERVER['REQUEST_URI']).
					'viewlog.php?type=session&logname='.
					preg_replace('/.*log_(\d\d\d\d-\d\d-\d\d.\d\d-\d\d-\d\d.\d\d\d_.*).log$/','$1',$logFile['filename']).
					'&robot');
		else {
			echo "<tr>\n";
			echo "<td>\n";
			switch ($logFile['type']) {
				case 'session': echo '<img src="img/logsymbols/session.png" alt="User session"/>'; break;
				case 'day': echo '<img src="img/logsymbols/day.png" alt="Non-session logs for a specific day"/>'; break;
				case 'python': echo '<img src="img/logsymbols/Python.png" alt="Python"/>'; break;
				case 'calabash': echo '<img src="img/logsymbols/XProc.png" alt="XProc"/>'; break;
				default: echo '&nbsp;';
			}
			echo "</td>\n";
			echo "<td>";
			switch ($logFile['type']) {
				case 'session': echo prettyTimeToMillisecond($logFile['time']); break;
				case 'day': echo prettyTimeToDate($logFile['time']); break;
				case 'python': echo prettyTimeToMinute($logFile['time']); break;
				case 'calabash': echo prettyTimeToMinute($logFile['time']); break;
				defalt: echo prettyTimeToMinute($logFile['time']);
			}
			echo "</td>";
			echo "\t<td>".(isset($logFile['user'])?$logFile['user']:'')."</td>\n";
			$link = "";
			switch ($logFile['type']) {
				case 'session': $link = 'viewlog.php?type=session&logname='.preg_replace('/.*log_(\d\d\d\d-\d\d-\d\d.\d\d-\d\d-\d\d.\d\d\d_.*).log$/','$1',$logFile['filename']); break;
				case 'day': $link = 'viewlog.php?type=day&logname='.preg_replace('/.*log_(\d\d\d\d-\d\d-\d\d).log$/','$1',$logFile['filename']); break;
				case 'python': $link = 'viewlog.php?type=python&logname='.$logFile['filename']; break;
				case 'calabash': $link = 'viewlog.php?type=calabash&logname='.$logFile['filename']; break;
				default: $link = '&nbsp;';
			}
			echo "<td><a href='$link'>Vis logg</a></td>";
			switch ($logFile['type']) {
			case 'session':
				if ($thisLogFile = file(fix_directory_separators("$logdir/".$logFile['filename']))) {
					$b = null;
					$backend = 'unknown';
					$maxLines = 500;
					foreach ($thisLogFile as $logEntry) {
						if (empty($logEntry)) continue;
						$json = json_decode($logEntry, true);
						if (is_array($json['message']) and array_key_exists("browser_name", $json['message'])) {
							$b = $json['message'];
							if ($backend !== 'unknown') break;
						}
						if (is_string($json['message']) and preg_match('/^audio backend:(.*)$/', $json['message'], $matches)) {
							$backend = $matches[1];
							if ($b !== null) break;
						}
						
						if (--$maxLines <= 0) break;
					}
					$html = "<td><img src='img/logsymbols/unknown.png'/></td><td><img src='img/logsymbols/unknown.png'/></td><td><img src='img/logsymbols/unknown.png'/></td>";
					if (!empty($b)) {
						$html = "<td><img src='img/logsymbols/".$backend.".png'/>".($backend==='html5'?'HTML5 Audio':($backend==='flash'?'Flash Audio':($backend==='noaudio'?'Audio not supported':'Unknown audio support')))."</td>";
						$html .= "<td><img src='img/logsymbols/".$b['Browser'].".png'/>";
						$html .= $b['Parent']."</td>";
						$html .= "<td><img src='img/logsymbols/".$b['Platform'].".png'/>";
						$html .= ', '.$b['Platform'];
						$html .= ($b['Win64']?', 64-bit':($b['Win32']?', 32-bit':($b['Win16']?', 16-bit':'')));
						if ($b['isMobileDevice']) $html .= ', is mobile device';
						if (!$b['JavaScript']) $html .= ', no javascript';
						if ($b['CssVersion'] < 3) $html .= ', CSS version '.$b['CssVersion'];
						if (!$b['Cookies']) $html .= ', no cookie support';
						$html .= "</td>";
					}
					echo $html;
				}
				break;
			case 'day':
				echo '<td colspan="3">Logg uten session-tilhørighet</td>';
				break;
			case 'calabash':
				echo '<td colspan="3">Calabash-logg</td>';
				break;
			case 'python':
				echo '<td colspan="3">Python-logg</td>';
				break;
			default:
				echo '<td colspan="3">Ukjent loggtype</td>';
			}
			echo "</table>\n";
		}
	}
echo "</table>\n";
?>
</body>
</html>
