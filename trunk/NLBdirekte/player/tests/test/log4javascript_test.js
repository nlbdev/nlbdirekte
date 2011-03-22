TestCase("log4javascript", {
	setUp: function() {
		log4javascript.logLog.setQuietMode(true);
		this.log = log4javascript.getLogger();
		this.browserConsoleAppender = new log4javascript.BrowserConsoleAppender();
		//var browserConsoleLayout = new log4javascript.PatternLayout("%d{HH:mm:ss} %-5p - %m%n");
		//browserConsoleAppender.setLayout(browserConsoleLayout);
		if (typeof logging_client_level=="string") switch (logging_client_level) {
			case 'ALL':		browserConsoleAppender.setThreshold(log4javascript.Level.ALL);   break;
			case 'TRACE':	browserConsoleAppender.setThreshold(log4javascript.Level.TRACE); break;
			case 'DEBUG':	browserConsoleAppender.setThreshold(log4javascript.Level.DEBUG); break;
			case 'INFO':	browserConsoleAppender.setThreshold(log4javascript.Level.INFO);  break;
			case 'WARN':	browserConsoleAppender.setThreshold(log4javascript.Level.WARN);  break;
			case 'ERROR':	browserConsoleAppender.setThreshold(log4javascript.Level.ERROR); break;
			case 'FATAL':	browserConsoleAppender.setThreshold(log4javascript.Level.FATAL); break;
			case 'OFF':		browserConsoleAppender.setThreshold(log4javascript.Level.OFF);   break;
		}
		log.addAppender(browserConsoleAppender);
		this.ajaxAppender = new log4javascript.AjaxAppender(serverUrl+"log.php");
		this.jsonLayout = new log4javascript.JsonLayout();
		jsonLayout.setCustomField('ticket',ticket);
		jsonLayout.setCustomField('launchTime',launchTime);
		ajaxAppender.setLayout(jsonLayout);
		//ajaxAppender.setBatchSize(20);
		//ajaxAppender.setTimerInterval(5000);
		if (typeof logging_server_level=="string") switch (logging_server_level) {
			case 'ALL':		ajaxAppender.setThreshold(log4javascript.Level.ALL);   break;
			case 'TRACE':	ajaxAppender.setThreshold(log4javascript.Level.TRACE); break;
			case 'DEBUG':	ajaxAppender.setThreshold(log4javascript.Level.DEBUG); break;
			case 'INFO':	ajaxAppender.setThreshold(log4javascript.Level.INFO);  break;
			case 'WARN':	ajaxAppender.setThreshold(log4javascript.Level.WARN);  break;
			case 'ERROR':	ajaxAppender.setThreshold(log4javascript.Level.ERROR); break;
			case 'FATAL':	ajaxAppender.setThreshold(log4javascript.Level.FATAL); break;
			case 'OFF':		ajaxAppender.setThreshold(log4javascript.Level.OFF);   break;
		}
		log.addAppender(ajaxAppender);
	},
	
	tearDown: function () {
		
	},
	
	"test nothing": function() {
		assertEquals(true,true);
	}
});
