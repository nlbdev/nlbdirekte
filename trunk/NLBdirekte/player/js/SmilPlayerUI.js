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
function resizeImage(img,maxWidth,maxHeight) {
	var width = Math.min($(img).attr('data-original-width'),maxWidth);
	var height = Math.min($(img).attr('data-original-height'),maxHeight);
	var originalRatio = $(img).attr('data-original-height')/$(img).attr('data-original-width');
	var newRatio = height/width;
	if (newRatio > originalRatio + 0.01) {
		// cut down on the height
		height = width*originalRatio;
	} else if (newRatio < originalRatio - 0.01) {
		// cut down on the width
		width = height/originalRatio;
	}
	$(img).css('max-width',width);
	$(img).css('max-height',height);
}
$(window).resize(function() {
	var maxWidth = Math.floor($('#book').width()*0.95);
	var maxHeight = Math.floor($(window).height()*0.6);
	$('img.book-image').each(function(){resizeImage($(this),maxWidth,maxHeight);});
});
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
				var element = $(player.textDocument).find('#'+id);
				if (element.length > 0) {
					$(element).html(
						"<a href=\"javascript:s('"+id+"');\">"+
						$(element).html()+
						"</a>"
					);
				}
			}
		} else {
			for (i = (smil.length>1&&typeof smil[1]==='object')?2:1; i < smil.length; i++) {
				stack.push(smil[i]);
			}
		}
	}
	
	$(player.textDocument).find('img').each(function(){$(this).load(function() {
		$(this).addClass('book-image');
		$(this).attr('data-original-width',$(this).width());
		$(this).attr('data-original-height',$(this).height());
		var maxWidth = Math.floor($('#book').width()*0.95);
		var maxHeight = Math.floor($(window).height()*0.6);
		resizeImage($(this),maxWidth,maxHeight);
	});});
	
	//loadBookmarks();
}
var totalTime = Infinity;
//var myBookmarks = [];
//var currentBookmark = -1;
function playerIsLoaded() {
	if (player !== null && player.doneLoading) {
		if (typeof log=='object') log.debug('player is loaded');

		// create table of contents
		var txt = "";
		if (typeof player.toc !== 'undefined' && player.toc !== null &&
			(player.toc.length>2||player.toc.length===2&&$.isArray(player.toc[1]))) {
			var toc = player.toc;
			txt = "";
			var previousLevel = 0;
			for (var i = $.isArray(toc[1])?1:2; i < toc.length; i++) {
				// {title,level,id,begin,end}
				for (var l = previousLevel; l < toc[i][1]['level']; l++) {
					txt += "<ul>\n";
				}
				for (var l = previousLevel; l > toc[i][1]['level']; l--) {
					txt += "</ul>\n";
				}
				txt += '<li class="toc-level toc-level-'+toc[i][1]['level']+'"><a href="javascript:player.skipToTime('+toc[i][1]['b']+');scrollToHighlightedText();" data-role="button">'+toc[i][1]['title']+" <small class='timerange'>("+niceTime(toc[i][1]['b'])+' - '+niceTime(toc[i][1]['e'])+')</small></a></li>'+"\n";
				previousLevel = toc[i][1]['level'];
			}
			for (var l = previousLevel; l > 0; l--) {
				txt += "</ul>\n";
			}
		} else {
			txt = "<p>Boken har ingen innholdsfortegnelse</p>";
		}
		$('#toc').html(txt);
		
		// show list of pages
		if (typeof player.pagelist !== 'undefined' && player.pagelist !== null &&
			(player.pagelist.length>2||player.pagelist.length===2&&$.isArray(player.pagelist[1]))) {
			var pagelist = player.pagelist;
			txt = "";
			for (var i = $.isArray(pagelist[1])?1:2; i < pagelist.length; i++) {
				// {page,id,begin,end}
				txt += "<a href='javascript:player.skipToTime("+pagelist[i][1]['b']+");scrollToHighlightedText();' data-role='button' data-inline='true' class='pagelist-entry'>"+
						pagelist[i][1]['page']+" <small class='timerange'>("+niceTime(i===0?0:pagelist[i][1]['b'])+" - "+
						niceTime((i+1===pagelist.length)?player.getTotalTime():pagelist[i+1][1]['b'])+")</small></a>\n";
			}
		} else {
			if (typeof log=='object') log.info('no page information in book');
			txt = "<p>Boken har ikke sideinformasjon</p>";
		}
		$('#pages').html(txt);
		
		totalTime = Math.max(player.getTotalTime(),1);
		if (typeof Bookmark === 'function') {
			var that = this;
			log.debug('getting lastmark...');
			Bookmark.getLastmark(function(lastmark) {
				log.debug('running callback for getLastmark...');
				log.debug(lastmark);
				if (lastmark && lastmark.position) {
					player.skipToTime(lastmark.position);
					setTimeout(scrollToHighlightedText,1000);
				}
			});
			//loadBookmarks();
		}
		
	} else {
		window.setTimeout(playerIsLoaded,100);
	}
}
playerIsLoaded();

