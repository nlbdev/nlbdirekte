<!doctype html>
<html>
<?php $debug = false; include('player/common.inc.php'); ?>
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
		<select id="bookId" size="15" style="width:228px;">
			<!-- Add options for each test book here, with value=bookId and title as the text: -->
			<!--option value="613757">Frosken finn ein venn</option-->
			<option value="613757" selected="yes">Jamen, Benny</option>
			<option value="613839">Bare r&oslash;re, ikke se</option>
			<option value="213755">Verneombudet</option>
			<option value="606939">Bestemors julebok for de sm&aring;</option>
			<option value="608816">Bake kake s&oslash;te</option>
			<option value="610289">Presten i Nibbleswicke</option>
			<option value="611288">Fru Andersen har hump i halen</option>
			<option value="612430">Illustrert vitenskap nr 01/2008</option>
			<option value="614172">Illustrert vitenskap nr 01/2010</option>
			<option value="614188">Illustrert vitenskap nr 17/2011</option>
			<option value="614202">Min kamp: femte bok</option>
			<option value="612045">TV-guiden</option>
			<option value="210012">Idrettens treningsl&aelig;re</option>
			<option value="601005">Kjerringa som ble s&aring; lita som ei teskje</option>
			<option value="601061">Vett og uvett</option>
			<option value="604389">I d&oslash;dens fotspor</option>
		</select>
	</div>
</body>
</html>
