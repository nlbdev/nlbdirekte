<?php
header('Content-Type: text/html; charset=utf-8');

include('common.inc.php');

list($user, $book) = decodeTicket($_REQUEST['ticket']);
authorize($user,$book,isset($_REQUEST['session'])?$_REQUEST['session']:'');

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

?><!doctype html>
<html class="ui-mobile landscape min-width-320px min-width-480px min-width-768px min-width-1024px">
    <head>
		<!-- Page metadata -->
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <base href="." />
        <meta name="viewport" content="width=device-width, minimum-scale=1, maximum-scale=1" />
        <meta charset="utf-8" />
		<title>NLBdirekte v<?php echo $version;?></title>
		<link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon" />
		
		<!-- The code recieved from the library system, used to validate the session -->
		<script type="text/javascript" charset="utf-8">
		/* <![CDATA[ */
			var ticket = '<?php echo $_REQUEST['ticket']; ?>';
			var launchTime = '<?php echo $launchTime; ?>';
		/* ]]> */
		</script>
		
		<script type="text/javascript">
		<!-- strip eventual fragment identifier through redirection -->
		if (window.location.toString().indexOf('#') >= 0) {
			var newLocation = window.location.toString().substring(0,window.location.toString().indexOf('#'));
			if (newLocation.indexOf('?') >= 0)
				newLocation += '&';
			else
				newLocation += '?';
			newLocation += 'launchTime='+launchTime;
			window.location = newLocation;
		}
		</script>
		
		<!-- jQuery Mobile -->
		<link rel="stylesheet" href="css/jQuery/jquery.mobile-1.0a4.1<?php echo $debug?'':'min.';?>.css" />
		<script type="text/javascript" src="js/jQuery/jquery-1.5.2<?php echo $debug?'':'min.';?>.js"></script>
		<script type="text/javascript" src="js/jQuery/jquery.mobile-1.0a4.1<?php echo $debug?'':'min.';?>.js"></script>
		
		<!-- NLBdirekte; stylesheets and configuration -->
		<link type="text/css" href="css/NLBdirekte.css" rel="stylesheet" />
		<link type="text/css" href="css/Daisy202Book.css" rel="stylesheet" />
		<script type="text/javascript" src="js/common.js"></script>
		<script type="text/javascript" src="config/config.js"></script>
		
		<!-- Logging framework -->
		<script type="text/javascript" src="js/javascript-stacktrace/stacktrace.js"></script>
		<script type="text/javascript" src="js/log4javascript/log4javascript.js"></script>
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
		<script type="text/javascript" src="js/soundmanager/script/soundmanager2.js"></script>
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
		<script src='js/NLBServer.js'></script>
		<script src='js/Daisy202Loader.js'></script>
		<script src='js/SmilPlayer.js'></script>
		
		<!-- Bookmarks synchronization -->
		<script type="text/javascript" src="js/Bookmarks.js"></script>
		
		<!-- (loads the player, updates the graphics etc.) -->
		<script type="text/javascript" src="js/SmilPlayerUI.js"></script>
		
		<script type="text/javascript">
			var lastMenuPage = 'settings-page';
			function toggleMenu() {
				if ($.mobile.activePage[0].id==='content-page') {
					$.mobile.changePage(lastMenuPage,"slide");
					log.debug('toggled page from content-page to '+lastMenuPage);
				} else {
					lastMenuPage = $.mobile.activePage[0].id;
					$.mobile.changePage('content-page',"slide",true);
					log.debug('toggled page from '+lastMenuPage+' to content-page');
				}
			}
		</script>
    </head>
    <body class="ui-mobile-viewport">
		
        <div data-role="page" id="content-page" data-url="content-page" class="ui-page ui-body-c ui-page-active">
            <div data-role="content" data-theme="c" class="ui-content" style="padding: 0px;">
                <div id="book" aria-live="off" style="background-color: white; padding: 10px;"></div>
				<div style="height:64px;"></div>
            </div>
			<?php if ($browser['isMobileDevice']) { ?>
			<div data-role="footer" data-position="fixed" class="ui-bar-c ui-footer">
			<?php } else { ?>
			<div data-role="footer" class="ui-bar-c ui-footer" id="footer">
			<?php } ?>
				<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
					<ul>
						<li><a href="javascript:backward();" alt="Bakover" data-role="button" id="backward" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" accesskey="1"><?php if (!($browser['isMobileDevice'])) echo "Bakover";?></a></li>
						<li><a href="javascript:togglePlay();" alt="Start/stopp" data-role="button" id="play-pause" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" class="paused" accesskey="2"><?php if (!($browser['isMobileDevice'])) echo "Start / Stopp";?></a></li>
						<li><a href="javascript:forward();" alt="Fremover" data-role="button" id="forward" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" accesskey="3"><?php if (!($browser['isMobileDevice'])) echo "Fremover";?></a></li>
						<li><a href="javascript:toggleMute();" alt="Lyd av/på" data-role="button" id="mute-unmute" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" class="unmuted" accesskey="4"><?php if (!($browser['isMobileDevice'])) echo "Lyd av / p&aring;";?></a></li>
						<li><a href="javascript:toggleMenu();" alt="Meny" data-role="button" id="menu" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="slideup" accesskey="5"><?php if (!($browser['isMobileDevice'])) echo "Meny";?></a></li>
					</ul>
				</div>
            </div>
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
							<option value="on">P&aring;</option>
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
			<?php } else { ?>
			<div data-role="footer" class="ui-bar-c ui-footer" id="footerSettings">
			<?php } ?>
           		<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
					<ul>
						<li><a href="javascript:toggleMenu();" alt="Lukk meny" class="exit-menu-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="slidedown"><?php if (!($browser['isMobileDevice'])) echo "Tilbake";?></a></li>
						<li><a href="#settings-page" alt="Innstillinger" class="settings-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"><?php if (!($browser['isMobileDevice'])) echo "Innstillinger";?></a></li>
						<li><a href="#metadata-page" alt="Om boken" class="metadata-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade" class="paused"><?php if (!($browser['isMobileDevice'])) echo "Om boken";?></a></li>
						<li><a href="#toc-page" alt="Innholdsfortegnelse" class="toc-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"><?php if (!($browser['isMobileDevice'])) echo "Innholdsfortegnelse";?></a></li>
						<li><a href="#pages-page" alt="Sideliste" class="pages-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade" class="unmuted"><?php if (!($browser['isMobileDevice'])) echo "Sideliste";?></a></li>
					</ul>
				</div>
            </div>
        </div>
		
        <div data-role="page" id="metadata-page" data-url="metadata-page" class="ui-page ui-body-c">
            <div data-role="content" class="ui-content">
                <h2>Om boken</h2>
				<div id="metadata"></div>
				<div style="height:64px;"></div>
            </div>
            <?php if ($browser['isMobileDevice']) { ?>
			<div data-role="footer" data-position="fixed" class="ui-bar-c ui-footer">
			<?php } else { ?>
			<div data-role="footer" class="ui-bar-c ui-footer" id="footerMetadata">
			<?php } ?>
           		<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
					<ul>
						<li><a href="javascript:toggleMenu();" alt="Lukk meny" class="exit-menu-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="slidedown"><?php if (!($browser['isMobileDevice'])) echo "Tilbake";?></a></li>
						<li><a href="#settings-page" alt="Innstillinger" class="settings-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"><?php if (!($browser['isMobileDevice'])) echo "Innstillinger";?></a></li>
						<li><a href="#metadata-page" alt="Om boken" class="metadata-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade" class="paused"><?php if (!($browser['isMobileDevice'])) echo "Om boken";?></a></li>
						<li><a href="#toc-page" alt="Innholdsfortegnelse" class="toc-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"><?php if (!($browser['isMobileDevice'])) echo "Innholdsfortegnelse";?></a></li>
						<li><a href="#pages-page" alt="Sideliste" class="pages-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade" class="unmuted"><?php if (!($browser['isMobileDevice'])) echo "Sideliste";?></a></li>
					</ul>
				</div>
            </div>
        </div>
		
        <div data-role="page" id="toc-page" data-url="toc-page" class="ui-page ui-body-c">
            <div data-role="content" class="ui-content">
                <h2>Innholdsfortegnelse</h2>
				<div id="toc"></div>
				<div style="height:64px;"></div>
            </div>
            <?php if ($browser['isMobileDevice']) { ?>
			<div data-role="footer" data-position="fixed" class="ui-bar-c ui-footer">
			<?php } else { ?>
			<div data-role="footer" class="ui-bar-c ui-footer" id="footerTOC">
			<?php } ?>
           		<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
					<ul>
						<li><a href="javascript:toggleMenu();" alt="Lukk meny" class="exit-menu-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="slidedown"><?php if (!($browser['isMobileDevice'])) echo "Tilbake";?></a></li>
						<li><a href="#settings-page" alt="Innstillinger" class="settings-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"><?php if (!($browser['isMobileDevice'])) echo "Innstillinger";?></a></li>
						<li><a href="#metadata-page" alt="Om boken" class="metadata-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade" class="paused"><?php if (!($browser['isMobileDevice'])) echo "Om boken";?></a></li>
						<li><a href="#toc-page" alt="Innholdsfortegnelse" class="toc-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"><?php if (!($browser['isMobileDevice'])) echo "Innholdsfortegnelse";?></a></li>
						<li><a href="#pages-page" alt="Sideliste" class="pages-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade" class="unmuted"><?php if (!($browser['isMobileDevice'])) echo "Sideliste";?></a></li>
					</ul>
				</div>
            </div>
        </div>
		
        <div data-role="page" id="pages-page" data-url="pages-page" class="ui-page ui-body-c">
            <div data-role="content" class="ui-content">
                <h2>Sideliste</h2>
				<div id="pages"></div>
				<div style="height:64px;"></div>
            </div>
            <?php if ($browser['isMobileDevice']) { ?>
			<div data-role="footer" data-position="fixed" class="ui-bar-c ui-footer">
			<?php } else { ?>
			<div data-role="footer" class="ui-bar-c ui-footer" id="footerPages">
			<?php } ?>
           		<div data-role="navbar" role="navigation" class="nav-nlbdirekte">
					<ul>
						<li><a href="javascript:toggleMenu();" alt="Lukk meny" class="exit-menu-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="slidedown"><?php if (!($browser['isMobileDevice'])) echo "Tilbake";?></a></li>
						<li><a href="#settings-page" alt="Innstillinger" class="settings-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"><?php if (!($browser['isMobileDevice'])) echo "Innstillinger";?></a></li>
						<li><a href="#metadata-page" alt="Om boken" class="metadata-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade" class="paused"><?php if (!($browser['isMobileDevice'])) echo "Om boken";?></a></li>
						<li><a href="#toc-page" alt="Innholdsfortegnelse" class="toc-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade"><?php if (!($browser['isMobileDevice'])) echo "Innholdsfortegnelse";?></a></li>
						<li><a href="#pages-page" alt="Sideliste" class="pages-page-link" data-role="button" data-icon="custom" data-iconpos="<?php echo $iconpos;?>" data-transition="fade" class="unmuted"><?php if (!($browser['isMobileDevice'])) echo "Sideliste";?></a></li>
					</ul>
				</div>
            </div>
        </div>
		
		<div id="soundmanager-debug" style="display: none;"></div>
    </body>
</html>
