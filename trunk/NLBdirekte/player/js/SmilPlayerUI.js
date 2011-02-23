var server = null;
var player = null;
var loader = null;
function niceTime(s) {
	ms = Math.floor((s - Math.floor(s))*1000);
	s = Math.floor(s);
	if (ms < 10) ms = '00'+ms;
	else if (ms < 100) ms = '0'+ms;
	var m = Math.floor(s/60);
	s %= 60;
	if (s < 10) s = '0'+s;
	var h = Math.floor(m/60);
	m %= 60;
	if (m < 10 && h > 0) m = '0'+m;
	
	if (h > 0) return h+':'+m+':'+s;
	if (m > 0) return m+':'+s;
	return '0:'+s;
}
function postProcessText() {
	// Add links for skipping to paragraphs
	var stack = [];
	for (var i = (player.smil.length>1&&typeof player.smil[1]==='object')?2:1; i < player.smil.length; i++) {
		stack.push(player.smil[i]);
	}
	while (stack.length > 0) {
		var smil = stack.pop();
		if (smil.length === 1) { continue; }
		if (typeof smil[1] === 'object' && typeof smil[1]['t'] === 'string' && smil[1]['t'].split('/')[0] === 'text') {
			var id = smil[1]['s'].split('#',2);
			if (id.length === 2) {
				id = id[1];
				var element = player.textDocument.getElementById(id);
				if (element !== null) {
					element.innerHTML =
						"<a href=\"javascript:s('"+id+"');\">"+
						element.innerHTML+
						"</a>";
				}
			}
		} else {
			for (i = (smil.length>1&&typeof smil[1]==='object')?2:1; i < smil.length; i++) {
				stack.push(smil[i]);
			}
		}
	}
	
	// default stylesheet
	//CSS.load('css/Daisy202Book.css','screen',document); TODO
	
	//loadBookmarks();
}
var totalTime = Infinity;
//var myBookmarks = [];
//var currentBookmark = -1;
function playerIsLoaded() {
	if (player !== null && player.doneLoading) {

		// create table of contents
		var txt = "";
		if (typeof player.toc !== 'undefined' && player.toc !== null &&
			(player.toc.length>2||player.toc.length===2&&typeof player.toc[1]!=='object')) {
			var toc = player.toc;
			txt = "";
			var previousLevel = 0;
			for (var i = (toc.length>1&&typeof toc[1]==='object')?2:1; i < toc.length; i++) {
				// {title,level,id,begin,end}
				//var level = "";
				//for (var l = 1; l < toc[i][1]['level']; l++) { level += "&nbsp;&nbsp;&nbsp;&nbsp;"; }
				//txt += level+'<nobr><a onclick="player.skipToTime('+toc[i][1]['b']+');$(\'#menuCloseButton\').click();scrollToHighlightedText();return false;" href="">'+toc[i][1]['title']+" ("+niceTime(toc[i][1]['b'])+' - '+niceTime(toc[i][1]['e'])+')</a></nobr><br/>';
				for (var l = previousLevel; l < toc[i][1]['level']; l++) {
					txt += '<ul>';
				}
				for (var l = previousLevel; l > toc[i][1]['level']; l--) {
					txt += '</ul>';
				}
				txt += '<li class="toc-level toc-level-'+toc[i][1]['level']+'"><a onclick="player.skipToTime('+toc[i][1]['b']+');$(\'#menuCloseButton\').click();scrollToHighlightedText();return false;" href="">'+toc[i][1]['title']+" <span class='timerange'>("+niceTime(toc[i][1]['b'])+' - '+niceTime(toc[i][1]['e'])+')</span></a></li>';
				previousLevel = toc[i][1]['level'];
			}
			for (var l = previousLevel; l > 0; l--) {
				txt += '</ul>';
			}
		} else {
			txt = "<p>Boken har ingen innholdsfortegnelse</p>";
		}
		$('#toc').html(txt);
		
		// show list of pages
		if (typeof player.pagelist !== 'undefined' && player.pagelist !== null &&
			(player.pagelist.length>2||player.pagelist.length===2&&typeof player.pagelist[1]!=='object')) {
			var pagelist = player.pagelist;
			txt = "";
			for (var i = (pagelist.length>1&&typeof pagelist[1]==='object')?2:1; i < pagelist.length; i++) {
				// {page,id,begin,end}
				txt += '<span class="pagelist-entry"><a onclick="player.skipToTime('+pagelist[i][1]['b']+');$(\'#menuCloseButton\').click();scrollToHighlightedText();return false;" href="">side '+pagelist[i][1]['page']+
					" <span class='timerange'>("+niceTime(i===0?0:pagelist[i][1]['b'])+' - '+niceTime((i+1===pagelist.length)?player.getTotalTime():pagelist[i+1][1]['b'])+
					')</span></a></span><br/>';
			}
		} else {
			txt = "<p>Boken har ikke sideinformasjon</p>";
		}
		$('#pages').html(txt);
		
		totalTime = Math.max(player.getTotalTime(),1);
		if (typeof Bookmark === 'function') {
			Bookmark.loadLastmark(player.metadata[1]['dc:identifier'], function(lastmark, error) {
				if (lastmark && lastmark.startTime) {
					player.skipToTime(lastmark.startTime);
					setTimeout(scrollToHighlightedText,1000); // TODO: this only works if text is loaded within one second
				}
			});
			
			loadBookmarks();
		}
		
	} else {
		window.setTimeout(playerIsLoaded,100);
	}
}
//playerIsLoaded(); TODO

