var bokelskere_thread = window.setInterval(function(){
	if ($('#metadata-table').size() > 0) {
		tnr = ticket.split('_')[1];
		$('<tr><td>Lenke til tittelen hos NLB:</td><td><a href="http://128.39.10.81/cgi-bin/websok?tnr='+tnr+'" target="_blank">http://128.39.10.81/cgi-bin/websok?tnr='+tnr+'</a></td></tr>').appendTo('#metadata-table');
		$.getJSON('http://beta.nlb.no/metadata/rdf.php?format=json&namespaces=hide&tnr='+tnr+'&callback=?',
			'',
			function (data, textStatus, jqXHR) {
				isbn = null;
				for (var i = (data.length>1&&typeOf(data[1])==='object')?2:1; i < data.length; i++) {
					if (data[i][0] === 'Description') {
						for (var j = (data[i].length>1&&typeOf(data[i][1])==='object')?2:1; j < data[i].length; j++) {
							if (data[i][j][0] === 'source') {
								isbn = data[i][j][data[i][j].length-1];
								break;
							}
						}
						break;
					}
				}
				$('<tr><td>ISBN:</td><td>'+isbn+'</td></tr>').appendTo('#metadata-table');
				isbn = isbn.replace(/[^\d]/g,'');
				$.getJSON('http://bokelskere.no/api/1.0/boker/info/'+isbn+'/?format=json&callback=?',
					'',
					function (data, textStatus, jqXHR) {
						$('<tr><td>Gjennomsnittlig terningkast hos Bokelskere.no:</td><td>'+data['gjennomsnittelig_terningkast']+'</td></tr>').appendTo('#metadata-table');
						$('<tr><td>Lenke til tittelen hos Bokelskere.no:</td><td><a href="'+data['link']+'" target="_blank">'+data['link']+'</a></td></tr>').appendTo('#metadata-table');
					}
				);
			}
		);
		window.clearTimeout(bokelskere_thread);
	}
},100);

function typeOf(value) {
	var s = typeof value;
	if (s === 'object') {
		if (value) {
			if (typeof value.length === 'number' &&
					!(value.propertyIsEnumerable('length')) &&
					typeof value.splice === 'function') {
				s = 'array';
			}
		} else {
			s = 'null';
		}
	}
	return s;
}