<?php
header("content-type: application/json");

session_start();

$user = array(
			'patronId' => $_SESSION['patronId'],
			'isAuthorized' => !empty($_SESSION['patronId'])
			);

$request = json_decode($_POST['JSONRequest']);
if (!isset($request->{'action'})) {
	echo '{"error":"no action"}';
	exit;
}

if (true) { # TODO test for authorization
	$user['isAuthorized'] = true;
}

switch ($request->{'action'}) {
case 'saveLastmark': saveLastmark($user, $request->{'bookId'}, $request->{'time'}, $request->{'charOffset'}); break;
case 'saveBookmark': saveBookmark($user, $request->{'bookmark'}); break;
case 'deleteBookmark': deleteBookmark($user, $request->{'uid'}); break;
case 'loadLastmark': loadLastmark($user, $request->{'bookId'}); break;
case 'loadBookmark': loadBookmark($user, $request->{'uid'}); break;
case 'loadBookmarks': loadBookmarks($user, $request->{'bookId'}); break;
case 'listBookmarks': listBookmarks($user, $request->{'bookId'}); break;
case 'listPublicBookmarks': listPublicBookmarks($user, $request->{'bookId'}); break;
default: echo '{"error":"Undefined action \''.$request->{'action'}.'\'."}';
}

function saveLastmark(&$user, $bookId, $time, $charOffset) {
	if (!$user['isAuthorized']) {
		echo '{"error":"permission denied"}';
	} else {
		
		$database = null;
		database_connect($database);
		$bookmarkDao = new BookmarkDAO();
		
		$lastmark = $bookmarkDao->get(array(
											"patronId" => $user['patronId'],
											"bookId" => $bookId,
											"isLastmark" => true,
											"orderBy" => "created DESC"
										));
		
		if (empty($lastmark)) {
			$lastmark = new Bookmark();
			$lastmark->created = time();
			$lastmark->patronId = $user['patronId'];
			$lastmark->bookId = $bookId;
			$lastmark->title = "Lastmark for book #$bookId";
			$lastmark->isPublic = false;
			$lastmark->isLastmark = true;
		} else {
			$lastmark = $lastmark[0];
		}
		
		$lastmark->modified = time();
		$lastmark->startTime = $time;
		$lastmark->startCharOffset = $charOffset;
		
		$error = $bookmarkDao->save($lastmark);
		
		database_close($database);
		
		if (!empty($error))
			echo '{"returnValue":false,"error":"could not save bookmark ('.$error.')"';
		else
			echo '{"returnValue":"'.($new?'trued':'false').'"}';
	}
}
	
function saveBookmark(&$user, $bookmarkJSON) {
	if (!$user['isAuthorized']) {
		echo '{"error":"permission denied"}';
	} else {
		
		$database = null;
		database_connect($database);
		$bookmarkDao = new BookmarkDAO();
		
		$bookmark = new Bookmark();
		$bookmark->fromAssociativeArray($bookmarkJSON);
		
		// in case the bookmark is new
		$bookmark->patronId = $user['patronId'];
		
		// set created time for new bookmarks
		if (!isset($bookmark->created))
			$bookmark->created = time();
		
		// update last modification time
		$bookmark->modified = time();
		
		$error = $bookmarkDao->save($bookmark);
		
		database_close($database);
		
		if (!empty($error))
			echo '{"returnValue":false,"error":"could not save bookmark ('.$error.')"';
		else
			echo '{"returnValue":'.$bookmark->encodeJSON().'}';
	}
}

function deleteBookmark(&$user, $uid) {
	if (!$user['isAuthorized']) {
		echo '{"error":"permission denied"}';
	} else {
		
		$database = null;
		database_connect($database);
		$bookmarkDao = new BookmarkDAO();
		
		$bookmark = $bookmarkDao->getByUid($uid);
		
		if (!isset($bookmark)) {
			echo '{"error":"bookmark does not exist"}';
			
		} else if ($bookmark->patronId !== $user['patronId']) {
			echo '{"error":"permission denied - can only delete your own bookmarks"}';
			
		} else {
			$error = $bookmarkDao->delete($bookmark);
			
			if (!empty($error))
				echo '{"error":"could not save bookmark ('.$error.')"';
			else
				echo '{}';
		}
		
		database_close($database);
	}
}

