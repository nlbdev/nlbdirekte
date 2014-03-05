function NLBServer(get_params_) {
	var that = this;
	
	this.get_params = typeof get_params_ === 'undefined'?"":get_params_;
	this.url = "";
	this.state = "";
	
	this.getUrl = function(filename) {
		// Note: SmilPlayer.js depends on NLBServer.getUrl returning the
		// same URL each time for the same filename.
		return this.url+'getfile.php?'+this.get_params+'&file='+(typeof filename==='undefined'?"":filename);
	};
	this.readyUrl = function() {
		return this.url+'isprepared.php?'+this.get_params;
	}
	
	this.state = "initialized";
}