function playerHasMetadata() {
	var hasMetadata = false;
	if (player !== null && player.metadata !== null && player.metadata) {
		// show book information
		var m = player.metadata[1];
		var txt = "<table>";
		
		if (typeof m['dc:title'] !== 'undefined')
			txt += "<tr><td>Tittel:</td><td>"+m['dc:title']+"</td></tr>\n";
		
		if (typeof player.metadata[1]['dc:creator'] !== 'undefined')
			txt += "<tr><td>Forfatter:</td><td>"+m['dc:creator']+"</td></tr>\n";
		
		if (typeof player.metadata[1]['dc:subject'] !== 'undefined')
			txt += "<tr><td>Emne:</td><td>"+m['dc:subject']+"</td></tr>\n";
		
		if (typeof player.metadata[1]['dc:description'] !== 'undefined')
			txt += "<tr><td>Beskrivelse:</td><td>"+m['dc:description']+"</td></tr>\n";
		
		if (typeof player.metadata[1]['dc:publisher'] !== 'undefined')
			txt += "<tr><td>Utgiver:</td><td>"+m['dc:publisher']+"</td></tr>\n";
		
		if (typeof player.metadata[1]['dc:date'] !== 'undefined')
			txt += "<tr><td>Dato:</td><td>"+m['dc:date']+"</td></tr>\n";
		
		if (typeof player.metadata[1]['dc:contributor'] !== 'undefined')
			txt += "<tr><td>Bidragsytere:</td><td>"+m['dc:contributor']+"</td></tr>\n";
		
		if (typeof player.metadata[1]['dc:type'] !== 'undefined')
			txt += "<tr><td>Type:</td><td>"+m['dc:type']+"</td></tr>\n";
		
		if (typeof player.metadata[1]['dc:format'] !== 'undefined')
			txt += "<tr><td>Format:</td><td>"+m['dc:format']+"</td></tr>\n";
		
		if (typeof player.metadata[1]['dc:identifier'] !== 'undefined')
			txt += "<tr><td>Identifikator:</td><td>"+m['dc:identifier']+"</td></tr>\n";
		
		if (typeof player.metadata[1]['dc:source'] !== 'undefined')
			txt += "<tr><td>Kilde:</td><td>"+m['dc:source']+"</td></tr>\n";
		
		if (typeof player.metadata[1]['dc:language'] !== 'undefined')
			txt += "<tr><td>Spr&aring;k:</td><td>"+m['dc:language']+"</td></tr>\n";
		
		if (typeof player.metadata[1]['dc:relation'] !== 'undefined')
			txt += "<tr><td>Relasjon:</td><td>"+m['dc:relation']+"</td></tr>\n";
		
		if (typeof player.metadata[1]['dc:coverage'] !== 'undefined')
			txt += "<tr><td>Dekning:</td><td>"+m['dc:coverage']+"</td></tr>\n";
		
		if (typeof player.metadata[1]['dc:rights'] !== 'undefined')
			txt += "<tr><td>Rettigheter:</td><td>"+m['dc:rights']+"</td></tr>\n";
		
		txt += "</table>\n";
		$('#metadata').html(txt);
		
	} else {
		window.setTimeout(playerHasMetadata,100);
	}
}
playerHasMetadata();

