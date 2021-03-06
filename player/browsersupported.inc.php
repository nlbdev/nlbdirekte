<?php
// Designed to be included from direkte.php

function browserSupported($browser, $launchTime) {
	global $debug;
	
    if (        $browser['Browser'] == 'Chrome' and $browser['MajorVer'] < 8 or
            $browser['Browser'] == 'Chromium' and $browser['MajorVer'] < 8 or
            $browser['Browser'] == 'Opera' and $browser['MajorVer'] < 11 or
            $browser['Browser'] == 'IE' and $browser['MajorVer'] < 9) { // These are known not to work
		?><!doctype html>
		<html class="ui-mobile landscape min-width-320px min-width-480px min-width-768px min-width-1024px">
			<head>
				<!-- Page metadata -->
			        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
			        <base href="." />
			        <meta name="viewport" content="width=device-width, minimum-scale=1, maximum-scale=1" />
			        <meta charset="utf-8" />
				<title>NLBdirekte</title>
				<link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon" />
				<!-- jQuery + jQuery Mobile -->
				<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery<?php echo $debug?'':'.min';?>.js"></script>
				<script>window.jQuery || document.write("<script src='js/jQuery/jquery-1.6.2<?php echo $debug?'':'.min';?>.js'>\x3C/script>")</script>
				<script src="http://code.jquery.com/mobile/1.0/jquery.mobile-1.0<?php echo $debug?'':'.min';?>.js"></script>
				<script>
					if (typeof jQuery.mobile == 'undefined') {
						document.write("<script src='js/jQuery/jquery.mobile-1.0<?php echo $debug?'':'.min';?>.js'>\x3C/script>");
						document.write("<link rel='stylesheet' href='css/jQuery/jquery.mobile-1.0<?php echo $debug?'':'.min';?>.css' />");
					} else {
						document.write("<link rel='stylesheet' href='http://code.jquery.com/mobile/1.0/jquery.mobile-1.0<?php echo $debug?'':'.min';?>.css' />");
					}
				</script>
				<style type="text/css">
					h2 { text-indent:10px; }
				</style>
			</head>
			<body class="ui-mobile-viewport">
				<div data-role="page" class="ui-page ui-body-c ui-page-active">
					<div data-role="content">
						<h1>
		<?php
			if (		$browser['Browser'] == 'IE' and $browser['MajorVer'] < 9)	echo "Internet Explorer ".$browser['MajorVer']." er ikke støttet. Støttede nettlesere:";
			else if (	$browser['Browser'] == 'Firefox')				echo "Firefox er ikke støttet. Støttede nettlesere:";
			else if (	$browser['Browser'] == 'Safari')				echo "Safari er ikke støttet. Støttede nettlesere:";
			else										echo "Nettleseren din er ikke støttet. Støttede nettlesere:";
			?>
			</h1>
			<ul data-role="listview">
                <li><a rel="external" href="http://www.opera.com/"><img src="img/logsymbols/Opera.png" class="ui-li-icon"/><h2>Opera (11 eller nyere)</h2></a></li>
				<li><a rel="external" href="http://www.google.com/chrome/index.html?hl=no"><img src="img/logsymbols/Chrome.png" class="ui-li-icon"/><h2>Chrome</h2></a></li>
				<li><a rel="external" href="http://www.apple.com/no/safari/"><img src="img/logsymbols/Safari.png" class="ui-li-icon"/><h2>Safari</h2></a></li>
				<li><a rel="external" href="http://windows.microsoft.com/nb-NO/internet-explorer/products/ie/home"><img src="img/logsymbols/IE.png" class="ui-li-icon"/><h2>Internet Explorer 9 (eller nyere)</h2></a></li>
				<li><a rel="external" href="direkte.php?launchTime=<?php echo $launchTime."&".$_SERVER["QUERY_STRING"];?>"><img src="img/logsymbols/<?php echo $browser['Browser'];?>" class="ui-li-icon"/>
				    <h2>Prøv med <?php echo $browser['Browser'] == 'IE' ? "Internet Explorer ".$browser['MajorVer'] :
						 	    ($browser['Browser'] == 'Default Browser' or $browser['Browser'] == 'DefaultProperties') ? "nettleseren min" :
							    $browser['Browser']
					?> allikevel</h2></a></li>
			</ul>
			</div></div>
			</body>
			</html>
		<?php
		exit;
    } else {
        return true;
	}
}
