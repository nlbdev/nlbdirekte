/*
 *	Configuration file for client-side of NLBdirekte.
 */

// URL to NLBdirekte
var serverUrl = 'http://'+window.location.host+'/NLBdirekte/player/';

// Bookmarks
var bookmarksEnabled = true;
var bookmarksUrl = 'bookmarks.php';

// Debug
var debug = true;

// Logging (levels: 'ALL' = 'TRACE' < 'DEBUG' < 'INFO' < 'WARN' < 'ERROR' < 'FATAL' < 'OFF')
var log4javascript_disabled = false;
var logging_client_level = 'DEBUG';
var logging_server_level = 'DEBUG';

// Custom extensions
$.getScript('custom/js/bokelskere.js');
$.getScript('lib/countrycodes-to-norwegian.js');

function iso369ToText(txt) {
	return countryCodeToNorwegian(txt);
}