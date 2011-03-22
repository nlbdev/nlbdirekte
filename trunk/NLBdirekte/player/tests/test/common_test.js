TestCase("common", {
	"test the global variable serverUrl must be defined and be a string": function() {
		assertNotUndefined(serverUrl);
		assertString(serverUrl);
	},
	
	"test the global variable bookmarksEnabled must be defined and be a boolean": function() {
		assertNotUndefined(bookmarksEnabled);
		assertBoolean(bookmarksEnabled);
	},
	
	"test if bookmarksEnabled is true, then the global variable bookmarksUrl must be defined and be a string": function() {
		if (bookmarksEnabled) {
			assertNotUndefined(bookmarksUrl);
			assertString(bookmarksUrl);
		}
	},
	
	"test the global variable debug must be defined and be a boolean": function() {
		assertNotUndefined(debug);
		assertBoolean(debug);
	},
	
	"test the global variable log4javascript_disabled must be defined and be a boolean": function() {
		assertNotUndefined(log4javascript_disabled);
		assertBoolean(log4javascript_disabled);
	},
	
	"test if log4javascript_disabled is false, then the global variable logging_client_level must be defined and be one of the valid strings": function() {
		assertNotUndefined(logging_client_level);
		assertString(logging_client_level);
		assertTrue(		logging_client_level == "ALL"
					||	logging_client_level == "TRACE"
					||	logging_client_level == "DEBUG"
					||	logging_client_level == "INFO"
					||	logging_client_level == "WARN"
					||	logging_client_level == "ERROR"
					||	logging_client_level == "FATAL"
					||	logging_client_level == "OFF");
	},
	
	"test if log4javascript_disabled is false, then the global variable logging_server_level must be defined and be one of the valid strings": function() {
		assertNotUndefined(logging_server_level);
		assertString(logging_server_level);
		assertTrue(		logging_server_level == "ALL"
					||	logging_server_level == "TRACE"
					||	logging_server_level == "DEBUG"
					||	logging_server_level == "INFO"
					||	logging_server_level == "WARN"
					||	logging_server_level == "ERROR"
					||	logging_server_level == "FATAL"
					||	logging_server_level == "OFF");
	}
});
