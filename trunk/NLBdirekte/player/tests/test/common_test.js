TestCase("common", {
	"test the global variable serverUrl must be defined and be a string": function() {
		assertNotUndefined("serverUrl is defined",serverUrl);
		assertString("serverUrl is string",serverUrl);
	},
	
	"test the global variable bookmarksEnabled must be defined and be a boolean": function() {
		assertNotUndefined("bookmarksEnabled is defined",bookmarksEnabled);
		assertBoolean("bookmarksEnabled is boolean",bookmarksEnabled);
	},
	
	"test if bookmarksEnabled is true, then the global variable bookmarksUrl must be defined and be a string": function() {
		if (bookmarksEnabled) {
			assertNotUndefined("bookmarksUrl is defined",bookmarksUrl);
			assertString("bookmarksUrl is string",bookmarksUrl);
		}
	},
	
	"test the global variable debug must be defined and be a boolean": function() {
		assertNotUndefined("debug is defined",debug);
		assertBoolean("debug is boolean",debug);
	},
	
	"test the global variable log4javascript_disabled must be defined and be a boolean": function() {
		assertNotUndefined("log4javascript_disabled is defiend",log4javascript_disabled);
		assertBoolean("log4javascript_disabled is boolean",log4javascript_disabled);
	},
	
	"test if log4javascript_disabled is false, then the global variable logging_client_level must be defined and be one of the valid strings": function() {
		assertNotUndefined("client logging level is defined",logging_client_level);
		assertString("client logging level is string",logging_client_level);
		assertTrue("client logging level is valid",
						logging_client_level == "ALL"
					||	logging_client_level == "TRACE"
					||	logging_client_level == "DEBUG"
					||	logging_client_level == "INFO"
					||	logging_client_level == "WARN"
					||	logging_client_level == "ERROR"
					||	logging_client_level == "FATAL"
					||	logging_client_level == "OFF");
	},
	
	"test if log4javascript_disabled is false, then the global variable logging_server_level must be defined and be one of the valid strings": function() {
		assertNotUndefined("server logging level is defined",logging_server_level);
		assertString("server logging level is string",logging_server_level);
		assertTrue("server logging level is valid",
						logging_server_level == "ALL"
					||	logging_server_level == "TRACE"
					||	logging_server_level == "DEBUG"
					||	logging_server_level == "INFO"
					||	logging_server_level == "WARN"
					||	logging_server_level == "ERROR"
					||	logging_server_level == "FATAL"
					||	logging_server_level == "OFF");
	}
});