function loadLastmark(&$user, $bookId) {
	if (!$user['isAuthorized']) {
		echo '{"error":"permission denied"}';
	} else {
		
		$database = null;
		database_connect($database);
		$bookmarkDao = new BookmarkDAO();
		
		$lastmark = $bookmarkDao->get(array(
											"patronId" => $user['patronId'],
											"bookId" => $bookId,
											"isLastmark" => true,
											"orderBy" => "created DESC"
										));
		
		if (is_null($lastmark) || count($lastmark) === 0) {
			echo '{"returnValue":null}';
		} else {
			while (count($lastmark) > 1) {
				# "There is one true Lastmark, and there is no other;
				# There is no Lastmark besides this. It will mark you, though you have not known It."
				# (so let's merge the lastmarks for this {book,patron}-combo)
				
				$popped = array_pop($lastmark);
				$last = count($lastmark)-1;
				
				if ($popped->modified > $lastmark[$last]->modified) {
					$lastmark[$last]->modified = $popped->modified;
					$lastmark[$last]->startTime = $popped->startTime;
					$lastmark[$last]->startCharOffset = $popped->startCharOffset;
					$lastmark[$last]->endTime = $popped->endTime;
					$lastmark[$last]->endCharOffset = $popped->endCharOffset;
				}
				
				if ($popped->title !== $lastmark[$last]->title)
					$lastmark[$last]->title .= ' '.$popped->title;
				
				if ($popped->text !== $lastmark[$last]->text)
					$lastmark[$last]->text .= ' '.$popped->text;
				
				if (!$popped->isPublic)
					$lastmark[$last]->isPublic = false;
				
				$bookmarkDao->delete($popped);
			}
			$bookmarkDao->save($lastmark[0]);
			
			echo '{"returnValue":'.$lastmark[0]->encodeJSON().'}';
		}
		
		database_close($database);
	}
}

function loadBookmark(&$user, $uid) {
	
	$database = null;
	database_connect($database);
	$bookmarkDao = new BookmarkDAO();
	
	$bookmark = $bookmarkDao->getByUid($uid);
	
	database_close($database);
	
	if (is_null($bookmark)) {
		echo '{"error":"no such bookmark (uid = '.$uid.')"}';
	} else if ($bookmark->patronId === $user['patronId'] && $user['isAuthorized'] || $bookmark->isPublic) {
		# TODO: resolve any missing values in $bookmark here
		echo '{"returnValue":'.$bookmark->encodeJSON().'}';
	} else {
		echo '{"error":"permission denied (uid = '.$uid.')"}';
	}
}

function loadBookmarks(&$user, $bookId) {
	if (!$user['isAuthorized']) {
		echo '{"error":"permission denied"}';
	} else {
		
		$database = null;
		database_connect($database);
		$bookmarkDao = new BookmarkDAO();
		
		$bookmarks = $bookmarkDao->get(array(
											"patronId" => $user['patronId'],
											"bookId" => $bookId,
											"isLastmark" => false,
											"orderBy" => "startTime ASC"
										));
		
		database_close($database);
		
		if (is_null($bookmarks)) {
			echo '{"returnValue":[]}';
		} else {
			echo '{"returnValue":[';
			for ($i = 0; $i < count($bookmarks); $i++) {
				# TODO: resolve any missing values for $bookmarks[$i]
				echo $bookmarks[$i]->encodeJSON();
				if ($i+1 < count($bookmarks)) {
					echo ',';
				}
			}
			echo ']}';
		}
	}
}

function listBookmarks(&$user, $bookId) {
	if (!$user['isAuthorized']) {
		echo '{"error":"permission denied"}';
	} else {
		
		$database = null;
		database_connect($database);
		$bookmarkDao = new BookmarkDAO();
		
		$bookmarks = $bookmarkDao->get(array(
											"patronId" => $user['patronId'],
											"bookId" => $bookId,
											"isLastmark" => false,
											"orderBy" => "startTime ASC, startCharOffset ASC"
										));
		
		database_close($database);
		
		if (is_null($bookmarks)) {
			echo '{"returnValue":[]}';
		} else {
				echo '{"returnValue":[';
				for ($i = 0; $i < count($bookmarks); $i++) {
					# TODO: resolve any missing values for $bookmarks[$i]
					echo '{'.
							'"uid":"'.$bookmarks[$i]->uid.'",'.
							'"startTime":"'.$bookmarks[$i]->startTime.'",'.
							'"startCharOffset":"'.$bookmarks[$i]->startCharOffset.'",'.
							'"title":"'.$bookmarks[$i]->title.'"'.
						 '}';
					if ($i+1 < count($bookmarks)) {
						echo ',';
					}
				}
				echo ']}';
		}
	}
}

