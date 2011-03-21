<?php
/*
 *	bookmarks.php?data=...
 *	Jostein Austvik Jacobsen, NLB, 2011
 */
include('common.inc.php');

if ($_REQUEST['format'] === 'xml') { // ---- start of XML API ----
	// <-- This is where the DAISY Online Bookmarks API could be hooked in at a later stage.
	// <-- However, NLBdirekte uses the database field 'position' to determine the position
	// <-- of a lastmark/bookmark, while DAISY Online uses a combination of 'ncxRef', 'URI'
	// <-- and 'position'. So if both APIs are to be used; then some conversion between
	// <-- the two formats must be employed.
	echo "XML API not implemented. \$_REQUEST: ";
} else if ($_REQUEST['format'] === 'json') { // ---- end of XML API, start of JSON API ----
	
	header('Content-Type: application/json; charset=utf-8');
	/*
	# decode JSON-data
	$data = json_decode($_REQUEST,true);
	switch (json_last_error()) {
		// potential problem: unauthenticated users may hammer this
		// script with invalid JSON-data to fill up the logs with garbage.
		case JSON_ERROR_DEPTH:			trigger_error('json_decode - The maximum stack depth has been exceeded'); break;
		case JSON_ERROR_CTRL_CHAR:		trigger_error('json_decode - Control character error, possibly incorrectly encoded'); break;
		case JSON_ERROR_STATE_MISMATCH:	trigger_error('json_decode - Invalid or malformed JSON'); break;
		case JSON_ERROR_SYNTAX:			trigger_error('json_decode - Syntax error'); break;
	}*/

	# decode ticket here
	list($user, $book) = decodeTicket($_REQUEST['ticket']);

	// Not valid request?
	/*if (!(valid request)) {
		return "you are not logged in";
	}*/

	# if launchTime is set, use that to put log entries in its own log
	if (isset($_REQUEST['launchTime']))
		$logfile = microtimeAndUsername2logfile($_REQUEST['launchTime'],$user);
	
	if (empty($_REQUEST['function'])) {
		echo json_encode(array("response"=>false));
		trigger_error("'function' not defined");
	}
	else switch ($_REQUEST['function']) {
	case 'getBookmarks':
		echo json_encode(array("response"=>false)); // not implemented yet
		trigger_error("getBookmarks is not implemented yet");
		break;
	case 'setBookmark':
		echo json_encode(array("response"=>false)); // not implemented yet
		trigger_error("setBookmarks is not implemented yet");
		break;
	case 'deleteBookmark':
		echo json_encode(array("response"=>false)); // not implemented yet
		trigger_error("deleteBookmark is not implemented yet");
		break;
	case 'getLastmark':
		$bookmarksDao = new BookmarksDAO();
		$lastmark = $bookmarksDao->getLastmark($user, $book);
		if ($lastmark===null) {
			echo json_encode(array( "response" => false ));
			trigger_error("could not get lastmark (lastmark === null)");
		}
		else {
			$lastmarkArray = $lastmark->asArray();
			unset($lastmarkArray['user']);
			echo json_encode(array(
								"response" => $lastmarkArray
							));
		}
		if ($lastmark===null) trigger_error("could not get lastmark (lastmark === null)");
		break;
	case 'setLastmark':
		if (!is_numeric($_REQUEST['position'])) {
			echo json_encode(array("response"=>false));
			trigger_error("position is not numeric: "+$_REQUEST['position']);
		} else {
			$bookmarksDao = new BookmarksDAO();
			echo json_encode(array(
								"response" => $bookmarksDao->setLastmark($user, $book, $_REQUEST['position'])
							  ));
		}
	}

} else { // ---- end of JSON API ----
	
	echo "Unknown format: ".(empty($_REQUEST['format'])?'[undefinde]':$_REQUEST['format']);
	
}

class Bookmark {
	public $id = -1;				// int(11) , auto_increment
	public $created = -1;			// datetime
	public $modified = -1;			// datetime
	public $user = -1;				// int(11)
	public $book = -1;				// int(11)
	public $position = -1;			// double
	public $lastmark = false;		// tinyint(1)
	public $text = '';				// text
	
	public function __construct() {
		$modified = $created = time();
	}
	
	public function asArray() {
		return array(
			"id" => $this->id,
			"created" => $this->created,
			"modified" => $this->modified,
			"user" => $this->user,
			"book" => $this->book,
			"position" => $this->position,
			"lastmark" => $this->lastmark,
			"text" => $this->text
		);
	}
}

class BookmarksDAO {
	
	// ---- public functions ----
	
	public function __construct() {
		$this->connect();
	}
	
	public function getById($id) {
		$this->connect();
		$result = mysql_query("SELECT * FROM bookmarks WHERE id = ".$id);
		if ($error = mysql_error()) { trigger_error($error); return null; }
		else return $this->getFromResult($result);
	}
	
	public function getLastmark($user, $book) {
		$this->connect();
		$result = mysql_query("SELECT * FROM bookmarks WHERE user = '".$user."' AND book = '".$book."' AND lastmark = 1");
		if ($error = mysql_error()) { trigger_error($error); return null; }
		if (mysql_num_rows($result) > 0) {
			return $this->getFromResult($result);
		} else {
			return null;
		}
	}
	
