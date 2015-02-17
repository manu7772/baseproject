jQuery(document).ready(function($) {

	var dataTable_language_fr = {
			decimal: 				",",
			processing:				"Traitement en cours...",
			search:					"Rechercher&nbsp;:",
			lengthMenu:				"Afficher _MENU_ &eacute;l&eacute;ments",
			info:					"Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
			infoEmpty:				"Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
			infoFiltered:			"(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
			infoPostFix:			"",
			loadingRecords: 		"Chargement en cours...",
			zeroRecords:			"Aucun &eacute;l&eacute;ment &agrave; afficher",
			emptyTable:				"Aucune donnée disponible dans le tableau",
			paginate: {
				first:				"Premier",
				previous:			"Pr&eacute;c&eacute;dent",
				next:				"Suivant",
				last:				"Dernier"
			},
			aria: {
				sortAscending:		": activer pour trier la colonne par ordre croissant",
				sortDescending: 	": activer pour trier la colonne par ordre décroissant"
			}
	};

	if($('.dataTable').length) {
		$('.dataTable').DataTable({
			responsive: true,
			language: dataTable_language_fr
		});
	}

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
		setTimeout(function() { $(".messages").dialog('close'); }, 600000);
		$(".messages").bind('clickoutside', function(e) {
			$target = $(e.target);
			if (!$target.filter('.hint').length && !$target.filter('.hintclickicon').length) {
				$(this).dialog('close');
			}
		});
	}

});


