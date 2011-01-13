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
var CSS = {
	load: /*static*/ function (url_, /*optional*/ media_, /*optional*/ document_) {
		// For using iframes or similar
		document_ = typeof document_ === 'undefined' || document_ === null ? document : document_;
		
		// make url_ absolute if needed
		var thisUrl_ = window.location.href;
		thisUrl_ = thisUrl_.substring(0,thisUrl_.lastIndexOf('/')+1);
		if (url_.indexOf('://') === -1)
			url_ = thisUrl_+url_;
		
		// We are preventing loading a file already loaded
		var _links = document_.getElementsByTagName("link");
		if (_links.length > 0 && _links["href"] === url_) { return; }
		
		// Optional parameters check
		var _media = typeof media_ === 'undefined' || media_ === null ? "all" : media_;
		
		var _elstyle = document_.createElement("link");
		_elstyle.setAttribute("rel", "stylesheet");
		_elstyle.setAttribute("type", "text/css");
		_elstyle.setAttribute("media", _media);
		_elstyle.setAttribute("href", url_);
		
		var _head = document_.getElementsByTagName("head")[0];
		_head.appendChild(_elstyle);
	}
};
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
	CSS.load('css/Daisy202Book.css','screen',document);
	
	loadBookmarks();
}
var totalTime = Infinity;
var myBookmarks = [];
var currentBookmark = -1;
function playerIsLoaded() {
	if (player !== null && player.doneLoading) {

		// create table of contents
		var txt = "";
		if (typeof player.toc !== 'undefined' && player.toc !== null &&
			(player.toc.length>2||player.toc.length===2&&typeof player.toc[1]!=='object')) {
			var toc = player.toc;
			txt = "";
			for (var i = (toc.length>1&&typeof toc[1]==='object')?2:1; i < toc.length; i++) {
				// {title,level,id,begin,end}
				var level = "";
				for (var l = 1; l < toc[i][1]['level']; l++) { level += "&nbsp;&nbsp;&nbsp;&nbsp;"; }
				txt += level+'<nobr><a onclick="player.skipToTime('+toc[i][1]['b']+');$(\'#menuCloseButton\').click();scrollToHighlightedText();return false;" href="">'+toc[i][1]['title']+" ("+niceTime(toc[i][1]['b'])+' - '+niceTime(toc[i][1]['e'])+')</a></nobr><br/>';
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
				txt += '<a onclick="player.skipToTime('+pagelist[i][1]['b']+');$(\'#menuCloseButton\').click();scrollToHighlightedText();return false;" href="">side '+pagelist[i][1]['page']+
					" ("+niceTime(i===0?0:pagelist[i][1]['b'])+' - '+niceTime((i+1===pagelist.length)?player.getTotalTime():pagelist[i+1][1]['b'])+
					')</a><br/>';
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
playerIsLoaded();

function loadBookmarks() {
	if (typeof Bookmark === 'function') {
		Bookmark.loadBookmarks(player.metadata[1]['dc:identifier'], function(bookmarkList, error) {
			if (bookmarkList && typeof bookmarkList === 'object') {
				myBookmarks = bookmarkList;
				var htmlList = "<table style=''>";
				htmlList += "<tr>";
				htmlList += "<th>Tittel</th>";
				htmlList += "<th>Brødtekst</th>";
				htmlList += "<th>Posisjon</th>";
				htmlList += "<th></th>";
				htmlList += "</tr>";
				for (var b = 0; b < myBookmarks.length; b++) {
					// {uid,created,modified,bookId,title,text,isPublic,isReplyTo,startTime,startCharOffset,endTime,endCharOffset,isLastmark}
					htmlList += "<tr id='bookmarkRow"+myBookmarks[b].uid+"'>";
					htmlList += "<td style='padding-right:20px;'>"+
									(myBookmarks[b].title.length<100?myBookmarks[b].title:(myBookmarks[b].title.substring(0,100)+'...'))
								+"</td>";
					htmlList += "<td style='padding-right:20px;'>"+
									(myBookmarks[b].text.length<100?myBookmarks[b].text:(myBookmarks[b].text.substring(0,100)+'...'))
								+"</td>";
					htmlList += "<td><a onclick='player.skipToTime("+myBookmarks[b].startTime+");$(\"#menuCloseButton\").click();scrollToHighlightedText();return false;' href='#'>"+niceTime(myBookmarks[b].startTime)+"</a></td>";
					htmlList += "<td><span id='editDeleteBookmarkButtons' style='text-align: center'>"+
									"<button id='editBookmark_"+myBookmarks[b].uid+"' value='"+myBookmarks[b].uid+"' class='bookmarkEditButton'>Rediger</button>"+
									"<button id='deleteBookmark_"+myBookmarks[b].uid+"' value='"+myBookmarks[b].uid+"' class='bookmarkDeleteButton'>Slett</button>"+
								"</span></td>";
					htmlList += "</tr>";
				}
				htmlList += "</table>";
				$('#bookmarks').html(htmlList);
				$(".bookmarkEditButton").button().click(function() {
					currentBookmark = $(this).val();
					var bookmarkNr = -1;
					for (var b = 0; b < myBookmarks.length; b++) {
						if (myBookmarks[b].uid == $(this).val()) {
							bookmarkNr = b;
							break;
						}
					}
					if (bookmarkNr !== -1) {
						$('#bookmarkTitle').val(myBookmarks[bookmarkNr].title);
						$('#bookmarkText').val(myBookmarks[bookmarkNr].text);
						$('#bookmarkTime').val(myBookmarks[bookmarkNr].startTime);
						$('#bookmarkTitle,#bookmarkText,#bookmarkTime,#bookmarkTimeNow,#bookmarkEndEdit_save,bookmarkEndEdit_cancel').attr("disabled", false);
						$('#editBookmark').show('slow');
						$('.bookmarkEditButton').hide('slow');
						$('.bookmarkDeleteButton').hide('slow');
						//$("#bookmarkRow"+currentBookmark).addClass('ui-state-highlight');
						$("#bookmarkRow"+currentBookmark).css('background-color','#FFFF99');
					}
				}).width('108px');
				$(".bookmarkDeleteButton").button().click(function() {
					currentBookmark = $(this).val();
					$("#deleteBookmarkConfirmation_yes #deleteBookmarkConfirmation_no").attr("disabled", false);
					$('#deleteBookmarkConfirmation').show('slow');
					$('.bookmarkEditButton').hide('slow');
					$('.bookmarkDeleteButton').hide('slow');
					$("#bookmarkRow"+currentBookmark).css('background-color','#FFFF99');
				}).width('108px');
				
				// Add bookmark icons
				$('.bookmarkLink').remove();
				for (var b = 0; b < myBookmarks.length; b++) {
					var smilsAtTime = player.getSmilElements(myBookmarks[b].startTime);
					var smilAtTime = null;
					for (var i = 0; i < smilsAtTime.length; i++) {
						switch (getAttr(smilsAtTime[i],'t','').split('/')[0]) {
						case 'text':
							smilAtTime = smilsAtTime[i];
							break;
						}
					}
					
					var fragment = getAttr(smilAtTime,'s','').split('#',2);
					if (fragment.length === 2 && fragment[1] !== '') {
						console.log('bookmark at id="'+fragment[1]+'"');
						var textElement = player.textDocument.getElementById(fragment[1]);
						console.log('player.textDocument.getElementById('+fragment[1]+') === '+textElement);
						if (textElement) {console.log('inserting...');
							// TODO: add bookmark icons ('img/nlb_bokmerke.png')
							var span = player.textDocument.createElement('span');
							span.innerHTML = "<a class='bookmarkLink' href=\"javascript:(function(){"
												+"$('#menuOpenButton').click();"
												+"$('#menuTab-bookmarks-button').click();"
												+"$('#editBookmark_"+myBookmarks[b].uid+"').click();})();\">"
												+"<img style='vertical-align: middle' src='img/nlb_bokmerke.png' alt='Bokmerke: "+
													(myBookmarks[b].title.length>50?myBookmarks[b].title.substring(0,50):myBookmarks[b].title)
												+"' />"
											+"</a>";
							textElement.parentNode.insertBefore(span, textElement);
						}
					}
				}
			}
		});
	}
}

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
playerHasMetadata();
var isAnimatingScroll = false;
function scrollToHighlightedText() {
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
var autoScroll = true;
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
$(function() {
	$("#volume").slider({
		min: 0,
		max: 100,
		animate: true,
		value: 75,
		change: function(){
			// set player volume here
			player.setVolume( $('#volume').slider('value')/100 );
		}
	});
	$("#autoscroll_buttons").buttonset(
	).change(function() {
		switch ($('input[name="autoscroll"]:checked').val()) {
		case 'on':
			autoScroll = true;
			break;
		case 'off':
			autoScroll = false;
			break;
		}
	});
});
$(function() {
	$("#deleteBookmarkConfirmation_yes").button().click(function() {
		$("#deleteBookmarkConfirmation_yes #deleteBookmarkConfirmation_no").attr("disabled", true);
		$('#bookmarkMessageBox').html('Deleting bookmark...');
		$('#bookmarkMessageBox').show('slow');
		
		var error = Bookmark.deleteBookmark(currentBookmark, function(bm, e) {
			if (e) {
				$('#bookmarkMessageBox').html('Klarte ikke å slette bokmerket:<br/>'+e);
				$("#deleteBookmarkConfirmation_yes #deleteBookmarkConfirmation_no").attr("disabled", false);
			} else {
				$('#bookmarkMessageBox').html('Bokmerket ble slettet.');
				setTimeout(function(){
					$('#bookmarkMessageBox').hide('slow');
					$('#deleteBookmarkConfirmation').hide('slow');
					$('.bookmarkEditButton').show('slow');
					$('.bookmarkDeleteButton').show('slow');
					$("#bookmarkRow"+currentBookmark).css('background-color','#FFFFFF');
				},1000);
				$("#deleteBookmarkConfirmation_yes #deleteBookmarkConfirmation_no").attr("disabled", false);
				loadBookmarks();
			}
		});
		if (error !== false) {
			$('#bookmarkMessageBox').html('Klarte ikke å be om å få bokmerket slettet:<br/>'+error);
			$("#deleteBookmarkConfirmation_yes #deleteBookmarkConfirmation_no").attr("disabled", false);
		}
	});
	$("#deleteBookmarkConfirmation_no").button().click(function() {
		currentBookmark = -1;
		$("#deleteBookmarkConfirmation_yes #deleteBookmarkConfirmation_no").attr("disabled", true);
		$("#deleteBookmarkConfirmation").hide('slow');
	});
	$("#bookmarkTimeNow").button().click(function() {
		
	});
	$("#bookmarkEndEdit_save").button().click(function() {
		$('#bookmarkTitle,#bookmarkText,#bookmarkTime,#bookmarkTimeNow,#bookmarkEndEdit_save,bookmarkEndEdit_cancel').attr("disabled", true);
		$('#bookmarkMessageBox').html('Saving bookmark...');
		$('#bookmarkMessageBox').show('slow');
		
		var bookmark = null;
		
		for (var b = 0; b < myBookmarks.length; b++) {
			if (myBookmarks[b].uid == currentBookmark) {
				bookmark = myBookmarks[b];
				break;
			}
		}
		if (bookmark === null) {
			bookmark = new Bookmark();
			bookmark.uid = 0;
			bookmark.bookId = bookId;
			bookmark.isPublic = false;
			bookmark.isReplyTo = 0;
			bookmark.startCharOffset = 0;
			bookmark.endTime = 0;
			bookmark.endCharOffset = 0;
			bookmark.isLastmark = false;
		}
		bookmark.title = $('#bookmarkTitle').val();
		bookmark.text = $('#bookmarkText').val();
		bookmark.startTime = $('#bookmarkTime').val();
		
		var error = Bookmark.saveBookmark(bookmark, function(bm, e) {
			if (e) {
				$('#bookmarkMessageBox').html('Klarte ikke å lagre bokmerket:<br/>'+e);
				$('#bookmarkTitle,#bookmarkText,#bookmarkTime,#bookmarkTimeNow,#bookmarkEndEdit_save,bookmarkEndEdit_cancel').attr("disabled", false);
			} else {
				$('#bookmarkMessageBox').html('Bokmerket ble lagret.');
				setTimeout(function(){
					$('#bookmarkMessageBox').hide('slow');
					$('#editBookmark').hide('slow');
					$('.bookmarkEditButton').show('slow');
					$('.bookmarkDeleteButton').show('slow');
					$("#bookmarkRow"+currentBookmark).css('background-color','#FFFFFF');
				},1000);
				$('#bookmarkTitle,#bookmarkText,#bookmarkTime,#bookmarkTimeNow,#bookmarkEndEdit_save,bookmarkEndEdit_cancel').attr("disabled", false);
				loadBookmarks();
			}
		});
		if (error !== false) {
			$('#bookmarkMessageBox').html('Klarte ikke å sende avgårde bokmerket:<br/>'+error);
			$('#bookmarkTitle,#bookmarkText,#bookmarkTime,#bookmarkTimeNow,#bookmarkEndEdit_save,bookmarkEndEdit_cancel').attr("disabled", false);
		}
	});
	$("#bookmarkEndEdit_cancel").button().click(function() {
		$('#bookmarkMessageBox').hide('slow');
		$('#editBookmark').hide('slow');
		$('.bookmarkEditButton').show('slow');
		$('.bookmarkDeleteButton').show('slow');
		$("#bookmarkRow"+currentBookmark).css('background-color','#FFFFFF');
	});
});
$(function() {
	$('#back').button({
		text: false,
		icons: {
			primary: 'ui-icon-seek-start'
		}
	}).click(function(){
		player.skipToTime(player.getCurrentTime()-30);
		scrollToHighlightedText();
		if (typeof Bookmark === 'function') {
			Bookmark.saveLastmark(player.metadata[1]['dc:identifier'], player.getCurrentTime(), 0,
				function(success, error) {
					//if (console) console.log('success:'+success+',error:'+error);
				}
			);
		}
	});
	$('#smallBack').button({
		text: false,
		icons: {
			primary: 'ui-icon-seek-prev'
		}
	});
	$('#play').button({
		text: false,
		icons: {
			primary: 'ui-icon-play'
		}
	})
	.click(function() {
		var options;
		if ($(this).text() === 'spill av') {
			options = {
				label: 'stopp',
				icons: {
					primary: 'ui-icon-stop'
				}
			};
			if (player !== null && !player.isPlaying()) {
				player.play();
			}
		} else {
			options = {
				label: 'spill av',
				icons: {
					primary: 'ui-icon-play'
				}
			};
			if (player !== null && player.isPlaying()) {
				player.pause();
			}
		}
		$(this).button('option', options);
	});
	$('#smallForward').button({
		text: false,
		icons: {
			primary: 'ui-icon-seek-next'
		}
	});
	$('#forward').button({
		text: false,
		icons: {
			primary: 'ui-icon-seek-end'
		}
	}).click(function(){
		player.skipToTime(player.getCurrentTime()+30);
		scrollToHighlightedText();
		if (typeof Bookmark === 'function') {
			Bookmark.saveLastmark(player.metadata[1]['dc:identifier'], player.getCurrentTime(), 0,
				function(success, error) {
					//if (console) console.log('success:'+success+',error:'+error);
				}
			);
		}
	});
	$('#bookmark').button({
		text: false,
		icons: {
			primary: 'ui-icon-pin-s'
		}
	}).click(function(){
		$('#menuOpenButton').click();
		$('#menuTab-bookmarks-button').click();
		
		currentBookmark = 0;
		var title = $(player.getHighlightedTextElement()).text().replace(/^\s*/, "").replace(/\s*$/, "");
		var time = player.getCurrentTime();
		if (title.length > 50)
			title = title.substring(0,50);
		title = niceTime(time)+' '+title;
		$('#bookmarkTitle').val(title);
		$('#bookmarkText').val('');
		$('#bookmarkTime').val(player.getCurrentTime());
		$('#bookmarkTitle,#bookmarkText,#bookmarkTime,#bookmarkTimeNow,#bookmarkEndEdit_save,bookmarkEndEdit_cancel').attr("disabled", false);
		$('#editBookmark').show('slow');
		$('.bookmarkEditButton').hide('slow');
		$('.bookmarkDeleteButton').hide('slow');
	});
	$('#mute').button({
		text: false,
		icons: {
			primary: 'ui-icon-volume-on'
		}
	})
	.click(function() {
		var options;
		if ($(this).text() === 'demp') {
			options = {
				label: 'ikke demp',
				icons: {
					primary: 'ui-icon-volume-off'
				}
			};
			player.setVolume(0);
		} else {
			options = {
				label: 'demp',
				icons: {
					primary: 'ui-icon-volume-on'
				}
			};
			player.setVolume(100);
		}
		$(this).button('option', options);
	});
	$('#menuCloseButton').button({
		text: 'Tilbake',
		icons: {
			primary: 'ui-icon-arrowreturnthick-1-w'
		}
	})
	.click(function() {
		$('#menu').slideUp('fast');
		$('#menuOpenButton').focus();
	});
	$('#menuOpenButton').button({
		text: false,
		icons: {
			primary: 'ui-icon-plus'
		}
	})
	.click(function() {
		$('#menu').slideDown('fast');
		$('#menuCloseButton').focus();
	});
	$(function() {
		$("#menuTabs").tabs();
	});
	$(function() {
		$("#controlButtons").buttonset();
	});
	$(function () {
		var tabContainers = $('div.menuTabs > div');

		$('div.menuTabs ul.menuTab-navigation a').click(function () {
			tabContainers.hide().filter(this.hash).show();

			$('div.menuTabs ul.menuTab-navigation a').removeClass('selected');
			$(this).addClass('selected');

			return false;
		}).filter(':first').click();
	});
});
$(document).ready(function() {
	$('#mute').change();
	$('#menu').hide('fast');
	$('#editBookmark').hide();
	$('#deleteBookmarkConfirmation').hide();
});

var backend = null;
window.setInterval(function(){
	// fix mute click color problem
	if ($('#mute').is(':checked') && !$('#muteLabel').hasClass('ui-state-active')) {
		mute(false);
		$('#mute').change();
	} else if (!$('#mute').is(':checked') && $('#muteLabel').hasClass('ui-state-active')) {
		mute(true);
		$('#mute').change();
	}
	
	if (player === null || server === null || loader === null)
		return;
	
	// make sure play/stop button is up to date on wether the player is playing
	if (	$('#play').text() === 'spill av' && player.isPlaying()
		||	$('#play').text() === 'stopp' && !player.isPlaying()) {
		$('#play').click();
	}
	
	if (player.doneLoading) {
		var currentTime = player.getCurrentTime();
		var totalTime = player.getTotalTime();
		$('#time').text(niceTime(currentTime)+' / '+niceTime(totalTime));
		//$('#progressbar').value((100*currentTime/totalTime));
		
		
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
		}
		
		if (autoScroll && player.isPlaying()) {
			keepHighlightedTextOnScreen();
		}
	}
	// update progress bar and volume
	// hvis feil volum; $('#volume').progressbar('value', player.getVolume() );
},100);
window.setInterval(function(){
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
	server = new NLBServer(ticket);	// authorization, downloading of files
	player = new SmilPlayer();		// playback of SMIL filesets
	loader = new Daisy202Loader();	// loading and parsing SMIL-files from the server into the player
	
	server.url = 'http://'+window.location.host+'/NLBdirekte/player/'; // <-- place in a config-file or something?
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
if (typeof window.onload !== 'function') {
	window.onload = init;
} else {
	window.onload = function() {
		if (oldonload) oldonload();
		init();
	};
}
