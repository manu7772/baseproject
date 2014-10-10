<?php

namespace ensemble01\filemakerBundle\Controller;

use filemakerBundle\Controller\filemakerController as fmController;

class filemakerController extends fmController {

    public function indexAction($name) {
        return $this->render('ensemble01filemakerBundle:pages:homepage.html.twig', array('name' => $name));
    }

	/**
	 * Affichage de la barre de navigation
	 */
	public function navbarAction() {
		$data = array();
		return $this->render('ensemble01filemakerBundle:menus:navbar.html.twig', $data);
	}

}
