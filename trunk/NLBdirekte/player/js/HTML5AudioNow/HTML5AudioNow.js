HTML5AudioNow.version = "0.2.1";

// determine relative address
var scriptName = "HTML5AudioNow.js";
var scripts = document.getElementsByTagName('script');
var relative = "";
for (var i = 0; i < scripts.length; i++) {
	var src = scripts[i].attributes.getNamedItem('src').value;
	if (src && src.length > scriptName.length && src.indexOf(scriptName) !== -1) {
		relative = src.substr(0,src.length-scriptName.length);
		break;
	}
}

// include SoundManager2 and html5flash
var SM2_DEFER = true;
document.write('<script type="text/javascript" src="'+relative+'script/soundmanager2.js"></scr'+'ipt>');
document.write('<script type="text/javascript" src="'+relative+'html5flash.js"></scr'+'ipt>');

HTML5AudioNow.ready = false;
window.setTimeout(function(){HTML5AudioNow.ready = true;},5000);
HTML5AudioNow.loadSM2 = function() {
	if (typeof SoundManager === 'function') {
		if (!soundManager)
			soundManager = new SoundManager();
		soundManager.url = relative+'swf'; // path to directory containing SoundManager2 .SWF file
		soundManager.flashVersion = 8;
		soundManager.allowFullScreen = false;
		soundManager.wmode = 'transparent';
		soundManager.debugMode = false;
		soundManager.debugFlash = false;
		soundManager.useHighPerformance = true;
		soundManager.onready(function(){HTML5AudioNow.ready = true;});
		soundManager.useHTML5Audio = false;
	} else {
		window.setTimeout(HTML5AudioNow.loadSM2,10);
	}
};
HTML5AudioNow.loadSM2();

function HTML5AudioNow(attributes) {
	if (typeof attributes === "string") attributes = { src: attributes };
	
	var ext = null;
	var mime = null;
	
	ext = attributes.src.split('.');
	ext = ext[ext.length-1].toLowerCase();
	mime = null;
	switch (ext) {
	case 'mp3': mime = 'audio/mpeg'; break;
	case 'ogg': mime = 'audio/ogg'; break;
	case 'wav': mime = 'audio/x-wav'; break;
	case 'au': 
	case 'snd': mime = 'audio/basic'; break;
	case 'aif': 
	case 'aifc': 
	case 'aiff': mime = 'audio/x-aiff'; break;
	}
	if (!!soundManager && soundManager.supported()) {
		// SoundManager2
		var sm2Audio = new FlashHTMLAudioElement({
			src: attributes.src,
			autobuffer: (attributes.preload&&attributes.preload.toLowerCase()==='auto'?true:false),
			autoplay: (attributes.autoplay?true:false),
			loop: attributes.loop
		});
		
		sm2Audio.backend = "soundmanager";
		return sm2Audio;
	} else if (HTML5AudioSupport.supportsObj() && !!HTML5AudioSupport.supportsObjMime(mime)) {
		// HTML5
		//if (console) console.log('var htmlAudio = new Audio(attributes.src);');
		var htmlAudio = new Audio(attributes.src);
		if (typeof attributes.preload !== 'undefined') htmlAudio.preload = attributes.preload;
		if (typeof attributes.autoplay !== 'undefined') htmlAudio.autoplay = attributes.autoplay;
		if (typeof attributes.loop !== 'undefined') htmlAudio.loop = attributes.loop;
		htmlAudio.backend = "html";
		//if (console) console.log(typeof htmlAudio);
		return htmlAudio;
	} else {
		//alert('if (HTML5AudioSupport.supportsObj():'+HTML5AudioSupport.supportsObj()+
		//		' && !!HTML5AudioSupport.supportsObjMime('+mime+'):'+(!!HTML5AudioSupport.supportsObjMime(mime))+")\n"+
		//	  'if (soundManager:'+(typeof soundManager!=='undefined'&&soundManager!==null)+' && soundManager.supported():'+soundManager.supported()+')');
		return null;
	}
}
HTML5AudioNow.onready = function(fn) {
	if (this.ready) {
		fn();
	} else {
		window.setTimeout(function(){HTML5AudioNow.onready(fn);},100);
	}
}

var HTML5AudioSupport = new function() {
	var initialized = false;
	var audioObjSupport = false;
	var basicAudioSupport = false;
	var audioTagSupport = false;
	var htmlAudioTag = undefined;
	var htmlAudioObj = undefined;
	
	function init() {
		audioTagSupport = !!((htmlAudioTag = document.createElement('audio')).canPlayType);
		try {
			htmlAudioObj = new Audio(""); // The 'attributes.src' parameter is mandatory in Opera 10, so have used an empty string "", otherwise an exception is thrown.
			audioObjSupport = !!(htmlAudioObj.canPlayType);
			basicAudioSupport = !!(!audioObjSupport ? htmlAudioObj.play : false); // has the canPlayType(mime) method available ?
		} catch (e) {
			audioObjSupport = false;
			basicAudioSupport = false;
		}
	}
	
	this.supportsTag = function() {
		if (!initialized) init();
		return audioTagSupport;
	}
	
	this.supportsObj = function() {
		if (!initialized) init();
		return audioObjSupport;
	}
	
	this.supportsTagMime = function(mime) {
		if (!this.supportsTag()) return "";
		var canPlayTag = audioTagSupport ? htmlAudioTag.canPlayType(mime).toLowerCase() : "";
		if (!canPlayTag || canPlayTag === "no") canPlayTag = "";
		else if (canPlayTag.toLowerCase() !== "probably") canPlayTag = "maybe";
		return canPlayTag;
	}
	
	this.supportsObjMime = function(mime) {
		if (!this.supportsObj()) return "";
		var canPlayObj = audioObjSupport ? htmlAudioObj.canPlayType(mime).toLowerCase() : "";
		if (!canPlayObj || canPlayObj === "no") canPlayObj = "";
		else if (canPlayObj.toLowerCase() !== "probably") canPlayObj = "maybe";
		return canPlayObj;
	}
}
