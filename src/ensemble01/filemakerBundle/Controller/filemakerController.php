<?php

namespace ensemble01\filemakerBundle\Controller;

use filemakerBundle\Controller\filemakerController as fmController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

class filemakerController extends fmController {

    public function indexAction() {
    	return $this->pagewebAction('homepage');
    }

	public function pagewebAction($page = null, $dossier = null) {
		$data = array();
		$data["User"] = $this->get('security.context')->getToken()->getUser();
		$data["page"] = $page;
		$_fm = $this->get('filemaker.database');
		$_fm->log_user($data["User"]);
		switch ($page) {
			case 'liste-rapports-complete':
				$data['rapportsall'] = array(
					1 => array("id" => 1, "ref" => "rapport_001"),
					2 => array("id" => 2, "ref" => "rapport_002"),
					3 => array("id" => 3, "ref" => "rapport_003"),
					4 => array("id" => 4, "ref" => "rapport_004"),
					5 => array("id" => 5, "ref" => "rapport_005"),
					);
				break;
			case 'liste-lieux':
				// liste des lieux
				$data['lieux'] = $_fm->getLieux();
				break;
			case 'liste-locaux':
				// liste des locaux
				$data['locauxByLieux'] = $_fm->getLocauxByLieux();
				break;
			case 'liste-layouts':
				// liste des modèles
				$data['layouts'] = $_fm->getLayouts();
				break;
			case 'liste-affaires':
				// liste des affaires
				$data['affaires'] = $_fm->getAffaires();
				break;
			case 'liste-tiers':
				// liste des tiers
				$data['tiers'] = $_fm->getTiers();
				break;
			case 'liste-scripts':
				// liste des scripts - regroupés par dossiers
				$beg = chr(238).chr(128).chr(129);
				$end = chr(238).chr(128).chr(130);
				$default_niv = "FileMaker scripts";
				$actual_niv = $default_niv;
				$data['scripts'][$actual_niv] = array();
				$data['nb_scripts'] = 0;
				$list = $_fm->getScripts();
				if(!is_string($list)) {
					foreach($list as $nom) {
						if(substr($nom, 0, strlen($end)) === $end) {
							// fin sous-catégorie
							// $nomm = substr($nom, 3);
							$actual_niv = $default_niv;
						} else if(substr($nom, 0, strlen($beg)) === $beg) {
							// début sous-catégorie
							$nomm = substr($nom, strlen($beg));
							$actual_niv = $nomm;
							$data['scripts'][$actual_niv] = array();
						} else if($nom !== null) {
							// $sup = " (".ord($nom[0])."|".ord($nom[1])."|".ord($nom[2])." + ".ord($nom[3])."|".ord($nom[4]).")";
							$data['scripts'][$actual_niv][] = $nom;
							$data['nb_scripts']++;
						}
					}
					if(count($data['scripts'][$default_niv]) < 1) {
						$data['scripts'][$default_niv] = null;
						unset($data['scripts'][$default_niv]);
					}
				}
				break;
			case 'liste-databases':
				// liste des bases de données FM
				$data['databases'] = $_fm->getDatabases();
				break;
			
			default: // homepage, ou null
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


	public function generate_rapportAction($id, $type, $mode, $format) {
		$data = array();
		$data["id"] = $id;
		$data["ref_rapport"] = $id."-1855-".$type;
		$data["nom_logo_geodem"] = 'LogoGeodem.png';
		$data["local"]["adresse"] = "11 rue des hérissons";
		$data["local"]["cp"] = "27000";
		$data["local"]["ville"] = "EVREUX";
		$data["type_logement"] = "T4";
		$data["annee_construction"] = "≤ 1998";
		$data["commanditaire"]["nom"] = "SILOGE";
		$data["commanditaire"]["adresse"] = "6bis Boulevard Chambaudoin";
		$data["commanditaire"]["cp"] = "27009";
		$data["commanditaire"]["ville"] = "EVREUX";
		$data["commanditaire"]["telephone"] = "02 32 38 88 88";
		$data["commanditaire"]["representant"]["civilite"] = "Monsieur";
		$data["commanditaire"]["representant"]["nom"] = "DU TRANOY";
		$data["commanditaire"]["representant"]["prenom"] = "";
		$data["commanditaire"]["representant"]["fonction"] = "Ingénieur Travaux";
		$data["societe"]["representant"]["civilite"] = "Monsieur";
		$data["societe"]["representant"]["nom"] = "LEGENDRE";
		$data["societe"]["representant"]["prenom"] = "Nicolas";
		$data["societe"]["representant"]["fonction"] = "Directeur Opérationnel";
		 
		switch(strtolower($format)) {
			case 'html':
				return $this->render("ensemble01filemakerBundle:pdf:rapport_".$type."_001.html.twig", $data);
				break;
			default:
				$filePDF = __DIR__.'/../../../../app/Resources/tools/html2pdf/html2pdf.class.php';
				if(!file_exists($filePDF)) die('Service HTML2PDF non trouvé !');
				require_once($filePDF);
				$html2pdf = new \HTML2PDF('P', 'A4', 'fr', true, 'UTF-8', array(10, 8, 10, 10));
				$html2pdf->pdf->SetDisplayMode('fullpage');
				// $html = $this->renderView("ensemble01filemakerBundle:pdf:rapport_DAPP_001.html.twig", $data);
				$html = $this->renderView("ensemble01filemakerBundle:pdf:rapport_".$type."_001.html.twig", $data);
				$html2pdf->writeHTML($html, false);
				$html2pdf->Output($data["ref_rapport"].'.pdf');
				return new Response();
				break;
		}

		// return new Response($content, 200, array(
		// 	'Content-Type' => 'application/force-download',
		// 	'Content-Disposition' => 'attachment; filename=test.pdf'
		// 	)
		// );
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
