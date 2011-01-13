<?php
header('Content-Type: text/html; charset=utf-8');

session_start();
if (!empty($_REQUEST['username'])) {
	$_SESSION['patronId'] = '';
	for ($i = 0; $i < strlen($_REQUEST['username']) && $i < 4; $i++) {
		$_SESSION['patronId'] .= str_pad((string)(ord($_REQUEST['username'][$i])-32), 2, "0", STR_PAD_LEFT);
	}
}

?>
<!doctype html>
<html lang="no">
<head>
	<title>NLBdirekte</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon" />
	
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
	<script src='js/StandardServer.js'></script>
	<script src='js/Daisy202Loader.js'></script>
	<script src='js/SmilPlayer.js'></script>
	
	<style type="text/css">
	* html .centered { position:absolute } /* position fixed for IE 6 */
	/*#controls {
		padding: 10px 4px;
	}*/
	</style>
	
	<!-- jQuery -->
	<link type="text/css" href="css/jQuery/smoothness/jquery-ui-1.8.custom.css" rel="stylesheet" />
	<script type="text/javascript" src="js/jQuery/jquery-1.4.2.min.js"></script>
	<script type="text/javascript" src="js/jQuery/jquery-ui-1.8.custom.min.js"></script>
	
	<!-- Bookmarks synchronization -->
	<script type="text/javascript" src="js/Bookmarks.js" ></script>
	
	<!-- Site-specific code for loading the player, updating the gui etc. -->
	<script type="text/javascript" src="js/SmilPlayerUI.js"></script>
	
	<script type="text/javascript" charset="utf-8">
	/* <![CDATA[ */
		var bookId = '<?php echo $_REQUEST['bookId']; ?>';
	/* ]]> */
	</script>
	
	<style>
	/* jQuery centered tabs: http://osdir.com/ml/jquery-ui/2009-04/msg00472.html */
	.ui-tabs .ui-tabs-nav { float: none; text-align: center; }
	.ui-tabs .ui-tabs-nav li { float: none; display: inline; }
	.ui-tabs .ui-tabs-nav li a { float: none; }
	
#soundmanager-debug {
 position:fixed;
 bottom:1em;
 right:1em;
 width:38em;
 height:30em;
 overflow:auto;
 padding:0px;
 margin:1em;
 font-family:monaco,"VT-100",terminal,"lucida console",courier,system;
 opacity:0.9;
 color:#333;
 border:1px solid #ccddee;
 -moz-border-radius:3px;
 -khtml-border-radius:3px;
 -webkit-border-radius:3px;
 background:#f3f9ff;
}

/*
#soundmanager-debug code {
 font-size:1.1em;
 *font-size:1em;
}
*/

#soundmanager-debug div {
 font-size:x-small;
 padding:0.2em;
 margin:0px;
}
	</style>
	
</head>
<body>
	<div>
		<div id="controls" class='ui-widget centered' style="position: fixed; top: 0%; width: 100%; text-align: center;">
			<div id="controlButtons">
				<button id="back">tilbake</button>
				<button id="play">spill av</button>
				<button id="forward">fremover</button>
				<button id="mute">demp</button>
				<button id="bookmark">sett bokmerke</button>
				<button id="menuOpenButton">åpne meny</button>
			</div>
		</div>
		<div id="book" style="height: 100%; margin-top: 100px;"></div>
		<div id="menu" class="centered ui-widget ui-widget-content ui-widget-overlay" style="position: fixed; overflow: auto; opacity:1.0; filter:alpha(opacity=100)">
			<div style="min-width: 750px; max-width: 800px; margin:0 auto; border-width: 1px; border-style: dotted;">
				<div style="text-align: center; margins: 0 auto;"><button id="menuCloseButton" class="flip">Tilbake</button></div>
				<div class="ui-tabs ui-widget ui-widget-content" id="menuTabs">
					<div style="text-align: right;" class="centered">
					<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all" id="menuTab-navigation" style="text-align: center;">
						<li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="#menuTab-bookinfo">Om boken</a></li>
						<li class="ui-state-default ui-corner-top"><a href="#menuTab-settings" id="menuTab-settings-button">Alternativer</a></li>
						<li class="ui-state-default ui-corner-top"><a href="#menuTab-contents" id="menuTab-contents-button">Innholdsfortegnelse</a></li>
						<li class="ui-state-default ui-corner-top"><a href="#menuTab-pages" id="menuTab-pages-button">Sidetall</a></li>
						<li class="ui-state-default ui-corner-top"><a href="#menuTab-bookmarks" id="menuTab-bookmarks-button">Bokmerker</a></li>
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
						
						<!--
						Posisjon i boken:
						<span id="progressbar"></span>
						-->
					</div>
					<div class="ui-tabs-panel ui-widget-content ui-corner-bottom" id="menuTab-contents">
						<div id="toc"></div>
					</div>
					<div class="ui-tabs-panel ui-widget-content ui-corner-bottom" id="menuTab-pages">
						<div id="pages"></div>
					</div>
					<div class="ui-tabs-panel ui-widget-content ui-corner-bottom" id="menuTab-bookmarks">
						<div id="bookmarkMessageBox" style="text-align: center; font-weigth: bold; background-color: #FFFF88;"></div>
						<div id="editBookmark">
							Tittel:
							<br/>
							<input type="text" id="bookmarkTitle" style="width: 700px;" />
							<br/><br/>
							
							Brødtekst:
							<textarea id="bookmarkText" rows="8" style="width: 700px;" ></textarea>
							<br/><br/>
							
							Posisjon i boken:
							<input type="text" id="bookmarkTime" />
							<button id="bookmarkTimeNow">Flytt til nåværende posisjon</button>
							<br/><br/>
							
							<div id="bookmarkEndEdit_buttons" style="text-align: center">
								<button id="bookmarkEndEdit_save" value="save">Lagre</button>
								<button id="bookmarkEndEdit_cancel" value="cancel">Avbryt</button>
							</div>
							
							<br/>
							<hr/>
							<br/>
						</div>
						<div id="deleteBookmarkConfirmation">
							Er du sikker på at du vil slette dette bokmerket?
							<div id="deleteBookmarkConfirmation_buttons" style="text-align: center">
								<button id="deleteBookmarkConfirmation_yes" value="yes">Ja, slett</button>
								<button id="deleteBookmarkConfirmation_no" value="no">Nei, ikke slett</button>
							</div>
							
							<br/>
							<hr/>
							<br/>
						</div>
						<div id="bookmarks"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!--div id="soundmanager-debug" style="display: block;"></div-->
</body>
</html>