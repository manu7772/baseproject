<?php

namespace ensemble01\filemakerBundle\Controller;

use filemakerBundle\Controller\filemakerController as fmController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

class filemakerController extends fmController {

	protected $_fm;	// service filemakerservice

	public function indexAction() {
		return $this->pagewebAction('homepage');
	}

	protected function initFM($datt = null) {
		if(is_string($datt)) $datt = array($datt);
		$data = array();
		foreach($datt as $nom => $val) $data[$nom] = $val;
		// init User
		$data["User"] = $this->get('security.context')->getToken()->getUser();
		$this->_fm = $this->get('ensemble01services.geodiag');
		$this->_fm->log_user($data["User"]);
		return $data;
	}

	protected function actionsRequest() {
		$request = $this->getRequest();
		// Reload fm data
		if($request->query->get('fmreload') === "reload") $this->_fm->reinitService();
		// Change server
		if(is_string($request->query->get('serverchange'))) $this->_fm->setCurrentSERVER($request->query->get('serverchange'));
		// Change base
		if(is_string($request->query->get('basechange'))) $this->_fm->setCurrentBASE($request->query->get('basechange'), null);
	}

	public function pagewebAction($page = null, $pagedata = null) {
		$pagedata = $this->compileData($pagedata);
		// var_dump($pagedata);
		$data = $this->initFM(array("page" => $page));
		// actions GET
		$this->actionsRequest();
		// actions en fonction de données $pagedata
		if(is_array($pagedata)) {
			foreach($pagedata as $nom => $val) {
				switch ($nom) {
					case 'fmreload':
						if($val === "reload") $this->_fm->reinitService();
						break;
					default:
						# code...
						break;
				}
			}
		}
		// données en fonction de la page
		switch ($page) {
			case 'liste-rapports-complete':
				$data['locauxByLieux'] = $this->_fm->getRapports($pagedata);
				break;
			case 'liste-lieux':
				// liste des lieux
				$data['lieux'] = $this->_fm->getLieux();
				break;
			case 'liste-locaux':
				// liste des locaux
				$data['locauxByLieux'] = $this->_fm->getRapports();
				break;
			case 'liste-layouts':
				// liste des modèles
				$data['layouts'] = $this->_fm->getLayouts();
				break;
			case 'liste-affaires':
				// liste des affaires
				$BASEnom = 'GEODIAG_SERVEUR';
				if($this->_fm->setCurrentBASE($BASEnom) !== false) {
					$data['affaires'] = $this->_fm->getAffaires();
				} else $data['affaires'] = 'Base '.$BASEnom.' absente. Accès aux données impossible';
				break;
			case 'liste-tiers':
				// liste des tiers
				$data['tiers'] = $this->_fm->getTiers();
				break;
			case 'liste-scripts':
				// liste des scripts - regroupés par dossiers
				$beg = chr(238).chr(128).chr(129);
				$end = chr(238).chr(128).chr(130);
				$default_niv = "FileMaker scripts";
				$actual_niv = $default_niv;
				$data['scripts'][$actual_niv] = array();
				$data['nb_scripts'] = 0;
				$list = $this->_fm->getScripts();
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
				} else {
					$data['scripts'] = $list;
				}
				break;
			case 'liste-databases':
				// liste des bases de données FM
				$data['databases'] = $this->_fm->getDatabases();
				break;
			case 'liste-servers':
				// liste des bases de données FM
				$data['servers'] = $this->_fm->getListOfServersNames();
				break;
			
			default: // homepage, ou null
				# code...
				break;
		}
		return $this->render($this->verifVersionPage($data['page']), $data);
	}


	/**
	 * Change de serveur courant
	 * @param string $servernom - nom du serveur
	 * @param string $page - page web
	 * @return Response
	 */
	public function changeserverAction($servernom, $page = "homepage") {
		$data = $this->initFM(array("page" => $page));
		$this->_fm->setCurrentSERVER($servernom);
		return $this->render($this->verifVersionPage($data['page']), $data);
	}

	/**
	 * Change de base courante
	 * @param string $basenom - nom du serveur
	 * @param string $page - page web
	 * @return Response
	 */
	public function changebaseAction($basenom, $page = "homepage") {
		$data = $this->initFM(array("page" => $page));
		$this->_fm->setCurrentBASE(null, $basenom);
		return $this->render($this->verifVersionPage($data['page']), $data);
	}


	/**
	 * Traite tous les rapports en BDD FM
	 */
	public function traitement_rapportsAction() {
		$data = $this->initFM(array("page" => 'result-rapports'));

		$data['locauxByLieux'] = $this->_fm->getRapports(0);
		$data['LieuxInRapport'] = $this->_fm->getRapportsLieux();

		$data['result'] = array();
		foreach ($data['locauxByLieux'] as $key => $rapport) {
			// echo('Class : '.get_class($rapport)."<br />");
			$id = $rapport->getField('id');
			// $data['result'][$id] = $this->generate_rapports($rapport);
			if(rand(0,1000) > 800) $data['result'][$id] = false;
				else $data['result'][$id] = true;
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

	/**
	 * génère un rapport et l'enregistre sur disque
	 * @param FileMaker_Record $rapport - objet rapport issu de FM
	 * @return boolean (true si succès)
	 */
	protected function generate_rapports(FileMaker_Record $rapport) {
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

		$filePDF = __DIR__.'/../../../../app/Resources/tools/html2pdf/html2pdf.class.php';
		if(!file_exists($filePDF)) die('Service HTML2PDF non trouvé !');
		require_once($filePDF);
		$html2pdf = new \HTML2PDF('P', 'A4', 'fr', true, 'UTF-8', array(10, 8, 10, 10));
		$html2pdf->pdf->SetDisplayMode('fullpage');
		// $html = $this->renderView("ensemble01filemakerBundle:pdf:rapport_DAPP_001.html.twig", $data);
		$html = $this->renderView("ensemble01filemakerBundle:pdf:rapport_".$type."_001.html.twig", $data);
		$html2pdf->writeHTML($html, false);
		$result = $html2pdf->Output($data["ref_rapport"].'.pdf');

		return $result;
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

	/**
	 * compile les données $pagedata passées dans pageweb (ou pagemodale)
	 * @param string $pagedata
	 * @return string/array selon le type de données
	 */
	private function compileData($pagedata) {
		$pd = json_decode($pagedata, true);
		if($pd !== null) $pagedata = $pd;
		return $pagedata;
	}

	private function verifVersionPage($page, $dossier = "pages") {
		if(!$this->get('templating')->exists("ensemble01filemakerBundle:".$dossier.":".$page.".html.twig")) {
			// si la page n'existe pas, on prend le template de la version par défaut
			$page = 'error404';
			$dossier = 'errors';
		}
		return "ensemble01filemakerBundle:".$dossier.":".$page.".html.twig";
	}

}
