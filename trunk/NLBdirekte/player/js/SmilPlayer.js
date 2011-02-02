var consoleLog = debug?debug:true; // set to true for debugging with Firebug
var debugCurrentTime = true;
var debugIsSameSrc = true;
var debugJsonML = false;
function SmilPlayer() {
	var that = this;
	
	var inaccurateTimeMeasurement = 0.250; // compensates for inaccurate time measurements
	
	// Communication with the server (resolves URLs, deals with authorization, fetches XML/HTML)
	this.server = null;
	this.loader = null;
	this.doneLoading = false; // The loader must set this to true when it's done loading
	this.postProcessText = null; // optional: point this to a function that should be called when a text has been loaded
	
	this.smil = []; // All SMIL-files parsed and merged (based on master.smil) into
					// nested JSON-objects defined as {id,type,src,par[],seq[],begin,clipBegin,end,clipEnd,dur}.
					// The 'smil'-variable itself represents a "master-seq".
	this.metadata = null;	// {name:value, name:value, name:value , ...}
	this.toc = null; // Contains a sequential list of {title, level, id, begin, end}-entries
	this.pagelist = null; // Contains a sequential list of {page, id, begin, end}-entries
	
	var currentTime = 0; // Current position in seconds from start of SMIL
	var paused = true; // Wether the player is playing
	var volume = 1.0; // volume between 0.0 and 1.0. set and get using setVolume(volume) and getVolume()
	
	this.textDocument = document; // If, for instance, an iframe is used; the iframes Document must be set here
	this.textElement = null; // The html of the main content is displayed inside this element. Typically a <div/>
	var textObject = null; // body, div or similar containing all the raw text fetched from the server
	var textObjectSrc = '';
	var isLoadingText = false;
	
	this.extraElement = null; // If the SMIL contains media of type image or video, they will be displayed here
	var extraObject = null; // analogous with audioObject
	var extraObjectBegin = 0; // analogous with audioObjectBegin
	
	var audioObject = null; // The audio object used to play audio. HTML5 Audio with fallback to SoundManager2.
	var audioObjectBegin = 0; // The offset into the SMIL content
	
	var threadId = null; // ID of the thread (so we can make sure there's only one of them)
	var updateDelay = 100; // Maximum delay between each time the thread() is run
	
	// Starts, or prematurely updates, the thread
	var lastThreadRun = new Date();
	function run(delay) {
		if (threadId) {
			window.clearInterval(threadId);
			threadId = null;
		}
		
		if (!delay) delay = updateDelay;
		if (delay > updateDelay)
			delay = updateDelay;
		window.setTimeout(delegate(that,function(){
			if (threadId) return;
			delegate(that,thread)();
			threadId = window.setInterval(delegate(that,thread),updateDelay);
		}),delay);
	};
	
	// The main thread, which runs every updateDelay milliseconds
	var lastThreadRun = Date.now();
	function thread() {
		var thisThreadRun = Date.now();
		
		if (this.doneLoading) {
			if (!paused) {
				//if (audioObject !== null && audioObject.readyState >= 3 && !audioObject.ended) {
				if (audioObject !== null && audioObject.readyState >= 3 && audioObject.playState === 1) {
					// wait until we're done loading the audio file
					// (don't bother checking the text file anywhere, it loads quickly anyway)
					// use audio object to calculate currentTime
					currentTime = audioObjectBegin + audioObject.position/1000.;
					if (consoleLog && debugCurrentTime) console.log('#2A currentTime = '+currentTime+' audioObject.duration:'+(audioObject.duration/1000.));
				} else {
					// use timers to update currentTime when no audio is playing
					if (consoleLog && debugCurrentTime) console.log('currentTime:'+currentTime+' += '+((thisThreadRun-lastThreadRun)/1000.));
					currentTime += (thisThreadRun-lastThreadRun)/1000.;
					if (consoleLog && debugCurrentTime) console.log('#3A currentTime = '+currentTime);
				}
				
				// keep within valid range
				if (currentTime > getChildAttr(this.smil,lastChild(this.smil),'e')) {
					currentTime = getChildAttr(this.smil,lastChild(this.smil),'e');
					paused = true;
				} else if (currentTime < getChildAttr(this.smil,0,'b')) {
					currentTime = getChildAttr(this.smil,0,'b');
				}
				if (consoleLog && debugCurrentTime) console.log('#V1 currentTime = '+currentTime);
				
				// stop when finished
				if (currentTime === getChildAttr(this.smil,lastChild(this.smil),'e'))
					window.setTimeout(delegate(that,this.pause),0);
			}
			
			delegate(that,update)();
		}
		
		lastThreadRun = thisThreadRun;
	}
	
	function update() {
		if (skipTo >= 0) {
			currentTime = skipTo;
			if (consoleLog && debugCurrentTime) console.log('#4 currentTime = '+currentTime+' audioObject.duration:'+(audioObject===null?'?':(audioObject.duration/1000.)));
		}
		
		var activeSmilElements = this.getSmilElements.call(that, currentTime);
		var text = null;
		var audio = null;
		var extra = null;
		for (var i = 0; i < activeSmilElements.length; i++) {
			switch (getAttr(activeSmilElements[i],'t','').split('/')[0]) {
			case 'text':
			case 'application':
			case 'multipart':
				text = activeSmilElements[i];
				break;
			case 'audio':
				audio = activeSmilElements[i];
				break;
			case 'image':
			case 'video':
				extra = activeSmilElements[i];
				break;
			}
		}
		
		updateText.call(that, text);
		updateAudio.call(that, audio);
		updateExtra.call(that, extra);
		
		if (skipTo >= 0) {
			if (!paused && consoleLog && debugIsSameSrc && audioObject !== null)
				console.log('isSameSrc #1: audioObject.src:'+audioObject.src);
			
			if (   (audioObject === null || isSameSrc(audioObject.url,this.server.getUrl(getAttr(audio,'s',''))) &&
											Math.abs(audioObjectBegin+audioObject.position/1000. - currentTime) < inaccurateTimeMeasurement)
				/*|| (this.textElement === null || textObject === null ||
							!isSameSrc(textObjectSrc,this.server.getUrl(getAttr(text,'s',''))) || isLoadingText)*/
				/*|| (this.extraElement === null || extraObject === null ||
						extraObject.src === this.server.getUrl(getAttr(extra,'s','')))*/) {
					// skipping done
					skipTo = -1;
			} else {
				window.setTimeout(delegate(that,function(){audioObject.pause();}),0);
			}
		}
	}
	
	var prevHighlightId = null;
	var prevHighlightColor = null;
	var HIGHLIGHT_COLOR = '#FFFF00';
	this.getHighlightedTextElement = function() {
		return this.textDocument.getElementById(prevHighlightId);
	}
	function updateText(smilNode) {
		if (this.textElement === null || isLoadingText) return;
		if (smilNode === null) {
			if (this.textElement.innerHTML.length > 0) {
				this.textElement.innerHTML = '';
				//while (this.textElement.childNodes.length >= 1)
				//	this.textElement.removeChild(this.textElement.firstChild);
			}
		} else {
			var split = getAttr(smilNode,'s','').split('#');
			var filename = split[0];
			if (textObjectSrc !== filename) {
				// un-highlight text, just to be sure
				var prevHighlightElement = this.textDocument.getElementById(prevHighlightId);
				if (prevHighlightElement !== null)
					prevHighlightElement.style.backgroundColor = prevHighlightColor;
				prevHighlightId = null;
				prevHighlightColor = null;
				
				// get the new text
				isLoadingText = true;
				textObjectSrc = filename;
				this.server.loadXmlFile(
					filename,
					delegate(that,function(xmlDoc) {
						// success
						if (consoleLog && console !== null && typeof console !== "undefined") console.log('xmlDoc == '+typeof xmlDoc);
						if (consoleLog && console !== null && typeof console !== "undefined") console.log('xmlDoc == '+xmlDoc);
						if (consoleLog && console !== null && typeof console !== "undefined") console.log('xmlDoc == '+typeof xmlDoc.innerHTML);
						if (consoleLog && console !== null && typeof console !== "undefined") console.log('xmlDoc == '+typeof xmlDoc.getElementsByTagNames);
						textObject = this.loader.xmlToHtml(xmlDoc); // TODO: make async in case of time-consuming transformations
						// TODO: handle textObject === null ?
						if (consoleLog && console !== null && typeof console !== "undefined") console.log('textObject == '+textObject);
						if (consoleLog && console !== null && typeof console !== "undefined") console.log('textObject.innerHTML == '+textObject.innerHTML);
						this.textElement.innerHTML = textObject.innerHTML;
						
						// Resolve urls (i.e. images)
						// elements with the 'src'-attribute:
						// script img iframe embed source input frameset
						// img, and possibly embed and source are the only ones
						// that may occur in a book, so we ignore the rest.
						var img = this.textElement.getElementsByTagName('img');
						var embed = this.textElement.getElementsByTagName('embed');
						var source = this.textElement.getElementsByTagName('source');
						for (var i = 0; i < img.length; i++) {
							if (img[i].hasAttribute('src'))
								img[i].src = this.server.getUrl(img[i].getAttribute('src'));
							if (typeof mobile === 'boolean' && mobile && img[i].width > 240) {
								var scale = 240./img[i].width;
								img[i].height = parseInt(img[i].height*scale);
								img[i].width = 240;
							}
						}
						for (var i = 0; i < embed.length; i++)
							if (embed[i].hasAttribute('src'))
								embed[i].setAttribute('src',this.server.getUrl(embed[i].getAttribute('src')));
						for (var i = 0; i < source.length; i++)
							if (source[i].hasAttribute('src'))
								source[i].setAttribute('src',this.server.getUrl(source[i].getAttribute('src')));
						
						if (typeof this.postProcessText === 'function')
							this.postProcessText();
						
						isLoadingText = false;
					}),
					delegate(that,function() {
						// failure
						textObjectSrc = '';
						textObject = null;
						isLoadingText = false;
						if (consoleLog) console.log('failed to load text object with src: "'+textObjectSrc+'"');
					})
				);
			}
			else if (getAttr(smilNode,'s','').indexOf('#') >= 0) {
				// highlight content of element pointed to by fragment identifier
				if (paused || getAttr(smilNode,'e',-1) - currentTime > inaccurateTimeMeasurement) { // compensate for audio time-inaccuracy when skipping
					var fragment = split[1];
					if (prevHighlightId !== fragment) {
						if (prevHighlightId) {
							this.textDocument.getElementById(prevHighlightId).style.backgroundColor = prevHighlightColor;
						}
						var element = this.textDocument.getElementById(fragment);
						if (element !== null) {
							prevHighlightId = fragment;
							prevHighlightColor = element.style.backgroundColor;
							element.style.backgroundColor = HIGHLIGHT_COLOR;
						}
					}
				}
			}
			else {
				// no paragraph pointed at, don't highlight anything
				if (prevHighlightId) {
					this.textDocument.getElementById(prevHighlightId).style.backgroundColor = prevHighlightColor;
					prevHighlightId = null;
				}
			}
		}
	}
	
	var skipTo = -1; // hold the time to skip to while sound is loading
	function updateAudio(smilNode) {
		//if (consoleLog && debugCurrentTime) console.log('updateAudio('+typeof smilNode+')');
		if (audioObject === null && smilNode === null) {
			// nothing playing and nothing to play
			return;
		}
		if (audioObject === null) {
			// nothing playing; start playing
			if (consoleLog) console.log('nothing playing; start playing');
			audioObject = soundManager.createSound({
				id: "audio"+(new Date()).getTime(),
				url: this.server.getUrl(getAttr(smilNode,'s','')),
				volume: Math.round(volume*100.),
				autoPlay: false
			});
			if (audioObject !== null) {
				audioObject.setVolume(Math.round(volume*100.));
				if (audioObject.readyState >= 3) {
					audioObject.setPosition(Math.round((getAttr(smilNode,'B',-1) + (currentTime - getAttr(smilNode,'b',-1)))*1000.));
					if (consoleLog && debugCurrentTime) console.log('#1B audioObject.position/1000. = '+audioObject.position/1000.);
				}
				audioObjectBegin = getAttr(smilNode,'b',-1) - getAttr(smilNode,'B',-1);
				audioObject.load();
				audioObject.play();
				if (paused) {
					audioObject.pause();
				}
			}
		} else if (audioObject.readyState >= 3) {
			// something playing (might be paused though)
			if (smilNode === null) {
				// stop playing
				if (consoleLog) console.log('stop playing');
				audioObject.pause();
				if (consoleLog) console.log('audioObject.pause();');
				audioObject.destruct(); // preserve memory
				audioObject = null;
			} else {
				// play the right thing
				if (consoleLog && !paused && debugIsSameSrc)
					console.log('isSameSrc #2: audioObject:'+typeof audioObject+(typeof audioObject==='object'?(' audioObject.src:'+audioObject.src):''));
				
				if (isSameSrc(audioObject.url,this.server.getUrl(getAttr(smilNode,'s','')))) {
					// the right audioObject is selected
					if (audioObject.readyState >= 3) {
						// the audioObject is loaded
						if (Math.abs(audioObjectBegin+(audioObject.playState!==1?(audioObject.duration/1000.):(audioObject.position/1000.)) - currentTime) > inaccurateTimeMeasurement) {
							// currentTime is not close to the time indicated by the audioObject
							if (consoleLog && debugCurrentTime) console.log('(Math.abs('+audioObjectBegin+'+'+(audioObject.position/1000.)+' - '+currentTime+' = '+Math.abs(audioObjectBegin+audioObject.position/1000.-currentTime)+') > '+inaccurateTimeMeasurement+')');
							audioObject.setPosition(Math.round((currentTime - audioObjectBegin)*1000.));
							if (consoleLog && debugCurrentTime) console.log('#2B audioObject.currentTime = '+currentTime+' - '+audioObjectBegin+' = '+audioObject.position/1000.);
							audioObject.play(); // update position
							if (paused) {
								window.setTimeout(delegate(that,function(){audioObject.pause()}),0);
								if (consoleLog) console.log('adjusting time to '+currentTime+'-'+audioObjectBegin+' = '+(currentTime-audioObjectBegin));
							}
						}
						if (audioObject.position/1000. < getAttr(smilNode,'B',-1)-inaccurateTimeMeasurement ||
							audioObject.position/1000. > getAttr(smilNode,'E',-1)+inaccurateTimeMeasurement) {
								// correct file, but too far off. we probably skipped to another place in the file...
								if (consoleLog) console.log('correct file, but too far off. we probably skipped to another place in the file...');
								if (consoleLog && debugCurrentTime) console.log('if ('+(audioObject.position/1000.)+' < '+(getAttr(smilNode,'B',-1)-inaccurateTimeMeasurement)+' || '+(audioObject.position/1000.)+' > '+(getAttr(smilNode,'E',-1)+inaccurateTimeMeasurement)+')');
								audioObject.setPosition(Math.round(getAttr(smilNode,'B',-1)*1000.) + 100);
								//audioObject.setPosition(Math.round((currentTime-audioObjectBegin)*1000.));
								//if (consoleLog && debugCurrentTime) console.log('#3B audioObject.position/1000. = '+(audioObject.position/1000.));
								//if (consoleLog && debugCurrentTime) console.log('audioObject.position/1000. = '+getAttr(smilNode,'B',-1)+' = '+(audioObject.position/1000.));
								audioObject.play(); // update position
								if (paused)
									window.setTimeout(delegate(that,function(){audioObject.pause()}),0);
								if (consoleLog && debugCurrentTime) console.log('audioObject.position/1000. = '+audioObject.position/1000.);
						} else {
							// everything playing as it should. pause/resume as needed
							
							// if (should be playing && is not playing)
							if (!paused && audioObject.paused) {
								if (consoleLog) console.log('should be playing and is not playing; playing');
								audioObject.play();
							}
							// if (should not be playing && is playing)
							if (paused && !audioObject.paused && audioObject.playState !== 1) {
								if (consoleLog) console.log('should not be playing but is playing; pause');
								audioObject.pause();
							}
						}
					}
				} else {
					// playing the wrong file
					// if (not near end of file && not near currentTime || ended)
					if (Math.abs(audioObject.duration/1000. - audioObject.position/1000.) > inaccurateTimeMeasurement &&
						Math.abs(audioObject.position/1000.+audioObjectBegin - currentTime) > inaccurateTimeMeasurement
						|| audioObject.playState !== 1) {
						// switch file
						if (consoleLog) console.log('playing the wrong file, switch file');
						audioObject.pause();
						audioObject.destruct(); // preserve memory
						//audioObject = HTML5AudioNow(this.server.getUrl(getAttr(smilNode,'s','')));
						audioObject = soundManager.createSound({
							id: "audio"+(new Date()).getTime(),
							url: this.server.getUrl(getAttr(smilNode,'s','')),
							volume: Math.round(volume*100.),
							autoPlay: false
						});
						audioObject.setVolume(Math.round(volume*100.));
						if (audioObject.readyState >= 3) {
							audioObject.setPosition(Math.round((getAttr(smilNode,'B',-1) + (currentTime - getAttr(smilNode,'b',-1)))*1000.));
							if (consoleLog && debugCurrentTime) console.log('#A1 audioObject.position/1000. = '+audioObject.position/1000.);
						}
						audioObject.play(); // update position
						if (paused)
							window.setTimeout(delegate(that,function(){audioObject.pause()}),0);
						audioObjectBegin = getAttr(smilNode,'b',-1) - getAttr(smilNode,'B',-1);
					} else if (!paused && consoleLog && debugCurrentTime) {
						console.log("if ("+Math.abs(audioObject.duration/1000. - audioObject.position/1000.)+" > "+inaccurateTimeMeasurement+" &&\n"+
									"    "+Math.abs(audioObject.position/1000.+audioObjectBegin - currentTime)+" > "+inaccurateTimeMeasurement+")");
					}
				}
			}
		}
	}
	
	function updateExtra(smilNode) {
		return; // TODO: this is initially not an important function, so make everything else work first
		if (this.extraElement) {
			if (true /* element already exists? */) {
				/* resize if necessary */
				return;
			}
			
			if (getAttr(smilNode,'t','').split('/')[0] === 'video') {
				// Only support actual HTML5 for video for now (untested. and SM2 video support might be added later?)
				var video = this.textDocument.createElement('video');
				video.setAttribute('src',this.server.getUrl(getAttr(smilNode,'s','')));
				video.setAttribute('width',this.extraElement.clientWidth);
				video.setAttribute('height',this.extraElement.clientHeight);
				video.setAttribute('autoplay',false);
				video.setAttribute('controls',true);
				video.currentTime = getAttr(smilNode,'B',-1) + (currentTime - getAttr(smilNode,'b',-1));
				this.extraElement.appendChild(video);
			}
			else if (getAttr(smilNode,'t','').split('/')[0] === 'image') {
				var image = this.textDocument.createElement('img');
				image.setAttribute('src',this.server.getUrl(getAttr(smilNode,'s','')));
				image.setAttribute('width',this.extraElement.clientWidth);
				image.setAttribute('height',this.extraElement.clientHeight);
				this.extraElement.appendChild(image);
			}
			else {
				var iframe = this.textDocument.createElement('iframe');
				iframe.setAttribute('src',this.server.getUrl(getAttr(smilNode,'s','')));
				iframe.setAttribute('width',this.extraElement.clientWidth);
				iframe.setAttribute('height',this.extraElement.clientHeight);
				this.extraElement.appendChild(iframe);
			}
		}
	}
	
	this.getSmilElements = function(ms) {
		ms = parseFloat(ms);
		var elements = [];
		if (typeof this.smil === 'undefined')
			return elements;
		
		var stack = [];
		for (var i = 0; i < numberOfChildren(this.smil); i++) {
			var child = getChild(this.smil,i);
			if ((getAttr(child,'b',-1) < 0 || getAttr(child,'b',-1) <= ms) &&
				(getAttr(child,'e',-1) < 0 ||                        ms <= getAttr(child,'e',-1))) {
				
				if (child[0] === 's' || child[0] === 'p') {
					stack.push(child);
				} else {
					// sort ascending by 'begin' (this.smil is already sorted this way)
					elements.push(child);
				}
			}
		}
		while (stack.length > 0) {
			var current = stack.shift();
			
			for (var i = 0; i < numberOfChildren(current); i++) {
				var child = getChild(current,i);
				if ((getAttr(child,'b') < 0 || getAttr(child,'b') <= ms) &&
					(getAttr(child,'e') < 0 ||                 ms <= getAttr(child,'e'))) {
					
					if (child[0] === 's' || child[0] === 'p')
						stack.push(child);
					else {
						// sort ascending by 'begin'
						var j = 0;
						while (j < elements.length && getAttr(elements[j],'b',-1) < getAttr(child,'b'))
							j++;
						elements.splice(j,0,child);
					}
				}
			}
		}
		
		return elements;
	}
	
	function getSmilById(id) {
		// find out which smil element is referencing this id in the this.textDocument
		if (typeof this.smil === 'undefined' || this.smil === null)
			return null;
		
		var search = [];
		if (getAttr(this.smil,'i',-1) === id)
			return this.smil;
		for (var i = 0; i < numberOfChildren(this.smil); i++)
			search.push(getChild(this.smil,i));
		while (search.length > 0) {
			var e = search.shift(); // width-first search => shift (depth-first => pop)
			// check id
			var fragment = getAttr(e,'s','').split('#',2);
			if (fragment.length === 2 && fragment[1] !== '' && id === fragment[1]) {
				search = null;
				return e;
			}
			// queue children
			for (var i = 0; i < numberOfChildren(e); i++)
				search.push(getChild(e,i));
		}
		
		return null;
	}
	
	var thisURL = window.location.href;
	thisURL = thisURL.substring(0,thisURL.lastIndexOf('/')+1);
	// TODO: re-check this function for correctness
	function isSameSrc(srcA, srcB) {
		if (srcA.indexOf(thisURL) === -1)
			srcA = thisURL+srcA;
		if (srcB.indexOf(thisURL) === -1)
			srcB = thisURL+srcB;
		if (srcA === srcB)
			return true;
		return false;
	}
	
	// Functions for controlling the player
	this.skipToTime = function(ms) {
		if (typeof ms === 'string')
			ms = parseFloat(ms);
		if (typeof ms !== 'number' || isNaN(ms)) {
			return false;
		}
		if (ms < 0.001)
			skipTo = 0.001;
		else if (ms >= this.getTotalTime())
			skipTo = this.getTotalTime();
		else
			skipTo = ms;
		delegate(that,update)();
		return true;
	}
	this.skipToId = function(id) {
		var element = getSmilById.call(that,id);
		
		// if found, go to that smil element 
		if (element !== null) {
			return this.skipToTime(getAttr(element,'b',-1));
		}
		return false;
	}
	this.skipToPage = function(page) {
		// skip to the page
		for (var i = 0; i < numberOfChildren(this.pagelist); i++) {
			if (getChildAttr(this.pagelist,i,'page') === page) {
				return this.skipToTime(getChildAttr(this.pagelist,i,'b',-1));
			}
		}
		return false;
	}
	this.play = function() {
		if (!this.doneLoading)
			return;
		paused = false;
		run(0);
	}
	this.pause = function() {
		if (!this.doneLoading)
			return;
		paused = true;
		run(0);
	}
	this.stop = function() {
		if (!this.doneLoading)
			return;
		paused = true;
		run(0);
		var activeSmilElements = this.getSmilElements(currentTime);
		var text = null;
		for (var i = 0; i < activeSmilElements.length; i++) {
			switch (getAttr(activeSmilElements[i],'t','').split('/')[0]) {
			case 'text':
			case 'application':
			case 'multipart':
				text = activeSmilElements[i];
				break;
			}
		}
		if (text)
			skipToTime(getAttr(text,'b',-1));
	}
	this.setVolume = function(vol) {
		if (vol < 0.0) vol = 0.0;
		if (vol > 1.0) vol = 1.0;
		volume = vol;
		if (audioObject !== null) {
			audioObject.setVolume(Math.round(volume*100.));
		}
	}
	
	// Functions for checking the state of the player
	this.getVolume = function() {
		if (audioObject !== null)
			volume = audioObject.volume/100.;
		return volume;
	}
	this.getCurrentTime = function() {
		return currentTime;
	}
	this.getTotalTime = function() {
		if (this.smil === null || numberOfChildren(this.smil) === 0)
			return 0;
		else
			return getChildAttr(this.smil,lastChild(this.smil),'e',-1) - getChildAttr(this.smil,0,'b',-1);
	}
	this.getPage = function() {
		if (this.pagelist === null || numberOfChildren(this.pagelist) === 0)
			return 0;
		for (var i = 1; i < numberOfChildren(this.pagelist); i++) {
			if (currentTime < getChildAttr(this.pagelist,i,'b',-1))
				return getChildAttr(this.pagelist,i-1,'page',0);
		}
		return getChildAttr(this.pagelist,0,'page',0);
	}
	this.isPlaying = function() {
		return !paused;
	}
	this.getAudioBackend = function() {
		if (audioObject === null)
			return '';
		return 'soundmanager'; // TODO: should return 'html' when HTML5 is used
	}
	
	// Functions for easier use of JsonML elements
	function isJsonML(elem) {
		// loose
		if (typeOf(elem) !== 'array' || typeOf(elem[0]) !== 'string') {
			if (!paused) {
				if (consoleLog&&debugJsonML) console.log('is not JsonML: elem:'+typeOf(elem)+(typeOf(elem)==='array'?(' elem[0]:'+typeOf(elem[0])):''));
				if (consoleLog&&debugJsonML) console.log(elem);
			}
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
		if (!isJsonML(elem)) { if(consoleLog&&debugJsonML&&!paused)console.log('numberOfChildren'); return 0; }
		if (elem.length === 1) return 0;
		else return typeof elem[1] === 'object' ? elem.length-2 : elem.length-1;
	}
	function lastChild(elem) { // shorthand and easier-to-read code
		if (consoleLog&&debugJsonML&&!paused&&!isJsonML(elem)) console.log('lastChild');
		return numberOfChildren(elem)-1;
	}
	function getChild(elem, nr) {
		if (!isJsonML(elem)) { if(consoleLog&&debugJsonML&&!paused)console.log('getChild'); return null; }
		if (elem.length === 1) return null;
		if (typeof elem[1] === 'object') {
			if (elem.length === 2) return null;
			return elem[nr+2];
		}
		return elem[nr+1];
	}
	function getAttr(elem, attr, def) {
		if (!isJsonML(elem)) { if(consoleLog&&debugJsonML&&!paused)console.log('getAttr'); return def; }
		if (elem.length === 1 || typeof elem[1] !== 'object' || typeof elem[1][attr] === 'undefined') return def;
		return elem[1][attr];
	}
	function setAttr(elem, attr, val) {
		if (!isJsonML(elem)) { if(consoleLog&&debugJsonML&&!paused)console.log('setAttr'); return false; }
		if (elem.length === 1 || typeof elem[1] !== 'object') {
			elem.splice(1,0,{attr:val});
		} else {
			elem[attr] = val;
		}
		return true;
	}
	function deleteAttr(elem, attr) {
		if (!isJsonML(elem)) { if(consoleLog&&debugJsonML&&!paused)console.log('deleteAttr'); return false; }
		if (elem.length === 1 || typeof elem[1] !== 'object') return false;
		delete elem[1][attr];
		return true;
	}
	function getChildAttr(elem, childNr, attr, def) {
		if (consoleLog&&debugJsonML&&!paused&&!isJsonML(elem)) console.log('getChildAttr');
		var child = getChild(elem, childNr);
		if (child === null) return def;
		else return getAttr(child, attr);
	}
	function setChildAttr(elem, child, attr, val) {
		if (consoleLog&&debugJsonML&&!paused&&!isJsonML(elem)) console.log('setChildAttr');
		var child = getChild(elem, childNr);
		if (child === null) return false;
		else setAttr(child, attr, val);
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
	// Used to make sure that 'this' points to the right object
	function delegate(instance, method) {
		return function() {
			return method.apply(instance, arguments);
		}
	};
	
	run(0);
	
	this.test = function(){return audioObject;}
}

// Date.now for old browsers
if (typeof Date.now === 'undefined') { Date.now = function() { return 0+(new Date()); }; }
