var showDownloadZipButton = function(n) {
	if(n == undefined) n = 1;
	$('#download-Zip-Button-'+n).fadeIn(300);
}
var hideDownloadZipButton = function(n) {
	if(n == undefined) n = 1;
	$('#download-Zip-Button-'+n).fadeOut(20);
}

jQuery(document).ready(function($) {

	/* **************************************************** */
	/* Liens externes -> dans une nouvelle fenêtre
	/* **************************************************** */
	$(".URLext").on("click", function(event) {
		URL = $(this).attr("href");
		if(URL == undefined) URL = $("a", this).first().attr("href");
		// alert(URL);
		window.open(URL);
		event.preventDefault();
		return false;
	});

	// Désactivation des liens sur les <a href="#">
	$("a").each(function() {
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
	$('body').on('click', '.exportZipRapports', function() {
		// alert('URL : \n' + $(this).attr('data-href'));
		this.texte = $(this).html();
		$(this).html('Chargment…');
		var objparent = this;
		$.ajax({
			type: "POST",
			url: $(this).attr('data-url'),
			statusCode: {
				500: function() { alert('Erreur 500 - Erreur de script\nURL : '+$(objparent).attr('data-url')); },
				404: function() { alert('Erreur 404 - Page inconnue\nURL : '+$(objparent).attr('data-url')); }
			}
		});
		setTimeout(function(){ $(objparent).html(objparent.texte); }, 500);
	});

	var compteur = 0;
	verifLoad = function() {
		this.texte = $('#visu').html();
		var objparent1 = this;
		$('#visu').html('<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>');
		$('.ajax-reload').each(function(elem) {
			var objparent2 = this;
			if($(this).attr('data-url') != undefined) {
				$(this).load($(this).attr('data-url'), function( response, status, xhr ) {
					if ( status == "error" ) {
						var msg = "Une erreur est survenue : ";
						$(objparent2).html( msg + xhr.status + "/" + xhr.statusText );
					}
				});
			}
		});
		if($('body .rapport-ok').length == parseInt($('body #rapport-quantity'))) {
			showDownloadZipButton(1);
			hideDownloadZipButton(2);
		} else {
			hideDownloadZipButton(1);
			showDownloadZipButton(2);
		}
		compteur++;
		setTimeout(function(){ $('#visu').html(objparent1.texte); }, 100);
		setTimeout(function(){ verifLoad(); }, 1000);
	}
	if($('.ajax-reload').length) { verifLoad(); }

});


