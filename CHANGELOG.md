Changelog for major- and minor-version releases
(bugfix-releases not mentioned)

0.1: Start of versioning. Rewritten prepare-procedure
		based on XProc, XSLT and Python starting
		on-demand from a PHP-script when the user opens
		the book in NLBdirekte for the first time.
0.2: Works in both Windows and Linux. 
0.3: Attempt at reverting to pure SoundManager2-playback,
		without going through the HTML5AudioNow-script.
0.4: Based on 0.2, has improved browser compatibility,
		most notably IE9 (although Flash in IE9 is still
		buggy at best). Chromium/Chrome, Firefox and
		Opera seems to work best. Safari has issues.
0.5: Quick attempt at improving mobile phone compatibility.
0.6: Check whether the server is currently preparing the
		book, and if so, don't start yet another process
		preparing the book. (only Windows, Linux not
		implemented yet)
0.7: Same as 0.6, but with Linux support.
0.8: DTD resolution against a catalog of cached DTDs is
		implemented and works in Linux. Next up is
		Windows. This DTD resolution is needed since it
		turns out that a lot of XML-files target
		non-existing files. Also, in an operational
		environment DTDs should be cached anyway.
		There is a bug with p:http-request in Calabash
		(currently 0.9.30) as it does not make use
		of the URI resolver given to Calabash.
0.9: DTD resolution for Windows. Updated installation
		instrunctions.
0.10: Accesskeys for the menu buttons (map to numbers 1-6),
		Prettier TOC, Prettier pagelist (fixed pagelist
		not being created in python script as well)
0.11: config.inc.php and config.js are now used to configure
		each specific installation of NLBdirekte.
0.12: Replaced the HTML5Now-module with pure SoundManager2.
		Audio playback now works across more browsers but
		stuttering and other problems have been introduced.
		Further debugging and improvements are still needed
		on the audio playback front.
0.13: Tried with configurable DamnIT-integration. Then
		removed it and introduced log4javascript as an
		optional dependency for logging. log4javascript
		replaces all the 'console.log'-calls that caused
		problems earlier. log4javascript is configurable
		through config.js and logs to both Firebug-style
		browser-logs as well as server-side log files
		through JSON XMLHttpRequests.
0.14: Moved from jQuery UI to jQuery Mobile. Moved controls
		from top to bottom. Bigger buttons. Fixed positioning
		should now work on mobile devices, however jQuery Mobile
		is still in alpha so it may have bugs.
0.15: added a proper way to display logs through browselogs.php and
		viewlog.php. Also added detection of browser capabilities through "browscap", and
		desktop browsers (but not mobile) will now show text as well as icons on the
		buttons.
0.16: - Percentage complete, time started and estimated time remaining
			has been added to the JSON-response from isprepared.php.
		- Replaced jQuery Mobile alpha 3 with a newer version
			compiled from source on 2010-03-01, which has better fixed
			positioning support than in alpha 3. The menu are now
			always present as well. No need to toggle it on and off by
			tapping the page.
		- Python logging has been introduced, which was needed to report
			percentage complete and estimated time
			remaining for the preparation process.
		- The pages displaying settings, metadata, table of contents and list
			of pages are now working with jQuery Mobile.
		- Images will resize to fit browser windows smaller than themselves.
0.17: Improved accessibility
			- ARIA: mark up buttons and menus (role="navigation" etc)
			- ARIA: mark up dynamic content as dynamic (aria-live attribute)
		Feedback in browser:
			- "Book does not exist", "Book is being prepared", etc.
			- Progress bar showing estimated time remaining.
				- opted for percentage for now. jQM will probably provide a bar later
		Introduce jQuery as a dependency ("don't reinvent the wheel!")
			- use jQuery for AJAX-calls (XML, HTML and JSON)
				- Which means removing all dependencies on the JSON-libraries used so far
		Hook soundManager2 debugging into log4javascript
			- alternatively at least have SmilPlayerUI.js trigger a logging event at some
			  point to log support for flash, mp3, ogg, wav etc.
				- implementation: all logging output from SoundManager? are forwarded to
				  the logging framework
		support for line number and filename for javascript log entries
			- partly implemented now. fatal errors in browsers that support window.onerror
			  will now contain filename and linenumber as well as a stacktrace
		Updated Soundmanager to newest version ("V2.97a.20110306 - HTML5 audio updates,
		    Flash/HTML5 mode detection, IE flash init tweaks, reuse and instance options fixes")
0.18: Improved accessibility
			Conduct user testing
				Player didn't work in IE, so there was little useful feedback
		Bookmarks
			store last playback position
		Bugs
			HTML in links are escaped when unwrapped
			Python is unable to infer total time in some cases (see for instance book #614182)
			On some computers using Firefox 3.6: audioObject.setVolume() not defined and audioObject.duration NaN
				I think it's fixed now. Was not Firefox-specific. Happened when soundManager failed to initialize
			Firefox' audio playback does not work correctly
				won't change playing/pausing state unless clicking 'forward' or 'backward'
					added hack that constantly resumes or pauses depending on play state
			Does IE9 support getElementsByTagName as of 2011-03-03?
				I think it did. However, I removed all dependencies on getElementsByTagName and replaced them with jQuery queries
		Introduce jQuery as a dependency ("don't reinvent the wheel!")
			in general, use jQuery to simplify where possible
		Hook soundManager2 debugging into log4javascript
			should give the ability to detect whether flash is supported
				audio backend is now logged
0.19: Internet Explorer 9 is working. IE8 and IE7 not tested.
			- Fixed missing text bug in IE9
			- Fixed "innerHTML is null"-bug in IE8 (replaced with jQuery code)