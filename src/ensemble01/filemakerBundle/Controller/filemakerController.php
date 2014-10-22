<?php

namespace ensemble01\filemakerBundle\Controller;

use filemakerBundle\Controller\filemakerController as fmController;

class filemakerController extends fmController {

    public function indexAction($name) {
		$data = array();
		$data["User"] = $this->get('security.context')->getToken()->getUser();
		$data['fm'] = $this->get('filemaker.database');
		$data['fm']->log_user($data["User"]);
		// $data['lieux'] = $data['fm']->getLieux();
        return $this->render('ensemble01filemakerBundle:pages:homepage.html.twig', $data);
    }

	public function pagewebAction($page = null, $dossier = null) {
		$data = array();
		$data['page'] = $page;
		return $this->render($this->verifVersionPage($data['page']), $data);
	}

	/**
	 * Affichage de la barre de navigation
	 */
	public function navbarAction() {
		$data = array();
		return $this->render('ensemble01filemakerBundle:menus:navbar.html.twig', $data);
	}



	//////////////////////////
	// Autres fonctions
	//////////////////////////

	private function verifVersionPage($page, $dossier = "pages") {
		if(!$this->get('templating')->exists("ensemble01filemakerBundle:".$dossier.":".$page.".html.twig")) {
			// si la page n'existe pas, on prend le template de la version par défaut
			$page = 'error404';
			$dossier = 'errors';
		}
		return "ensemble01filemakerBundle:".$dossier.":".$page.".html.twig";
	}

}
