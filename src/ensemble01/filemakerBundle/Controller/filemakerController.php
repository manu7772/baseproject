<?php

namespace ensemble01\filemakerBundle\Controller;

use filemakerBundle\Controller\filemakerController as fmController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

class filemakerController extends fmController {

	const FORCE_SELECT = true;

	protected $_fm;				// service filemakerservice
	protected $selectService;	// service aeSelect
	protected $DEV = true;		// mode DEV
	protected $recurs = 0;
	protected $recursMAX = 6;
	protected $aetools;

	public function indexAction() {
		$data['title'] = "Tableau de bord";
		return $this->pagewebAction('BShomepage', $data);
	}

	/**
	 * Initialise les données liées au controller
	 * 	- $this->_fm 			= objet filemakservice (loggé avec l'utilisateur actuel)
	 * 	- $this->selectService	= objet aeService
	 * @param array $datt - données à ajouter
	 * @return array - données initialisées
	 */
	protected function initGlobalData($datt = null) {
		if($datt === null) $datt = array();
		if(is_string($datt)) $datt = array($datt);
		$data = array();
		foreach($datt as $nom => $val) $data[$nom] = $val;

		// choix du template (redirection si nécessaire)
		if(!isset($data['page'])) $data['page'] = null;
		if(!isset($data['selectname'])) $data['selectname'] = $data['page'];
		if(isset($data['pagedata']['redirect'])) {
			$data['template'] = $data['page'] = $data['pagedata']['redirect'];
		} else $data['template'] = $data['page'];
		// actions GET
		$data['GET_actions'] = $this->actionsRequest('GET');
		// actions POST
		$data['POST_actions'] = $this->actionsRequest('POST');
		// actions en fonction de données $pagedata
		$data['pagedata_actions'] = $this->actionsPagedata($data['pagedata']);

		// init User
		$data["User"] = $this->get('security.context')->getToken()->getUser();
		// sélection, tri (GET)
		$this->selectService = $this->get('ensemble01services.selection');
		$this->selectService->setSelectName($data['selectname']);
		// $data["old_select"] = $this->selectService->getCurrentSelect(self::FORCE_SELECT); // $this->compileSelection();
		$this->selectService->computeAllQueriesSelect();
		// $data["new_select"] = $this->selectService->getCurrentSelect(self::FORCE_SELECT);
		// objet FM
		$this->initFmData($data["User"]);
		return $data;
	}

	protected function initFmData($user = null) {
		if(!is_object($this->_fm)) {
			if($user === null) $user = $this->get('security.context')->getToken()->getUser();
			$this->_fm = $this->get('ensemble01services.geodiag');
			$this->_fm->log_user($user, null, true);
		}
		return $this->_fm;
	}

	/**
	 * traitements selon données passées en GET/POST
	 * ?serverchange=nom_du_serveur
	 * ?basechange=nom_de_la_base
	 * ?fmreload=reload
	 * @return array - actions réalisées en GET/POST
	 */
	protected function actionsRequest($method = 'GET') {
		$methods = array('GET' => 'request', 'POST' => 'query');
		if(!array_key_exists(strtoupper($method), $methods)) {
			reset($methods);
			$method = key($methods);
		}
		$request = $this->getRequest()->$methods[$method];
		$listRequ = array(
			'fmreload'		=> "reload",
			'serverchange'	=> null,
			'basechange'	=> null,
			);
		$data = array();
		
		foreach ($listRequ as $param => $commande) {
			if(strlen($request->get($param)) > 0) {
				$result = "no";
				switch ($param) {
					case 'fmreload':
						// Reload fm data
						if($commande === "reload") $result = $this->_fm->reinitService();
						break;
					case 'serverchange':
						// Change server
						$result = $this->_fm->setCurrentSERVER($commande);
						break;
					case 'basechange':
						// Change base
						$result = $this->_fm->setCurrentBASE($commande, null);
						break;
					default:
						# code...
						break;
				}
				if($result !== 'no') {
					$data[$param]['commande'] = $commande;
					$data[$param]['result'] = $result;
				}
			}
		}
		return $data;
	}

	protected function actionsPagedata($params) {
		$data = array();
		if(is_array($params)) {
			foreach($params as $param => $commande) {
				$result = "no";
				switch ($param) {
					case 'fmreload':
						if($commande === "reload") $result = $this->_fm->reinitService();
						break;
					default:
						# code...
						break;
				}
				if($result !== 'no') {
					$data[$param]['commande'] = $commande;
					$data[$param]['result'] = $result;
				}
			}
		}
		return $data;
	}