function listPublicBookmarks(&$user, $bookId) {
	echo '{"returnValue":[]}';
}

#### if code gets big, separate this into for instance lib/common/database.inc.php ####

function database_connect(&$database) {
	if (is_null($database)) {
		$databaseHostName = 'localhost';
		$databaseUserName = 'root';
		$databaseUserPassword = '';
		$databaseName = 'patrondata';
		
		$database = mysql_connect($databaseHostName, $databaseUserName, $databaseUserPassword);
		mysql_set_charset('utf8',$database); 
		mysql_select_db($databaseName, $database);
	}
}

function database_close(&$database) {
	if (!is_null($database)) {
		mysql_close($database);
		$database = null;
	}
}

function database_real_escape_string($string_to_escape) {
	return mysql_real_escape_string($string_to_escape,$database);
}
#### database.php end ####

#### if code gets big, separate this into for instance lib/dao/bookmark.inc.php ####
// Represents an entry in the 'bookmarks'-table
class Bookmark {
	public $uid;				// int(11)
	public $created;			// datetime
	public $modified;			// datetime
	public $patronId;			// int(11)
	public $bookId;				// int(11)
	public $title;				// varchar(100)
	public $text;				// varchar(1023)
	public $isPublic;			// tinyint(1)
	public $isReplyTo;			// int(11)
	public $startTime;			// double
	public $startCharOffset;	// int(11)
	public $endTime;			// double
	public $endCharOffset;		// int(11)
	public $isLastmark;			// tinyint(1)
	
	public function __construct() {
		// nothing to do
	}
	
	public function encodeJSON() {
		return json_encode(array(
					"uid" => $this->uid,
					"created" => $this->created,
					"modified" => $this->modified,
					# patronId should be kept secret, so don't export it to JSON
					"bookId" => $this->bookId,
					"title" => $this->title,
					"text" => $this->text,
					"isPublic" => $this->isPublic,
					"isReplyTo" => $this->isReplyTo,
					"startTime" => $this->startTime,
					"startCharOffset" => $this->startCharOffset,
					"endTime" => $this->endTime,
					"endCharOffset" => $this->endCharOffset,
					"isLastmark" => $this->isLastmark
				));
	}
	
	public function decodeJSON($json_str) {
		return fromAssociativeArray(json_decode($json_str, 1));
	}
	
	public function fromAssociativeArray($assoc) {
		foreach ($assoc as $key => $value) {
			if ($key === 'uid' or $key === 'created' or $key === 'modified' or $key === 'bookId' or
				$key === 'title' or $key === 'text' or $key === 'isPublic' or $key === 'isReplyTo' or $key === 'startTime' or
				$key === 'startCharOffset' or $key === 'endTime' or $key === 'endCharOffset' or $key === 'isLastmark')
				$this->$key = $value;
		}
	}
	
	public function isValid() {
		# TODO: check for validity
		return true;
	}
}

class BookmarkDAO {
	function BookmarkDAO() {
		# nothing to do
	}
	
	function save(&$bookmark) {
		if (!$bookmark->isValid()) {
			return "Invalid bookmark";
		} else {
			if (!empty($bookmark->uid) && $bookmark->uid > 0 && $this->getByUid($bookmark->uid)) {
				return $this->update($bookmark);
			} else {
				return $this->insert($bookmark);
			}
		}
	}
	
	function getByUid($uid) {
		$query = "SELECT * FROM bookmarks WHERE bookmarks.uid = '".$uid."'";
		$result = mysql_query($query) or die($query.': Database error at '.__FILE__.':'.__LINE__.", ".mysql_error());
		return $this->getFromResult($result);
	}
	
