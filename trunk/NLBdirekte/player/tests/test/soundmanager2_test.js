var SoundManagerTest = AsyncTestCase("SoundManagerTest");

SoundManagerTest.prototype.testSoundManager = function(queue) {
	this.error = false;
	
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
		soundManager._wD = soundManager._writeDebug = function(sText, sType, bTimestamp) {
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
	
	queue.call("Create sound", function(callbacks) {
		jstestdriver.console.log('create sound');
		var audioId = "audioId";
		var url = 'http://'+window.location.hostname+'/NLBdirekte/player/tests/test/minimal/dtb_0002.mp3';
		this.soundObject = soundManager.createSound({
			'id': audioId,
			'url': url,
			'volume': 100
		});
		assertNotUndefined("soundObject is defined", this.soundObject);
		assertObject("soundObject is an object", this.soundObject);
		assertEquals("audio id is corrrect", audioId, this.soundObject.sID);
		assertEquals("url is correct", url, this.soundObject.url);
		assertEquals("volume is 100", 100, this.soundObject.volume);
		jstestdriver.console.log('created sound...');
	});
	
	queue.call("Load and play sound", function(callbacks) {
		jstestdriver.console.log("Load and play sound");
		this.soundObject.options.onload = callbacks.add(function(success) {
			assertTrue('audio was loaded', success);
			jstestdriver.console.log('          audio was loaded');	
			setTimeout(this.soundObject.play,1000);
			
		});
		this.soundObject.options.onplay = callbacks.add(function() {
			assertTrue('audio started playing', true);
			jstestdriver.console.log('          audio started playing');
			jstestdriver.console.log(this.soundObject);
			jstestdriver.console.log(this.soundObject.position);
			jstestdriver.console.log(this.soundObject.duration);
			jstestdriver.console.log(this.soundObject.playState);
			setTimeout(this.soundObject.pause,1000);
		});
		this.soundObject.options.onpause = callbacks.add(function() {
			assertTrue('audio was paused', true);
			jstestdriver.console.log('          audio was paused');
			jstestdriver.console.log(this.soundObject);
			jstestdriver.console.log(this.soundObject.position);
			jstestdriver.console.log(this.soundObject.duration);
			jstestdriver.console.log(this.soundObject.playState);
			setTimeout(this.soundObject.resume,1000);
			//setTimeout(this.soundFinished,13000);
			setTimeout(this.soundObject.options.onfinish,13000);
		});
		this.soundObject.options.onfinish = callbacks.add(function() {
			jstestdriver.console.log(this.soundObject.position);
			jstestdriver.console.log(this.soundObject.duration);
			jstestdriver.console.log(this.soundObject.playState);
			assertNumber("playState of audio is a number", this.soundObject.playState);
			assertNotEquals("audio is not playing when it's finished playing", 1, this.soundObject.playState);
			this.soundObject.destruct();
		});
		
		this.soundObject.load();
	});
	
	queue.call("shutdown soundmanager",function(callbacks){
		soundManager.disable();
	});
};
