/**
 * Default value for resolving the url to bookmarks.php
 * This value should be overwritten if a different URL is needed.
 */
Bookmark.scriptUrl = 'bookmarks.php';

/**
 * A bookmark.
 * @constructor
 */
function Bookmark() {
	this.uid = null;				// number (integer)	unique identifier for this bookmark (don't modify!)
	this.created = null;			// Date				date and time created
	this.modified = null;			// Date				date and time last modified
	// this.patronId				// number (integer)	field is not transmitted to the browser
	this.bookId = null;				// number (integer)	unique identifier for the book being read
	this.title = null;				// string			title of the bookmark
	this.text = null;				// string			textual note of this bookmark
	this.isPublic = null;			// boolean			whether or not this bookmark is public
	this.isReplyTo = null;			// number (integer)	reference to another bookmark (its uid)
	this.startTime = null;			// number (real)	time of bookmark start
	this.startCharOffset = null;	// number (integer)	number of letters into the referenced text
	this.endTime = null;			// number (real)	time of bookmark end
	this.endCharOffset = null;		// number (integer)	number of letters into the referenced text
	this.isLastmark = null;			// boolean			the lastmark has this set to true
}

/**
 * Check that the bookmark is valid.
 * 
 * @returns				whether or not the bookmark is valid (true/false).
 */
Bookmark.prototype.isValid = function() {
	// TODO
	return true;
};

/**
 * Sets the 'lastmark'-entry on the server. This should be done
 * frequently, since the 'lastmark'-entry on the server should reflect
 * the current position in the book being read.
 * 
 * @param	bookId		book-identifier
 * @param	time		decimal time counting seconds since start of book
 * @param	charOffset	integer counting characters into the text element
 * @param	callback	a callback-function on the form function(boolean success, string error)
 * @returns				string if error, false otherwise
 */
Bookmark.saveLastmark = function(bookId, time, charOffset, callback) {
	try {
		JSONRequest.post(
			Bookmark.scriptUrl,
			{
				'action': 'saveLastmark',
				'bookId': bookId,
				'time': time,
				'charOffset': charOffset
			},
			function (sn, response, e) {
				if (typeof callback === 'function') {
					try {
						//         JSON fail                     JSON success
						callback( (e ? null					   : (response?(response.returnValue?response.returnValue:null):null)), // boolean success
								  (e ? (e.name+': '+e.message) : (response?(response.error?response.error:null):null)) );	// string error
					} catch (z) {}
				}
			}
		);
	}
	catch (e) {
		//if (console) console.log(e);
		return e.name+': '+e.message;
	}
	
	return false;
};

/**
 * Sets the bookmark, creating or overwriting as necessary.
 * 
 * @param	bookmark	the Bookmark-instance to save
 * @param	callback	a callback-function on the form function(Bookmark bookmark, string error)
 * @returns				string if error, false otherwise
 */
Bookmark.saveBookmark = function(bookmark, callback) {
	try {
		JSONRequest.post(
			Bookmark.scriptUrl,
			{
				'action': 'saveBookmark',
				'bookmark': bookmark
			},
			function (sn, response, e) {
				if (typeof callback === 'function') {
					try {
						//         JSON fail                     JSON success
						callback( (e ? null					   : (response?(response.returnValue?response.returnValue:null):null)), // Bookmark bookmark
								  (e ? (e.name+': '+e.message) : (response?(response.error?response.error:null):null)) );	// string error
					} catch (z) {}
				}
			}
		);
	}
	catch (e) {
		//if (console) console.log(e);
		return e.name+': '+e.message;
	}
	
	return false;
};

/**
 * Deletes the bookmark specified by its uid
 * 
 * @param	uid			the unique identifier of the Bookmark-instance to delete (Bookmark.uid)
 * @param	callback	a callback-function on the form function(string error)
 * @returns				string if error, false otherwise
 */
Bookmark.deleteBookmark = function(uid, callback) {
	try {
		JSONRequest.post(
			Bookmark.scriptUrl,
			{
				'action': 'deleteBookmark',
				'uid': uid
			},
			function (sn, response, e) {
				if (typeof callback === 'function') {
					try {
						callback( e ? (e.name+': '+e.message) : (response?(response.error?response.error:null):null) );	// string error
					} catch (z) {}
				}
			}
		);
	}
	catch (e) {
		//if (console) console.log(e);
		return e.name+': '+e.message;
	}
	
	return false;
};

/**
 * Gets the position in the book where the patron stopped reading.
 * 
 * @param	bookId		book-identifier
 * @param	callback	a callback-function on the form function(Bookmark lastmark, string error)
 * @returns				string if error, false otherwise
 */
