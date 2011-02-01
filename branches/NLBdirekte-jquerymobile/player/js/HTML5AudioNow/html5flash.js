if (typeof TimeRanges === 'undefined') {
function TimeRanges() {
    //readonly
    this.length = 0;
    this.start = function(index) {
      return this.starts[index];
    };
    this.end = function(index) {
      return this.ends[index];
    };
    //private
    this.starts = [];
    this.ends = [];
    this.add = function(start, end) {
      this.starts.push(start);
      this.ends.push(end);
      this.length++;
    }
};
}

if (typeof MediaError === 'undefined') {
function MediaError() {
    //readonly
    this.code = -1;
    this.init = function(code) {
      this.code = code;
    };
    
    this.MEDIA_ERR_ABORTED = 1;
    this.MEDIA_ERR_NETWORK = 2;
    this.MEDIA_ERR_DECODE = 3;
    this.MEDIA_ERR_SRC_NOT_SUPPORTED = 4
};
}

function FlashHTMLAudioElement(attributes) {
  //network state
  this.NETWORK_EMPTY = 0;
  this.NETWORK_IDLE = 1;
  this.NETWORK_LOADING = 2;
  this.NETWORK_LOADED = 3;
  this.NETWORK_NO_SOURCE = 4;
  
  //ready state
  this.HAVE_NOTHING = 0;
  this.HAVE_METADATA = 1;
  this.HAVE_CURRENT_DATA = 2;
  this.HAVE_FUTURE_DATA = 3;
  this.HAVE_ENOUGH_DATA = 4;
  
  // error state
  //readonly attribute MediaError error;
  this.error = 0;
  
  //attribute DOMString src;
  this.src = null;
  //readonly attribute currentSrc
  this.currentSrc = null;
  //readonly attribute unsigned short networkState
  this.networkState = this.NETWORK_EMPTY;
  //attribute boolean autobuffer
  this.autobuffer = false;
  //readonly attribute TimeRanges buffered
  this.buffered = null;
  //readonly attribute
  this.readyState = this.HAVE_NOTHING;
  this.seeking = false;
  
  //playback state (floats)
  this.currentTime = 0.0;
  this.prevCurrentTime = 0.0; // so we can check if it's been updated by the user
  
  //readonly
  this.startTime = 0.0;
  //readonly
  this.duration = 0.0;
  this.paused = true;
  this.defaultPlaybackRate = 1.0;
  this.playbackRate = 1.0;
  //TimeRanges - readonly
  this.played = new TimeRanges();
  this.seekable = new TimeRanges();
  this.ended = false;
  this.autoplay = false;
  this.loop = false;
  this.controls = false;
  this.volume = 1;
  this.prevVolume = 1; // so we can check if it's been updated by the user
  this.muted = false;
  this.listeners = {};
  
  this.init = function(attributes) {
    this.src = attributes.src;
    this.autobuffer = (attributes.autobuffer!==null&&attributes.autobuffer);
    this.autoplay = (attributes.autoplay!==null&&attributes.autoplay);
    this.loop = (attributes.loop!==null&&attributes.loop);
    this.controls = false;
    this.load();
    
    if (attributes.id) {
      this.id = attributes.id;
    } else {
      this.id = "id" + (new Date().getTime());
    }
    this.sound = soundManager.createSound(this.createSound());
    
    this.throwEvent("loadstart");
    this.sound.wrapper = this;
  };
    
  this.onfinish = function(e) {
    this.wrapper.currentTime = 0;
    this.wrapper.prevCurrentTime = 0;
    this.wrapper.throwEvent("timeupdate");
    if (this.wrapper.loop) {
      this.wrapper.play();
    } else {
      this.wrapper.ended = true;
      this.wrapper.throwEvent("ended");
    }
  };
  
  this.onid3 = function() {
    this.wrapper.HAVE_METADATA;
    this.wrapper.throwEvent("loadedmetadata");
  };
  
  this.whileloading = function() {
    if (this.readyState==3) {
      this.wrapper.networkState = this.wrapper.NETWORK_LOADED;
      
      var durationchange = (this.wrapper.duration!=this.duration / 1000.) ? true : false;
      this.wrapper.duration = this.duration / 1000.;
      
      this.wrapper.readyState = this.wrapper.HAVE_ENOUGH_DATA;
      this.wrapper.updateSeekable(this.duration / 1000.);
      if (durationchange) this.wrapper.throwEvent("durationchange");
      this.wrapper.throwEvent("load");
    } else if (this.readyState==2) {
      //error
      this.wrapper.networkState = this.wrapper.NETWORK_NO_SOURCE;
      this.wrapper.throwEvent("error");
    } else if (this.readyState==1) {
      //loading
      this.wrapper.networkState = this.wrapper.NETWORK_LOADING;
      var durationchange = (this.wrapper.duration!=this.durationEstimate / 1000.) ? true : false;
      this.wrapper.duration = this.durationEstimate / 1000.;
      
      if (this.duration==this.position) {
        this.wrapper.readyState = this.wrapper.HAVE_CURRENT_DATA;
        this.wrapper.throwEvent("stalled");
      } else if (this.duration>this.position) {
        var canplay = (this.wrapper.readyState!=this.wrapper.HAVE_FUTURE_DATA) ? true : false;
        this.wrapper.readyState = this.wrapper.HAVE_FUTURE_DATA;
        if (canplay) this.wrapper.throwEvent("canplay");
        this.wrapper.throwEvent("progress");
      }
      
      if (!this.wrapper.loadeddata) {
        this.wrapper.loadeddata = true;
        this.wrapper.throwEvent("loadeddata");
      }
      
      if (durationchange) this.wrapper.throwEvent("durationchange");
      this.wrapper.updateSeekable(this.duration / 1000.);
    } else if (this.readyState==0) {
      //uninitialized
      this.wrapper.networkState = this.wrapper.NETWORK_EMPTY;
      this.wrapper.throwEvent("emptied");
    }
  };
  
  this.onload = function(success) {
    if (success) {
      this.wrapper.networkState = this.wrapper.NETWORK_LOADED;
      this.wrapper.readyState = this.wrapper.HAVE_ENOUGH_DATA;
      var durationchange = (this.wrapper.duration!=this.duration / 1000.) ? true : false;
      this.wrapper.duration = this.duration / 1000.;
      if (durationchange) this.wrapper.throwEvent("durationchange");
      this.wrapper.throwEvent("canplaythrough");
      this.wrapper.throwEvent("load");
    } else {
      this.wrapper.readyState = this.wrapper.HAVE_NOTHING;
      this.wrapper.networkState = this.wrapper.NETWORK_NO_SOURCE;
      this.wrapper.error = new MediaError(MediaError.prototype.NETWORK);
      this.wrapper.throwEvent("error");
    }
  };
  
  this.whileplaying = function() {
	if (this.wrapper.currentTime !== this.wrapper.prevCurrentTime) {
		this.setPosition(Math.floor(this.wrapper.currentTime*1000.));
	}
    this.wrapper.currentTime = this.position / 1000.;
	this.wrapper.prevCurrentTime = this.wrapper.currentTime;
	if (this.wrapper.volume !== this.wrapper.prevVolume) {
		this.setVolume(Math.floor(100*Math.max(0, Math.min(1,this.wrapper.volume))));
		this.wrapper.prevVolume = this.wrapper.volume;
	}
    this.wrapper.checkCueRanges(this.position / 1000.);
    this.wrapper.updatePlayed(this.position / 1000.);
    this.wrapper.throwEvent("timeupdate");
  };
  
  //updates the played time range
  this.updatePlayed = function(currentTime) {
    if (this.played.length==0) {
      //create a new time range
      this.played.add(this.startTime, currentTime);
    } else {
      //extend the last time range
      this.played.ends[this.played.length-1] = currentTime;
    }
  };
  
  //updates the played time range
  this.updateSeekable = function(currentTime) {
    if (this.seekable.length==0) {
      //create a new time range
      this.seekable.add(this.startTime, currentTime);
    } else {
      //extend the last time range
      this.seekable.ends[this.seekable.length-1] = currentTime;
    }
  };
  
  this.play = function() {
    if (this.muted) {
      this.sound.setVolume(0);
    } else {
      this.sound.setVolume(Math.floor(100*Math.max(0, Math.min(1,this.volume))));
    }
    
    if (this.sound.playState==1) {
      this.sound.setPosition(Math.max(Math.floor(this.currentTime * 1000.),1));
      this.paused = false;
      this.ended = false;
      this.sound.resume();
    } else {
      var that = this;
      this.sound.wrapper = that;
      this.paused = false;
      this.ended = false;
	  
      this.sound.play({
          onfinish: that.onfinish,
          whileplaying: that.whileplaying,
          position: Math.floor(that.startTime * 1000.),
          whileloading: that.whileloading,
          onid3: that.onid3
      });
    }
    this.throwEvent("play");
  };
  
  this.pause = function() {
    this.paused = true;
    this.sound.pause();
    this.throwEvent("pause");
  };

  
  //returns void
  this.load = function() {
    this.currentSrc = this.src;
	var ext = this.src.split('.');
	ext = ext[ext.length-1];
	ext = ext?ext.toLowerCase():ext;
	var mime = null;
	switch (ext) {
	case 'mp3': mime = 'audio/mpeg'; break;
	case 'wav': mime = 'audio/x-wav'; break;
	case 'aif': 
	case 'aifc': 
	case 'aiff': mime = 'audio/x-aiff'; break;
	}
	if (!this.canPlayType(mime)) {
      //media selection failed
      this.error = new MediaError(MediaError.prototype.MEDIA_ERR_SRC_NOT_SUPPORTED);
    }
  };
  
  this.addEventListener = function(type, listener, useCapture) {
    if (this.listeners[type]) {
      this.listeners[type].push(listener);
    } else {
      this.listeners[type] = [ listener ];
    }
  };
  
  this.removeEventListener = function(type, listener, useCapture) {
    var newarray = [];
    if (this.listeners[type]) {
      var oldarray = this.listeners[type];
      for (var i=0;i<oldarray.length;i++) {
        if (oldarray[i]!=listener) {
          this.newarray.push(oldarray[i]);
        }
      }
    }
    this.listeners[type] = newarray;
  };
  
  this.throwEvent = function(type) {
    var that = this;
    var e = {
      type: type,
      target: that,
      currentTarget: that,
      eventPhase: 2,
      bubbles: false,
      cancelable: false,
      timeStamp: new Date(),
      stopPropagation: function() {},
      preventDefault: function() {},
      initEvent: function() {}
    };
    
    if (this.listeners[type]) {
      for (var i=0;i<this.listeners[type].length;i++) {
        var listener = this.listeners[type][i];
        try {
          this.listener.call(this, e);
        } catch (t) {/*go through all event listeners*/}
      }
    }
  };
  
  //returns void
  //className, id - String
  //start, end - float
  //pauseOnExit - boolean
  //enterCallback, exitCallback - function
  this.addCueRange = function(className, id, start, end, pauseOnExit, enterCallback, exitCallback) {
    if (!this.cueRanges[className]||this.cueRanges[className]==null) {
      this.cueRanges[className] = [];
    }
    
    var cueRange = {
      start: start,
      end: end,
      pauseOnExit: pauseOnExit,
      enterCallback: enterCallback,
      exitCallback: exitCallback
    };
    this.cueRanges[className].push(cueRange);
  };
  
  this.removeCueRange = function(className) {
    //reset cue ranges
    this.cueRanges[className] = [];
  };
  
  //private
  this.cueRanges = {};
  this.lastPosition = 0.0;
  
  this.checkCueRanges = function(currentPosition) {
    try {
      var entering = new Array();
      var exititing = new Array();
      for (className in this.cueRanges) {
        if (this.cueRanges.hasOwnProperty(className)) {
          var cues = this.cueRanges[className];
          for (var i=0;i<cues.length;i++) {
            var cue = cues[i];
            if (currentPosition>this.lastPosition) {
              if (cue.start>this.lastPosition&&cue.start<currentPosition) {
                this.entering.push(cue);
              }
              if (cue.end>this.lastPosition&&cue.end<currentPosition) {
                this.exititing.push(cue);
              }
            } else if (currentPosition<this.lastPosition) {
              if (cue.end>this.lastPosition&&cue.end<currentPosition) {
                this.entering.push(cue);
              }
              if (cue.start>this.lastPosition&&cue.start<currentPosition) {
                this.exititing.push(cue);
              }
            }
          }
        }
      }
      
      //call the entering events
      for (var i=0;i<entering.length;i++) {
        if (entering[i].enterCallback) {
          this.entering[i].enterCallback.call(this, entering[i].id);
        }
      }
      //call the exiting events
      for (var i=0;i<exititing.length;i++) {
        if (exititing[i].exitCallback) {
          this.exititing[i].exitCallback.call(this, exititing[i].id);
        }
        if (exititing[i].pauseOnExit) {
          this.pause();
        }
      }
    } catch (e) {
      //if (console) console.error(e);
    }
    this.lastPosition = currentPosition;
  };
  
  this.id = "id" + (new Date().getTime());
  this.sound = null;
	
    //no additional properties
    this.createSound = function() {
      var that = this;
      var soundconfig = {
          id: that.id,
          url: that.currentSrc,
          autoLoad: that.autobuffer,
          autoPlay: that.autoplay,
          whileloading: that.whileloading,
          onid3: that.onid3
      };
      
	  return soundconfig;
    };
	
	this.destroySound = function() {
		soundManager.destroySound(this.id);
	}
    
    this.canPlayType = function(type) {
      if (type.match(/^audio\/mp3/)) {
        return "probably";
      }
      if (type.match(/^audio\/(x-)?wav/)) {
        return "probably";
      }
      if (type.match(/^audio\/(x-)?aiff/)) {
        return "probably";
      }
      if (type.match(/^audio\//)) {
        return "maybe";
      }
      return "";
    }
	
	this.init(attributes);
}
