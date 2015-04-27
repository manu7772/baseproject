jQuery(document).ready(function($) {

	var JsonResult = function(json) {
		jsonstatus = parseInt(json.status);
		// alert('Json status : '+json.status+" / "+jsonstatus);
		// ConsoleResult('JsonResult => ', jsonstatus, true);
		ResponseArray = Array();
		if(jsonstatus != 200) {
			ConsoleResult('Erreur ', jsonstatus, true);
			ResponseArray["result"] = false;
			ResponseArray["message"] = "BAD RETURN "+jsonstatus+" !";
			ResponseArray["html"] = "<p>"+ResponseArray["message"]+"</p>";
			return ResponseArray;
		} else ConsoleResult('Statut Retour ', jsonstatus, true);
		if(typeof json.responseText != "undefined") {
			ResponseArray = $.parseJSON(json.responseText);
			CR = new ConsoleResult();
			CR.add("Résultat : ", ResponseArray["result"]);
			CR.add("Message : ", ResponseArray["message"]);
			CR.add("Retour html : ", ResponseArray["html"].length);
			CR.show();
			return ResponseArray;
		} else {
			ResponseArray["result"] = false;
			ResponseArray["message"] = "BAD RETURN !!! json.responseText = "+typeof json.responseText;
			ResponseArray["html"] = "<p>"+ResponseArray["message"]+"</p>";
			return ResponseArray;
		}
	}

	var modedev = true;

	// Affichage en console (uniquement en mode dev ou test)
	// libelle :			libellé de l'information
	// texte :				texte de l'information
	// afficheToutDeSuite : affiche l'information aussitôt (sans appeler "show()")
	// force :				affiche sans condition de mode (même si modedev=false)
	var ConsoleResult = function(libelle, texte, afficheToutDeSuite, force) {
		var objCRparent = this;
		this.lib = Array();
		this.tx = Array();
		this.cpt = 0;
		this.frc = force;
		this.show = function(force) {
			if((modedev == true || objCRparent.frc == true || force == true) && objCRparent.lib.length > 0)
			for(i in objCRparent.lib) {
				console.log(objCRparent.lib[i], objCRparent.tx[i]);
			}
		}
		this.add = function(libelle, texte) {
			if(libelle && (texte != null)) {
				if(texte == false) texte = "(boolean) false";
				if(texte == true) texte = "(boolean) true";
				objCRparent.lib[objCRparent.cpt] = libelle+"";
				objCRparent.tx[objCRparent.cpt] = texte+"";
				objCRparent.cpt++;
			}
			return this.objCRparent;
		}
		if(libelle && texte) this.add(libelle, texte);
		if(afficheToutDeSuite == true) this.show();
	}

	ConsoleResult("mode DEV = ", modedev, true);

	initData = function() {
		if($('#JSdata .JSdataItem').length) {
			$('#JSdata .JSdataItem').each(function() {
				if($(this).attr('data-prototype') != null && $(this).attr('data-prototype') != "") {
					$('#JSdata').data($(this).attr('id'), $(this).attr('data-prototype'));
				}
			});
			$('#JSdata .JSdataItem').each(function() {
				$(this).remove();
			});
		}
	}
	initData();

	var JSdata = {
		get: function(nom) {
			return $('#JSdata').data(nom);
		}
	};


	var dataTable_language = {
		fr: {
			decimal:				",",
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
			},
		},
	};

	// alert("Nombre de tableaux : "+$('.dataTable').length);
	if($('.dataTable').length) {
		$('.dataTable').each(function(index) {
			var dtJQObjId = $(this);
			$(this).DataTable({
				responsive:			true,
				language:			dataTable_language.fr,
				stateSave:			true,
				stateLoadCallback: function (settings) {
					// alert('Trouvé Id : '+dtJQObj.attr('id'));
					if(JSdata.get('dtParams-'+dtJQObjId.attr('id')) !== undefined) {
						// alert("LOAD :\n"+JSdata.get('dtParams'));
						return $.parseJSON(JSdata.get('dtParams-'+dtJQObjId.attr('id')));
					} else {
						// alert("LOAD :\naucune donnée trouvée pour cette page");
						return null;
					}
				},
				stateSaveCallback:	function (settings, data) {
					passdata = {
						"UrlI": JSdata.get('UrlI'),
						"DtId": dtJQObjId.attr('id'),
						"data": data
					};
					$.ajax( {
						url: JSdata.get('datatables_statesave'),
						data: passdata,
						dataType: "json",
						type: "POST",
						success: function(json) {
							retour = $.parseJSON(json);
							if(retour.result != true) {
								// alert('Erreur à l\'enregistrement de vos paramètres de tri');
							}
							// alert("SAVE :\n"+retour.data);
							// alert(
							// 	'• Json data : result = '+retour.result
							// 	+'\n• Json data : message = '+retour.message
							// 	+'\n• Json data parsed = \n'+$.parseJSON(retour.data)
							// 	+'\n• Json data : data = \n'+retour.data
							// 	// +'\nRéf. page : '+UrlI
							// 	// +'\nJson data : nom = \n'+retour.data.UrlI
							// );
						},
						// error: function(json) {
						// },
					});
				}
			});
		});
	}

	/* **************************************************** */
	/* Liens externes -> dans une nouvelle fenêtre
	/* **************************************************** */
	$("body").on("click", ".URLext", function(event) {
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

	$('.list-toogle').each(function() {
		$($(this).attr('data-toogle-target')).hide(1);
	});

	$('body').on('click', '.list-toogle', function(event) {
		// alert($(this).attr('data-toogle-target'));
		$($(this).attr('data-toogle-target')).slideToggle(300);
	});

	/* **************************************************** */
	/* FANCYBOX
	/* **************************************************** */

	var backgroundFCY = 'rgba(0, 0, 0, 0.60)';

	$('.fancybox').fancybox({
		maxWidth	: 640,
		width		: 640,
		closeClick	: false,
		openEffect	: 'fade',
		closeEffect	: 'fade',
		title 		: 'Rapport en html',
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


