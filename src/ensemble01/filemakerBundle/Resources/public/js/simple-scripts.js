var showDownloadZipButton = function(n) {
	if(n == undefined) n = 1;
	$('#download-Zip-Button-'+n).show(1);
}
var hideDownloadZipButton = function(n) {
	if(n == undefined) n = 1;
	$('#download-Zip-Button-'+n).hide(1);
}

jQuery(document).ready(function($) {

	/* **************************************************** */
	/* Liens externes -> dans une nouvelle fenêtre
	/* **************************************************** */
	$('body').on("click", ".URLext", function(event) {
		URL = $(this).attr("href");
		if(URL == undefined) URL = $("a", this).first().attr("href");
		// alert(URL);
		if(URL != undefined) {
			window.open(URL);
		}
		event.preventDefault();
		return false;
	});

	// Désactivation des liens sur les <a href="#">
	$('body a').each(function() {
		if($(this).attr('href') == "#") {
			// $(this).css('cursor', 'default');
			$(this).addClass('disabled');
		}
	});
	// Désactivation des liens "disabled"
	$("body").on("click", ".disabled", function(event) {
		event.preventDefault();
		return false;
	});
	// Liens javascript:history.back();
	$("body").on("click", ".backpage", function() { history.back(); })


	/* **************************************************** */
	/* Fenêtre de visu des rapports + Export ZIP
	/* **************************************************** */
	// $('body').on('click', '.exportZipRapports', function(event) {
	// 	event.preventDefault();
	// 	// alert('URL : \n' + $(this).attr('data-href'));
	// 	this.texte = $(this).html();
	// 	$(this).html('Chargment…');
	// 	var objparent = this;
	// 	$.ajax({
	// 		type: "POST",
	// 		url: $(this).attr('href')
	// 		// statusCode: {
	// 		// 	// 200: function() { alert('Fichier ZIP téléchargé.'); },
	// 		// 	500: function() { alert('Erreur 500 - Erreur de script\nURL : '+$(objparent).attr('data-url')); },
	// 		// 	404: function() { alert('Erreur 404 - Page inconnue\nURL : '+$(objparent).attr('data-url')); }
	// 		// }
	// 	});
	// 	setTimeout(function(){ $(objparent).html(objparent.texte); }, 500);
	// });

	var freq = 1000;
	var compteur = 0;
	verifLoad = function() {
		this.texte = $('#visu').html();
		var objparent1 = this;
		$('#visu').html('<span class="glyphicon glyphicon-time" aria-hidden="true" style="color:#62a6e1;"></span>');
		$('.ajax-reload').each(function(elem) {
			var objparent2 = this;
			if($(this).attr('data-url') != undefined) {
				$.get($(this).attr('data-url'), function(data) {
					if($(objparent2).html() != data) {
						$(objparent2).html(data);
						// alert('Changing…');
					}
				});
				// $(this).load($(this).attr('data-url'), function( response, status, xhr ) {
				// 	if ( status == "error" ) {
				// 		var msg = "Une erreur est survenue : ";
				// 		$(objparent2).html( msg + xhr.status + "/" + xhr.statusText );
				// 	}
				// });
			}
		});
		ROK = $('body .rapport-ok').length;
		RQ = parseInt($('body #rapport-quantity').text());
		// $('#zonzon').html('<p>'+ROK+' / '+RQ+'</p>');
		if(ROK == RQ && ROK != 0) {
			hideDownloadZipButton(2);
			setTimeout('showDownloadZipButton(1)', 2);
		} else {
			hideDownloadZipButton(1);
			setTimeout('showDownloadZipButton(2)', 2);
		}
		compteur++;
		setTimeout(function(){ $('#visu').html(objparent1.texte); }, 200);
		setTimeout(function(){ verifLoad(); }, freq);
	}
	if($('.ajax-reload').length) { verifLoad(); }

	$('body').on('click', '.rapportPDFrefresh', function(event) {
		event.preventDefault();
		this.loadimg = false;
		if($(this).attr('data-loader-image').length) {
			this.loadimg = $(this).parent().html();
			$(this).parent().html('<div style="width:100%;height:34px;text-align:center;"><img src="'+$(this).attr('data-loader-image')+'"></div>');
		}
		// $(this).addClass('disabled');
		this.URL = $(this).attr('data-href');
		var objparent = this;
		$.ajax({
			type: "POST",
			url: this.URL,
			statusCode: {
				500: function() { alert('Erreur 500 - Erreur de script\nURL : '+objparent.URL); },
				404: function() { alert('Erreur 404 - Page inconnue\nURL : '+objparent.URL); }
			}
		}).done(function(json) {
			retour = $.parseJSON(json);
			if(retour.result == true) {
				alert(retour.messages.join('\n'));
			} else {
				alert(retour.ERRORmessages.join('\n'));
			}
		});
		if(this.loadimg != false) setTimeout(function(){ $(objparent).html(objparent.loadimg); }, 500);
		return false;
	});

	/* **************************************************** */
	/* FANCYBOX
	/* **************************************************** */

	var backgroundFCY = 'rgba(0, 0, 0, 0.60)';

	$('.fancybox').fancybox({
		openEffect	: 'fade',
		closeEffect	: 'fade',
        padding     : 6,
		helpers : {
			overlay : {
				css : {
					'background' : backgroundFCY
				}
			}
		}
	});

	$('.fancyboxext').fancybox({
		maxWidth	: 800,
		width		: 800,
		closeClick	: false,
		openEffect	: 'fade',
		closeEffect	: 'fade',
		title 		: true,
        padding     : 6,
		helpers : {
			overlay : {
				css : {
					'background' : backgroundFCY
				}
			}
		}
	});

});


