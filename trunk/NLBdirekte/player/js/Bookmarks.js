/**
 * Default value for resolving the url to bookmarks.php
 * This value should be overwritten if a different URL is needed.
 */
if (typeof bookmarksUrl != 'undefined')
	bookmarksUrl = 'bookmarks.php';

/**
 * A bookmark.
 * @constructor
 */
function Bookmark() {
	this.id = -1;			// number (integer)	unique identifier for this bookmark (don't modify!)
	this.created = -1;		// Date				date and time created
	this.modified = -1;		// Date				date and time last modified
	this.book = -1;			// number (integer)	unique identifier for the book being read
	this.position = -1;		// number (real)	time of bookmark
	this.lastmark = false;	// boolean			the lastmark has this set to true
	this.text = '';			// string			textual note of this bookmark
}

/**
 * Sets the 'lastmark'-entry on the server. This should be done
 * frequently, since the 'lastmark'-entry on the server should reflect
 * the current position in the book being read.
 * 
 * @param	position	decimal time counting seconds since start of book
 * @returns				string if error, false otherwise
 */
Bookmark.setLastmark = function(position) {
	$.ajax(bookmarksUrl,{
		data:{
			"format": "json",
			"ticket": ticket,
			"launchTime": launchTime,
			"function": "setLastmark",
			"position": position
		},
		success: function(data, textStatus, jqXHR) {
			if (typeof log=='object') log.debug('successfully saved lastmark: '+textStatus);
		},
		error: function(data, textStatus, jqXHR) {
			if (typeof log=='object') log.warn('failed to save lastmark: '+textStatus);
		}
	});
};

/**
 * Sets the bookmark, creating or overwriting as necessary.
 * 
 * @param	bookmark	the Bookmark-instance to save
 * @returns				string if error, false otherwise
 */
Bookmark.setBookmark = function(bookmark) {
	$.ajax(bookmarksUrl,{
		data:{
			"format": "json",
			"ticket": ticket,
			"launchTime": launchTime,
			"function": "setBookmark",
			"bookmark": bookmark
		},
		success: function(data, textStatus, jqXHR) {
			if (typeof log=='object') log.debug('successfully saved bookmark: '+textStatus);
		},
		error: function(data, textStatus, jqXHR) {
			if (typeof log=='object') log.warn('failed to save bookmark: '+textStatus);
		}
	});
};

/**
 * Deletes the bookmark specified by its id
 * 
 * @param	id			the unique identifier of the Bookmark-instance to delete (Bookmark.id)
 * @returns				string if error, false otherwise
 */
Bookmark.deleteBookmark = function(id) {
	$.ajax(bookmarksUrl,{
		data:{
			"format": "json",
			"ticket": ticket,
			"launchTime": launchTime,
			"function": "deleteBookmark",
			"id": id
		},
		success: function(data, textStatus, jqXHR) {
			if (typeof log=='object') log.debug('successfully deleted lastmark: '+textStatus);
		},
		error: function(data, textStatus, jqXHR) {
			if (typeof log=='object') log.warn('failed to delete bookmark: '+textStatus);
		}
	});
};

/**
 * Gets the position in the book where the patron stopped reading.
 * 
 * @param	callback	a callback-function on the form function(Bookmark lastmark) which are called *if* the request was successful
 * @returns				string if error, false otherwise
 */
Bookmark.getLastmark = function(callback) {
	$.ajax(bookmarksUrl,{
		data:{
			"format": "json",
			"ticket": ticket,
			"launchTime": launchTime,
			"function": "getLastmark"
		},
		context: callback,
		success: function(data, textStatus, jqXHR) {
			if (typeof log=='object') log.debug('successfully got lastmark: '+textStatus);
			var lastmark = new Bookmark();
			lastmark.id = data['response']['id'];
			lastmark.created = data['response']['created'];
			lastmark.modified = data['response']['modified'];
			lastmark.book = data['response']['book'];
			lastmark.position = data['response']['position'];
			lastmark.lastmark = data['response']['lastmark'];
			lastmark.text = data['response']['text'];
			this(lastmark);
		},
		error: function(data, textStatus, jqXHR) {
			if (typeof log=='object') log.warn('failed to get lastmark: '+textStatus);
			this(data.response);
		}
	});
};

/**
 * Gets one of the patrons private, or someone elses public, bookmark.
 * 
 * @param	id			identifier of the bookmark to get
 * @param	callback	a callback-function on the form function(Bookmark bookmark, string error)
 * @returns				string if error, false otherwise
 */
Bookmark.getBookmark = function(id, callback) {
	// not implemented
	if (typeof log=='object') log.debug('getBookmark is not implemented');
};

/**
 * Gets a list of all of the patrons bookmarks in a book.
 * 
 * @param	callback	a callback-function on the form function(Bookmark[] bookmarks, string error)
 * @returns				string if error, false otherwise
 */
Bookmark.getBookmarks = function(callback) {
	// not implemented
	if (typeof log=='object') log.debug('getBookmarks is not implemented');
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
Bookmark.listBookmarks = function(callback) {
	// not implemented
	if (typeof log=='object') log.debug('listBookmarks is not implemented');
};