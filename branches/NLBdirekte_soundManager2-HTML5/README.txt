= NLBdirekte =
by Jostein Austvik Jacobsen, NLB, 2010

System requirements:
- Calabash and Tagsoup (xmlcalabash.com / http://home.ccil.org/~cowan/XML/tagsoup/)
- PHP
- Python

== ./index.html ./jquery-1.4.2.min.js ./favicon.ico ==
Test page simulating a log-in screen. Lets you choose a book and username.

== ./books ==
This folder is the "general DMZ area" which contains all the books. The
current structure used is as follows:

  * books/
    * <bookId>/
	  * (...the book...)
	* <bookId>/
	  * ...
	* <bookId>/
	  * ...

== ./profiles ==
Profile storage. The JSON files are stored to the users profile.
Currently the following folder structure is used:

  * profiles/
    * <patronId>/
	  * <bookId>/
	    * smil.json
		* metadata.json
		* toc.json
		* pagelist.json
		* (...other stuff...)
	  * <bookId>/
	    * ...
	  * ...

== ./player ==
This is NLBdirekte itself.

=== server-side ===

==== ./player/direkte.php ====
This is mostly HTML, and is what you are redirected to when opening
the player. For instance, a link to NLBdirekte could look something
like this:

http://128.39.10.**/NLBdirekte/player/direkte.php?ticket=***

direkte.php also reference stylesheets in ./player/css and
images in ./player/img.

==== ./player/isprepared.php ====
NLBdirekte starts by querying this script to see if the book is ready
to be played back. If it's not ready, isprepared will start the XProc
script prepare/prepare.xpl, which in turn also executes the python
script prepare/prepare.py.

NLBdirekte sends the following request:

http://128.39.10.**/NLBdirekte/player/isprepared.php?ticket=***

and gets in return:

{"ready":"0", "state":"book does not exist"}
  or
{"ready":"0", "state":"book is being prepared"}
  or
{"ready":"1", "state":"book is ready for playback"}

NLBdirekte will keep requesting (every ten seconds or so) this web service
until it receives "ready" = "1".

==== ./player/getfile.php ====
This is used to request a single file from a book. It is used as follows:

http://128.39.10.**/NLBdirekte/player/getfile.php?ticket=***&file=***

1. If the file exists in the profile storage; return it
2. If the file exists in the general storage; return it
3. File does not exist

==== ./player/prepare/ ====
These are the scripts that makes the JSON-files based on the DAISY 2.02 book.
They use a combination of XProc, XSLT and Python,
and are run from ./player/isprepared.php

=== client-side ===

==== ./player/js/HTML5AudioNow/ ====
Based on SoundManager2, and provides audio playback capability.

==== ./player/js/JSON/ ====
JSON library that provides a JSONRequest implementation, based on
the browsers XMLHTTPRequest implementation.

==== ./player/js/Bookmarks.js ====
An old web service for storing bookmarks. Not in use anymore.

==== ./player/js/Daisy202Loader.js ====
Object that handles the initial loading of the Daisy 2.02 book.

==== ./player/js/NLBServer.js ====
Object that represents NLBs servers.

==== ./player/js/SmilPlayer.js ====
The core player object, storing the table of contents, list of pages,
metadata, JsonML-SMIL-objects, controlling current position in audio
and text. Exposes functions to control playback:

  * skipToTime(ms)
  * skipToId(id)
  * skipToPage(pagenum)
  * play()
  * pause()
  * stop()
  * setVolume(volume)
  * getVolume()
  * getCurrentTime()
  * getTotalTime()
  * getPage()
  * isPlaying()

==== ./player/js/SmilPlayerUI.js ====
Connects SmilPlayer.js to the actual frontend given
by ./player/direkte.php.

