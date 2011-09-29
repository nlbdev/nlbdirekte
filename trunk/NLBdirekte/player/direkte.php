<?php
header('Content-Type: text/html; charset=utf-8');

include('common.inc.php');

list($user, $book) = decodeTicket($_REQUEST['ticket']);
authorize($user,$book,isset($_REQUEST['session'])?$_REQUEST['session']:'',true);

# Create a launchTime that can be used to identify this instance of NLBdirekte
# if launchTime is already set, use that one instead
if (isset($_REQUEST['launchTime']))
	$launchTime = $_REQUEST['launchTime'];
else
	$launchTime = microtime(true);
$logfile = microtimeAndUsername2logfile($launchTime,$user);

include('lib/browscap/Browscap.php');
$browscap = new Browscap('lib/browscap/');
$browscap->doAutoUpdate = false;
$browser = $browscap->getBrowser(NULL, true);
logMessage(array(
	"eventTime" => microtime2isostring(microtime(true)),
	"language" => "php",
	"type" => E_NOTICE,
	"message" => $browser,
	"file" => __FILE__,
	"line" => __LINE__
));
logMessage(array(
	"eventTime" => microtime2isostring(microtime(true)),
	"language" => "php",
	"type" => E_NOTICE,
	"message" => "NLBdirekte v$version",
	"file" => __FILE__,
	"line" => __LINE__
));
logMessage(array(
	"eventTime" => microtime2isostring(microtime(true)),
	"language" => "php",
	"type" => E_NOTICE,
	"message" => "bookId=$book",
	"file" => __FILE__,
	"line" => __LINE__
));

$iconpos = $browser['isMobileDevice']?'notext':'top';

