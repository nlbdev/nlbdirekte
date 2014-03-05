/*
 *	Default configuration for client-side of NLBdirekte.
 */

// URL to NLBdirekte
var serverUrl = 'http://'+window.location.host+'/NLBdirekte/player/';

// Bookmarks
var bookmarksEnabled = false;
var bookmarksUrl = 'bookmarks.php';

// Debug
var debug = true;

// Logging (levels: 'ALL' = 'TRACE' < 'DEBUG' < 'INFO' < 'WARN' < 'ERROR' < 'FATAL' < 'OFF')
var log4javascript_disabled = false;
var logging_client_level = 'DEBUG';
var logging_server_level = 'DEBUG';

function iso369ToText(txt) {
	// placeholder function. override it in config.js.
	return txt;
}