	public function setLastmark($user, $book, $position) {
		$this->connect();
		$result = mysql_query("SELECT * FROM bookmarks WHERE user = '".$user."' AND book = '".$book."' AND lastmark = 1");
		if ($error = mysql_error()) { trigger_error($error); return null; }
		if (mysql_num_rows($result) > 0) {
			$lastmark = $this->getFromResult($result);
			$lastmark->position = $position;
			return $this->update($lastmark);
		} else {
			$lastmark = new Bookmark();
			$lastmark->user = $user;
			$lastmark->book = $book;
			$lastmark->lastmark = true;
			$lastmark->position = $position;
			return $this->insert($lastmark);
		}
	}
	
	public function delete(&$bookmark) {
		$this->connect();
		if ($bookmark->id >= 0) {
			mysql_query("DELETE FROM bookmarks WHERE id === ".$bookmark->id);
			if ($error = mysql_error()) { trigger_error($error); return true; }
			$bookmark->id = -1;
			return false;
		}
		else { trigger_error("bookmark id is below zero (\$bookmark->id == ".$bookmark->id.")"); return true; }
		return true;
	}
	
	public function getFromResult($result) {
		if ($row = mysql_fetch_assoc($result)) {
			$bookmark = new Bookmark();
			$bookmark->id = $row['id'];
			$bookmark->created = strtotime($row['created']);
			$bookmark->modified = strtotime($row['modified']);
			$bookmark->user = $row['user'];
			$bookmark->book = $row['book'];
			$bookmark->position = $row['position'];
			$bookmark->lastmark = $row['lastmark']?true:false;
			$bookmark->text = $row['text'];
			return $bookmark;
		} else {
			trigger_error("could not get bookmark from result");
			if ($error = mysql_error()) { trigger_error($error); }
			return null;
		}
	}

	public function update(&$bookmark) {
		$this->connect();
		$bookmark->modified = time();
		
		$query = "UPDATE bookmarks SET ".
					"created = '".date('Y-m-d H:i:s',$bookmark->created)."' , ".
					"modified = '".date('Y-m-d H:i:s',$bookmark->modified)."' , ".
					"user = '".$this->escape($bookmark->user)."' , ".
					"book = '".$this->escape($bookmark->book)."' , ".
					"position = '".$this->escape($bookmark->position)."' , ".
					"lastmark = ".($bookmark->lastmark?'1':'0')." , ".
					"text = '".$this->escape($bookmark->text)."' ".
				 "WHERE id = '".$this->escape($bookmark->id)."'";
		mysql_query($query);
		if ($error = mysql_error()) { trigger_error($error); return false; }
		return true;
	}

	public function insert(&$bookmark) {
		$this->connect();
		$bookmark->modified = $bookmark->created = time();
		
		// if this is a lastmark; don't insert if a lastmark already exists for this user/book-combination
		$result = mysql_query("SELECT id FROM bookmarks WHERE user = '".$this->escape($bookmark->user)."' AND book = '".$this->escape($bookmark->book)."' AND lastmark = 1");
		if ($error = mysql_error()) { trigger_error($error); return false; }
		if (mysql_num_rows($result) > 0) {
			trigger_error("lastmark for (user,book)=(".($bookmark->user).",".($bookmark->book).") already exists. insert operation aborted.");
			return false;
		}
		
		// insert
		$query = "INSERT INTO bookmarks (created, modified, user, book, position, lastmark, text) VALUES (".
					"'".date('Y-m-d H:i:s',$bookmark->created)."',".
					"'".date('Y-m-d H:i:s',$bookmark->modified)."',".
					"'".$this->escape($bookmark->user)."',".
					"'".$this->escape($bookmark->book)."',".
					"'".$this->escape($bookmark->position)."',".
					"".($bookmark->lastmark?'1':'0').",".
					"'".$this->escape($bookmark->text)."')";
		mysql_query($query);
		if ($error = mysql_error()) { trigger_error($error); return false; }
		
		// get id (user+book+created+modified+lastmark should be enough to uniquely identify it)
		$result = mysql_query("SELECT id FROM bookmarks WHERE ".
								"user = '".$this->escape($bookmark->user)."' AND ".
								"book = '".$this->escape($bookmark->book)."' AND ".
								"created = '".date('Y-m-d H:i:s',$bookmark->created)."' AND ".
								"modified = '".date('Y-m-d H:i:s',$bookmark->modified)."' AND ".
								"lastmark = ".($bookmark->lastmark?'1':'0')
							 );
		if ($error = mysql_error()) { trigger_error($error); return false; }
		$row = mysql_fetch_assoc($result);
		$bookmark->id = $row['id'];
		
		return true;
	}
	
	public function connect() {
		global $bookmarks_db_hostname, $bookmarks_db_username, $bookmarks_db_password, $bookmarks_db_database;
		$conn = mysql_connect($bookmarks_db_hostname, $bookmarks_db_username, $bookmarks_db_password);
		if ($error = mysql_error()) { trigger_error($error); }
		mysql_select_db($bookmarks_db_database,$conn);
		if ($error = mysql_error()) { trigger_error($error); }
	}
	
	// ---- private functions ----
	
	private function escape($string_to_escape) {
		$escaped = mysql_real_escape_string($string_to_escape);
		if ($error = mysql_error()) { trigger_error($error); }
		return $escaped;
	}
}
?>