if (!isset($_REQUEST['launchTime'])) {
	include('browsersupported.inc.php');
	browserSupported($browser, $launchTime);
}

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
		
		<!-- The code recieved from the library system, used to validate the session -->
		<script type="text/javascript" charset="utf-8">
		/* <![CDATA[ */
			var nlbdirekteVersion = '<?php echo $version;?>';
			var ticket = '<?php echo $_REQUEST['ticket']; ?>';
			var launchTime = '<?php echo $launchTime; ?>';
			var browscap = <?php echo json_encode($browser);?>;
		/* ]]> */
		</script>
		
		<script type="text/javascript">
		<!-- strip eventual fragment identifier through redirection -->
		if (window.location.toString().indexOf('#') >= 0) {
			var newLocation = window.location.toString().substring(0,window.location.toString().indexOf('#'));
			if (newLocation.indexOf('launchTime') < 0) {
				if (newLocation.indexOf('?') >= 0)
					newLocation += '&';
				else
					newLocation += '?';
				newLocation += 'launchTime='+launchTime;
			}
			window.location = newLocation;
		}
		</script>
		
		<!-- jQuery + jQuery Mobile -->
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery<?php echo $debug?'':'.min';?>.js"></script>
		<script>window.jQuery || document.write("<script src='js/jQuery/jquery-1.6.4<?php echo $debug?'':'.min';?>.js'>\x3C/script>")</script>
		<script src="http://code.jquery.com/mobile/1.0b1/jquery.mobile-1.0b3<?php echo $debug?'':'.min';?>.js"></script>
		<script>
			if (typeof jQuery.mobile == 'undefined') {
				document.write("<script src='js/jQuery/jquery.mobile-1.0b3<?php echo $debug?'':'.min';?>.js'>\x3C/script>");
				document.write("<link rel='stylesheet' href='css/jQuery/jquery.mobile-1.0b3<?php echo $debug?'':'.min';?>.css' />");
			} else {
				document.write("<link rel='stylesheet' href='http://code.jquery.com/mobile/1.0b1/jquery.mobile-1.0b3<?php echo $debug?'':'.min';?>.css' />");
			}
		</script>

		<!-- NLBdirekte; stylesheets and configuration (default and custom) -->
		<link type="text/css" href="css/NLBdirekte.css?v=<?php echo $version;?>" rel="stylesheet" />
		<link type="text/css" href="css/Daisy202Book.css?v=<?php echo $version;?>" rel="stylesheet" />
		<script type="text/javascript" src="js/common.js?v=<?php echo $version;?>"></script>
		<?php if (file_exists('config/config.js')) { ?>
			<script type="text/javascript" src="config/config.js?v=<?php echo $version;?>"></script>
		<?php } ?>
		
		<!-- Logging framework -->
		<script type="text/javascript" src="js/javascript-stacktrace/stacktrace.js?v=<?php echo $version;?>"></script>
		<script type="text/javascript" src="js/log4javascript/log4javascript<?php echo $debug?'_uncompressed':'';?>.js?v=<?php echo $version;?>"></script>
		<script type="text/javascript">
			//<![CDATA[
			log4javascript.logLog.setQuietMode(true);
			var log = log4javascript.getLogger();
			var browserConsoleAppender = new log4javascript.BrowserConsoleAppender();
			//var browserConsoleLayout = new log4javascript.PatternLayout("%d{HH:mm:ss} %-5p - %m%n");
			//browserConsoleAppender.setLayout(browserConsoleLayout);
			if (typeof logging_client_level=="string") switch (logging_client_level) {
				case 'ALL':		browserConsoleAppender.setThreshold(log4javascript.Level.ALL);   break;
				case 'TRACE':	browserConsoleAppender.setThreshold(log4javascript.Level.TRACE); break;
				case 'DEBUG':	browserConsoleAppender.setThreshold(log4javascript.Level.DEBUG); break;
				case 'INFO':	browserConsoleAppender.setThreshold(log4javascript.Level.INFO);  break;
				case 'WARN':	browserConsoleAppender.setThreshold(log4javascript.Level.WARN);  break;
				case 'ERROR':	browserConsoleAppender.setThreshold(log4javascript.Level.ERROR); break;
				case 'FATAL':	browserConsoleAppender.setThreshold(log4javascript.Level.FATAL); break;
				case 'OFF':		browserConsoleAppender.setThreshold(log4javascript.Level.OFF);   break;
			}
			log.addAppender(browserConsoleAppender);
			var ajaxAppender = new log4javascript.AjaxAppender(serverUrl+"log.php");
			var jsonLayout = new log4javascript.JsonLayout();
			jsonLayout.setCustomField('ticket',ticket);
			jsonLayout.setCustomField('launchTime',launchTime);
			ajaxAppender.setLayout(jsonLayout);
			//ajaxAppender.setBatchSize(20);
			//ajaxAppender.setTimerInterval(5000);
			if (typeof logging_server_level=="string") switch (logging_server_level) {
				case 'ALL':		ajaxAppender.setThreshold(log4javascript.Level.ALL);   break;
				case 'TRACE':	ajaxAppender.setThreshold(log4javascript.Level.TRACE); break;
				case 'DEBUG':	ajaxAppender.setThreshold(log4javascript.Level.DEBUG); break;
				case 'INFO':	ajaxAppender.setThreshold(log4javascript.Level.INFO);  break;
				case 'WARN':	ajaxAppender.setThreshold(log4javascript.Level.WARN);  break;
				case 'ERROR':	ajaxAppender.setThreshold(log4javascript.Level.ERROR); break;
				case 'FATAL':	ajaxAppender.setThreshold(log4javascript.Level.FATAL); break;
				case 'OFF':		ajaxAppender.setThreshold(log4javascript.Level.OFF);   break;
			}
			log.addAppender(ajaxAppender);
			/*window.onerror = function(errorMsg, url, lineNumber) {
				log.fatal(printStackTrace().join("\n"));
				return true;
			};*/
			//]]>
		</script>
		
		<!-- SoundManager 2 -->
		<script type="text/javascript" src="js/soundmanager/script/soundmanager2<?php echo $debug?'':'-jsmin';?>.js?v=<?php echo $version; ?>"></script>
		<script type="text/javascript">
			var soundManagerBackend = 'unknown';
			$(function(){
				if (!soundManager)
						soundManager = new SoundManager();
				soundManager.url = 'js/soundmanager/swf'; // path to directory containing SoundManager2 .SWF file
				soundManager.flashVersion = 8;
				soundManager.allowFullScreen = false;
				soundManager.wmode = 'transparent';
				soundManager.debugMode = debug;
				soundManager.debugFlash = false;
				soundManager.useHighPerformance = true;
				soundManager._wD = soundManager._writeDebug = function(sText, sType, bTimestamp) {
					log.debug('soundManager: '+sText);
					return true;
				};
				soundManager.useHTML5Audio = false;
				soundManager.onerror = function() {
					soundManagerBackend = 'noaudio';
					soundManagerBackendChanged(soundManagerBackend);
					log.debug('soundManager failed to initialize.');
				};
				soundManager.onload = function() {
					soundManagerBackend = soundManager.html5.usingFlash?'flash':'html5';
					log.info('audio backend:'+soundManagerBackend);
				}
			});
		</script>
		
		<!-- The player objects.
			The server is an interface to a specific server that resolves URLs amongst other things.
			The loader is an interface for a specific format that parses books into the player
			The player is the object that handles the playback logic, including synchronization -->
		<script type="text/javascript" src='js/NLBServer.js?v=<?php echo $version;?>'></script>
		<script type="text/javascript" src='js/Daisy202Loader.js?v=<?php echo $version;?>'></script>
		<script type="text/javascript" src='js/SmilPlayer.js?v=<?php echo $version;?>'></script>
		
		<!-- Bookmarks synchronization -->
		<script type="text/javascript" src="js/Bookmarks.js?v=<?php echo $version;?>"></script>
		
		<!-- (loads the player, updates the graphics etc.) -->
		<script type="text/javascript" src="js/SmilPlayerUI.js?v=<?php echo $version;?>"></script>
    </head>
    <body class="ui-mobile-viewport">
		
        <div data-role="page" id="content-page" data-url="content-page" class="ui-page ui-body-c ui-page-active">
            <div data-role="content" data-theme="c" class="ui-content" style="padding: 0px;">
                <div id="book" aria-live="off" style="background-color: white; padding: 10px;"></div>
				<div style="height:64px;"></div>
            </div>
			<?php if ($browser['isMobileDevice']) { ?>
				<div data-role="footer" data-position="fixed" class="ui-bar-c ui-footer">
					<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
						<ul>
							<li><a href="javascript:backward();"   alt="Bakover"     data-role="button" id="backward"    data-icon="custom" data-iconpos="<?php echo $iconpos;?>" accesskey="1"> </a></li>
							<li><a href="javascript:togglePlay();" alt="Start/stopp" data-role="button" id="play-pause"  data-icon="custom" data-iconpos="<?php echo $iconpos;?>" accesskey="2" class="paused"> </a></li>
							<li><a href="javascript:forward();"    alt="Fremover"    data-role="button" id="forward"     data-icon="custom" data-iconpos="<?php echo $iconpos;?>" accesskey="3"> </a></li>
							<!--li><a href="javascript:toggleMute();" alt="Lyd av/på"   data-role="button" id="mute-unmute" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" accesskey="4" class="unmuted"> </a></li-->
							<li><a href="javascript:toggleMenu();" alt="Meny"        data-role="button" id="menu"        data-icon="custom" data-iconpos="<?php echo $iconpos;?>" accesskey="5"> </a></li>
						</ul>
					</div>
				</div>
			<?php } else { ?>
				<div data-role="footer" class="ui-bar-c ui-footer" id="footer">
					<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
						<ul>
							<li id="backward">                   <input type="button" onclick="backward();"   value="Bakover"           alt="Bakover"     data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" accesskey="1" aria-disabled="false"/></li>
							<li id="play-pause"  class="paused"> <input type="button" onclick="togglePlay();" value="Start / Stopp"     alt="Start/stopp" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" accesskey="2" aria-disabled="false"/></li>
							<li id="forward">                    <input type="button" onclick="forward();"    value="Fremover"          alt="Fremover"    data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" accesskey="3" aria-disabled="false"/></li>
							<!--li id="mute-unmute" class="unmuted"><input type="button" onclick="toggleMute();" value="Lyd av / p&aring;" alt="Lyd av/på"   data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" accesskey="4" aria-disabled="false"/></li-->
							<li id="menu">                       <input type="button" onclick="toggleMenu();" value="Meny"              alt="Meny"        data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" accesskey="5" aria-disabled="false"/></li>
						</ul>
					</div>
				</div>
			<?php } ?>
        </div>
		
        <div data-role="page" id="settings-page" data-url="settings-page" class="ui-page ui-body-c">
            <div data-role="content" class="ui-content">
            <h2>Innstillinger</h2>
				<div id="settings">
					<div data-role="fieldcontain">
						<label for="volume">Volum:</label>
						<input type="range" name="volume" id="volume" value="100" min="0" max="100"/>
					</div>
					<div data-role="fieldcontain">
						<label for="autoscroll">Automatisk rulling:</label>
						<select name="autoscroll" id="autoscroll" data-role="slider">
							<option value="off">Av</option>
							<option value="on" selected="selected">P&aring;</option>
						</select>
					</div>
					<!--
						Posisjon i boken:
						&lt;span id="progressbar"&gt;&lt;/span&gt;
					-->
				</div>
				<div style="height:64px;"></div>
            </div>
            <?php if ($browser['isMobileDevice']) { ?>
				<div data-role="footer" data-position="fixed" class="ui-bar-c ui-footer">
					<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
						<ul>
							<li><a href="javascript:toggleMenu();" alt="Lukk meny"           class="exit-menu-link"     data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"> </a></li>
							<li><a href="#settings-page"           alt="Innstillinger"       class="settings-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
							<li><a href="#metadata-page"           alt="Om boken"            class="metadata-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
							<li><a href="#toc-page"                alt="Innholdsfortegnelse" class="toc-page-link"      data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
							<li><a href="#pages-page"              alt="Sideliste"           class="pages-page-link"    data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
						</ul>
					</div>
				</div>
			<?php } else { ?>
				<div data-role="footer" class="ui-bar-c ui-footer" id="footerSettings">
					<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
						<ul>
							<li class="exit-menu-link">    <input type="button" onclick="toggleMenu();"                                value="Tilbake"             alt="Lukk meny"           data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="settings-page-link"><input type="button" onclick=""                                             value="Innstillinger"       alt="Innstillinger"       data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="metadata-page-link"><input type="button" onclick="$.mobile.changePage('#metadata-page','fade');" value="Om boken"            alt="Om boken"            data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="toc-page-link">     <input type="button" onclick="$.mobile.changePage('#toc-page','fade');"      value="Innholdsfortegnelse" alt="Innholdsfortegnelse" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="pages-page-link">   <input type="button" onclick="$.mobile.changePage('#pages-page','fade');"    value="Sideliste"           alt="Sideliste"           data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
						</ul>
					</div>
				</div>
			<?php } ?>
        </div>
		
        <div data-role="page" id="metadata-page" data-url="metadata-page" class="ui-page ui-body-c">
            <div data-role="content" class="ui-content">
                <h2>Om boken</h2>
				<div id="metadata"></div>
				<div style="height:64px;"></div>
            </div>
            <?php if ($browser['isMobileDevice']) { ?>
				<div data-role="footer" data-position="fixed" class="ui-bar-c ui-footer">
					<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
						<ul>
							<li><a href="javascript:toggleMenu();" alt="Lukk meny"           class="exit-menu-link"     data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"> </a></li>
							<li><a href="#settings-page"           alt="Innstillinger"       class="settings-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
							<li><a href="#metadata-page"           alt="Om boken"            class="metadata-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
							<li><a href="#toc-page"                alt="Innholdsfortegnelse" class="toc-page-link"      data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
							<li><a href="#pages-page"              alt="Sideliste"           class="pages-page-link"    data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
						</ul>
					</div>
				</div>
			<?php } else { ?>
				<div data-role="footer" class="ui-bar-c ui-footer" id="footerMetadata">
					<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
						<ul>
							<li class="exit-menu-link">    <input type="button" onclick="toggleMenu();"                                value="Tilbake"             alt="Lukk meny"           data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="settings-page-link"><input type="button" onclick="$.mobile.changePage('#settings-page','fade');" value="Innstillinger"       alt="Innstillinger"       data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="metadata-page-link"><input type="button" onclick=""                                             value="Om boken"            alt="Om boken"            data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="toc-page-link">     <input type="button" onclick="$.mobile.changePage('#toc-page','fade');"      value="Innholdsfortegnelse" alt="Innholdsfortegnelse" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="pages-page-link">   <input type="button" onclick="$.mobile.changePage('#pages-page','fade');"    value="Sideliste"           alt="Sideliste"           data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
						</ul>
					</div>
				</div>
			<?php } ?>
        </div>
		
        <div data-role="page" id="toc-page" data-url="toc-page" class="ui-page ui-body-c">
            <div data-role="content" class="ui-content">
                <h2>Innholdsfortegnelse</h2>
				<div id="toc"></div>
				<div style="height:64px;"></div>
            </div>
            <?php if ($browser['isMobileDevice']) { ?>
				<div data-role="footer" data-position="fixed" class="ui-bar-c ui-footer">
					<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
						<ul>
							<li><a href="javascript:toggleMenu();" alt="Lukk meny"           class="exit-menu-link"     data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"> </a></li>
							<li><a href="#settings-page"           alt="Innstillinger"       class="settings-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
							<li><a href="#metadata-page"           alt="Om boken"            class="metadata-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
							<li><a href="#toc-page"                alt="Innholdsfortegnelse" class="toc-page-link"      data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
							<li><a href="#pages-page"              alt="Sideliste"           class="pages-page-link"    data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
						</ul>
					</div>
				</div>
			<?php } else { ?>
				<div data-role="footer" class="ui-bar-c ui-footer" id="footerTOC">
					<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
						<ul>
							<li class="exit-menu-link">    <input type="button" onclick="toggleMenu();"                                value="Tilbake"             alt="Lukk meny"           data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="settings-page-link"><input type="button" onclick="$.mobile.changePage('#settings-page','fade');" value="Innstillinger"       alt="Innstillinger"       data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="metadata-page-link"><input type="button" onclick="$.mobile.changePage('#metadata-page','fade');" value="Om boken"            alt="Om boken"            data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="toc-page-link">     <input type="button" onclick=""                                             value="Innholdsfortegnelse" alt="Innholdsfortegnelse" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="pages-page-link">   <input type="button" onclick="$.mobile.changePage('#pages-page','fade');"    value="Sideliste"           alt="Sideliste"           data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
						</ul>
					</div>
				</div>
			<?php } ?>
        </div>
		
        <div data-role="page" id="pages-page" data-url="pages-page" class="ui-page ui-body-c">
            <div data-role="content" class="ui-content">
                <h2>Sideliste</h2>
				<div id="pages"></div>
				<div style="height:64px;"></div>
            </div>
            <?php if ($browser['isMobileDevice']) { ?>
				<div data-role="footer" data-position="fixed" class="ui-bar-c ui-footer">
					<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
						<ul>
							<li><a href="javascript:toggleMenu();" alt="Lukk meny"           class="exit-menu-link"     data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"> </a></li>
							<li><a href="#settings-page"           alt="Innstillinger"       class="settings-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
							<li><a href="#metadata-page"           alt="Om boken"            class="metadata-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
							<li><a href="#toc-page"                alt="Innholdsfortegnelse" class="toc-page-link"      data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
							<li><a href="#pages-page"              alt="Sideliste"           class="pages-page-link"    data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"> </a></li>
						</ul>
					</div>
				</div>
			<?php } else { ?>
				<div data-role="footer" class="ui-bar-c ui-footer" id="footerPages">
					<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
						<ul>
							<li class="exit-menu-link">    <input type="button" onclick="toggleMenu();"                                value="Tilbake"             alt="Lukk meny"           data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="settings-page-link"><input type="button" onclick="$.mobile.changePage('#settings-page','fade');" value="Innstillinger"       alt="Innstillinger"       data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="metadata-page-link"><input type="button" onclick="$.mobile.changePage('#metadata-page','fade');" value="Om boken"            alt="Om boken"            data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="toc-page-link">     <input type="button" onclick="$.mobile.changePage('#toc-page','fade');"      value="Innholdsfortegnelse" alt="Innholdsfortegnelse" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
							<li class="pages-page-link">   <input type="button" onclick=""                                             value="Sideliste"           alt="Sideliste"           data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>"/></li>
						</ul>
					</div>
				</div>
			<?php } ?>
        </div>
		
		<div id="soundmanager-debug" style="display: none;"></div>
    </body>
</html>
