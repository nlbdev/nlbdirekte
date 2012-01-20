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
			<option value="613757" selected="yes">Jamen, Benny</option>
			<option value="613839">Bare r&oslash;re, ikke se</option>
			<option value="608816">Bake kake s&oslash;te: rim og regler for de minste</option>
			<option value="209999">Methodology and Ideology</option>
			<option value="210001">Rettskildel&aerlig;re</option>
			<option value="210525">Hvordan gjenomf&oslash;re undersøkelser. Innf&oslash;ring i samfunnsvitenskapelig metode</option>
			<option value="210812">Northern Lights</option>
			<option value="210826">Aktiv og supplerende kommunikasjon</option>
			<option value="211250">Moderne omsorgsbilder</option>
			<option value="211550">S9321_Allmennmedisin.Klinisk Arbeid</option>
			<option value="211641">Pedagogikk.</option>
			<option value="213403">Clinical pharmacy and therapeutics</option>
			<option value="213987">Classical and Contemporary Sociological Theory</option>
			<option value="214210">Med mattebriller i barnehagen</option>
			<option value="214214">Drama i barnehagen</option>
			<option value="600002">Tilbake til jorden. ¤I</option>
			<option value="601005">Kjerringa som ble s&aring; lita som ei teskje. av Pr&oslash;ysen, Alf</option>
			<option value="601061">Vett og uvett av Aas og Wessel Zappfe</option>
			<option value="610289">PRESTEN I NIBBLESWICKE</option>
			<option value="611288">Fru Andersen har hump i halen</option>
			<option value="612045">TV-Guiden</option>
			<option value="612430">Illustrert Vitenskap Nr. 1, 2008</option>
			<option value="614202">MIN KAMP 5</option>
			<option value="615165">STELL PENT MED MANNEN</option>
			<option value="615503">The Complete Stories Volume 2</option>
			<option value="616564">Artemis Fowl og Atlantiskomplekset</option>
			<option value="616565">Ikke bare villle Wilma</option>
		</select>
	</div>
</body>
</html>
