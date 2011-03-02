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
	
	this.xmlToHtml = function(documentElement) {
		// Daisy 2.02 documents is already HTML, so there is little to do here
		
		// Extract the body element
		var textObject = documentElement.getElementsByTagName('body');
		if (textObject !== null && textObject.length > 0)
			textObject = textObject[0];
		else if (textObject.length === 0)
			return null;
		
		// Remove all links from the documents (we'll probably be
		// adding proper ones ourselves afterwards)
		var a = textObject.getElementsByTagName('a');
		for (var i = a.length-1; i >= 0; i--) {
			var textNode = this.player.textDocument.createTextNode(a[i].innerHTML);
			var parentNode = a[i].parentNode;
			parentNode.insertBefore(textNode, a[i]);
			parentNode.removeChild(a[i]);
			a[i] = null;
		}
		
		return textObject;
	};
	
	var loadCount = 0;
	this.load = function() {
		try {
			JSONRequest.get(this.server.readyUrl(), delegate(that,function(sn, response, exception){
				if (exception) {
					//alert("Får ikke kontakt med NLB ("+exception+")");
					window.setTimeout(this.load,1000);
				}
				else if (response.ready !== true) {
					//alert(response.state);
					this.state = response.state;
					this.prepareStartTime = response.startTime;
					this.prepareEstimatedRemainingTime = response.estimatedRemainingTime;
					this.prepareProgress = response.progress;
					window.setTimeout(this.load,1000);
				}
				else {
					//alert("is ready! ("+response.state+")");
					this.prepareEstimatedRemainingTime = 0;
					this.prepareProgress = 100;
					this.loadReady();
				}
			}));
		} catch(e) {
			window.setTimeout(this.load,1000);
			if (typeof log=='object') log.warn("caught exception: "+e);
		}
	}
	this.loadReady = function() {
		this.state = 'loading metadata';
		try {
			JSONRequest.get(this.server.getUrl("metadata.json"), delegate(that,function(sn, response, exception){
				this.player.metadata = response;
				if (++loadCount === 4) this.player.doneLoading = true;
			}));
		} catch(e1) {
			if (typeof log=='object') log.warn("caught exception: "+e1);
		}
		this.state = 'loading smil';
		try {
			JSONRequest.get(this.server.getUrl("smil.json"), delegate(that,function(sn, response, exception){
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
		} catch(e2) {
			alert("caught exception: "+e2);
		}
		this.state = 'loading toc';
		try {
			JSONRequest.get(this.server.getUrl("toc.json"), delegate(that,function(sn, response, exception){
				this.player.toc = response;
				if (++loadCount === 4) this.player.doneLoading = true;
			}));
		} catch(e3) {
			alert("caught exception: "+e3);
		}
		this.state = 'loading pagelist';
		try {
			JSONRequest.get(this.server.getUrl("pagelist.json"), delegate(that,function(sn, response, exception){
				this.player.pagelist = response;
				if (++loadCount === 4) this.player.doneLoading = true;
			}));
		} catch(e4) {
			alert("caught exception: "+e4);
		}
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