function playerHasMetadata() {
	var hasMetadata = false;
	if (player !== null && player.metadata !== null && player.metadata) {
		
		// show book information
		if (typeof player.metadata[1]['dc:title'] !== 'undefined')
			$('#bookTitle').text('Tittel: '+player.metadata[1]['dc:title']);
		
		if (typeof player.metadata[1]['dc:creator'] !== 'undefined')
			$('#bookCreator').text('Forfatter: '+player.metadata[1]['dc:creator']);
		
		if (typeof player.metadata[1]['dc:subject'] !== 'undefined')
			$('#bookSubject').text('Emne: '+player.metadata[1]['dc:subject']);
		
		if (typeof player.metadata[1]['dc:description'] !== 'undefined')
			$('#bookDescription').text('Beskrivelse: '+player.metadata[1]['dc:description']);
		
		if (typeof player.metadata[1]['dc:publisher'] !== 'undefined')
			$('#bookPublisher').text('Utgiver: '+player.metadata[1]['dc:publisher']);
		
		if (typeof player.metadata[1]['dc:date'] !== 'undefined')
			$('#bookDate').text('Dato: '+player.metadata[1]['dc:date']);
		
		if (typeof player.metadata[1]['dc:contributor'] !== 'undefined')
			$('#bookContributor').text('Bidragsytere: '+player.metadata[1]['dc:contributor']);
		
		if (typeof player.metadata[1]['dc:type'] !== 'undefined')
			$('#bookType').text('Type: '+player.metadata[1]['dc:type']);
		
		if (typeof player.metadata[1]['dc:format'] !== 'undefined')
			$('#bookFormat').text('Format: '+player.metadata[1]['dc:format']);
		
		if (typeof player.metadata[1]['dc:identifier'] !== 'undefined')
			$('#bookIdentifier').text('Identifikator: '+player.metadata[1]['dc:identifier']);
		
		if (typeof player.metadata[1]['dc:source'] !== 'undefined')
			$('#bookSource').text('Kilde: '+player.metadata[1]['dc:source']);
		
		if (typeof player.metadata[1]['dc:language'] !== 'undefined')
			$('#bookLanguage').text('Språk: '+player.metadata[1]['dc:language']);
		
		if (typeof player.metadata[1]['dc:relation'] !== 'undefined')
			$('#bookRelation').text('Relasjon: '+player.metadata[1]['dc:relation']);
		
		if (typeof player.metadata[1]['dc:coverage'] !== 'undefined')
			$('#bookCoverage').text('Dekning: '+player.metadata[1]['dc:coverage']);
		
		if (typeof player.metadata[1]['dc:rights'] !== 'undefined')
			$('#bookRights').text('Rettigheter: '+player.metadata[1]['dc:rights']);
		
	} else {
		window.setTimeout(playerHasMetadata,100);
	}
}
//playerHasMetadata(); TODO
var isAnimatingScroll = false;
function scrollToHighlightedText() {
	return;//TODO
	if (isAnimatingScroll) {
		return;
	}
	
	var element = player.getHighlightedTextElement();
	if (element === null)
		return;
	
	var position = $(element).offset();
	if (position === null)
		return;
	
	isAnimatingScroll = true;
	$("html, body").animate({
		scrollTop: (position.top-100) + "px"
	}, 500, "swing", function() {
		isAnimatingScroll = false;
	});
	//scrollTo(position.left,position.top-100);
}
var autoScroll = false;
function keepHighlightedTextOnScreen() {
	return;//TODO
	var element = player.getHighlightedTextElement();
	if (element === null)
		return;
	
	var position = $(element).offset();
	var scrollTop = $(window).scrollTop();
	var scrollBottom = scrollTop + $(window).height();
	
	if (position !== null && (position.top < scrollTop || position.top > scrollBottom-100))
		scrollToHighlightedText();
}
$(function() {
	/*$("#volume").slider({ TODO
		min: 0,
		max: 100,
		animate: true,
		value: 75,
		change: function(){
			// set player volume here
			player.setVolume( $('#volume').slider('value')/100 );
		}
	});*/
	/*$("#autoscroll_buttons").buttonset(
	).change(function() {
		switch ($('input[name="autoscroll"]:checked').val()) {
		case 'on':
			autoScroll = true;
			break;
		case 'off':
			autoScroll = false;
			break;
		}
	});*/
});
function backward() {
	player.skipToTime(player.getCurrentTime()-30);
	//scrollToHighlightedText(); TODO
	/*if (typeof Bookmark === 'function') {
		Bookmark.saveLastmark(player.metadata[1]['dc:identifier'], player.getCurrentTime(), 0,
			function(success, error) {
				//if (console) console.log('success:'+success+',error:'+error);
			}
		);
	}*/
}
function togglePlay() {
	var button = $('#play-pause');
	if (button.hasClass('paused')) {
		if (player === null) {
			if (typeof log=='object') log.warn('GUI togglePlay from paused: player not initialized');
		} else if (player.isPlaying()) {
			button.removeClass('paused');
			button.addClass('playing');
			if (typeof log=='object') log.warn('GUI togglePlay from paused: player is already playing');
		} else {
			button.removeClass('paused');
			button.addClass('playing');
			player.play();
		}
	} else {
		if (player === null) {
			if (typeof log=='object') log.warn('GUI togglePlay from playing: player not initialized');
		} else if (!player.isPlaying()) {
			button.removeClass('playing');
			button.addClass('paused');
			if (typeof log=='object') log.warn('GUI togglePlay from playing: player is already paused');
		} else {
			button.removeClass('playing');
			button.addClass('paused');
			player.pause();
		}
	}
}
function forward() {
	player.skipToTime(player.getCurrentTime()+30);
	//scrollToHighlightedText(); TODO
	/*if (typeof Bookmark === 'function') {
		Bookmark.saveLastmark(player.metadata[1]['dc:identifier'], player.getCurrentTime(), 0,
			function(success, error) {
				//if (console) console.log('success:'+success+',error:'+error);
			}
		);
	}*/
}
function toggleMute() {
	var button = $('#mute-unmute');
	if (button.hasClass('muted')) {
		button.removeClass('muted');
		button.addClass('unmuted');
		player.setVolume(100);
	} else {
		button.removeClass('unmuted');
		button.addClass('muted');
		player.setVolume(0);
	}
}
var backend = null;
window.setInterval(function(){
	if (player === null || server === null || loader === null)
		return;
	
	// make sure play/stop button is up to date on wether the player is playing
	var playButton = $('#play-pause');
	if (player.isPlaying() && playButton.hasClass('paused')) {
			playButton.removeClass('paused');
			playButton.addClass('playing');
	} else if (!player.isPlaying() && playButton.hasClass('playing')) {
			playButton.removeClass('playing');
			playButton.addClass('paused');
	}
	
	if (player.doneLoading) {
		var currentTime = player.getCurrentTime();
		var totalTime = player.getTotalTime();
		//$('#time').text(niceTime(currentTime)+' / '+niceTime(totalTime));
		//$('#progressbar').value((100*currentTime/totalTime));
		
		/* TODO: detect backend in SmilPlayer.js and log the result
		switch (player.getAudioBackend()) {
			case 'html':
				if (backend !== 'html') {
					backend = 'html';
					//$('#backend').text('<p style="text-size: 22; display: table-cell; vertical-align: middle;"><img src="img/W3C_logo.png" height="30px"/>&nbsp;HTML5</p>');
					$('#backend').attr('src','img/W3C_logo.png');
					$('#backend').attr('alt','HTML5');
				}
				break;
			case 'soundmanager':
				if (backend !== 'soundmanager') {
					backend = 'soundmanager';
					//$('#backend').text('<p style="text-size: 22; display: table-cell; vertical-align: middle;"><img src="img/SoundManager2_logo.png" height="30px"/>&nbsp;SoundManager&nbsp;2</p>');
					$('#backend').attr('src','img/SoundManager2_logo.png');
					$('#backend').attr('alt','SoundManager 2');
				}
				break;
			default:
				if (backend !== '') {
					backend = '';
					//$('#backend').text('<p style="text-size: 22; display: table-cell; vertical-align: middle;">No audio backend!</p>');
					$('#backend').attr('src','img/noaudio.png');
					$('#backend').attr('alt','Denne nettleseren støtter ikke avspilling av lyd');
				}
		}*/
		
		if (autoScroll && player.isPlaying()) {
			//keepHighlightedTextOnScreen();
		}
	}
	// TODO: update progress bar and volume
	// if wrong volume; $('#volume').progressbar('value', player.getVolume() );
},100);
window.setInterval(function(){
	return;//TODO
	if (player === null || server === null || loader === null)
		return;
	
	if (player.doneLoading) {
		if (typeof Bookmark === 'function' && player.isPlaying()) {
			// it seems we have bookmark support
			Bookmark.saveLastmark(player.metadata[1]['dc:identifier'], player.getCurrentTime(), 0,
				function(success, error) {
					//if (console) console.log('success:'+success+',error:'+error);
				}
			);
		}// else if (console) console.log('typeof Bookmark: '+typeof Bookmark+', isPlaying: '+player.isPlaying());
	}
	
},5000);
function s(id) {
	player.skipToId(id);
	player.play();
	if (typeof Bookmark === 'function') {
		Bookmark.saveLastmark(player.metadata[1]['dc:identifier'], player.getCurrentTime(), 0,
			function(success, error) {
				//if (console) console.log('success:'+success+',error:'+error);
			}
		);
	}
}

