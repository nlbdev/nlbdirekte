/*
 *	Configuration file for client-side of NLBdirekte.
 */

// URL to NLBdirekte
var serverUrl = 'http://'+window.location.host+'/NLBdirekte/player/';

// Debug
var debug = true;

// Logging (levels: 'ALL' = 'TRACE' < 'DEBUG' < 'INFO' < 'WARN' < 'ERROR' < 'FATAL' < 'OFF')
var log4javascript_disabled = false;
var logging_client_level = 'INFO';
var logging_server_level = 'DEBUG';