	# get(...)
	#	General purpose function for querying the bookmarks-table
	#	the argument is an array of parameters, for instance:
	#	get(array('createdFrom' => 0, 'createdTo' => time(), 'patronId' => 25 ));
	#	'uid' => 1 means 'uid = 1', 'uidFrom' => 1 means 'uid >= 1' and
	#	'uidTo' => 1 means 'uid <= 1'. The same applies for all other numbers
	#	and dates. For text fields, the input is a string to search for. For
	#	boolean fields, the input (if given) is simply true/false. The
	#	orderBy-parameter is a string saying which column(s) to sort by. All
	#	parameters are optional, but keep in mind that with no parameters,
	#	the entire table will be returned (so... just don't do that).
	function get($arguments) {
		$conditions = array();
		
		# uid
		if (isset($arguments['uid'])) $conditions[] = "bookmarks.uid = '".$arguments['uid']."'";
		if (isset($arguments['uidFrom'])) $conditions[] = "bookmarks.uid >= '".$arguments['uidFrom']."'";
		if (isset($arguments['uidTo'])) $conditions[] = "bookmarks.uid <= '".$arguments['uidTo']."'";
		
		# created
		if (isset($arguments['created'])) $conditions[] = "bookmarks.created = '".date( 'Y-m-d G:i:s.u', $arguments['created'] )."'";
		if (isset($arguments['createdFrom'])) $conditions[] = "bookmarks.created >= '".date( 'Y-m-d G:i:s.u', $arguments['createdFrom'] )."'";
		if (isset($arguments['createdTo'])) $conditions[] = "bookmarks.created <= '".date( 'Y-m-d G:i:s.u', $arguments['createdTo'] )."'";
		
		# modified
		if (isset($arguments['modified'])) $conditions[] = "bookmarks.modified = '".date( 'Y-m-d G:i:s.u', $arguments['modified'] )."'";
		if (isset($arguments['modifiedFrom'])) $conditions[] = "bookmarks.modified >= '".date( 'Y-m-d G:i:s.u', $arguments['modifiedFrom'] )."'";
		if (isset($arguments['modifiedTo'])) $conditions[] = "bookmarks.modified <= '".date( 'Y-m-d G:i:s.u', $arguments['modifiedTo'] )."'";
		
		# patronId
		if (isset($arguments['patronId'])) $conditions[] = "bookmarks.patronId = '".$arguments['patronId']."'";
		if (isset($arguments['patronIdFrom'])) $conditions[] = "bookmarks.patronId >= '".$arguments['patronIdFrom']."'";
		if (isset($arguments['patronIdTo'])) $conditions[] = "bookmarks.patronId <= '".$arguments['patronIdTo']."'";
		
		# bookId
		if (isset($arguments['bookId'])) $conditions[] = "bookmarks.bookId = '".$arguments['bookId']."'";
		if (isset($arguments['bookIdFrom'])) $conditions[] = "bookmarks.bookId >= '".$arguments['bookIdFrom']."'";
		if (isset($arguments['bookIdTo'])) $conditions[] = "bookmarks.bookId <= '".$arguments['bookIdTo']."'";
		
		# title
		if (isset($arguments['title'])) $conditions[] = "MATCH(bookmarks.title) against ('".$arguments['title']." IN BOOLEAN MODE)";
		
		# text
		if (isset($arguments['text'])) $conditions[] = "MATCH(bookmarks.text) against ('".$arguments['text']." IN BOOLEAN MODE)";
		
		# isPublic
		if (isset($arguments['isPublic'])) $conditions[] = "bookmarks.isPublic = '".($arguments['isPublic']?1:0)."'";
		
		# isReplyTo
		if (isset($arguments['isReplyTo'])) $conditions[] = "bookmarks.isReplyTo = '".$arguments['isReplyTo']."'";
		if (isset($arguments['isReplyToFrom'])) $conditions[] = "bookmarks.isReplyTo >= '".$arguments['isReplyToFrom']."'";
		if (isset($arguments['isReplyToTo'])) $conditions[] = "bookmarks.isReplyTo <= '".$arguments['isReplyToTo']."'";
		
		# startTime
		if (isset($arguments['startTime'])) $conditions[] = "bookmarks.startTime = '".$arguments['startTime']."'";
		if (isset($arguments['startTimeFrom'])) $conditions[] = "bookmarks.startTime >= '".$arguments['startTimeFrom']."'";
		if (isset($arguments['startTimeTo'])) $conditions[] = "bookmarks.startTime <= '".$arguments['startTimeTo']."'";
		
		# startCharOffset
		if (isset($arguments['startCharOffset'])) $conditions[] = "bookmarks.startCharOffset = '".$arguments['startCharOffsetFrom']."'";
		if (isset($arguments['startCharOffsetFrom'])) $conditions[] = "bookmarks.startCharOffset >= '".$arguments['startCharOffsetFrom']."'";
		if (isset($arguments['startCharOffsetTo'])) $conditions[] = "bookmarks.startCharOffset <= '".$arguments['startCharOffsetTo']."'";
		
		# endTime
		if (isset($arguments['endTime'])) $conditions[] = "bookmarks.endTime = '".$arguments['endTime']."'";
		if (isset($arguments['endTimeFrom'])) $conditions[] = "bookmarks.endTime >= '".$arguments['endTimeFrom']."'";
		if (isset($arguments['endTimeTo'])) $conditions[] = "bookmarks.endTime <= '".$arguments['endTimeTo']."'";
		
		# endCharOffset
		if (isset($arguments['endCharOffset'])) $conditions[] = "bookmarks.endCharOffset = '".$arguments['endCharOffset']."'";
		if (isset($arguments['endCharOffsetFrom'])) $conditions[] = "bookmarks.endCharOffset >= '".$arguments['endCharOffsetFrom']."'";
		if (isset($arguments['endCharOffsetTo'])) $conditions[] = "bookmarks.endCharOffset <= '".$arguments['endCharOffsetTo']."'";
		
		# isLastmark
		if (isset($arguments['isLastmark'])) $conditions[] = "bookmarks.isLastmark = '".($arguments['isLastmark']?1:0)."'";
		
		# put together query
		$query = "SELECT * FROM bookmarks";
		if (count($conditions) > 0) $query .= " WHERE ".implode(" AND ",$conditions);
		$orderBy = explode(',',$orderBy);
		$orderByAdded = false;
		foreach ($orderBy as $orderByPart) {
			$orderByPart = explode(' ',trim($orderByPart));
			if (count($orderByPart) > 2)
				continue; // too many arguments
			if (strcasecmp($orderByPart[0],"uid") &&
				strcasecmp($orderByPart[0],"created") &&
				strcasecmp($orderByPart[0],"modified") &&
				strcasecmp($orderByPart[0],"patronId") &&
				strcasecmp($orderByPart[0],"bookId") &&
				strcasecmp($orderByPart[0],"isPublic") &&
				strcasecmp($orderByPart[0],"isReplyTo") &&
				strcasecmp($orderByPart[0],"startTime") &&
				strcasecmp($orderByPart[0],"startCharOffset") &&
				strcasecmp($orderByPart[0],"endTime") &&
				strcasecmp($orderByPart[0],"endCharOffset") &&
				strcasecmp($orderByPart[0],"isLastmark"))
				continue; // field not found
			if (count($orderByPart) == 2 && (strcasecmp($orderByPart[1],'ASC') && strcasecmp($orderByPart[1],'DESC')))
				continue; // specified sort bookmark neither ASCending nor DESCending
			if (!$orderByAdded) {
				$query .= " ORDER BY bookmarks.".implode(' ',$orderByPart);
				$orderByAdded = true;
			} else {
				$query .= ", bookmarks.".implode(' ',$orderByPart);
			}
		}
		
		# run query
		$result = mysql_query($query) or die($query.': Database error at '.__FILE__.':'.__LINE__.", ".mysql_error());
		
		# convert from database rows to an array of Bookmarks
		$bookmarks = array();
		for ($i = 0; $i < mysql_num_rows($result); $i++) {
			$bookmarks[] = $this->getFromResult($result);
		}
		
		# return results
		return $bookmarks;
	}
	