// Functions for easier use of JsonML elements:
//   isJsonML,numberOfChildren,getChild,getAttr,setAttr,deleteAttr
function isJsonML(elem) {
	// loose check
	if (typeOf(elem) !== 'array' || typeOf(elem[0]) !== 'string') {
		return false;
	}
	
	return true; // comment out for strict checking (more resource intensive)
	
	// strict
	if (elem.length > 0 && typeOf(elem[1]) !== 'object' && typeOf(elem[1]) !== 'array') return false;
	for (var i = 2; i < elem.length; i++) {
		if (typeOf(elem[i]) !== 'array') return false;
	}
	return true;
}
function numberOfChildren(elem) {
	if (!isJsonML(elem)) { return 0; }
	if (elem.length === 1) return 0;
	else return typeof elem[1] === 'object' ? elem.length-2 : elem.length-1;
}
function getChild(elem, nr) {
	if (!isJsonML(elem)) { return null; }
	if (elem.length === 1) return null;
	if (typeof elem[1] === 'object') {
		if (elem.length === 2) return null;
		return elem[nr+2];
	}
	return elem[nr+1];
}
function getAttr(elem, attr, def) {
	if (!isJsonML(elem)) { return def; }
	if (elem.length === 1 || typeof elem[1] !== 'object' || typeof elem[1][attr] === 'undefined') return def;
	return elem[1][attr];
}
function setAttr(elem, attr, val) {
	if (!isJsonML(elem)) { return false; }
	if (elem.length === 1 || typeof elem[1] !== 'object') {
		elem.splice(1,0,{attr:val});
	} else {
		elem[attr] = val;
	}
	return true;
}
function deleteAttr(elem, attr) {
	if (!isJsonML(elem)) { return false; }
	if (elem.length === 1 || typeof elem[1] !== 'object') return false;
	delete elem[1][attr];
	return true;
}
// improves upon the typeof operator by recognizing 'array' and 'null' as well
// http://javascript.crockford.com/remedial.html
function typeOf(value) {
	var s = typeof value;
	if (s === 'object') {
		if (value) {
			if (value instanceof Array) {
				s = 'array';
			}
		} else {
			s = 'null';
		}
	}
	return s;
}

function init() {
	server = new NLBServer('ticket='+ticket+'&launchTime='+launchTime);	// authorization, downloading of files
	player = new SmilPlayer();											// playback of SMIL filesets
	loader = new Daisy202Loader();										// loading and parsing SMIL-files from the server into the player
	
	if (typeof serverUrl !== 'undefined')
		server.url = serverUrl;
	else
		server.url = 'http://'+window.location.host+'/NLBdirekte/player/'; // default location of NLBdirekte
	
	//Bookmark.scriptUrl = 'http://'+window.location.host+'/NLBdirekte/patrondata/bookmarks.php';
	
	loader.player = player;
	loader.server = server;
	
	player.loader = loader;
	player.server = server;
	
	loader.load();
	player.textDocument = document;
	player.textElement = document.getElementById('book');
	player.postProcessText = postProcessText;
	
	document.body.focus();
}
if (typeof window.onload != "function") {
        window.onload = init;
} else {
        var oldonload = window.onload;
        window.onload = function(evt) {
                if (oldonload) oldonload(evt);
                init(evt);
        };
}