jQuery(document).ready(function($) {

	/* **************************************************** */
	/* Liens externes -> dans une nouvelle fenêtre
	/* **************************************************** */
	$(".URLext").on("click", function(event) {
		URL = $(this).attr("href");
		if(URL == undefined) URL = $(">a", this).first().attr("href");
		// alert(URL);
		window.open(URL);
		event.preventDefault();
		return false;
	});

	// Désactivation des liens sur les <a href="#">
	// $("a").each(function() {
	// 	if($(this).attr('href') == "#") {
	// 		$(this).css('cursor', 'default').addClass('disabled');
	// 	}
	// });
	// Désactivation des liens "disabled"
	$("body").on("click", ".disabled", function(event) {
		event.preventDefault();
		return false;
	});
	// Liens javascript:history.back();
	$("body").on("click", ".backpage", function() { history.back(); })
	// Suppression des liens "active"
	// $("body").on("click", ".active", function(event) {
	// 	event.preventDefault();
	// 	return false;
	// });

	/* **************************************************** */
	/* GESTION DES MESSAGES EN POP-IN / MODALES
	/* **************************************************** */
	if($(".messages >p").length) {
		$(".messages").dialog({
			autoOpen: true,
			width: 380,
			height: "auto",
			minHeight: 120,
			maxHeight: 500,
			minWidth: 400,
			maxWidth: 600,
			modal: true,
			closeText: 'Fermer',
			draggable: true,
			resizable: false,
			// dialogClass: "testss",
			// position: ["center", 250],
			// dialogClass: "RedTitleStuff",
			buttons: {
				"Fermer": function() {
					$(this).dialog("close");
				}
			}
		});
		setTimeout(function() { $(".messages").dialog('close'); }, 6000);
		$(".messages").bind('clickoutside', function(e) {
			$target = $(e.target);
			if (!$target.filter('.hint').length && !$target.filter('.hintclickicon').length) {
				$(this).dialog('close');
			}
		});
	}

});