	public function pagewebAction($page = null, $pagedata = null) {
		$ctrlData = array();
		// ctrlData > pagedata :
		// 	=> redirect : change de page (n'utilise pas $page, du coup)
		// 	=> …

		// ctrlData :
		// 	=> user : objet user connecté
		// 	=> old_select : élements de sélection des données antérieurs
		// 	=> new_select : élements de sélection des données nouveaux
		// 	=> page : utilisé pour les données à rendre
		// 	=> template : utilisé pour le template de rendu
		// 	=> title : balise title de la page
		// 	=> meta : balise title de la page
		// 	=> h1 : titre h1 de la page
		// 	=> data : données pour la page
		// 	=> pagedata : données envoyée avec $pagedata (dans l'URL)
		// 	=> pagedata_raw : données BRUTES envoyée avec $pagedata (dans l'URL)
		// 	=> GET_actions : requêtes envoyées en GET (?param=valeur)
		// 	=> POST_actions : requêtes envoyées en POST
		// 	=> pagedata_actions : requêtes envoyées dans pagedata
		// 	=> errors : erreurs

		// récupération des données et de l'bjet FM $this->_fm
		$ctrlData = $this->initGlobalData(array(
			'page'			=> $page,
			'pagedata'		=> $this->unCompileData($pagedata),
			'pagedata_raw'	=> $pagedata, // données $pagedata brutes
			'errors'		=> array(),
		));

		// données en fonction de la page
		switch ($ctrlData['template']) {
			case 'liste-rapports-lots':
				$ctrlData['h1'] = "Rapports par lots";
				$this->selectService->addGroupe(array($this->_fm->getCurrentSERVER(), 'GEODIAG_Rapports', 'Rapports_Local_Web'));
				// $this->selectService->setRecherche('intitule', 'Marché Evreux');
				$this->selectService
					// ->setRecherche('num_lot', $ctrlData['pagedata']['numlot'])
					->setRecherche('a_traiter', intval($ctrlData['pagedata']))
					;
				// $this->selectService->emptyRecherche();
				// $this->selectService->setSort('date_projet', 'DESC');

				$ctrlData["new_select"] = $this->selectService->getCurrentSelect(self::FORCE_SELECT);
				// $this->vardumpDev($ctrlData["new_select"], "New select : ".$this->selectService->getGroupeName());
				$ctrlData['rapports'] = $this->_fm->getListeRapportsByLot($ctrlData["new_select"]);
				break;
			case 'liste-rapports-complete':
				if(intval($ctrlData['pagedata']) === 1) {
					$a_traiter = 1;
					$ctrlData['h1'] = "Rapports générés";
				} else if(intval($ctrlData['pagedata']) === 0) {
					$a_traiter = 0;
					$ctrlData['h1'] = "Rapports en attente";
				} else {
					$a_traiter = 0;
					$ctrlData['h1'] = "Rapports en attente";
				}
				$this->selectService->addGroupe(array($this->_fm->getCurrentSERVER(), 'GEODIAG_Rapports', 'Rapports_Local_Web'));
				$this->selectService
					// ->setRecherche('num_lot', $ctrlData['pagedata']['numlot'])
					->setRecherche('a_traiter', $a_traiter)
					;
				$ctrlData["new_select"] = $this->selectService->getCurrentSelect(self::FORCE_SELECT);
				$ctrlData['rapports'] = $this->_fm->getRapports($ctrlData["new_select"]);
				break;
			case 'liste-lieux':
				$ctrlData['h1'] = "Lieux";
				$pagedata['recherche']['sort'][1] = array(
						'column' 	=> 'cle',
						'way' 		=> 'ASC'
					);
				$ctrlData['lieux'] = $this->_fm->getLieux($pagedata['recherche']);
				break;
			case 'liste-locaux':
				// liste des locaux
				$ctrlData['h1'] = "Locaux";
				$this->selectService->addGroupe(array($this->_fm->getCurrentSERVER(), 'GEODIAG_SERVEUR', 'Local'));
				$this->selectService->setRecherche('id_local', 'loc0000011620');
				$this->selectService->setSort('cle_lieux', 'ASC');
				$ctrlData['locauxByLieux'] = $this->_fm->getLocaux($this->selectService->getCurrentSelect(self::FORCE_SELECT));
				break;
			case 'liste-affaires':
				// liste des affaires
				$ctrlData['h1'] = "Liste des affaires";
				$this->selectService->addGroupe(array($this->_fm->getCurrentSERVER(), 'GEODIAG_SERVEUR', 'Projet_Liste'));
				// $this->selectService->setRecherche('intitule', 'Marché Evreux');
				$this->selectService
					->setRecherche('intitule', '*')
					->addRecherche('intitule', 'PROJET TEMOIN', '!')
					;
				// $this->selectService->emptyRecherche();
				// $this->selectService->setSort('date_projet', 'DESC');

				$ctrlData["new_select"] = $this->selectService->getCurrentSelect(self::FORCE_SELECT);
				// $this->vardumpDev($ctrlData["new_select"], "New select : ".$this->selectService->getGroupeName());
				$ctrlData['affaires'] = $this->_fm->getAffaires($ctrlData["new_select"]);

				break;
			case 'liste-layouts':
				// liste des modèles
				$ctrlData['h1'] = "Liste des modèles de ".$this->_fm->getCurrentBASE();
				$ctrlData['layouts'] = $this->_fm->getLayouts();
				break;
			case 'liste-fields':
				// liste des modèles
				$ctrlData['h1'] = "Liste des champs de ".$this->_fm->getCurrentBASE();
				// $ctrlData['layout'] = $pagedata['layout'];
				$r = $this->_fm->getDetailFields($pagedata['layout']);
				if(is_array($r)) $ctrlData = array_merge($ctrlData, $r);
					else $ctrlData['fields'] = $r;
				// $this->vardumpDev($ctrlData);
				// die($pagedata['layout']." => ".$ctrlData['layout']);
				break;
			case 'liste-tiers':
				// liste des tiers
				$ctrlData['h1'] = "Liste de tous les tiers";
				$pagedata['recherche']['sort'][1] = array(
						'column' 	=> 'type_tiers',
						'way' 		=> 'ASC'
					);
				$pagedata['recherche']['sort'][2] = array(
						'column' 	=> 'nom',
						'way' 		=> 'ASC'
					);
				$pagedata['recherche']['sort'][3] = array(
						'column' 	=> 'prenom',
						'way' 		=> 'ASC'
					);
				$ctrlData['titre'] = "Liste des tiers (tous tiers)";
				$ctrlData['tiers'] = $this->_fm->getTiers($pagedata['recherche']);
				break;
			case 'liste-tiers-personnel':
				// liste du personnel
				$ctrlData['h1'] = "Liste du personnel";
				$pagedata['recherche']['search'][0] = array(
						'column' 	=> 'type_tiers',
						'value' 	=> '02-Personnel'
					);
				$pagedata['recherche']['sort'][1] = array(
						'column' 	=> 'nom',
						'way' 		=> 'ASC'
					);
				$pagedata['recherche']['sort'][2] = array(
						'column' 	=> 'prenom',
						'way' 		=> 'ASC'
					);
				$ctrlData['titre'] = "Liste des tiers \"Personnel\"";
				$ctrlData['tiers'] = $this->_fm->getTiers($pagedata['recherche']);
				break;
			case 'liste-tiers-client':
				// liste des clients
				$ctrlData['h1'] = "Liste des clients";
				$pagedata['recherche']['search'][0] = array(
						'column' 	=> 'type_tiers',
						'value' 	=> '01-Client'
					);
				$pagedata['recherche']['sort'][1] = array(
						'column' 	=> 'nom',
						'way' 		=> 'ASC'
					);
				$pagedata['recherche']['sort'][2] = array(
						'column' 	=> 'prenom',
						'way' 		=> 'ASC'
					);
				$ctrlData['titre'] = "Liste des tiers \"Client\"";
				$ctrlData['tiers'] = $this->_fm->getTiers($pagedata['recherche']);
				break;
			case 'liste-scripts':
				// liste des scripts - regroupés par dossiers
				$ctrlData['h1'] = "Scripts FM ".$this->_fm->getCurrentBASE();
				$ctrlData['forceload'] = true;
				$ctrlData['scripts'] = array_merge($ctrlData,
					array('liste' => $this->_fm->getScripts(null, null, $ctrlData['forceload'], false)),
					array('group' => $this->_fm->getScripts(null, null, $ctrlData['forceload'], true))
					);
				break;
			case 'liste-databases':
				// liste des bases de données FM
				$ctrlData['h1'] = "Databases FM disponibles";
				$ctrlData['databases'] = $this->_fm->getDatabases();
				break;
			case 'liste-servers':
				// liste des bases de données FM
				$ctrlData['h1'] = "Serveurs FM disponibles";
				$ctrlData['servers'] = $this->_fm->getListOfServersNames();
				break;
			
			default: // homepage, ou null
				$ctrlData['h1'] = "Tableau de bord";
				break;
		}
		return $this->render($this->verifVersionPage($ctrlData['template']), $ctrlData);
	}