	function delete(&$bookmark) {
		$query = "DELETE FROM bookmarks WHERE bookmarks.uid = '".$bookmark->uid."'";
		mysql_query($query);
		return mysql_error();
	}
	
	// private functions
	function getFromResult($result) {
		$row = mysql_fetch_assoc($result);
		if (!is_null($row)) {
			$bookmark = new Bookmark();
			$bookmark->uid				= $row['uid'];
			$bookmark->created			= strtotime($row['created']);
			$bookmark->modified			= strtotime($row['modified']);
			$bookmark->patronId			= $row['patronId'];
			$bookmark->bookId			= $row['bookId'];
			$bookmark->title			= $row['title'];
			$bookmark->text				= $row['text'];
			$bookmark->isPublic			= ($row['isPublic']?true:false);
			$bookmark->isReplyTo		= $row['isReplyTo'];
			$bookmark->startTime		= $row['startTime'];
			$bookmark->startCharOffset	= $row['startCharOffset'];
			$bookmark->endTime			= $row['endTime'];
			$bookmark->endCharOffset	= $row['endCharOffset'];
			$bookmark->isLastmark		= ($row['isLastmark']?true:false);
			return $bookmark;
		} else {
			return null;
		}
	}
	
	function update(&$bookmark) {
		$query =
			"UPDATE bookmarks SET ".
				"bookmarks.uid = '".($bookmark->uid)."' , ".
				"bookmarks.created = '".date( 'Y-m-d G:i:s.u', $bookmark->created)."' , ".
				"bookmarks.modified = '".date( 'Y-m-d G:i:s.u', $bookmark->modified)."' , ".
				"bookmarks.patronId = '".($bookmark->patronId)."' , ".
				"bookmarks.bookId = '".($bookmark->bookId)."' , ".
				"bookmarks.title = '".mysql_real_escape_string($bookmark->title)."' , ".
				"bookmarks.text = '".mysql_real_escape_string($bookmark->text)."' , ".
				"bookmarks.isPublic = '".($bookmark->isPublic?1:0)."' , ".
				"bookmarks.isReplyTo = '".($bookmark->isReplyTo)."' , ".
				"bookmarks.startTime = '".($bookmark->startTime)."' , ".
				"bookmarks.startCharOffset = '".($bookmark->startCharOffset)."' , ".
				"bookmarks.endTime = '".($bookmark->endTime)."' , ".
				"bookmarks.endCharOffset = '".($bookmark->endCharOffset)."' , ".
				"bookmarks.isLastmark = '".($bookmark->isLastmark?1:0)."' ".
			"WHERE bookmarks.uid = '".($bookmark->uid)."'";
		mysql_query($query);
		return mysql_error();
	}
	
