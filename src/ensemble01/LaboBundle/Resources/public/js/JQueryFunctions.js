
////////////////////////////////
// JsonResult : traitement réponse AJAX
////////////////////////////////

// var parsJson = function(json) {
// 	var rep = eval('(' + json + ')');
// 	ConsoleResult("######## Message retour (parsJson) : ", rep.result, true);
// 	return rep;
// }

var JsonResult = function(json) {
	ResponseArray = new Array();
	if(parseInt(json.status) != 200) {
		ConsoleResult('Erreur ', parseInt(json.status), true);
		ResponseArray["html"] = "<p>BAD RETURN "+parseInt(json.status)+"!!!</p>";
		return ResponseArray;
	} else ConsoleResult('Statut Retour ', parseInt(json.status), true);
	if(typeof json.responseText != "undefined") {
		ResponseArray = $.parseJSON(json.responseText);
		CR = new ConsoleResult();
		CR.add("Résultat : ", ResponseArray["result"]);
		CR.add("Message : ", ResponseArray["message"]);
		CR.add("Retour html : ", ResponseArray["html"].length);
		CR.show();
		return ResponseArray;
	} else {
		ResponseArray["html"] = "<p>BAD RETURN !!! json.responseText = undefined</p>";
		return ResponseArray;
	}
}


var modedev = false;
if(($("#modedev").val() == "dev") || ($("#modedev").val() == "test")) modedev = true;

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
			console.log(objCRparent.lib[i],objCRparent.tx[i]);
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

ConsoleResult("mode DEV : ", modedev, true);

$.fn.tagName = function() {
	return this.get(0).tagName.toLowerCase();
}


/* **************************************************** */
/* MISES A JOUR BLOCS avec majZone
/* **************************************************** */
var majZone = function(zone) {
	// alert("Mise à jour "+zone);
	var nomZone = zone.replace("groupe", "");
	var $groupe = $('.'+"groupe"+nomZone);
	$groupe.each(function(){
		if($(this).hasClass("majZone")) { // il faut que la zone contienne la classe "majZone" !!!
			var $target = $(this).parent();
			var data = new Object();
			proto = $(this).attr('data-prototype').split("__");
			url = proto[0];
			data["methode"] = proto[1];
			$("input", $(this)).each(function() {
				nom = $(this).attr('class').replace(nomZone+"_", "");
				// alert(nom+" = "+$(this).val());
				data[nom] = $(this).val();
			});
			ConsoleResult("data send : ", data, true);
			$.ajax({
				type: "POST",
				url: url,
				data: data,
				error: function() {
					ConsoleResult("Désolé : ", "une erreur est survenue. Veuillez recommencer s.v.p.", true);
				}
			}).done( function(data) {
				ConsoleResult("Retour majZone : ", data, true);
				retour = $.parseJSON(data);
				if(retour.result == true) $target.html(retour.html);
			});
		}
	});
}




