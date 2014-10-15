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