var isAnimatingScroll = true;
function scrollToHighlightedText() {
	var element = player.getHighlightedTextElement();
	if ($(element).length === 0)
		return;
	
	isAnimatingScroll = true;
	log.debug(element);
	log.debug($(element).html());
	log.debug($(element).position());
	log.debug($(element).position().top);
	$("html, body").animate({
		scrollTop: Math.max(0,$(element).position().top-100)+"px"
	}, 500, "swing", function() {
		isAnimatingScroll = false;
	});
}
var autoScroll = false;
function keepHighlightedTextOnScreen() {
	var element = player.getHighlightedTextElement();
	if (element === null)
		return;
	
	var position = $(element).offset();
	var scrollTop = $(window).scrollTop();
	var scrollBottom = scrollTop + $(window).height();
	
	if (position !== null && (position.top < scrollTop || position.top > scrollBottom-100))
		scrollToHighlightedText();
}
$("input#volume").live("change", function() {
	// set player volume here
	player.setVolume( $(this).val()/100 );
});
$("select#autoscroll").live("change", function() {
	// toggle autoScroll variable here
	switch ($(this).val()) {
	case 'on':
		autoScroll = true;
		break;
	case 'off':
		autoScroll = false;
		break;
	}
});
function backward() {
	log.debug('clicked "backward"');
	player.skipToTime(player.getCurrentTime()-30);
	scrollToHighlightedText();
	if (typeof Bookmark === 'function') {
		Bookmark.setLastmark(player.getCurrentTime());
	}
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
			log.debug('toggled from paused to playing');
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
			log.debug('toggled from playing to paused');
		}
	}
}
function forward() {
	log.debug('clicked "forward"');
	player.skipToTime(player.getCurrentTime()+30);
	scrollToHighlightedText();
	if (typeof Bookmark === 'function') {
		Bookmark.setLastmark(player.getCurrentTime());
	}
}
function toggleMute() {
	var button = $('#mute-unmute');
	if (button.hasClass('muted')) {
		button.removeClass('muted');
		button.addClass('unmuted');
		player.setVolume(100);
		log.debug('toggled from muted to unmuted');
	} else {
		button.removeClass('unmuted');
		button.addClass('muted');
		player.setVolume(0);
		log.debug('toggled from unmuted to muted');
	}
}
$.fixedToolbars.setTouchToggleEnabled(false); // always show buttons
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
	
	// make sure autoScroll toggle switch is up to date (hopefully not too slow)
	if (!($('select#autoscroll').val() == 'off' && !autoScroll || $('select#autoscroll').val() == 'on' && autoScroll)) {
		if (typeof log=='object') log.debug("$('select#autoscroll').val():"+$('select#autoscroll').val()+" , autoScroll:"+autoScroll+"="+(autoScroll?'true':'false'));
		$('select#autoscroll').click();
	}
	
	// make sure buttons are always showing (hopefully not too slow)
	$('div.ui-footer-fixed').each(function(index){
		$.fixedToolbars.show(true); // always show buttons
	});
	
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
			keepHighlightedTextOnScreen();
		}
	} /*else {
		$('#book')TODO
	}*/
	// TODO: update progress bar and volume
	// if wrong volume; $('#volume').progressbar('value', player.getVolume() );
},100);
window.setInterval(function(){
	if (player === null || server === null || loader === null)
		return;
		
	// Show loading message if the player is either preparing the book or an audio file is buffering
	if (loader.errorCode < 0) {
		switch (loader.errorCode) {
			case "-1": $('div.ui-loader h1').html('Boken finnes ikke'); break;
			default: $('div.ui-loader h1').html('Ukjent feil');
		}
		if (!$('html').hasClass("ui-loading")) {
			$.mobile.pageLoading(false);
		}
	}
	else if (!player.doneLoading) {
		if (loader.prepareEstimatedRemainingTime < 0) {
			$('div.ui-loader h1').html('Kontakter NLB...');
		} else {
			var estimatedRemaining = loader.prepareEstimatedRemainingTime;
			if (estimatedRemaining > 3600)
				estimatedRemaining = Math.floor(estimatedRemaining/3600)+' timer';
			else if (estimatedRemaining > 60)
				estimatedRemaining = Math.floor(estimatedRemaining/60)+' minutter';
			else
				estimatedRemaining = Math.floor(estimatedRemaining)+' sekunder';
			$('div.ui-loader h1').html('Boken klargjøres ('+Math.floor(loader.prepareProgress)+'%)<br/><small>Gjenstående tid: '+estimatedRemaining+'</small>');
		}
		if (!$('html').hasClass("ui-loading")) {
			$.mobile.pageLoading(false);
			log.info('player is loading');
		}
	} else {
		if ($('html').hasClass("ui-loading")) {
			$.mobile.pageLoading(true);
			log.info('nothing is loading');
		}
	}
},500);
window.setInterval(function(){
	if (player === null || server === null || loader === null)
		return;
	
	if (player.doneLoading) {
		if (typeof Bookmark === 'function' && player.isPlaying()) {
			// it seems we have bookmark support
			Bookmark.setLastmark(player.getCurrentTime());
		}
	}
	
},5000);
function s(id) {
	player.skipToId(id);
	player.play();
	if (typeof Bookmark === 'function') {
		Bookmark.setLastmark(player.getCurrentTime());
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

$(function(){
	server = new NLBServer('ticket='+ticket+'&launchTime='+launchTime);	// authorization, downloading of files
	player = new SmilPlayer();											// playback of SMIL filesets
	loader = new Daisy202Loader();										// loading and parsing SMIL-files from the server into the player
	
	server.url = serverUrl;
	
	loader.player = player;
	loader.server = server;
	
	player.loader = loader;
	player.server = server;
	
	loader.load();
	player.textDocument = document;
	player.textElement = $(document).find('#book').get(0);
	player.postProcessText = postProcessText;
	
	document.body.focus();
});