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
		$data["User"] = $this->get('security.context')->getToken()->getUser();
		$data['page'] = $page;
		$data['fm'] = $this->get('filemaker.database');
		$data['fm']->log_user($data["User"]);
		switch ($page) {
			case 'liste-lieux':
				# code...
				break;
			case 'liste-locaux':
				# code...
				break;
			case 'liste-layouts':
				# code...
				break;
			case 'liste-databases':
				# code...
				break;
			
			default:
				# code...
				break;
		}
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