Bookmark.loadLastmark = function(bookId, callback) {
	try {
		JSONRequest.post(
			Bookmark.scriptUrl,
			{
				'action': 'loadLastmark',
				'bookId': bookId
			},
			function (sn, response, e) {
				if (typeof callback === 'function') {
					try {
						//         JSON fail                     JSON success
						callback( (e ? null					   : (response?(response.returnValue?response.returnValue:null):null)), // Bookmark bookmark
								  (e ? (e.name+': '+e.message) : (response?(response.error?response.error:null):null)) );	// string error
					} catch (z) {}
				}
			}
		);
	}
	catch (e) {
		//if (console) console.log(e);
		return e.name+': '+e.message;
	}
	
	return false;
};

/**
 * Gets one of the patrons private, or someone elses public, bookmark.
 * 
 * @param	uid			identifier of the bookmark to get
 * @param	callback	a callback-function on the form function(Bookmark bookmark, string error)
 * @returns				string if error, false otherwise
 */
Bookmark.loadBookmark = function(uid, callback) {
	try {
		JSONRequest.post(
			Bookmark.scriptUrl,
			{
				'action': 'loadBookmark',
				'uid': uid
			},
			function (sn, response, e) {
				if (typeof callback === 'function') {
					try {
						//         JSON fail                     JSON success
						callback( (e ? null					   : (response?(response.returnValue?response.returnValue:null):null)), // Bookmark bookmark
								  (e ? (e.name+': '+e.message) : (response?(response.error?response.error:null):null)) );	// string error
					} catch (z) {}
				}
			}
		);
	}
	catch (e) {
		//if (console) console.log(e);
		return e.name+': '+e.message;
	}
	
	return false;
};

/**
 * Gets a list of all of the patrons bookmarks in a book.
 * 
 * @param	bookId		book-identifier
 * @param	callback	a callback-function on the form function(Bookmark[] bookmarks, string error)
 * @returns				string if error, false otherwise
 */
Bookmark.loadBookmarks = function(bookId, callback) {
	try {
		JSONRequest.post(
			Bookmark.scriptUrl,
			{
				'action': 'loadBookmarks',
				'bookId': bookId
			},
			function (sn, response, e) {
				if (typeof callback === 'function') {
					try {
						//         JSON fail                     JSON success
						callback( (e ? null					   : (response?(response.returnValue?response.returnValue:null):null)), // Bookmark[] bookmarks
								  (e ? (e.name+': '+e.message) : (response?(response.error?response.error:null):null)) );	// string error
					} catch (z) {}
				}
			}
		);
	}
	catch (e) {
		//if (console) console.log(e);
		return e.name+': '+e.message;
	}
	
	return false;
};

/**
 * Lists all bookmarks in a book. The bookmarks is a list
 * of {uid,startTime,startCharOffset,title}-objects sorted chronologically
 * and ascending. For instance:
 * 
 * [
 *    { uid: '123', startTime: '1.23', startCharOffset: '123', title: 'asdf1' },
 *    { uid: '124', startTime: '1.24', startCharOffset: '124', title: 'asdf2' },
 *    { uid: '125', startTime: '1.25', startCharOffset: '125', title: 'asdf3' },
 *    { uid: '126', startTime: '1.26', startCharOffset: '126', title: 'asdf4' }
 * ]
 * 
 * @param	bookId		book-identifier
 * @param	callback	a callback-function on the form function(<as described above> bookmarkList, string error)
 * @returns				string if error, false otherwise
 */
Bookmark.listBookmarks = function(bookId, callback) {
try {
		JSONRequest.post(
			Bookmark.scriptUrl,
			{
				'action': 'listBookmarks',
				'bookId': bookId
			},
			function (sn, response, e) {
				if (typeof callback === 'function') {
					try {
						//         JSON fail                     JSON success
						callback( (e ? null					   : (response?(response.returnValue?response.returnValue:null):null)), // <as described above> bookmarkList
								  (e ? (e.name+': '+e.message) : (response?(response.error?response.error:null):null)) );	// string error
					} catch (z) {}
				}
			}
		);
	}
	catch (e) {
		//if (console) console.log(e);
		return e.name+': '+e.message;
	}
	
	return false;
};

if (!Date.now){Date.now = function now(){return +new Date();};}


/*
// --- Code for testing ---

//var marks = Bookmark.listBookmarks(1);

var bookmark = new Bookmark();
bookmark.created = Math.round(Date.now()/1000);
bookmark.modified = Math.round(Date.now()/1000);
bookmark.bookId = 1;
bookmark.title = "Bokmerke fra nettleseren";
bookmark.text = "Dette bokmerket er laget fra nettleseren.";
bookmark.isPublic = false;
bookmark.startTime = 0;
bookmark.startCharOffset = 0;
bookmark.endTime = 10;
bookmark.endCharOffset = 0;
bookmark.isLastmark = false;
//Bookmark.saveBookmark(bookmark);

var startTime = Date.now();
window.setInterval(function() {
	Bookmark.saveLastmark(1,(Date.now()-startTime)/1000,Math.floor(Math.random()*10));
},5000);

*/