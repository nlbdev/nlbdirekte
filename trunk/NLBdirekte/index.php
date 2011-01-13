<!doctype html>
<html>
<?php include('player/common.inc.php'); ?>
<head>
	<title>NLBdirekte v<?php echo $version; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
	<script type="text/javascript" src="jquery-1.4.2.min.js"></script>
	<link type="text/css" href="Daisy202Book.css" rel="stylesheet" />
	
	<script type="text/javascript" charset="utf-8">
	/* <![CDATA[ */
	function login(username, bookId) {
		window.location = 'player/direkte.php?ticket='+username+'_'+bookId;
	}
	/* ]]> */
	</script>
</head>
<body style="font-family: futura, helvetica, arial, sans-serif">
	<!-- wrap in id=book so that we can simply use the same css as for the book -->
	<div id="book">
		<h1>NLBdirekte v<?php echo $version; ?></h1>
		<h2>Login</h2>
		Username:
		<input id="username" type="text" onkeydown="if(event.which&&event.which===13||event.keyCode===13)login($('#username').val(),$('#bookId').val());"></input>
		<input id="login" type="button" value="Logg inn" onclick="login($('#username').val(),$('#bookId').val());"></input><br/>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		&nbsp;
		<select id="bookId" size="3" style="width:228px;">
			<!-- Add options for each test book here, with value=bookId and title as the text: -->
			<option value="613757" selected="yes">Jamen, Benny</option>
			<option value="613839">Bare r&oslash;re, ikke se</option>
		</select>
	</div>
</body>
</html>
