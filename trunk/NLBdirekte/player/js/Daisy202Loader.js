function Daisy202Loader() {
	var that = this;
	
	this.server = null; // Server object, for getting files
	this.player = null; // SMIL player
	
	this.state = "";
	this.progress = 0.0; // should make a better progress-system
	
	this.smillistCopy = [];
	
	var isLoading = false;
	
	var theFlow = [];
	var currentFlow = -1;
	
	this.prepareEstimatedRemainingTime = -1;
	this.prepareProgress = 0;
	this.prepareStartTime = 0;
	this.errorCode = 0;
	
	this.xmlToHtml = function(documentElement) {
		// Daisy 2.02 documents is already HTML, so there is little to do here
		
		var textObject = $(documentElement).find('body').get(0);
		$(textObject).find('a').each(function(index, element) {
			$(this).parent().html($(this).html());
		});
		
		return textObject;
	};
	
	var loadCount = 0;
	this.load = function() {
		try {
			$.getJSON(this.server.readyUrl(), delegate(that,function(response, textStatus, jqXHR){
				if (response.ready == 0) {
					this.state = response.state;
					this.prepareStartTime = response.startTime;
					this.prepareEstimatedRemainingTime = response.estimatedRemainingTime;
					this.prepareProgress = response.progress;
					window.setTimeout(this.load,1000);
				}
				else if (response.ready == 1) {
					this.prepareEstimatedRemainingTime = 0;
					this.prepareProgress = 100;
					this.loadReady();
				}
				else if (response.ready == -1) {
					this.state = response.state;
					this.prepareEstimatedRemainingTime = 0;
					this.prepareProgress = 0;
					this.errorCode = response.ready;
					window.setTimeout(this.load,10000);
				} else {
					if (typeof log=='object') log.warning('Unknown response from readyUrl');
					if (typeof log=='object') log.warning(response);
				}
			}));
		} catch(e) {
			window.setTimeout(this.load,1000);
			if (typeof log=='object') log.warn("caught exception: "+e);
		}
	}
	this.loadReady = function() {
		this.state = 'loading metadata';
		$.getJSON(this.server.getUrl("metadata.json"), delegate(that,function(response, textStatus, jqXHR) {
			this.player.metadata = response;
			if (++loadCount === 4) this.player.doneLoading = true;
		}));
		this.state = 'loading smil';
		$.getJSON(this.server.getUrl("smil.json"), delegate(that,function(response, textStatus, jqXHR) {
			this.player.smil = response;
			var test = response;
			var txt;
			if (typeof test === 'undefined')
				txt = 'undefined';
			else if (test === null)
				txt = 'null';
			else {
				txt = '[ '+test[0];
				for (var i = 1; i < test.length; i++) {
					txt += ', ';
					if (test[i] instanceof Array)
						txt += 'array';
					else
						txt += typeof test[i];
				}
				txt += ' ]';
			}
			if (++loadCount === 4) this.player.doneLoading = true;
		}));
		this.state = 'loading toc';
		$.getJSON(this.server.getUrl("toc.json"), delegate(that,function(response, textStatus, jqXHR) {
			this.player.toc = response;
			if (++loadCount === 4) this.player.doneLoading = true;
		}));
		this.state = 'loading pagelist';
		$.getJSON(this.server.getUrl("pagelist.json"), delegate(that,function(response, textStatus, jqXHR) {
			this.player.pagelist = response;
			if (++loadCount === 4) this.player.doneLoading = true;
		}));
	};
	
	function stripFragment(src) {
		if (typeof src === 'string')
			return src.split('#')[0];
		else
			return src;
	}
	
	// Parametrized delegate - parameters defined at the point of construction of the delegate
	// Used to make sure that 'this' points to the right object
	// i.e. window.setTimeout( delegate( obj, obj.method, "parameter!" ), 1000 );
	function delegateParametrized(instance, method) {
		if (arguments.length > 2) {
			var params = [];
			for (var n = 2; n < arguments.length; ++n) params.push(arguments[n]);
			return function() { return method.apply(instance,params); };
		}
		else
			return function() { return method.call(that); };
	}
	// Normal delegate - parameters defined at the point of invocation of the resulting function
	function delegate(instance, method) {
		return function() {
			return method.apply(instance, arguments);
		};
	}
	
	this.state = "initialized";
}
