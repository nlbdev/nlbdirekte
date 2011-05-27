TestCase("NLBServer_init", {
	setUp: function() {
		this.server = new NLBServer();
	},
	
	tearDown: function () {
		delete this.server;
	},
	
	"test new this.server objects should have initialized as state": function() {
		assertEquals("initialized",this.server.state);
	},
	
	"test default this.server url is empty": function() {
		assertEquals("",this.server.url);
	},
	
	"test this.server with empty base url and empty argument url": function() {
		assertEquals("getfile.php?&file=",this.server.getUrl());
	},
	
	"test empty base url and no parameters": function() {
		assertEquals("isprepared.php?",this.server.readyUrl());
	}
});

TestCase("NLBServer_url", {
	setUp: function() {
		this.server = new NLBServer();
		this.server.url = 'http://example.org/';
	},
	
	tearDown: function () {
		delete this.server;
	},
	
	"test this.server with base url, empty argument url and no parameters": function() {
		assertEquals("http://example.org/getfile.php?&file=",this.server.getUrl());
	},
	
	"test this.server with base url, argument url and no parameters": function() {
		assertEquals("http://example.org/getfile.php?&file=file.txt",this.server.getUrl("file.txt"));
	},
	
	"test base url and no parameters": function() {
		assertEquals("http://example.org/isprepared.php?",this.server.readyUrl());
	}
});

TestCase("NLBServer_params", {
	setUp: function() {
		this.server = new NLBServer("param1&param2");
	},
	
	tearDown: function () {
		delete this.server;
	},
	
	"test this.server with empty base url, two parameters and no argument url": function() {
		assertEquals("getfile.php?param1&param2&file=",this.server.getUrl());
	}
});
	
TestCase("NLBServer_param_url", {
	setUp: function() {
		this.server = new NLBServer("param1&param2");
		this.server.url = "http://example.org/";
	},
	
	tearDown: function () {
		delete this.server;
	},
	
	"test this.server with base url, two parameters and no argument url": function() {
		assertEquals("http://example.org/getfile.php?param1&param2&file=",this.server.getUrl());
	},
	
	"test this.server with base url, two parameters and argument url": function() {
		assertEquals("http://example.org/getfile.php?param1&param2&file=file.txt",this.server.getUrl("file.txt"));
	},
	
	"test base url and two parameters": function() {
		assertEquals("http://example.org/isprepared.php?param1&param2",this.server.readyUrl());
	}
});