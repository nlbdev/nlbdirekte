var SmilPlayerTest = AsyncTestCase("SmilPlayerTest");

SmilPlayerTest.prototype.testSmilPlayer = function(queue) {
	player = new SmilPlayer();
	server = new function() {
		this.getUrl = function(filename) {return '/NLBdirekte/player/tests/test/'+filename;};
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
	player.metadata = ["metadata",{
					"dc:language": "no",
					"ncc:charset": "utf-8",
					"dc:title": "Title",
					"ncc:tocItems": "1",
					"dc:publisher": "publisher",
					"ncc:footnotes": "2",
					"ncc:sidebars": "3",
					"ncc:generator": "generator",
					"ncc:prodNotes": "4",
					"dc:identifier": "identifier",
					"dc:format": "Daisy 2.02",
					"dc:date": "2011-03-25",
					"ncc:maxPageNormal": "5",
					"ncc:kByteSize": "6",
					"ncc:pageNormal": "7",
					"ncc:depth": "8",
					"ncc:totalTime": "00:00:11",
					"ncc:narrator": "Narrator",
					"ncc:pageSpecial": "9",
					"ncc:files": "10",
					"dc:creator": "Creator",
					"ncc:pageFront": "11",
					"ncc:setInfo": "1 of 1"
				}];
	player.smil =	["s",
						{"E":-1.0,"B":-1.0,"b":"0.0","e":"11","d":"11"},
						["s",
							{"B":-1.0,"E":-1.0,"d":"11","i":"smil0_0.smil","b":"0.0","e":"11"},
							["s",
								{"E":-1.0,"B":-1.0,"b":"0.0","e":"11","d":"11"},
								["p",
									{"E":-1.0,"B":-1.0,"b":"0.0","e":"2","d":"2"},
									["t",
										{"b":"0.0","E":-1.0,"d":"2","i":"smil0_text0","s":"content1.html#id0","B":-1.0,"t":"text/html","e":"2"}
									],
									["s",
										{"E":-1.0,"B":-1.0,"b":"0.0","e":"2","d":"2"},
										["a",
											{"B":"0.0","e":"2","d":"2","i":"smil0_audio0","s":"20060826 - Armstrong.mp3","b":"0.0","t":"audio/mpeg","E":"2"}
										]
									]
								],
								["p",
									{"E":-1.0,"B":-1.0,"b":"2","e":"5","d":"3"},
									["t",
										{"b":"2","E":-1.0,"d":"3","i":"smil0_text1","s":"content1.html#id1","B":-1.0,"t":"text/html","e":"5"}
									],
									["s",
										{"E":-1.0,"B":-1.0,"b":"0","e":"3","d":"3"},
										["a",
											{"B":"0","5":"3","d":"3","i":"smil0_audio1","s":"20060826 - Armstrong.mp3","b":"2","t":"audio/mpeg","E":"3"}
										]
									]
								],
								["p",
									{"E":-1.0,"B":-1.0,"b":"5","e":"7","d":"2"},
									["t",
										{"b":"5","E":-1.0,"d":"2","i":"smil0_text2","s":"content1.html#id2","B":-1.0,"t":"text/html","e":"7"}
									],
									["s",
										{"E":-1.0,"B":-1.0,"b":"5","e":"7","d":"2"},
										["a",
											{"B":"3","e":"7","d":"2","i":"smil0_audio2","s":"20060826 - Armstrong.mp3","b":"5","t":"audio/mpeg","E":"5"}
										]
									]
								],
								["p",
									{"E":-1.0,"B":-1.0,"b":"7","e":"9","d":"2"},
									["t",
										{"b":"7","E":-1.0,"d":"2","i":"smil1_text0","s":"content2.html#id0","B":-1.0,"t":"text/html","e":"9"}
									],
									["s",
										{"E":-1.0,"B":-1.0,"b":"7","e":"9","d":"2"},
										["a",
											{"B":"5","e":"9","d":"2","i":"smil1_audio0","s":"20060826 - Armstrong.mp3","b":"7","t":"audio/mpeg","E":"7"}
										]
									]
								],
								["p",
									{"E":-1.0,"B":-1.0,"b":"9","e":"11","d":"2"},
									["t",
										{"b":"9","E":-1.0,"d":"2","i":"smil1_text1","s":"content2.html#id1","B":-1.0,"t":"text/html","e":"11"}
									],
									["s",
										{"E":-1.0,"B":-1.0,"b":"9","e":"11","d":"2"},
										["a",
											{"B":"0.5","e":"11","d":"2","i":"smil1_audio1","s":"office_lobby.mp3","b":"9","t":"audio/mpeg","E":"2.5"}
										]
									]
								]
							]
						]
					];
	player.toc =	["toc",
						["h", {"i": "smil0_toc1", "title": "Headline 1", "b": "0.0", "e": "1.5", "level": 1} ],
						["h", {"i": "smil0_toc2", "title": "Headline 2", "b": "5.0", "e": "6.5", "level": 1} ],
						["h", {"i": "smil0_toc3", "title": "Headline 3", "b": "9.0", "e": "10.5", "level": 1} ]
					];
	player.pagelist =	["pagelist", 
							["p", {"i": "smil0_page1", "b": "0.0", "e": "2.0", "page": 1} ], 
							["p", {"i": "smil0_page3", "b": "6.0", "e": "8.0", "page": 2} ], 
							["p", {"i": "smil0_page5", "b": "9.0", "e": "10", "page": 3} ]
						];
	
	/*DOC += <div id="book"></div> */
	player.textDocument = document;
	player.textElement = $(document).find('#book').get(0);
	player.doneLoading = true;
	
	queue.call("test Date.now", function(callbacks) {
		jstestdriver.console.log("test Date.now");
		assertNotNull("Date.now is not null",Date.now);
		assertFunction("Date.now is a function",Date.now);
		assertNumber("Date.now() returns a number";Date.now());
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
		assertEquals("audio id is correct",'smil0_audio0',a[1]['i']);
		assertEquals("text id is correct",'smil0_text0',t[1]['i']);
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
		assertEquals("audio id is correct",'smil1_audio0',a[1]['i']);
		assertEquals("text id is correct",'smil1_text0',t[1]['i']);
		assertEquals("audio refers to mp3",'audio/mpeg',a[1]['t']);
		assertEquals("text refers to html",'text/html',t[1]['t']);
	});
	queue.call("test getTotalTime", function(callbacks) {
		jstestdriver.console.log("test getTotalTime");
		assertEquals("total time of the book is 11 seconds",11,player.getTotalTime());
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
	});
	queue.call("test skipToId(id)", function(callbacks) {
		jstestdriver.console.log("test skipToId(id)");
		player.skipToId('smil0_audio2');
		assertEquals("skipping to smil0_audio2 puts the player at position=5",5,player.getCurrentTime());
	});
	queue.call("test skipToPage(page)", function(callbacks) {
		jstestdriver.console.log("test skipToPage(page)");
	});
	queue.call("test play", function(callbacks) {
		jstestdriver.console.log("test play");
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