jQuery(document).ready(function($) {

	////////////////////////////////
	// personnalisation select   //
	// Chosen                   //
	/////////////////////////////
	$("select").attr("data-placeholder", "Selectionner...");
	$("select").chosen({
		no_results_text:'Aucun élément trouvé…',
		allow_single_deselect:true,
		disable_search_threshold:10,
		allow_single_deselect: true
	});

	////////////////////////////////
	// Formulaires dynammiques   //
	//////////////////////////////
	// $("body").on("change", ".dynform select, .dynform input", function() {
	// 	alert("Formulaire modifié");
	// });
	
	////////////////////////////////
	// Formulaires dynammiques   //
	//////////////////////////////
	// $("body").on("change", ".dynform select, .dynform input", function() {
	// 	alert("Formulaire modifié");
	// });
	
	$( ".datepicker" ).datepicker({
		// dates : futures uniquement et jusqu'à -1 mois
		minDate: "-1M",
		// minDate: 0,
		dateFormat: "dd-mm-yy"
	});

	$( ".datepicker2" ).datepicker({
		// dates : min - 1 mois / max + 1 mois et 10 jours
		minDate: "-1M",
		maxDate: "+1M +10D",
		dateFormat: "dd-mm-yy"
	});

	$( ".datepickerAll" ).datepicker({
		// toutes dates
		dateFormat: "dd-mm-yy"
	});

	// Initialisation du chemin
	tinyMCE.baseURL = $("#hiddenStuffs input#homepath").val().replace("app_dev.php/", "") + "bundles/ensemble01labo/js/tinymce";
	tinyMCE.init({
		language : 'fr_FR',
		selector: 'form .richtexts',
		plugins: [
		    "advlist autolink lists link image charmap print preview anchor",
		    "searchreplace visualblocks code fullscreen",
		    "insertdatetime media table contextmenu paste"
		],
		toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
	});

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
	$("body").on("click", ".homepage", function() {
		var homepath = $("#homepath").val();
		document.location = homepath;
	});



	/* **************************************************** */
	/* FANCYBOX
	/* **************************************************** */

	// FOND MODALES : légèrement bleuté
	var backgroundFCY = 'rgba(220, 240, 255, 0.60)';

	$('.fancybox').fancybox({
		openEffect	: 'fade',
		closeEffect	: 'fade',
        padding     : 6,
		helpers : {
			overlay : {
				css : {'background' : backgroundFCY}
			}
		}
	});

	$(".various").fancybox({
		// fitToView	: true,
		// closeBtn	: false,
		maxWidth	: 640,
		width		: 640,
		// height		: '50%',
		// autoSize	: true,
		closeClick	: false,
		openEffect	: 'fade',
		closeEffect	: 'fade',
		title 		: false,
        padding     : 6,
		helpers : {
			overlay : {
				css : {'background' : backgroundFCY}
			}
		}
	}).trigger('click');

	$(".youtubeFancy").fancybox({
		maxWidth	: 800,
		maxHeight	: 600,
		fitToView	: true,
		// width		: '70%',
		// height		: '70%',
		autoSize	: true,
		closeClick	: false,
		openEffect	: 'fade',
		closeEffect	: 'fade',
        padding     : 6,
		helpers : {
			overlay : {
				css : {'background' : backgroundFCY}
			}
		}
	});

	$("a.fancymd").fancybox({ 			
		hideOnContentClick		: true,
		padding					: 4,
		// overlayColor			:'#00FF00',
		// overlayOpacity		: 0.7,
		transitionIn			:'elastic',
		transitionOut			:'elastic',
		zoomSpeedIn				: 300,
		zoomSpeedOut			: 300,
		minWidth				: 400,
		minHeight				: 300,
		maxWidth				: 800,
		maxHeight				: 600,
		fitToView				: true,
		// width				: 600,
		// height				: 400,
		type					:'ajax',
		helpers : {
			overlay : {
				css : {'background' : backgroundFCY}
			}
		}
	});


	/* **************************************************** */
	/* TINYMCE --> directeditor richtext
	/* **************************************************** */

	// Initialisation du chemin --> fait plus haut (pour formulaires)
	// tinyMCE.baseURL = $("#hiddenStuffs input#homepath").val().replace("app_dev.php/", "") + "bundles/ensemble01labo/js/tinymce";

	// Sauvegarde son propore contenu
	var saveHtml = function(elem) {
		// CR = new ConsoleResult();
		// CR.add(elem.id, $("#"+elem.id).html());
		// CR.add(elem.id, $("#"+elem.id).attr('data-prototype'));
		// CR.show();
		$("#"+elem.id).data("texte", tinyMCE.html.Entities.decode($("#"+elem.id).html()));
		path = $("#"+elem.id).attr('data-prototype').split("___");
		$("#"+elem.id).data("pathSend", path[0]);
		$("#"+elem.id).data("pathShort", path[1]);
		$("#"+elem.id).data("pathGet", path[2]);
		$("#"+elem.id).removeAttr('data-prototype');
	}

	var saveTinyChanges = function(elem) {
		texte = tinyMCE.html.Entities.decode(elem.getContent());
		ConsoleResult("Path : ", $("#"+elem.id).data("pathSend"), true);
		ConsoleResult("Changement POST", elem.id + " : " + texte, true);
		// $("#"+elem.id).data("texte", tinyMCE.html.Entities.decode(elem.getContent()));
		$.ajax({
			type: "POST",
			url: $("#"+elem.id).data("pathSend"),
			data: {'data': texte},
			error: function() {
				ConsoleResult("Erreur : ", "changement", true);
			},
			success: function() {
				ConsoleResult("Succès : ", "chargement", true);
			}
		}).done( function(data) {
			retour = eval('('+data+')');
			ConsoleResult("Retour enregistrement : ", data, true);
			$("#"+elem.id).load($("#"+elem.id).data("pathShort"));
		});
	}

	tinyMCE.init({
		// language : 'fr_FR',
		selector: ".editableRich",
		inline: true,
		plugins: [
			"advlist autolink lists link image charmap print preview anchor",
			"searchreplace visualblocks code fullscreen",
			"insertdatetime media table contextmenu paste"
		],
		toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image",
		// toolbar: "undo redo | styleselect | bold italic "
		setup : function(ed){
			saveHtml(ed);
			ed.on('blur', function(e) {
				if($("#"+ed.id).data("texte") != tinyMCE.html.Entities.decode(ed.getContent())) saveTinyChanges(ed);
			}).on('focus', function(e) {
				$("#"+ed.id).load($("#"+ed.id).data("pathGet"))
			}).on('click', function(e) {
				e.preventDefault();
				return false;
			});
		}
	});

	tinyMCE.init({
		selector: ".editable",
		inline: true,
		plugins: ["insertdatetime media contextmenu"],
		toolbar: "undo redo",
		menubar: false,
		setup : function(ed){
			saveHtml(ed);
			ed.on('blur', function(e) {
				if($("#"+ed.id).data("texte") != tinyMCE.html.Entities.decode(ed.getContent())) saveTinyChanges(ed);
			}).on('focus', function(e) {
				$("#"+ed.id).load($("#"+ed.id).data("pathGet"))
			}).on('click', function(e) {
				e.preventDefault();
				return false;
			});;
		}
	});

	tinyMCE.init({
		selector: ".editablePrix",
		inline: true,
		plugins: ["insertdatetime media contextmenu"],
		toolbar: false,
		menubar: false,
		setup : function(ed){
			saveHtml(ed);
			ed.on('blur', function(e) {
				if($("#"+ed.id).data("texte") != tinyMCE.html.Entities.decode(ed.getContent())) saveTinyChanges(ed);
			}).on('focus', function(e) {
				$("#"+ed.id).load($("#"+ed.id).data("pathGet"))
			}).on('click', function(e) {
				e.preventDefault();
				return false;
			});;
		}
	});

	var col = "#5F5"; // couleur highlight
	var clignote = function(elem) {
		// bgcol = $(elem).css("backgroundColor");
		bgcol = "transparent";
		$(elem)
			.animate({backgroundColor: col}, 200)
			.delay(200).animate({backgroundColor: bgcol}, 200)
			.delay(100).animate({backgroundColor: col}, 30)
			.delay(50).animate({backgroundColor: bgcol}, 50)
			.delay(50).animate({backgroundColor: col}, 30)
			.delay(50).animate({backgroundColor: bgcol}, 50);
	}
	var highlight = function(elem) {
		// bgcol = $(elem).css("backgroundColor");
		bgcol = "transparent";
		$(elem)
			.animate({backgroundColor: col}, 100)
			.delay(200)
			.animate({backgroundColor: bgcol}, 300);
	}

	// mémorise les données texte pour comparaison lors de changements
	$(".editable, .editableRich, .editablePrix").each(function() {
		// html = $(this).html();
		// if(html == null) html = "";
		// $(this).data("texte", html);
		clignote(this);
	});

	$("body").on("mouseenter", ".editable, .editableRich, .editablePrix", function() {
		highlight(this);
	});


	// $("body").on("focusout", ".editable, .editableRich, .editablePrix", function() {
	// 	if(!$(this).hasClass("mce-edit-focus")) {
	// 		newhtml = $(this).html();
	// 		if(newhtml == null) newhtml = "";
	// 		if(newhtml != $(this).data("texte")) {
	// 			// Le texte a été modifié : on le persiste en base de données
	// 			highlight(this);
	// 			alert("Texte modifié : \n" + $(this).data("texte") + "\ndevient\n" + newhtml);
	// 		}
	// 	}
	// });


	/* **************************************************** */
	/* ACTIONS SUR ENTITÉS
	/* **************************************************** */
	$('body').on("click", '.LaboAction', function(event) {
		test = $(this).attr("data-prototype").split("__");
		alert("Action : "+test[0]+" "+test[2]+" d'id = "+test[1]);
		event.preventDefault();
	});



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
			modal: true,
			closeText: 'Fermer',
			draggable: true,
			resizable: false,
			dialogClass: "testss",
			position: ["center", 250],
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