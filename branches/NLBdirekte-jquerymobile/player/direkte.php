<?php
header('Content-Type: text/html; charset=utf-8');

include('common.inc.php');

session_start();
if (!empty($_REQUEST['username'])) {
	$_SESSION['patronId'] = '';
	for ($i = 0; $i < strlen($_REQUEST['username']) && $i < 4; $i++) {
		$_SESSION['patronId'] .= str_pad((string)(ord($_REQUEST['username'][$i])-32), 2, "0", STR_PAD_LEFT);
	}
}

?><!doctype html>
<html>
<head>
	<!-- Page metadata -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no" />
	<meta charset="utf-8" />
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>NLBdirekte v<?php echo $version;?></title>
	<link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon" />

	<!-- jQuery Mobile -->
	<link rel="stylesheet" href="css/jQuery/jquery.mobile-1.0a3pre.min.css" />
	<script type="text/javascript" src="js/jQuery/jquery-1.5.min.js"></script>
	<script type="text/javascript" src="js/jQuery/jquery.mobile-1.0a3pre.min.js"></script>

	<!-- Debug mechanism -->
	<script type='text/javascript' src='https://damnit.jupiterit.com/damnit.js?f4beb70f446e2e2ff2f26681e39a3bb5c533df1b'></script>

	<!-- NLBdirekte; stylesheet and configuration -->
	<link type="text/css" href="css/NLBdirekte.css" rel="stylesheet" />
	<script type="text/javascript" src="config/config.js"></script>

	<!-- Easy fetching (and sending if needed) of JSON data structures -->
	<script type='text/javascript' src='js/JSON/json2.js'></script>
	<script type='text/javascript' src='js/JSON/JSONRequest.js'></script>
	<script type='text/javascript' src='js/JSON/JSONRequestError.js'></script>

	<!-- Provides a fallback mechanism for HTML5 Audio to SoundManager 2 -->
	<script type="text/javascript" src="js/HTML5AudioNow/HTML5AudioNow.js"></script>

	<!-- The player objects.
		The server is an interface to a specific server that resolves URLs amongst other things.
		The loader is an interface for a specific format that parses books into the player
		The player is the object that handles the playback logic, including synchronization -->
	<script src='js/NLBServer.js'></script>
	<script src='js/Daisy202Loader.js'></script>
	<script src='js/SmilPlayer.js'></script>
	
	<!-- Bookmarks synchronization -->
	<!--script type="text/javascript" src="js/Bookmarks.js" ></script-->
	
	<!-- The code recieved from the library system, used to validate the session -->
	<script type="text/javascript" charset="utf-8">
	/* <![CDATA[ */
		var ticket = '<?php echo $_REQUEST['ticket']; ?>';
	/* ]]> */
	</script>
	
	<!-- Site-specific code for loading the player, updating the gui etc. -->
	<script type="text/javascript" src="js/SmilPlayerUI.js"></script>
</head>
<body>

<div data-role="page" id="content-page">

	<div data-role="content" id="book"></div>
	
	<div data-role="footer" data-position="fixed" data-theme="d">
		<div data-role="navbar" class="nav-nlbdirekte">
			<ul>
				<li><a href="#" data-role="button" id="backward" data-icon="custom" data-iconpos="notext" accesskey="1"></a></li>
				<li><a href="#" data-role="button" id="play-pause" data-icon="custom" data-iconpos="notext" class="paused" accesskey="2"></a></li>
				<li><a href="#" data-role="button" id="forward" data-icon="custom" data-iconpos="notext" accesskey="3"></a></li>
				<li><a href="#" data-role="button" id="mute-unmute" data-icon="custom" data-iconpos="notext" class="unmuted" accesskey="4"></a></li>
				<li><a href="#settings-page" data-role="button" id="menu" data-icon="custom" data-iconpos="notext" data-transition="slidedown" accesskey="5"></a></li>
			</ul>
		</div>
	</div>
	
