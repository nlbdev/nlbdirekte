function NLBServer(ticket_) {
	var that = this;
	
	this.ticket = ticket_;
	this.url = '';
	this.state = "";
	
	this.getUrl = function(filename) {
		return this.url+'getfile.php?ticket='+this.ticket+'&file='+filename;
	};
	this.readyUrl = function() {
		return this.url+'isprepared.php?ticket='+this.ticket;
	}
	
	// Retrieves an XML or HTML document
	// Returns true if successfully sent request for file, false otherwise.
	var failureThreadTimeout = null;
	this.loadXmlFile = function(filename, callbackSuccess, callbackFailure) {
		// wait for turn (load only one file at a time)
		if (this.isLoadingFile) {
			window.setTimeout(delegate(that,function(){this.loadXmlFile(filename, callbackSuccess, callbackFailure);}),50);
			return;
		}
		this.isLoadingFile = true;
		
		this.state = 'preparing to load "'+this.getUrl(filename)+'"';
		
		// get the XMLHttpRequest-object, and fall back to Microsofts ActiveX-versions for their older IE-browsers
		var xmlhttp = null;
		try { xmlhttp = new XMLHttpRequest(); }
		catch (e1) { try { xmlhttp = new ActiveXObject("Msxml2.XMLHTTP.6.0"); }
		catch (e2) { try { xmlhttp = new ActiveXObject("Msxml2.XMLHTTP.5.0"); }
		catch (e3) { try { xmlhttp = new ActiveXObject("Msxml2.XMLHTTP.4.0"); }
		catch (e4) { try { xmlhttp = new ActiveXObject("Msxml2.XMLHTTP.3.0"); }
		catch (e5) { try { xmlhttp = new ActiveXObject("Msxml2.XMLHTTP"); }
		catch (e6) { try { xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); }
		catch (e7) { xmlhttp = null; }}}}}}}
		if (!xmlhttp) {
			this.isLoadingFile = false;
			return false;
		}
		
		// request the file
		try {
			xmlhttp.open("GET", this.getUrl(filename), true);
			xmlhttp.onreadystatechange = delegate(that,function() {
				if (xmlhttp.readyState === 4) {
					window.clearTimeout(failureThreadTimeout);
					this.state = 'successfully loaded "'+this.getUrl(filename)+'"';
					var xmlDoc;
					if (DOMParser) {
						var parser = new DOMParser();
						try {
							xmlDoc = parser.parseFromString(xmlhttp.responseText,"text/xml");
						}
						catch (e) {
							if (console) console.log("Unable to parse responseText: \n"+xmlhttp.responseText);
							throw e;
						}
					}
					else { // Internet Explorer
						//var withoutDoctype = xmlhttp.responseText.replace(/<!DOCTYPE[^>]*>/i,'');
						//xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
						//xmlDoc.async="false";
						//xmlDoc.loadXML(withoutDoctype);
						window.alert('NLBdirekte fungerer dessverre inntil videre ikke med Internet Explorer 8 eller eldre. Det anbefales � bruke en annen nettleser enn Internet Explorer, eller alternativt Internet Explorer 9.');
					}
					if (this.isLoadingFile === true) {
						this.isLoadingFile = false;
						if (typeof callbackSuccess === 'function') {
							callbackSuccess(xmlDoc);
						}
					}
				}
			});
			if (xmlhttp.overrideMimeType)
				xmlhttp.overrideMimeType('text/xml');
			xmlhttp.send();
			this.state = 'requested "'+this.getUrl(filename)+'"';
			failureThreadTimeout = window.setTimeout(delegate(that,function() {
				this.state = 'timed out while loading "'+this.getUrl(filename)+'"';
				if (this.isLoadingFile === true) {
					this.isLoadingFile = false;
					if (typeof callbackFailure === 'function') {
						callbackFailure();
					}
				}
			}), 10000);
		}
		catch(z) {
			this.state = 'exception occured while trying to load "'+this.getUrl(filename)+'": '+z;
			window.clearTimeout(failureThreadTimeout);
			this.isLoadingFile = false;
			if (typeof callbackFailure === 'function') {
				callbackFailure();
			}
			return false;
		}
		return true;
	};
	
	// Used to make sure that 'this' points to the right object
	function delegate(instance, method) {
		return function() {
			return method.apply(instance, arguments);
		};
	}
	
	this.state = "initialized";
}
