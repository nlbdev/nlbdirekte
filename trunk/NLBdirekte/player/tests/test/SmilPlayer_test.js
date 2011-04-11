var SmilPlayerTest = AsyncTestCase("SmilPlayerTest");

SmilPlayerTest.prototype.testSmilPlayer = function(queue) {
	player = new SmilPlayer();
	server = new function() {
		this.getUrl = function(filename) {return '/NLBdirekte/player/tests/test/minimal/'+filename;};
		this.readyUrl = function() {return '/NLBdirekte/player/tests/test/'+'isprepared.php';};
	}
	loader = new function() {
		this.xmlToHtml = function(doc) {
			return doc; // no prettying up needed for DAISY 2.02 unit testing purposes
		}
	}
	loader.server = server;
	loader.player = player;
	player.server = server;
	player.loader = loader;
	
	/*DOC += <div id="book"></div> */
	player.textDocument = document;
	player.textElement = $(document).find('#book').get(0);
	player.doneLoading = true;
	
	var threadId = null; // used for setInterval etc.
	
	queue.call("loading metadata/smil/toc/pagelist", function(callbacks) {
		$.getJSON(
			server.getUrl("metadata.json"),
			callbacks.add(function(response, textStatus, jqXHR) {
				player.metadata = response;
			})
		);
		$.getJSON(
			server.getUrl("smil.json"),
			callbacks.add(function(response, textStatus, jqXHR) {
				player.smil = response;
			})
		);
		$.getJSON(
			server.getUrl("toc.json"),
			callbacks.add(function(response, textStatus, jqXHR) {
				player.toc = response;
			})
		);
		$.getJSON(
			server.getUrl("pagelist.json"),
			callbacks.add(function(response, textStatus, jqXHR) {
				player.pagelist = response;
			})
		);
	});
	
	queue.call("Initialize soundManager", function(callbacks) {
		jstestdriver.console.log('initialize soundmanager');
		var fnComplete = callbacks.add(function(success) {
			assertTrue("soundmanager loaded successfully", success);
		});
		
		if (!soundManager)
			soundManager = new SoundManager();
		soundManager.url = 'http://'+window.location.hostname+'/NLBdirekte/player/js/soundmanager/swf';
		soundManager.flashVersion = 8;
		soundManager.allowFullScreen = false;
		soundManager.wmode = 'transparent';
		soundManager.debugMode = true;
		soundManager.debugFlash = false;
		soundManager.useHighPerformance = true;
		soundManager._writeDebug = function(sText, sType, bTimestamp) {
			jstestdriver.console.log('soundManager: '+sText);
			return true;
		};
		soundManager.useHTML5Audio = false;
		soundManager.onerror = function() {
			fnComplete(false);
		};
		soundManager.onload = function() {
			fnComplete(true);
		};
	});
	
	queue.call("test Date.now", function(callbacks) {
		jstestdriver.console.log("test Date.now");
		assertNotNull("Date.now is not null",Date.now);
		assertFunction("Date.now is a function",Date.now);
		assertNumber("Date.now() returns a number",Date.now());
	});
	
	queue.call("test getSmilElements(ms)", function(callbacks) {
		jstestdriver.console.log("test getSmilElements(ms)");
		var smilElements = player.getSmilElements(0.5);
		assertEquals("two smil nodes are returned from smilElements at position=0.5",2,smilElements.length);
		assertEquals("the first smil array returned from smilElements has a length=2",2,smilElements[0].length);
		assertString("the first smil node returned from smilElements has a name",smilElements[0][0]);
		var a,t;
		if (smilElements[0][0] === 'a') {
			a = smilElements[0];
			t = smilElements[1];
		} else {
			a = smilElements[1];
			t = smilElements[0];
		}
		assertEquals("audio node represents audio element",'a',a[0]);
		assertEquals("text node represents text element",'t',t[0]);
		assertEquals("audio id is correct",'smil0_rgn_aud_0001_0001',a[1]['i']);
		assertEquals("text id is correct",'smil0_rgn_txt_0001_0001',t[1]['i']);
		assertEquals("audio refers to mp3",'audio/mpeg',a[1]['t']);
		assertEquals("text refers to html",'text/html',t[1]['t']);
		
		smilElements = player.getSmilElements(7.5);
		assertEquals("two smil nodes are returned from smilElements at position=7.5",2,smilElements.length);
		assertEquals("the first smil array returned from smilElements has a length=2",2,smilElements[0].length);
		assertString("the first smil node returned from smilElements has a name",smilElements[0][0]);
		if (smilElements[0][0] === 'a') {
			a = smilElements[0];
			t = smilElements[1];
		} else {
			a = smilElements[1];
			t = smilElements[0];
		}
		assertEquals("audio node represents audio element",'a',a[0]);
		assertEquals("text node represents text element",'t',t[0]);
		assertEquals("audio id is correct",'smil0_rgn_aud_0001_0004',a[1]['i']);
		assertEquals("text id is correct",'smil0_rgn_txt_0001_0004',t[1]['i']);
		assertEquals("audio refers to mp3",'audio/mpeg',a[1]['t']);
		assertEquals("text refers to html",'text/html',t[1]['t']);
	});
	queue.call("test getTotalTime", function(callbacks) {
		jstestdriver.console.log("test getTotalTime");
		assertEquals("total time of the book is 12 seconds",12,player.getTotalTime());
	});
	queue.call("test getAudioBackend", function(callbacks) {
		jstestdriver.console.log("test getAudioBackend");
		assert("backend of the player is valid",
			   player.getAudioBackend() == 'soundmanager'
			|| player.getAudioBackend() == 'html'
			|| player.getAudioBackend() == '');
	});
	queue.call("test getCurrentTime and skipToTime", function(callbacks) {
		jstestdriver.console.log("test getCurrentTime and skipToTime");
		assertEquals("current position before doing anything is 0",0,player.getCurrentTime());
		player.skipToTime(5.25);
		assertEquals("skipping to 5.25 puts the player at position=5.25",5.25,player.getCurrentTime());
		player.skipToTime(-10);
		assertEquals("skipping to -10 puts the player at position=0.001",0.001,player.getCurrentTime());
		player.skipToTime(0);
		assertEquals("skipping to 0 puts the player at position=0.001",0.001,player.getCurrentTime());
		player.skipToTime(player.getTotalTime()+10);
		assertEquals("skipping to getTotalTime()+10 puts the player at position=getTotalTime()",player.getTotalTime(),player.getCurrentTime());
	});
	queue.call("test skipToId(id)", function(callbacks) {
		jstestdriver.console.log("test skipToId(id)");
		player.skipToId('rgn_cnt_0003');
		assertEquals("skipping to rgn_cnt_0003 puts the player at position=5",5,player.getCurrentTime());
	});
	queue.call("test skipToPage(page)", function(callbacks) {
		jstestdriver.console.log("test skipToPage(page)");
		player.skipToPage(1);
		assertEquals("skipping to page 1 puts the player at position=3",3,player.getCurrentTime());
		player.skipToPage(2);
		assertEquals("skipping to page 2 puts the player at position=5",5,player.getCurrentTime());
	});
	queue.call("test play", function(callbacks) {
		jstestdriver.console.log("test play");
		assertEquals("position=5 before play()",5,player.getCurrentTime());
		
		// waiting for the audio to be completely loaded (so buffering don't mess with the test)
		threadId = setInterval(callbacks.add(function(){
			if (player.buffering() == 1.) {
				clearInterval(threadId);
				threadId = null;
				player.play();
				setTimeout(callbacks.add(function(){
					assertNotEquals("position not the same as when playback started",5,player.getCurrentTime());
					assert(
							"position=7±0.5 two seconds after playing from position=5",
							6.5 <= player.getCurrentTime() && player.getCurrentTime() <= 7.5
						  );
				}),2000);
			}
		}),100);
	});
	queue.call("test pause", function(callbacks) {
		jstestdriver.console.log("test pause");
	});
	queue.call("test stop", function(callbacks) {
		jstestdriver.console.log("test stop");
	});
	queue.call("test setVolume", function(callbacks) {
		jstestdriver.console.log("test setVolume");
	});
	queue.call("test getPage", function(callbacks) {
		jstestdriver.console.log("test getPage");
	});
	queue.call("test isPlaying", function(callbacks) {
		jstestdriver.console.log("test isPlaying");
	});
	queue.call("test getHighlightedTextElement", function(callbacks) {
		jstestdriver.console.log("test getHighlightedTextElement");
	});
	
};