<?php

namespace ensemble01\filemakerBundle\Controller;

use filemakerBundle\Controller\filemakerController as fmController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

class filemakerController extends fmController {

	protected $_fm;			// service filemakerservice


	public function indexAction() {
		return $this->pagewebAction('homepage');
	}

	protected function initFMdata($datt = null) {
		if($datt === null) $datt = array();
		if(is_string($datt)) $datt = array($datt);
		$data = array();
		foreach($datt as $nom => $val) $data[$nom] = $val;
		// init User
		$data["User"] = $this->get('security.context')->getToken()->getUser();
		// sélection, tri (GET)
		$data["select"] = $this->compileSelection();
		// objet FM
		$this->_fm = $this->get('ensemble01services.geodiag');
		$this->_fm->log_user($data["User"], null, true);
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
		$data = $this->initFMdata(array("page" => $page));
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
		$crit = array();
		switch ($data["page"]) {
			case 'liste-rapports-complete':
				$data['locauxByLieux'] = $this->_fm->getRapports($pagedata);
				break;
			case 'liste-lieux':
				// liste des lieux
				// $crit['search'][0] = array(
				// 		'column' 	=> 'cle',
				// 		'value' 	=> 'SILOG0000001472'
				// 	);
				$crit['sort'][1] = array(
						'column' 	=> 'cle',
						'way' 		=> 'ASC'
					);
				$data['lieux'] = $this->_fm->getLieux($crit);
				break;
			case 'liste-locaux':
				// liste des locaux
				$crit['search'][0] = array(
						'column' 	=> 'id_local',
						'value' 	=> 'loc0000011620'
					);
				$crit['sort'][1] = array(
						'column' 	=> 'cle_lieux',
						'way' 		=> 'ASC'
					);
				$data['locauxByLieux'] = $this->_fm->getLocaux($crit);
				break;
			case 'liste-affaires':
				// liste des affaires
				$data['affaires'] = $this->_fm->getAffaires($data);
				break;
			case 'liste-layouts':
				// liste des modèles
				$data['layouts'] = $this->_fm->getLayouts();
				break;
			case 'liste-tiers':
				// liste des tiers
				$crit['sort'][1] = array(
						'column' 	=> 'type_tiers',
						'way' 		=> 'ASC'
					);
				$crit['sort'][2] = array(
						'column' 	=> 'nom',
						'way' 		=> 'ASC'
					);
				$crit['sort'][3] = array(
						'column' 	=> 'prenom',
						'way' 		=> 'ASC'
					);
				$data['titre'] = "Liste des tiers (tous tiers)";
				$data['tiers'] = $this->_fm->getTiers($crit);
				break;
			case 'liste-tiers-personnel':
				// liste du personnel
				$crit['search'][0] = array(
						'column' 	=> 'type_tiers',
						'value' 	=> '02-Personnel'
					);
				$crit['sort'][1] = array(
						'column' 	=> 'nom',
						'way' 		=> 'ASC'
					);
				$crit['sort'][2] = array(
						'column' 	=> 'prenom',
						'way' 		=> 'ASC'
					);
				$data['titre'] = "Liste des tiers \"Personnel\"";
				$data['tiers'] = $this->_fm->getTiers($crit);
				break;
			case 'liste-tiers-client':
				// liste des clients
				$crit['search'][0] = array(
						'column' 	=> 'type_tiers',
						'value' 	=> '01-Client'
					);
				$crit['sort'][1] = array(
						'column' 	=> 'nom',
						'way' 		=> 'ASC'
					);
				$crit['sort'][2] = array(
						'column' 	=> 'prenom',
						'way' 		=> 'ASC'
					);
				$data['titre'] = "Liste des tiers \"Client\"";
				$data['tiers'] = $this->_fm->getTiers($crit);
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
		$data['image_logo_geodem'] = "logos/logoGeodem.png";
		return $this->render($this->verifVersionPage($data['page']), $data);
	}


	/**
	 * Change de serveur courant
	 * @param string $servernom - nom du serveur
	 * @param string $page - page web
	 * @return Response
	 */
	public function changeserverAction($servernom, $page = "homepage") {
		$data = $this->initFMdata(array("page" => $page));
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
		$data = $this->initFMdata(array("page" => $page));
		$this->_fm->setCurrentBASE(null, $basenom);
		return $this->render($this->verifVersionPage($data['page']), $data);
	}


	/**
	 * Traite tous les rapports en BDD FM
	 */
	public function traitement_rapportsAction() {
		$data = $this->initFMdata(array("page" => 'result-rapports'));

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
	 * Génère un rapport d'id $id
	 * @param string $id - champ "id" de fm
	 * @param string $type - type de rapport -> "RDM-DAPP", etc.
	 * @param string $mode - type d'enregistrement -> "file" ou "load" ou "screen"
	 * @param string $format - type de document -> "pdf" ou "html"
	 */
	public function generate_rapportAction($id = null, $type = "RDM-DAPP", $mode = "file", $format = "pdf") {

		$data = $this->initFMdata();
		$message = array();
		$messageERR = array();

		$rootpath = $this->container->getParameter('pathrapports');
		$path = $rootpath.$type.'/';
		$aetools = $this->get('ensemble01services.aetools');
		// vérifie la présence du dossier pathrapports et pointe dessus
		$aetools->verifDossierAndCreate($rootpath);
		$aetools->setWebPath($rootpath);
		// vérifie la présence du dossier $type
		$aetools->verifDossierAndCreate($type);

		if($id === null) {
			// récupération de la liste des rapports à générer en base FM
			$mode = 'file';
			$format = 'pdf';
			$data["rapport"] = $this->_fm->getRapports("0");
		} else {
			$data["rapport"] = $this->_fm->getOneRapport($id);
		}
		// var_dump($data["rapport"]);
		// Si erreur
		if(is_string($data["rapport"])) $messageERR[] = $data["rapport"];
		// sinon
		if(count($messageERR) < 1) foreach($data["rapport"] as $rapport) {
			$RAPP = array();
			$RAPP['format'] = $format;
			$RAPP['image_logo_geodem'] = "logos/logoGeodem.png";
			$RAPP["rapport"] = $rapport;
			$RAPP["ref_rapport"] = $rapport->getField('id')."-".$type;
			if(count($messageERR) < 1) switch(strtolower($format)) {
				case 'html':
					$RAPP['imgpath'] = 'bundles/ensemble01filemaker/images/';
					$templt = "ensemble01filemakerBundle:pdf:rapport_".$type."_001.html.twig";
					if(!$this->get('templating')->exists($templt)) {
						// template non trouvé…
						$messageERR[] = 'Template de rapport non trouvé : '.$type;
					} else return $this->render($templt, $RAPP);
					break;
				default:
					$RAPP['imgpath'] = __DIR__.'../../../../../web/bundles/ensemble01filemaker/images/';
					// $html2pdf->pdf->SetDisplayMode('fullpage');
					// $html = $this->renderView("ensemble01filemakerBundle:pdf:rapport_DAPP_001.html.twig", $RAPP);
					$templt = "ensemble01filemakerBundle:pdf:rapport_".$type."_001.html.twig";
					if(!$this->get('templating')->exists($templt)) {
						// template non trouvé…
						$messageERR[] = 'Template de rapport non trouvé : '.$type;
					} else {
						$html = $this->renderView("ensemble01filemakerBundle:pdf:rapport_".$type."_001.html.twig", $RAPP);
						// $html = "<html><body><p>Page de test</p></body></html>"; // TEST
						try {
							$html2pdf = $this->get('html2pdf_factory')->create();
							$html2pdf->writeHTML($html, false);
						} catch (HTML2PDF_exception $e){
							$messageERR[] = 'Erreur génération PDF : '.$e;
						}
						// $html2pdf->Output($RAPP["ref_rapport"].'.pdf');
					}
					break;
			}
			if(count($messageERR) < 1) switch ($mode) {
				case 'screen':
					try {
						$html2pdf->Output($RAPP["ref_rapport"].'.'.$format);
						$message[] = "Le rapport ".$type." réf.".$RAPP["ref_rapport"]." a été généré.";
					} catch (HTML2PDF_exception $e){
						$messageERR[] = 'Erreur génération PDF : '.$e;
					}
					break;
				case 'load':
					$message[] = 'Loading…';
					break;
				default: // file (sur disque dur)
					try {
						$html2pdf->Output($path.$RAPP["ref_rapport"].'.'.$format, "F");
						$message[] = "Le rapport ".$type." réf.".$RAPP["ref_rapport"]." a été généré.";
					} catch (HTML2PDF_exception $e){
						$messageERR[] = 'Erreur génération PDF : '.$e;
					}
					break;
			}
		}
		foreach ($message as $mess) {
			$this->get('session')->getFlashBag()->add('info', $mess);
		}
		foreach ($messageERR as $mess) {
			$this->get('session')->getFlashBag()->add('error', $mess);
		}
		return $this->redirect($this->generateUrl("ensemble01filemaker_pageweb", array("page"=>'liste-rapports-complete', "pagedata" => '0')));
		// return $this->pagewebAction('liste-rapports-complete', '0');
	}

	/**
	 *
	 *
	 */
	public function visuRapportsAction($type = null) {
		$aetools = $this->get('ensemble01services.aetools');
	}


	//////////////////////////
	// Sélection, tri
	//////////////////////////
	// avec GET :
	// ?server=nom_du_serveur
	// ?base=nom_de_la_base
	// ?modele=nom_du_modele
	// ?column=nom_de_la_rubrique
	// ?value=valeur_de_recherche
	// ?order=ordre_de_tri ("ASC" ou "DESC")

	/**
	 * Renvoie un tableau avec les valeurs de sélection pour la base FM
	 * @param array $select
	 * @return array
	 */
	private function compileSelection() {
		$values = array(
			"server",
			"base",
			"modele",
			"column",
			"value",
			"order",
			);
		$GETparams = $this->getRequest()->query->all();
		// $POSTparams = $this->getRequest()->request->all();
		$r = array();
		foreach($GETparams as $nom => $val) {
			if(in_array($nom, $values)) $r[$nom] = $val;
		}
		return $r;
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