	function insert(&$bookmark) {
		$query = "INSERT INTO bookmarks (bookmarks.created, bookmarks.modified, bookmarks.patronId,".
										" bookmarks.bookId, bookmarks.title, bookmarks.text, bookmarks.isPublic,".
										" bookmarks.isReplyTo, bookmarks.startTime, bookmarks.startCharOffset,".
										" bookmarks.endTime, bookmarks.endCharOffset, bookmarks.isLastmark) ".
						"VALUES('".	date( 'Y-m-d G:i:s.u', $bookmark->created)."' , '".
									date( 'Y-m-d G:i:s.u', $bookmark->modified)."' , '".
									($bookmark->patronId)."' , '".
									($bookmark->bookId)."' , '".
									mysql_real_escape_string($bookmark->title)."' , '".
									mysql_real_escape_string($bookmark->text)."' , '".
									($bookmark->isPublic?1:0)."' , '".
									($bookmark->isReplyTo)."' , '".
									($bookmark->startTime)."' , '".
									($bookmark->startCharOffset)."' , '".
									($bookmark->endTime)."' , '".
									($bookmark->endCharOffset)."' , '".
									($bookmark->isLastmark?1:0)."')";
		mysql_query($query);
		$error = mysql_error();
		if (!empty($error)) {
			$query = "SELECT bookmarks.uid FROM bookmarks ORDER BY bookmarks.uid DESC LIMIT 1";
			$result = mysql_query($query) or die($query.': Database error at '.__FILE__.':'.__LINE__.", ".mysql_error());
			$bookmark = $this->getFromResult($result); // gets the uid generated by the database
			return mysql_error();
		} else {
			return $error;
		}
	}
}
#### bookmark.inc.php end ####
?>