</div><!-- /page (#content-page) -->

<!--div data-role="page" id="settings-page">

	<div data-role="content">
		Innstillinger-->
		<!--div style="min-width: 750px; max-width: 800px; margin:0 auto; border-width: 1px; border-style: dotted;">
			<div style="text-align: center; margins: 0 auto;"><button id="menuCloseButton" class="flip">Tilbake</button></div>
			<div class="ui-tabs ui-widget ui-widget-content" id="menuTabs">
				<div style="text-align: right;" class="centered">
				<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all" id="menuTab-navigation" style="text-align: center;">
					<li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="#menuTab-bookinfo">Om boken</a></li>
					<li class="ui-state-default ui-corner-top"><a href="#menuTab-settings" id="menuTab-settings-button">Alternativer</a></li>
					<li class="ui-state-default ui-corner-top"><a href="#menuTab-contents" id="menuTab-contents-button">Innholdsfortegnelse</a></li>
					<li class="ui-state-default ui-corner-top"><a href="#menuTab-pages" id="menuTab-pages-button">Sidetall</a></li>
				</ul>
				</div>
				<div class="ui-tabs-panel ui-widget-content ui-corner-bottom" id="menuTab-bookinfo">
					<img id="backend" src="#" width="40" height="40" style="vertical-align: top;"/><br/>
					<div id="bookTitle"></div>
					<div id="bookCreator"></div>
					<div id="bookSubject"></div>
					<div id="bookDescription"></div>
					<div id="bookPublisher"></div>
					<div id="bookContributor"></div>
					<div id="bookDate"></div>
					<div id="bookType"></div>
					<div id="bookFormat"></div>
					<div id="bookIdentifier"></div>
					<div id="bookSource"></div>
					<div id="bookLanguage"></div>
					<div id="bookRelation"></div>
					<div id="bookCoverage"></div>
					<div id="bookRights"></div>
				</div>
				<div class="ui-tabs-panel ui-widget-content ui-corner-bottom" id="menuTab-settings">
					Volum:
					<div id="volume"></div>
					<br/>
					
					Automatisk rulling:
					<span id="autoscroll_buttons">
						<input type="radio" id="autoscroll_on" name="autoscroll" value="on" checked="checked" /><label for="autoscroll_on">På</label>
						<input type="radio" id="autoscroll_off" name="autoscroll" value="off" /><label for="autoscroll_off">Av</label>
					</span>
					
					Posisjon i boken:
					&lt;span id="progressbar"&gt;&lt;/span&gt;
				</div>
				<div class="ui-tabs-panel ui-widget-content ui-corner-bottom" id="menuTab-contents">
					<div id="toc"></div>
				</div>
				<div class="ui-tabs-panel ui-widget-content ui-corner-bottom" id="menuTab-pages">
					<div id="pages"></div>
				</div>
			</div>
		</div-->
	<!--/div>
	
	<div data-role="footer" data-position="fixed" data-theme="d">
		<div data-role="navbar" class="nav-nlbdirekte">
			<a href="#" data-role="button" class="settingsQ" data-icon="custom" data-iconpos="notext" data-transition="fade" data-theme="b"></a>
			<a href="#metadata-page" data-role="button" class="metadata" data-icon="custom" data-iconpos="notext" data-transition="fade"></a>
			<a href="#toc-page" data-role="button" class="toc" data-icon="custom" data-iconpos="notext" data-transition="fade"></a>
			<a href="#pages-page" data-role="button" class="pages" data-icon="custom" data-iconpos="notext" data-transition="fade"></a>
			<a href="#content-page" data-role="button" class="menu-exit" data-icon="custom" data-iconpos="notext" data-transition="slideup"></a>
		</div>
	</div>
	
</div--><!-- /page (#settings-page) -->

<div data-role="page" id="metadata-page">
	
	<div data-role="content">
		Metadata
	</div>
	
	<div data-role="footer" data-position="fixed" data-theme="d" class="nav-nlbdirekte">
		<div data-role="navbar" class="nav-nlbdirekte">
			<a href="#settings-page" data-role="button" class="settingsQ" data-icon="custom" data-iconpos="notext" data-transition="fade"></a>
			<a href="#" data-role="button" class="metadata" data-icon="custom" data-iconpos="notext" data-transition="fade" data-theme="b"></a>
			<a href="#toc-page" data-role="button" class="toc" data-icon="custom" data-iconpos="notext" data-transition="fade"></a>
			<a href="#pages-page" data-role="button" class="pages" data-icon="custom" data-iconpos="notext" data-transition="fade"></a>
			<a href="#content-page" data-role="button" class="menu-exit" data-icon="custom" data-iconpos="notext" data-transition="slideup"></a>
		</div>
	</div>
	
</div>

<div data-role="page" id="toc-page">
	
	<div data-role="content">
		TOC
	</div>
	
	<div data-role="footer" data-position="fixed" data-theme="d" class="nav-nlbdirekte">
		<div data-role="navbar" class="nav-nlbdirekte">
			<a href="#settings-page" data-role="button" class="settingsQ" data-icon="custom" data-iconpos="notext" data-transition="fade"></a>
			<a href="#metadata-page" data-role="button" class="metadata" data-icon="custom" data-iconpos="notext" data-transition="fade"></a>
			<a href="#" data-role="button" class="toc" data-icon="custom" data-iconpos="notext" data-transition="fade" data-theme="b"></a>
			<a href="#pages-page" data-role="button" class="pages" data-icon="custom" data-iconpos="notext" data-transition="fade"></a>
			<a href="#content-page" data-role="button" class="menu-exit" data-icon="custom" data-iconpos="notext" data-transition="slideup"></a>
		</div>
	</div>
	
</div>

<div data-role="page" id="pages-page">
	
	<div data-role="content">
		Sideliste
	</div>
	
	<div data-role="footer" data-position="fixed" data-theme="d" class="nav-nlbdirekte">
		<div data-role="navbar" class="nav-nlbdirekte">
			<a href="#settings-page" data-role="button" class="settingsQ" data-icon="custom" data-iconpos="notext" data-transition="fade"></a>
			<a href="#metadata-page" data-role="button" class="metadata" data-icon="custom" data-iconpos="notext" data-transition="fade"></a>
			<a href="#toc-page" data-role="button" class="toc" data-icon="custom" data-iconpos="notext" data-transition="fade"></a>
			<a href="#" data-role="button" class="pages" data-icon="custom" data-iconpos="notext" data-transition="fade" data-theme="b"></a>
			<a href="#content-page" data-role="button" class="menu-exit" data-icon="custom" data-iconpos="notext" data-transition="slideup"></a>
		</div>
	</div>
	
</div>

</body>
</html>