	/**
	 * Traite tous les rapports en BDD FM
	 */
	public function traitement_rapportsAction() {
		$data = $this->initGlobalData(array("page" => 'result-rapports'));

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
	 * Génère un rapport d'id $id
	 * @param string $id - champ "id" de fm
	 * @param string $type - type de rapport -> "RDM-DAPP", etc.
	 * @param string $mode - type d'enregistrement -> "file" ou "load" ou "screen"
	 * @param string $format - type de document -> "pdf" ou "html"
	 */
	public function generate_rapportAction($id = null, $mode = "file", $format = "pdf", $pagedata = null) {
		$pagedata['pagedata'] = $pagedata;
		$pagedata = array_merge($pagedata, $this->unCompileData($pagedata));

		$data = array_merge($data, $this->initGlobalData());
		$message = array();
		$messageERR = array();

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
			$type = $rapport->getField('type_rapport');
			// vérifie la présence du dossier $type
			$path = $this->verifAndGoDossier($type);


			$RAPP = array();
			$RAPP['format'] = $format;
			$RAPP['image_logo_geodem'] = "logos/logoGeodem.png";
			$RAPP["rapport"] = $rapport;
			$RAPP["ref_rapport"] = $this->_fm->getRapportFileName($rapport);
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
					$message[] = 'Téléchargement…';
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
		if(count($messageERR) < 1) foreach ($message as $mess) {
			$this->get('session')->getFlashBag()->add('info', $mess);
		}
		foreach ($messageERR as $mess) {
			$this->get('session')->getFlashBag()->add('error', $mess);
		}
		// page à afficher
		if(!isset($pagedata['redirect'])) $pagedata['redirect'] = 'liste-rapports-complete';
		return $this->pagewebAction($pagedata['redirect'], $pagedata);
		// return $this->redirect($this->generateUrl("ensemble01filemaker_pageweb", array("page"=>'liste-rapports-complete', "pagedata" => '0')));
	}

	// http://localhost:8888/GitHub/baseproject/web/app_dev.php/fm/rapportfm-by-lot/000000147205-02-2015-17-42

	/**
	 * Génère les rapports selon un numéro de lot (ensemble de rapports)
	 * @param string $numlot - numéro du lot
	 * @param string $pagedata - Données diverses
	 */
	public function generate_by_lot_rapportAction($numlot, $pagedata = null) {
		$pagedata_raw = $pagedata;
		$pagedata = $this->unCompileData($pagedata);
		$pagedata['pagedata'] = $pagedata_raw;

		$this->initFmData($pagedata);
		$message = array();
		$messageERR = array();
		$format = 'pdf';

		$data["rapport"] = $this->_fm->Cloture_Rapport_Apres_Serveur($numlot);
		// $this->vardumpDev($action, "Résultat de Cloture_Rapport_Apres_Serveur");
		// $data["rapport"] = $this->_fm->getRapportsByLot($numlot);
		// $this->vardumpDev($data["rapport"], 'Rapports de lot '.$numlot);
		// die('end');

		// var_dump($data["rapport"]);
		// Si erreur
		if(is_string($data["rapport"])) $messageERR[] = $data["rapport"];
		// sinon
		if(count($messageERR) < 1) {
			$RAPP = array();
			foreach($data["rapport"] as $key => $rapport) {
				$RAPP['format'] = $format;
				$RAPP['image_logo_geodem'] = "logos/logoGeodem.png";
				$RAPP["rapport"] = $rapport;
				$RAPP["ref_rapport"] = $this->_fm->getRapportFileName($rapport);
				$type = $rapport->getField('type_rapport');
				$path = $this->verifAndGoDossier($type);
	
				$RAPP['imgpath'] = __DIR__.'../../../../../web/bundles/ensemble01filemaker/images/';
				// $html2pdf->pdf->SetDisplayMode('fullpage');
				// $html = $this->renderView("ensemble01filemakerBundle:pdf:rapport_DAPP_001.html.twig", $RAPP);
				$templt = "ensemble01filemakerBundle:pdf:rapport_".$type."_001.html.twig";
				if($this->get('templating')->exists($templt)) {
					$html = $this->renderView("ensemble01filemakerBundle:pdf:rapport_".$type."_001.html.twig", $RAPP);
					// $html = "<html><body><p>Page de test</p></body></html>"; // TEST
					try {
						$html2pdf = $this->get('html2pdf_factory')->create();
						$html2pdf->writeHTML($html, false);
					} catch (HTML2PDF_exception $e){
						$messageERR[] = 'Erreur génération PDF : '.$e;
					}
					// $html2pdf->Output($RAPP["ref_rapport"].'.pdf');
				} else {
					// template non trouvé…
					$messageERR[] = 'Template de rapport non trouvé : '.$type;
				}
				if(count($messageERR) < 1) {
					try {
						$html2pdf->Output($path.$RAPP["ref_rapport"].'.'.$format, "F");
						$message[] = "Le rapport ".$type." réf.".$RAPP["ref_rapport"]." a été généré.";
					} catch (HTML2PDF_exception $e){
						$messageERR[] = 'Erreur génération PDF : '.$e;
					}
				}
			}
		}
		if(count($messageERR) < 1) foreach ($message as $mess) {
			$this->get('session')->getFlashBag()->add('info', $mess);
		}
		foreach ($messageERR as $mess) {
			$this->get('session')->getFlashBag()->add('FMerror', $mess);
		}
		// page à afficher
		if(!isset($pagedata['redirect'])) $pagedata['redirect'] = 'liste-rapports-complete';
		return $this->pagewebAction($pagedata['redirect'], $pagedata);
		// return $this->redirect($this->generateUrl("ensemble01filemaker_pageweb", array("page"=>'liste-rapports-complete', "pagedata" => '0')));
	}
	// http://localhost:8888/GitHub/baseproject/web/app_dev.php/fm/s-admin/retablir-rapportfm-by-lot/Proj0000004000000151417-02-2015-17-09-31/%7B%22redirect%22:%22liste-rapports-complete%22%7D
	// %7B%22redirect%22:%22liste-rapports-complete%22%7D

	/**
	 * Génère les rapports selon un numéro de lot (ensemble de rapports)
	 * --> VIA COMMANDE FILEMAKER
	 * @param string $numlot - numéro du lot
	 */
	public function generate_by_lot_rapport_fmAction($numlot) {
		$this->generate_by_lot_rapportAction($numlot);
		return $this->listeRapportsLotsAction($numlot);
	}

	public function public_listeRapportsLotsAction($numlot = null) {
		$data = array();
		$numlot === null ? $all = true : $all = false;
		$data["rapports"] = $this->initFmData()->getRapportsByLot($numlot, $all);
		$data["numlot"] = $numlot;
		return $this->render($this->verifVersionPage("liste-rapports-by-lots", "public-views"), $data);
	}

	/**
	 * Rétablit les rapports selon un numéro de lot (ensemble de rapports)
	 * @param string $numlot - numéro du lot
	 * @param string $pagedata - Données diverses
	 */
	public function retablir_by_lot_rapportAction($numlot, $pagedata = null) {
		$pagedata_raw = $pagedata;
		$pagedata = $this->unCompileData($pagedata);
		$pagedata['pagedata'] = $pagedata_raw;

		$this->initFmData($pagedata);
		$message = array();
		$messageERR = array();

		$action = $this->_fm->Retablir_Rapport_Apres_Serveur($numlot);
		if(is_string($action)) $messageERR[] = $action;
		else {
			foreach ($action as $key => $rapport) {
				$message[] = "Rapport rétabli : ".$rapport->getField('id')." (version ".$rapport->getField('version').")";
			}
		}

		if(count($messageERR) < 1) foreach ($message as $mess) {
			$this->get('session')->getFlashBag()->add('info', $mess);
		}
		foreach ($messageERR as $mess) {
			$this->get('session')->getFlashBag()->add('FMerror', $mess);
		}
		// page à afficher
		if(!isset($pagedata['redirect'])) $pagedata['redirect'] = 'liste-rapports-complete';
		return $this->pagewebAction($pagedata['redirect'], $pagedata);
	}

	/**
	 * Change de serveur courant
	 * @param string $servernom - nom du serveur
	 * @param string $page - page web
	 * @return Response
	 */
	public function changeserverAction($servernom, $page = "homepage") {
		$data = $this->initGlobalData(array("page" => $page));
		$this->_fm->setCurrentSERVER($servernom);
		return $this->render($this->verifVersionPage($data['page']), $data);
	}


	/****************************************/
	/*** BLOCS ET MODULES
	/****************************************/

	/**
	 * Affichage de la barre de navigation
	 * @return Response
	 */
	public function navbarAction() {
		$data = array();
		return $this->render('ensemble01filemakerBundle:menus:navbar.html.twig', $data);
	}

	/**
	 * Affichage de la barre TOP de navigation BS
	 * @return Response
	 */
	public function BSnavbarAction($title = '') {
		$data = array();
		return $this->render('ensemble01filemakerBundle:menus:BSnavbar.html.twig', $data);
	}

	/**
	 * Affichage de la barre SIDE de navigation BS
	 * @return Response
	 */
	public function BSsidebarAction($title = 'Tableau de bord', $icon = 'fa-dashboard', $template = 'BSsidebar') {
		$data = array();
		$data = $this->initFmData();
		// sélection
		$this->selectService = $this->get('ensemble01services.selection');
		$this->selectService->setSelectName('BSsideBar');
		$this->selectService->addGroupe(array($this->_fm->getCurrentSERVER(), 'GEODIAG_SERVEUR', 'Projet_Liste'));
		// $this->selectService->emptyRecherche();
		$this->selectService
			->setRecherche('intitule', '*')
			->addRecherche('intitule', 'PROJET TEMOIN', '!')
			// ->emptyRecherche();
			;
		// data
		$data['title'] = $title;
		$data['icon'] = $icon;
		$data['affaires'] = $this->_fm->getAffaires($this->selectService->getCurrentSelect(self::FORCE_SELECT));
		return $this->render('ensemble01filemakerBundle:menus:'.$template.'.html.twig', $data);
	}

	/**
	 * Affichage d'un module d'admin BS
	 * @param sting $template - template à utiliser (on peut préciser aussi le template dans $blocdata['template'], qui prend alors la priorité sur $template)
	 * @param string $blocdata - données supplémentaires (encodées en JSON ou mixed)
	 * @return Response
	 */
	public function moduleAdminAction($template, $blocdata = null) {
		$def_template = 'inconnu';
		$blocdata = $this->unCompileData($blocdata);
		$blocdata['error'] = null;
		$template_path = "ensemble01filemakerBundle:blocs:module_".$template.".html.twig";
		if(!$this->get('templating')->exists($template_path)) {
			$template_path = "ensemble01filemakerBundle:blocs:module_".$def_template.".html.twig";
			$blocdata['error'] = "Module <strong>".$template."</strong> inconnu.";
		}
		// titre/icone du module par défaut
		$blocdata['module']['title'] = $template;
		$blocdata['module']['icon'] = 'fa-gear';
		// remplissage des données selon le module
		if(isset($blocdata['template']) && is_string($blocdata['template'])) $template = $blocdata['template'];
		switch ($template) {
			case 'notifications':
				$blocdata['module']['title'] = 'Notifications';
				$blocdata['module']['icon'] = 'fa-bell';
				break;
			case 'barchart':
				$blocdata['module']['title'] = 'Graphique à barres';
				$blocdata['module']['icon'] = 'fa-bar-chart-o';
				break;
			case 'areachart':
				$blocdata['module']['title'] = 'Graphique surface';
				$blocdata['module']['icon'] = 'fa-bar-chart-o';
				break;
			case 'donutchart':
				$blocdata['module']['title'] = 'Graphique circulaire';
				$blocdata['module']['icon'] = 'fa-bar-chart-o';
				break;
			
			default:
				// $blocdata['module']['icon'] = 'fa-comments';
				break;
		}

		return $this->render($template_path, $blocdata);
	}


	/****************************************/
	/*** AUTRES MÉTHODES
	/****************************************/

	/**
	 * Change de base courante
	 * @param string $basenom - nom du serveur
	 * @param string $page - page web
	 * @return Response
	 */
	public function changebaseAction($basenom, $page = "homepage") {
		$data = $this->initGlobalData(array("page" => $page));
		$this->_fm->setCurrentBASE(null, $basenom);
		return $this->render($this->verifVersionPage($data['page']), $data);
	}

	// public function visuRapportsAction($type = null) {
	// 	$aetools = $this->get('ensemble01services.aetools');
	// }

	protected function verifAndGoDossier($type) {
		$rootpath = $this->container->getParameter('pathrapports');
		$path = $rootpath.$type.'/';
		$this->aetools = $this->get('ensemble01services.aetools');
		// vérifie la présence du dossier pathrapports et pointe dessus
		$this->aetools->verifDossierAndCreate($rootpath);
		$this->aetools->setWebPath($rootpath);
		$this->aetools->verifDossierAndCreate($type);
		return $path;
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

	/**
	 * décompile les données $pagedata passées dans pageweb (ou pagemodale) si elles sont en JSON
	 * @param string $pagedata
	 * @return string/array selon le type de données
	 */
	private function unCompileData($pagedata) {
		$pd = @json_decode($pagedata, true);
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



	/****************************************/
	/*** AUTRES MÉTHODES DE DÉVELOPPEMENT
	/****************************************/

	/**
	 * affiche le contenu de $data (récursif)
	 * @param mixed $data
	 */
	protected function affPreData($data, $nom = null) {
		$this->recurs++;
		if($this->recurs <= $this->recursMAX) {
			$style = " style='margin:4px 0px 8px 20px;padding-left:4px;border-left:1px solid #666;'";
			$istyle = " style='color:#999;font-style:italic;'";
			if(is_string($nom)) {
				$affNom = "[\"".$nom."\"] ";
			} else if(is_int($nom)) {
				$affNom = "[".$nom."] ";
			} else {
				$affNom = "[?]";
				$nom = null;
			}
			switch (strtolower(gettype($data))) {
				case 'array':
					echo("<div".$style.">");
					echo($affNom."<i".$istyle.">".gettype($data)."</i> (".count($data).")");
					foreach($data as $nom2 => $dat2) $this->affPreData($dat2, $nom2);
					echo("</div>");
					break;
				case 'object':
					$tests = array('id', 'nom', 'dateCreation');
					$tab = array();
					foreach($tests as $nomtest) {
						$method = 'get'.ucfirst($nomtest);
						if(method_exists($data, $method)) {
							$val = $data->$method();
							// if($val instanceOf \DateTime) $val = $val->format("Y-m-d H:i:s");
							$tab[$nomtest] = $val;
						}
					}
					if($data instanceOf \DateTime) $affdata = $data->format("Y-m-d H:i:s");
						else $affdata = '';
					echo("<div".$style.">");
					echo($affNom." <i".$istyle.">".gettype($data)." > ".get_class($data)."</i> ".$affdata); // [ ".implode(" ; ", $tab)." ]
					foreach($tab as $nom2 => $dat2) $this->affPreData($dat2, $nom2);
					echo("</div>");
					break;
				case 'string':
				case 'integer':
					echo("<div".$style.">");
					echo($affNom." <i".$istyle.">".gettype($data)."</i> \"".$data."\"");
					echo("</div>");
					break;
				case 'boolean':
					echo("<div".$style.">");
					if($data === true) $databis = 'true';
						else $databis = 'false';
					echo($affNom." <i".$istyle.">".gettype($data)."</i> ".$databis);
					echo("</div>");
					break;
				case 'null':
					echo("<div".$style.">");
					echo($affNom." <i".$istyle.">type ".strtolower(gettype($data))."</i> ".gettype($data));
					echo("</div>");
					break;
				default:
					echo("<div".$style.">");
					echo($affNom." <i".$istyle.">".gettype($data)."</i> ");
					echo("</div>");
					break;
			}
		}
		$this->recurs--;
	}


	/**
	 * DEV : affiche $data (uniquement en environnement DEV)
	 * @param mixed $data
	 * @param string $titre = null
	 */
	protected function vardumpDev($data, $titre = null) {
		if($this->DEV === true) {
			echo("<div style='border:1px dotted #666;padding:4px 8px;margin:8px 24px;'>");
			if($titre !== null && is_string($titre) && strlen($titre) > 0) {
				echo('<h3 style="margin-top:0px;padding-top:0px;border-bottom:1px dotted #999;margin-bottom:4px;">'.$titre.'</h3>');
			}
			$this->affPreData($data);
			echo("</div>");
		}
	}

}
