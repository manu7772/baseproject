<?php
namespace ensemble01\siteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

class siteController extends Controller {

	public function indexAction() {
		$data = array();
		$data['name'] = "anonyme";
		return $this->render('ensemble01siteBundle:pages:homepage.html.twig', $data);
	}

	public function pagewebAction($page = null, $dossier = null) {
		$data = array();
		$data['page'] = $page;
		return $this->render($this->verifVersionPage($data['page']), $data);
	}

	public function menuAction($route, $routeparams) {
		$data = array();
		$data['route'] = $route;
		$data['routeparams'] = urldecode($routeparams);
		return $this->render('ensemble01siteBundle:menus:menuprincipal.html.twig', $data);
	}

	public function downloadAction($filename) {
		$file = file_get_contents(__DIR__.'/../../../../web/images/applis/'.$filename);
		// return new Response('Fichier : '.$filename.' / size : '.strlen($file).' chars.');
		$file = __DIR__.'/../../../../web/images/applis/'.$filename;
		$response = new Response(file_get_contents($file)));
		$response->headers->set('Content-Type', 'application/x-filemaker'); // modification du content-type pour forcer le téléchargement (sinon le navigateur internet essaie d'afficher le document)
		$response->headers->set('Content-Length', filesize($file));
		$response->headers->set('Content-Disposition', 'attachment;filename='.$filename);
		return $response;
	}

	//////////////////////////
	// Autres fonctions
	//////////////////////////

	private function verifVersionPage($page, $dossier = "pages") {
		if(!$this->get('templating')->exists("ensemble01siteBundle:".$dossier.":".$page.".html.twig")) {
			// si la page n'existe pas, on prend le template de la version par défaut
			$page = 'error404';
			$dossier = 'errors';
		}
		return "ensemble01siteBundle:".$dossier.":".$page.".html.twig";
	}

}
