var Daisy202Test = AsyncTestCase("Daisy202Test");

Daisy202Test.prototype.testDaisy202 = function(queue) {
	loader = new Daisy202Loader();
	server = new function() {
		this.getUrl = function(filename) {return '/NLBdirekte/player/tests/test/minimal/'+filename;};
		this.readyUrl = function() {return '/NLBdirekte/player/tests/test/'+'isprepared.php';};
	}
	player = new function() {
		this.server = null;
		this.loader = null;
		this.doneLoading = false;
		this.smil = [];
		this.metadata = null;
		this.toc = null;
		this.pagelist = null;
	}
	loader.server = server;
	loader.player = player;
	player.server = server;
	player.loader = loader;
	metadata = ["metadata",{
					"dc:language": "no",
					"ncc:charset": "utf-8",
					"dc:title": "title",
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
					"ncc:totalTime": "12:34:56",
					"ncc:narrator": "narrator",
					"ncc:pageSpecial": "9",
					"ncc:files": "10",
					"dc:creator": "creator",
					"ncc:pageFront": "11",
					"ncc:setInfo": "1 of 1"
				}];
	smil =	["s",
				{"E":-1.0,"B":-1.0,"b":"0.0","e":"273.131","d":"273.131"},
				["s",
					{"B":-1.0,"E":-1.0,"d":"15.416","i":"smil0_mqia0001.smil","b":"0.0","e":"15.416"},
					["s",
						{"E":-1.0,"B":-1.0,"b":"0.0","e":"15.416","d":"15.416"},
						["p",
							{"E":-1.0,"B":-1.0,"b":"0.0","e":"4.109","d":"4.109"},
							["t",
								{"b":"0.0","E":-1.0,"d":"4.109","i":"smil0_mqia0001","s":"mqia0001.html#dol_1_1_uafu_0001","B":-1.0,"t":"text/html","e":"4.109"}
							],
							["s",
								{"E":-1.0,"B":-1.0,"b":"0.0","e":"4.109","d":"4.109"},
								["a",
									{"B":"0.0","e":"4.109","d":"4.109","i":"smil0_audio_0001","s":"1_audio.mp3","b":"0.0","t":"audio/mpeg","E":"4.109"}
								]
							]
						],
						["p",
							{"E":-1.0,"B":-1.0,"b":"4.109","e":"9.256","d":"5.147"},
							["t",
								{"b":"4.109","E":-1.0,"d":"5.147","i":"smil0_abme_0001","s":"mqia0001.html#dol_1_1_pkrg_0002","B":-1.0,"t":"text/html","e":"9.256"}
							],
							["s",
								{"E":-1.0,"B":-1.0,"b":"4.109","e":"9.256","d":"5.147"},
								["a",
									{"B":"4.109","e":"9.256","d":"5.147","i":"smil0_audio_0002","s":"1_audio.mp3","b":"4.109","t":"audio/mpeg","E":"9.256"}
								]
							]
						],
						["p",
							{"E":-1.0,"B":-1.0,"b":"9.256","e":"15.416","d":"6.16"},
							["t",
								{"b":"9.256","E":-1.0,"d":"6.16","i":"smil0_abme_0002","s":"mqia0001.html#dol_1_1_pkrg_0003","B":-1.0,"t":"text/html","e":"15.416"}
							],
							["s",
								{"E":-1.0,"B":-1.0,"b":"9.256","e":"15.416","d":"6.16"},
								["a",
									{"B":"9.256","e":"15.416","d":"6.16","i":"smil0_audio_0003","s":"1_audio.mp3","b":"9.256","t":"audio/mpeg","E":"15.416"}
								]
							]
						]
					]
				]
			];
	toc =	["toc",
				["h", {"i": "smil0_mqia0001", "title": "Headline", "b": "0.0", "e": "4.109", "level": 1} ],
				["h", {"i": "smil1_mqia_0002", "title": "Copyright information", "b": "15.416", "e": "17.274", "level": 1} ],
				["h", {"i": "smil2_mqia_0001", "title": "About the book", "b": "25.604", "e": "28.097", "level": 1} ],
				["h", {"i": "smil3_mqia_0005", "title": "Title", "b": "51.218", "e": "54.71", "level": 1} ],
				["h", {"i": "smil4_itdk_0001", "title": "End of book", "b": "270.166", "e": "273.131", "level": 1} ]
			];
	pagelist =	["pagelist", 
					["p", {"i": "smil4_lngp_0001", "b": "180.613", "e": "181.156", "page": 1} ], 
					["p", {"i": "smil4_lngp_0002", "b": "181.997", "e": "182.838", "page": 2} ], 
					["p", {"i": "smil4_lngp_0003", "b": "183.272", "e": "183.95", "page": 3} ], 
					["p", {"i": "smil4_lngp_0004", "b": "185.008", "e": "185.496", "page": 4} ], 
					["p", {"i": "smil4_lngp_0005", "b": "186.066", "e": "186.5", "page": 5} ], 
					["p", {"i": "smil5_vzab_0001", "b": "190.095", "e": "190.695", "page": 6} ], 
					["p", {"i": "smil5_vzab_0002", "b": "191.475", "e": "192.135", "page": 7} ], 
					["p", {"i": "smil6_yiio_0001", "b": "317.24", "e": "317.9", "page": 8} ], 
					["p", {"i": "smil6_yiio_0002", "b": "459.62", "e": "460.4", "page": 9} ], 
					["p", {"i": "smil6_yiio_0003", "b": "609.378", "e": "610.038", "page": 10} ], 
					["p", {"i": "smil6_yiio_0004", "b": "755.342", "e": "756.242", "page": 11} ], 
					["p", {"i": "smil6_rdjg_0001", "b": "876.824", "e": "877.484", "page": 12} ], 
					["p", {"i": "smil6_rdjg_0002", "b": "1021.379", "e": "1022.219", "page": 13} ], 
					["p", {"i": "smil6_rdjg_0003", "b": "1159.349", "e": "1159.889", "page": 14} ], 
					["p", {"i": "smil6_rdjg_0004", "b": "1301.874", "e": "1302.594", "page": 15} ], 
					["p", {"i": "smil7_orip_0001", "b": "1435.369", "e": "1436.029", "page": 16} ], 
					["p", {"i": "smil8_zkga_0001", "b": "1563.605", "e": "1564.325", "page": 17} ], 
					["p", {"i": "smil8_zkga_0002", "b": "1706.796", "e": "1707.516", "page": 18} ]
				];
	
	queue.call("test xmlToHtml()", function(callbacks) {
		// (most code copied from SmilPlayer.js:updateText(). Should probably be kept in sync if possible)
		jstestdriver.console.log('1 - xmlToHtml');
		$.ajax({
			url: server.getUrl('content1.html'),
			mimeType: 'text/xml',
			dataType: "xml",
			success: callbacks.add(function(data, textStatus, jqXHR) {
						
						var textObject;
						
						var aBefore = null;
						var aAfter = null;
						
						if ($.browser.msie) {
							var xmlstr = data.xml ? data.xml : (new XMLSerializer()).serializeToString(data);
							var htmlstr, htmlhead, htmlbody;
							var htmlregex = /<head[^>]*>([\s\S]*)<\/head[\s\S]*<body[^>]*>([\s\S]*)<\/body[^>]*>/gm;
							matches = htmlregex.exec(xmlstr);
							htmlstr = matches[0];
							htmlhead = matches[1];
							htmlbody = matches[2];
							var htmldoc = $('<html></html>');
							htmldoc.append($('<head></head>'));
							htmldoc.append($('<body></body>'));
							htmldoc.find('head').html(htmlhead);
							htmldoc.find('body').html(htmlbody);
							
							aBefore = $(htmldoc).find('a').length;
							textObject = loader.xmlToHtml(htmldoc);
							aAfter = $(textObject).find('a').length;
						} else {
							// non-IE-browsers recognize XHTML
							aBefore = $(data).find('a').length;
							textObject = loader.xmlToHtml(data);
							aAfter = $(textObject).find('a').length;
						}
						
						assertNumber("number of links initially is a number",aBefore);
						assertNotNaN("number of links initially is not NaN",aBefore);
						assertEquals("there are 3 links in content.html",3,aBefore);
						assertNumber("number of links remaining is a number",aAfter);
						assertNotNaN("number of links remaining is not NaN",aAfter);
						assertEquals("all links are removed by xmlToHtml",0,aAfter);
						assertEquals("there are 2 headlines (h1) in content.html",2,$(textObject).find('h1').length);
						assertEquals("there are 3 divs in content.html",3,$(textObject).find('div').length);
						assertEquals("there are 3 spans in content.html",3,$(textObject).find('span').length);
						
					}),
			error:	function(data, textStatus, jqXHR) {
						jstestdriver.console.log('failed to load document: '+textStatus);
					}
		});
	});
	
	queue.call("test load() and loadReady()", function(callbacks) {
		
		jstestdriver.console.log('2 - initialize');
		assertBoolean("doneLoading is boolean",player.doneLoading);
		assertFalse("is not done loading",player.doneLoading);
		assertNull("metadata is null",player.metadata);
		assertNull("pagelist is null",player.pagelist);
		assertArray("smil is array",player.smil);
		assertEquals("smil is empty",0,player.smil.length);
		assertNull("toc is null",player.toc);
		assertFunction("getUrl is a function",server.getUrl);
		assertFunction("readyUrl is a function",server.readyUrl);
		assertObject("server is an object",server);
		assertObject("player is an object",player);
		assertObject("loader is an object",loader);
		assertSame("player has a reference to server",server,player.server);
		assertSame("player has a reference to loader",loader,player.loader);
		assertSame("loader has a reference to server",server,loader.server);
		assertSame("loader has a reference to player",player,loader.player);
		assertString("state of the loader is a string",loader.state);
		assertEquals("state of the loader signals that the loader is initialized","initialized",loader.state);
		assertNumber("prepare estimated remaining time is number",loader.prepareEstimatedRemainingTime);
		assertNumber("prepare progress is number",loader.prepareProgress);
		assertNumber("prepare start time is number",loader.prepareStartTime);
		assertNumber("error code is number",loader.errorCode);
		assertNotNaN("prepare estimated remaining time is not NaN",loader.prepareEstimatedRemainingTime);
		assertNotNaN("prepare progress is not NaN",loader.prepareProgress);
		assertNotNaN("prepare start time is not NaN",loader.prepareStartTime);
		assertNotNaN("error code is not NaN",loader.errorCode);
		assertEquals("prepare estimated remaining time is -1",-1,loader.prepareEstimatedRemainingTime);
		assertEquals("prepare progress is 0",0,loader.prepareProgress);
		assertEquals("prepare start time is 0",0,loader.prepareStartTime);
		assertEquals("error code is 0",0,loader.errorCode);
		assertFunction("loader has a public 'xmlToHtml'-function",loader.xmlToHtml);
		
		$.ajax = callabacks.add(function(params) {
			
			jstestdriver.console.log('3 - first request: return error');
			assertEquals("state of loader states that the loader is initialized","initialized",loader.state);
			assertFalse("not done loading yet",player.doneLoading);
			assertNull("metadata is null",player.metadata);
			assertNull("pagelist is null",player.pagelist);
			assertArray("smil struct is array",player.smil);
			assertEquals("smil struct is empty",0,player.smil.length);
			assertNull("toc is null",player.toc);
			assertNumber("prepare estimated remaining time is number",loader.prepareEstimatedRemainingTime);
			assertNumber("prepare progress is number",loader.prepareProgress);
			assertNumber("prepare start time is number",loader.prepareStartTime);
			assertNumber("error code is number",loader.errorCode);
			assertNotNaN("prepare estimated remaining time is not NaN",loader.prepareEstimatedRemainingTime);
			assertNotNaN("prepare progress is not NaN",loader.prepareProgress);
			assertNotNaN("prepare start time is not NaN",loader.prepareStartTime);
			assertNotNaN("error code is not NaN",loader.errorCode);
			assertEquals("prepare estimated remaining time is -1",-1,loader.prepareEstimatedRemainingTime);
			assertEquals("prepare progress is 0",0,loader.prepareProgress);
			assertEquals("prepare start time is 0",0,loader.prepareStartTime);
			assertEquals("error code is 0",0,loader.errorCode);
			
			$.ajax = callabacks.add(function(params) {
				
				jstestdriver.console.log('4 - second request: return not ready');
				assertEquals("state of loader signals non-existent book","book does not exist",loader.state);
				assertFalse("not done loading yet",player.doneLoading);
				assertNull("metadata is null",player.metadata);
				assertNull("pagelist is null",player.pagelist);
				assertArray("smil is array",player.smil);
				assertEquals("smil structure is empty",0,player.smil.length);
				assertNull("toc is null",player.toc);
				assertNumber("prepare estimated remaining time is number",loader.prepareEstimatedRemainingTime);
				assertNumber("prepare progress is number",loader.prepareProgress);
				assertNumber("prepare start time is number",loader.prepareStartTime);
				assertNumber("error code is number",loader.errorCode);
				assertNotNaN("prepare estimated remaining time is not NaN",loader.prepareEstimatedRemainingTime);
				assertNotNaN("prepare progress is not NaN",loader.prepareProgress);
				assertNotNaN("prepare start time is not NaN",loader.prepareStartTime);
				assertNotNaN("error code is not NaN",loader.errorCode);
				assertEquals("prepare estimated remaining time is 0",0,loader.prepareEstimatedRemainingTime);
				assertEquals("prepare progress is 0",0,loader.prepareProgress);
				assertEquals("prepare start time is 0",0,loader.prepareStartTime);
				assertEquals("error code is -1",-1,loader.errorCode);
				
				$.ajax = callabacks.add(function(params) {
					
					jstestdriver.console.log('5 - third request: return ready');
					assertEquals("state of loader signals preparation underway","a book is being prepared",loader.state);
					assertFalse("not done loading yet",player.doneLoading);
					assertNull("metadata is null",player.metadata);
					assertNull("pagelist is null",player.pagelist);
					assertArray("smil structure is array",player.smil);
					assertEquals("smil structure is empty",0,player.smil.length);
					assertNull("toc is null",player.toc);
					assertNumber("prepare estimated remaining time is a number",loader.prepareEstimatedRemainingTime);
					assertNumber("prepare progress is a number",loader.prepareProgress);
					assertNumber("prepare start time is a number",loader.prepareStartTime);
					assertNumber("error code is a number",loader.errorCode);
					assertNotNaN("prepare estimated remaining time isn't NaN",loader.prepareEstimatedRemainingTime);
					assertNotNaN("prepare progress isn't NaN",loader.prepareProgress);
					assertNotNaN("prepare start time isn't NaN",loader.prepareStartTime);
					assertNotNaN("error code isn't NaN",loader.errorCode);
					assertEquals("prepare estimated remaining time is 60 seconds",60,loader.prepareEstimatedRemainingTime);
					assertEquals("prepare progress has not started",0,loader.prepareProgress);
					assertEquals("prepare start time is set to a time",1300980368785,loader.prepareStartTime);
					assertEquals("error code is 0",0,loader.errorCode);
					
					$.getJSON = callbacks.add(function(url, callback) {
						
						jstestdriver.console.log('6 - metadata request');
						assertEquals("state of the loader refers to metadata","loading metadata",loader.state);
						assertFalse("is not loaded yet",player.doneLoading);
						assertNull("metadata is null",player.metadata);
						assertNull("pagelist is null",player.pagelist);
						assertArray("smil structure is array",player.smil);
						assertEquals("smil structure is empty",0,player.smil.length);
						assertNull("toc is null",player.toc);
						assertNumber("prepare estimated remaining time is number",loader.prepareEstimatedRemainingTime);
						assertNumber("prepare progress is number",loader.prepareProgress);
						assertNumber("prepare start time is number",loader.prepareStartTime);
						assertNumber("error code is number",loader.errorCode);
						assertNotNaN("prepare estimated remaining time is not NaN",loader.prepareEstimatedRemainingTime);
						assertNotNaN("prepare progress is not NaN",loader.prepareProgress);
						assertNotNaN("prepare start time is not NaN",loader.prepareStartTime);
						assertNotNaN("error code is not NaN",loader.errorCode);
						assertEquals("preparation estimated remaining time is 0",0,loader.prepareEstimatedRemainingTime);
						assertEquals("preparation progress is 100",100,loader.prepareProgress);
						assertEquals("preparation start time is set",1300980368785,loader.prepareStartTime);
						assertEquals("error code is 1",1,loader.errorCode);
						
						$.getJSON = callbacks.add(function(url, callback) {
							jstestdriver.console.log('7 - smil request');
							assertEquals("state of the loader refers to smil","loading smil",loader.state);
							assertFalse("loading is not done yet (while fetching smil)",player.doneLoading);
							
							$.getJSON = callbacks.add(function(url, callback) {
								
								jstestdriver.console.log('8 - toc request');
								assertEquals("state of the loader refers to pagelist","loading toc",loader.state);
								assertFalse("loading is not done yet (while fetching toc)",player.doneLoading);
								
								$.getJSON = callbacks.add(function(url, callback) {
									
									jstestdriver.console.log('9 - pagelist request');
									assertEquals("state of the loader refers to pagelist","loading pagelist",loader.state);
									assertFalse("loading is not done yet (while fetching pagelist)",player.doneLoading);
									
									// Step 9. get pagelist.json
									var response = pagelist;
									setTimeout(
										callbacks.add(function(){callback(response, null, null);}),
										100
									);
								});
								
								// Step 8. get toc.json
								var response = toc;
								setTimeout(
									callbacks.add(function(){callback(response, null, null);}),
									100
								);
							});
							
							// Step 7. get smil.json
							var response = smil;
							setTimeout(
								callbacks.add(function(){callback(response, null, null);}),
								100
							);
						});
						
						// Step 6. get metadata.json
						var response = metadata;
						setTimeout(
							callbacks.add(function(){callback(response, null, null);}),
							100
						);
					});
					
					// Step 5. return ready
					var response = {
									"ready":"1",
									"state":"book is ready for playback"
									};
					params.callback(response, null, null);
				});
				
				// Step 4. return not ready
				var response = {
								"ready":"0",
								"state":"a book is being prepared",
								"progress":"0",
								"startedTime":"1300980368785",
								"estimatedRemainingTime":"60"
								};
				params.callback(response, null, null);
			});
			
			// Step 3. return error
			params.callback(
				{"ready":"-1", "state":"book does not exist"},
				null,
				null
			);
		});
		
		// Step 2. start loading
		var that = this;
		loader.load.apply(that,null);
	});
	
	queue.call("test result from load() and loadReady()", function(callbacks) {
		
		jstestdriver.console.log('10 - finished loading');
		assertEquals("loader state is 'finished'","finished",loader.state);
		assertBoolean("player.doneLoading is boolean",player.doneLoading);
		assertTrue("player knows that it's done loading",player.doneLoading);
		assertEquals("metadata is assigned the complete structure",metadata,player.metadata);
		assertEquals("smil is assigned the complete structure",smil,player.smil);
		assertEquals("toc is assigned the complete structure",toc,player.toc);
		assertEquals("pagelist is assigned the complete structure",pagelist,player.pagelist);
		assertNumber("prepareEstimatedRemainingTime is a number",loader.prepareEstimatedRemainingTime);
		assertNumber("prepareProgress is a number",loader.prepareProgress);
		assertNumber("prepareStartTime is a number",loader.prepareStartTime);
		assertNumber("error code is a number",loader.errorCode);
		assertNotNaN("prepareEstimatedRemainingTime is not NaN",loader.prepareEstimatedRemainingTime);
		assertNotNaN("prepareProgress is not NaN",loader.prepareProgress);
		assertNotNaN("prepareStartTime is not NaN",loader.prepareStartTime);
		assertNotNaN("error code is not NaN",loader.errorCode);
		assertEquals("prepareEstimatedRemainingTime is 0",0,loader.prepareEstimatedRemainingTime);
		assertEquals("prepareProgress is 100",100,loader.prepareProgress);
		assertEquals("prepareStartTime is assigned",1300980368785,loader.prepareStartTime);
		
	});
};