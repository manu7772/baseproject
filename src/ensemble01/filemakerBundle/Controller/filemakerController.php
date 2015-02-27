<?php

namespace ensemble01\filemakerBundle\Controller;

use filemakerBundle\Controller\filemakerController as fmController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class filemakerController extends fmController {

	const FORCE_SELECT = true;

	protected $_fm;				// service filemakerservice
	protected $selectService;	// service aeSelect
	protected $DEV = true;		// mode DEV
	protected $DEVdata = array();
	protected $recurs = 0;
	protected $recursMAX = 6;
	protected $aetools;

	function __destruct() {
		// parent::__destruct();
		$this->affAllDev();
	}

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
			'pagedata'		=> array("from_url" => $this->unCompileData($pagedata)),
			'pagedata_raw'	=> $pagedata, // données $pagedata brutes
		));

		// données en fonction de la page
		$this->vardumpDev($ctrlData, "ctrlData : ");
		switch ($ctrlData['template']) {
			case 'liste-rapports-lots':
				switch (strval($ctrlData['pagedata']["from_url"])) {
					case '0': $ctrlData['h1'] = "Lots en attente"; break;
					case '1': $ctrlData['h1'] = "Lots incomplets"; break;
					case '2': $ctrlData['h1'] = "Lots générés"; break;
					case 'all': $ctrlData['h1'] = "Lots liste complète"; break;
					default: $ctrlData['h1'] = "Lots liste complète";$ctrlData['pagedata']["from_url"] = 'all'; break;
				}
				$this->selectService->addGroupe(array($this->_fm->getCurrentSERVER(), 'GEODIAG_Rapports', 'Rapports_Local_Web'));
				// $this->selectService->setRecherche('intitule', '*');
				// if($ctrlData['pagedata']["from_url"] !== 'all') {
				// 	$this->selectService
				// 		// ->setRecherche('num_lot', $ctrlData['pagedata']['numlot'])
				// 		->setRecherche('a_traiter', $ctrlData['pagedata']["from_url"])
				// 		;
				// }
				// $this->selectService->emptyRecherche();
				// $this->selectService->setSort('date_projet', 'DESC');

				$ctrlData["new_select"] = $this->selectService->getCurrentSelect(self::FORCE_SELECT);
				// $this->vardumpDev($ctrlData["new_select"], "New select : ".$this->selectService->getGroupeName());
				$ctrlData['rapports'] = $this->_fm->getListeRapportsByLot($ctrlData["new_select"], $ctrlData['pagedata']['from_url']);
				break;
			case 'liste-rapports-complete':
				$selecValues = array('0','1');
				switch (strval($ctrlData['pagedata']["from_url"])) {
					case '0': $ctrlData['h1'] = "Rapports en attente"; break;
					case '1': $ctrlData['h1'] = "Rapports générés"; break;
					case 'all': $ctrlData['h1'] = "Rapports liste complète"; break;
					default: $ctrlData['h1'] = "Rapports liste complète";$ctrlData['pagedata']["from_url"] = 'all'; break;
				}
				$this->selectService->addGroupe(array($this->_fm->getCurrentSERVER(), 'GEODIAG_Rapports', 'Rapports_Local_Web'));
				if(in_array(strval($ctrlData['pagedata']["from_url"]), $selecValues)) {
					$this->selectService
						// ->setRecherche('num_lot', $ctrlData['pagedata']['numlot'])
						->setRecherche('a_traiter', $ctrlData['pagedata']["from_url"])
						;
				}
				$ctrlData["new_select"] = $this->selectService->getCurrentSelect(self::FORCE_SELECT);
				$ctrlData['rapports'] = $this->_fm->getRapports($ctrlData["new_select"]);
				// ajout présence fichiers PDF
				foreach($ctrlData['rapports'] as $rapport) {
					if($this->_fm->verifRapportFile($rapport) === true) $ctrlData['pdf_file'][$rapport->getField('id')] = true;
						else $ctrlData['pdf_file'][$rapport->getField('id')] = false;
				}
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
			case 'liste-tiers-all':
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

		$data['locauxByLieux'] = $this->_fm->getRapports('0');
		$data['LieuxInRapport'] = $this->_fm->getRapportsLieux();

		$data['result'] = array();
		foreach ($data['locauxByLieux'] as $key => $rapport) {
			$id = $rapport->getField('id');
			// $data['result'][$id] = $this->generate_rapports($rapport);
			if(rand(0,1000) > 800) $data['result'][$id] = false;
				else $data['result'][$id] = true;
		}

		return $this->render($this->verifVersionPage($data['page']), $data);
	}


	/**
	 * Génère un rapport d'id $id ou objet
	 * @param mixed $id - id du rapport ou objet rapport
	 * @param string $format - type de retour -> "pdf" ou "html"
	 * @return aeReponse
	 */
	public function generate_un_rapport($id_rapport = null, $format = "pdf", $aeReponse = null) {
		// renvoie un objet aeReponse
		// data[id] => array()
		// 		['pdf'] => objet HTML2PDF
		// 		['html'] => code HTML du rapport à générer
		// 		["rapport"] => array()
		// 			['rapport'] => objet rapport
		// 			['format'] => format (pdf|html)
		// 			['type'] => type de rapport
		// 			['template'] => nom du template

		if($aeReponse === null) $aeReponse = $this->get('ensemble01services.aeReponse');
		if($aeReponse->isValid()) {
			// transforme l'ID en objet
			if(!is_object($id_rapport)) {
				$this->initFmData();
				$id_rapport = $this->_fm->getOneRapport(strval($id_rapport));
				// Rapport non trouvé…
				if(is_string($id_rapport)) return $aeReponse->addErrorMessage($id_rapport);
			}
			// données globales
			$RAPP["date"] = new \DateTime;
			$RAPP["rapport"] = $id_rapport;
			$RAPP["format"] = $format;
			$RAPP["type"] = $id_rapport->getField('type_rapport');
			$RAPP["template"] = "ensemble01filemakerBundle:pdf:rapport_".$RAPP["type"]."_001.html.twig";
			if(!$this->get('templating')->exists($RAPP["template"])) {
				$aeReponse->addErrorMessage('Modèle de rapport introuvable : '.$RAPP["template"], true);
			}
			// nom du fichier du rapport
			$RAPP["ref_rapport"] = $this->_fm->getRapportFileName($RAPP["rapport"]);
			// documents annexes
			$RAPP["documents"] = $this->getCertificats();
			// données images
			$RAPP['image_logo_geodem'] = "logos/logoGeodem.png";
			// données spécifiques au format
			if($aeReponse->isValid()) {
				switch(strtolower($format)) {
					case 'html':
						$RAPP['imgpath'] = 'bundles/ensemble01filemaker/images/';
						// template trouvé…
						try {
							$html = $this->render($RAPP["template"], $RAPP);
							$aeReponse->addMessage('Le rapport '.$RAPP["format"].' '.$RAPP["rapport"]->getField('id').' a été généré.');
							$aeReponse->addData(array('html' => $html, "rapport" => $RAPP), $RAPP["rapport"]->getField('id'));
						} catch (\Exception $e) {
							$aeReponse->addErrorMessage('Erreur génération html : '.$e->getMessage());
						}
						break;
					default: // PDF ou autre
						$RAPP['imgpath'] = __DIR__.'../../../../../web/bundles/ensemble01filemaker/images/';
						try {
							$html = $this->renderView($RAPP["template"], $RAPP);
						} catch (\Exception $e){
							$aeReponse->addErrorMessage('Erreur génération template : '.$e->getMessage());
						}
						try {
							$html2pdf = $this->get('html2pdf_factory')->create();
							$html2pdf->pdf->setFont('helvetica', '', 10, '', 'false');
							$html2pdf->pdf->addFont('helvetica', 'B', 10, '', 'false');
							$html2pdf->pdf->addFont('helvetica', 'I', 10, '', 'false');
							$html2pdf->pdf->addFont('helvetica', 'BI', 10, '', 'false');
							// $html2pdf->pdf->addFont('ZapfDingbats', '', 12, '', 'false');
							// $fonts = array('Arial Black.ttf');
							// foreach ($fonts as $font) {
							// 	$html2pdf->pdf->addTTFfont(__DIR__.'../../../../../web/bundles/ensemble01filemaker/images/'.$font, 'TrueTypeUnicode', '', 32);
							// }
							$html2pdf->setTestIsImage(false);
							// $html2pdf->pdf->SetProtection(array('modify'), $this->container->getParameter('pdf_protect_passwrd'));
							$html2pdf->pdf->SetAuthor('Société GÉODEM - Agence Normandie');
							$html2pdf->pdf->SetTitle('Rapport réf.'.$RAPP['rapport']->getField('type_rapport').' '.$RAPP['rapport']->getField('id').' du '.$RAPP["date"]->format($this->container->getParameter('formatDateTwig')));
							$html2pdf->writeHTML($html, false);
							$html2pdf->createIndex("Sommaire", 25, 12, false, true, 2);
							$aeReponse->addData(array('html' => $html, "rapport" => $RAPP), $RAPP["rapport"]->getField('id'));
							$aeReponse->addData(array('pdf' => $html2pdf, "rapport" => $RAPP), $RAPP["rapport"]->getField('id'));
						} catch (HTML2PDF_exception $e){
							$aeReponse->addErrorMessage('Erreur génération PDF : '.$e->getMessage());
						}
						unset($html2pdf);
						break;
				}
			}
		} else {
			$aeReponse->addErrorMessage("l'objet aeReponse est invalide (Erreurs présentes).");
		}
		return $aeReponse;
	}


	/**
	 * Génère un rapport d'id $id - ou tous les rapports à générer si pas d'id précisé
	 * @param string $id - champ "id" de fm
	 * @param string $type - type de rapport -> "RDM-DAPP", etc.
	 * @param string $mode - type d'enregistrement -> "file" ou "load" ou "screen"
	 * @param string $format - type de document -> "pdf" ou "html"
	 */
	public function generate_rapportAction($id = null, $mode = "file", $format = "pdf", $pagedata = null) {
		$ctrlData = $this->initGlobalData(array(
			// 'page'			=> $page,
			'pagedata'		=> array("from_url" => $this->unCompileData($pagedata)),
			'pagedata_raw'	=> $pagedata, // données $pagedata brutes
		));
		$aeReponse = $this->get('ensemble01services.aeReponse');

		if($id === null) {
			// récupération de la liste des rapports à générer en base FM
			$mode = 'file';
			$format = 'pdf';
			$rapports = $this->_fm->getRapports("0");
		} else {
			$rapports = array();
			$rapports[] = $this->_fm->getOneRapport($id);
		}
		// var_dump($rapports);
		// Si erreur
		if(is_string($rapports)) {
			$aeReponse->addErrorMessage($rapports);
		// sinon
		} else foreach($rapports as $rapport) {
			// $this->vardumpDev($aeReponse->getData(), "aeReponse avant génération :");
			$aeReponse = $this->generate_un_rapport($rapport, $format, $aeReponse);
		}
		// $this->vardumpDev($aeReponse->getData(), "Data après génération");
		// $this->vardumpDev($aeReponse->getDataKeys(), "Keys après génération");
		if($aeReponse->isValid()) {
			foreach ($aeReponse->getDataAndSupp() as $key => $oneRapport) {
				switch ($mode) {
					case 'screen':
						if($format === "html") {
							return $oneRapport['html'];
						} else if ($format === "pdf") {
							try {
								$oneRapport['pdf']->Output($oneRapport['rapport']["ref_rapport"].'.'.$format);
								$aeReponse->addMessage("Le rapport ".$oneRapport['rapport']["type"]." réf.".$oneRapport['rapport']["ref_rapport"]." a été généré.");
							} catch (HTML2PDF_exception $e) {
								$aeReponse->addErrorMessage('Erreur génération PDF : '.$e->getMessage());
							}
						}
						break 2;
					case 'load':
						if ($format === "pdf") {
							$path = $this->_fm->verifAndGoDossier("TEMP");
							try {
								$tempfile = $oneRapport['rapport']["ref_rapport"].'.'.$format;
								// $oneRapport['pdf']->Output($path.$tempfile, "F");
								// return new Response(file_get_contents($path.$tempfile), 200, array(
								// 	'Content-Type' => 'application/force-download',
								// 	'Content-Disposition' => 'attachment; filename='.$tempfile
								// 	));
								$oneRapport['pdf']->Output($tempfile, "D");
							} catch (HTML2PDF_exception $e) {
								$aeReponse->addErrorMessage('Erreur génération PDF : '.$e->getMessage());
							}
							// $aeReponse->addMessage('Téléchargement…');
						} else {
							$aeReponse->addErrorMessage('Le format demandé n\'est pas du PDF : '.$format);
						}
						$aeReponse->putErrorMessagesInFlashbag();
						break 2;
					default: // file (sur disque dur)
						$path = $this->_fm->verifAndGoDossier($oneRapport['rapport']["type"]);
						try {
							$oneRapport['pdf']->Output($path.$oneRapport['rapport']["ref_rapport"].'.'.$format, "F");
							$aeReponse->addMessage("Le rapport ".$oneRapport['rapport']["type"]." réf.".$oneRapport['rapport']["ref_rapport"]." a été généré.");
						} catch (HTML2PDF_exception $e) {
							$aeReponse->addErrorMessage('Erreur génération PDF : '.$e->getMessage());
						}
						if($aeReponse->isValid()) {
							$idr = $oneRapport['rapport']["rapport"]->getField('id');
							$r = $this->_fm->Cloture_UN_Rapport_Apres_Serveur($idr);
							if(is_string($r)) {
								$aeReponse->addErrorMessage('Le rapport '.$path.$oneRapport['rapport']["ref_rapport"].'('.$idr.') n\'a pu être traité en BDD FM');
							}
						}
						$aeReponse->putAllMessagesInFlashbag();
						break;
				}
			}
		} else {
			$aeReponse->addErrorMessage('Opération de génération de rapports échouée');
			$aeReponse->putErrorMessagesInFlashbag();
		}
		// page à afficher
		if(!isset($ctrlData['redirect'])) $ctrlData['redirect'] = 'liste-rapports-complete';
		return $this->pagewebAction($ctrlData['redirect'], $ctrlData['pagedata_raw']);
		// return $this->redirect($this->generateUrl("ensemble01filemaker_pageweb", array("page"=>'liste-rapports-complete', "pagedata" => '0')));
	}

	// http://localhost:8888/GitHub/baseproject/web/app_dev.php/fm/rapportfm-by-lot/000000147205-02-2015-17-42

	/**
	 * Génère les rapports selon un numéro de lot (ensemble de rapports)
	 * @param string $numlot - numéro du lot
	 * @param string $pagedata - Données diverses
	 */
	public function generate_by_lot_rapportAction($numlot, $pagedata = null) {
		set_time_limit(3600); // délai pour le script : 1h !!!
		$ctrlData = $this->initGlobalData(array(
			// 'page'			=> $page,
			'pagedata'		=> array("from_url" => $this->unCompileData($pagedata)),
			'pagedata_raw'	=> $pagedata, // données $pagedata brutes
		));
		$aeReponse = $this->get('ensemble01services.aeReponse');

		// rapports passés en "généré"
		$resltRapports = array();
		$test = $this->_fm->Cloture_LOT_Rapport_Apres_Serveur($numlot);
		foreach ($test as $rapport) {
			if(is_object($rapport)) {
				$resltRapports[$rapport->getField('id')] = $rapport;
			} else {
				$aeReponse->addErrorMessage($rapport);
			}
		}
		// // liste des rapports à vérifier
		// $verifRapports = array();
		// $test = $this->_fm->getRapportsByLot($numlot, true);
		// foreach ($test as $rapport) {
		// 	if(is_object($rapport)) {
		// 		$verifRapports[$rapport->getField('id')] = $rapport;
		// 	}
		// }
		// unset($test);
		// // comparaison
		// if(count($resltRapports) === count($verifRapports))
		
		if($aeReponse->isValid()) {
			foreach ($resltRapports as $id_rapport => $oneRapport) {
				$format = 'pdf';
				$aeReponse = $this->generate_un_rapport($oneRapport, $format, $aeReponse);
				$one2Rapport = $aeReponse->getDataAndSupp($id_rapport);
				// $one2Rapport = $one2Rapport["rapport"];
				if(is_array($one2Rapport)) {
					$type = $one2Rapport["rapport"]["type"];
					$path = $this->_fm->verifAndGoDossier($type);
					$templt = "ensemble01filemakerBundle:pdf:rapport_".$type."_001.html.twig";
					$nomRapport = $one2Rapport["rapport"]["ref_rapport"].'.'.$format;
					// nom du fichier du rapport
					$one2Rapport["rapport"]["ref_rapport"] = $this->_fm->getRapportFileName($one2Rapport["rapport"]["rapport"]);
					$one2Rapport["rapport"]['imgpath'] = __DIR__.'../../../../../web/bundles/ensemble01filemaker/images/';
					// données images
					$one2Rapport["rapport"]['image_logo_geodem'] = "logos/logoGeodem.png";

					// if($this->get('templating')->exists($templt)) {
					// 	$html = $this->renderView($templt, $one2Rapport["rapport"]);
					try {
						$one2Rapport['pdf']->Output($path.$nomRapport, "F");
						$aeReponse->addMessage("Le rapport ".$one2Rapport["rapport"]["type"]." réf.".$one2Rapport["rapport"]["ref_rapport"]." a été généré.");
					} catch (HTML2PDF_exception $e) {
						$aeReponse->addErrorMessage('Erreur génération PDF rapport '.$id_rapport.' : '.$e->getMessage());
					}
					// } else {
					// 	$aeReponse->addErrorMessage('Modèle de rapport introuvable : '.$one2Rapport["rapport"]["template"], true);
					// }
				} else {
					echo('Rapport non récupéré :( !!!<br>');
				}
				$one2Rapport = array();
				unset($one2Rapport);
			}
		} else {
			// Au moins une erreur sur le passage en mode "généré" : on rétablit tout
			$action = $this->_fm->Retablir_LOT_Rapport_Apres_Serveur($numlot);
			if(is_string($action)) $aeReponse->addErrorMessage('Rétablissement rapports : '.$action);
		}
		$aeReponse->putAllMessagesInFlashbag();
		// page à afficher
		if(!isset($ctrlData['redirect'])) $ctrlData['redirect'] = 'liste-rapports-lots';
		return $this->pagewebAction($ctrlData['redirect'], 'all');
		// return $this->redirect($this->generateUrl("ensemble01filemaker_pageweb", array("page"=>'liste-rapports-complete', "pagedata" => '0')));
	}
	// http://localhost:8888/GitHub/baseproject/web/app_dev.php/fm/s-admin/retablir-rapportfm-by-lot/Proj0000004000000151417-02-2015-17-09-31/%7B%22redirect%22:%22liste-rapports-complete%22%7D
	// %7B%22redirect%22:%22liste-rapports-complete%22%7D

	/**
	 * Génère les rapports selon un numéro de lot (ensemble de rapports)
	 * --> VIA COMMANDE FILEMAKER
	 * @param string $numlot - numéro du lot
	 * @return Response
	 */
	public function generate_by_lot_rapport_fmAction($numlot) {
		$this->generate_by_lot_rapportAction($numlot);
		return $this->public_listeRapportsLotsAction($numlot);
	}

	/**
	 * Affiche la liste des rapports d'un lot, présents sur le disque
	 * 
	 * @param string $numlot - référence du lot
	 * @return Response
	 */
	public function public_listeRapportsLotsAction($numlot = null) {
		$data = array();
		$numlot === null ? $all = true : $all = false;
		$data["rapports"] = $this->initFmData()->getRapportsByLot($numlot, $all);
		$data["numlot"] = $numlot;
		return $this->render($this->verifVersionPage("liste-rapports-by-lots", "public-views"), $data);
	}

	public function retablir_un_rapportAction($id, $pagedata = null) {
		$ctrlData = $this->initGlobalData(array(
			// 'page'			=> $page,
			'pagedata'		=> array("from_url" => $this->unCompileData($pagedata)),
			'pagedata_raw'	=> $pagedata, // données $pagedata brutes
		));
		$aeReponse = $this->get('ensemble01services.aeReponse');

		$action = $this->_fm->Retablir_UN_Rapport_Apres_Serveur($id);
		// $action = 'test';
		// Si erreur
		if(is_string($action)) {
			$aeReponse->addErrorMessage($action);
		} else {
			foreach ($action as $key => $rapport) {
				if($this->_fm->effaceRapportFile($rapport) === true) $addRep = ' Fichier effacé.';
					else $addRep = ' Fichier introuvable.';
				$aeReponse->addMessage("Rapport rétabli : ".$rapport->getField('id')." (version ".$rapport->getField('version').")".$addRep);
			}
		}

		$aeReponse->putAllMessagesInFlashbag();
		// page à afficher
		if(!isset($ctrlData['redirect'])) $ctrlData['redirect'] = 'liste-rapports-lots';
		return $this->pagewebAction($ctrlData['redirect'], 'all');
	}

	/**
	 * Rétablit les rapports selon un numéro de lot (ensemble de rapports)
	 * @param string $numlot - numéro du lot
	 * @param string $pagedata - Données diverses
	 */
	public function retablir_by_lot_rapportAction($numlot, $pagedata = null) {
		$ctrlData = $this->initGlobalData(array(
			// 'page'			=> $page,
			'pagedata'		=> array("from_url" => $this->unCompileData($pagedata)),
			'pagedata_raw'	=> $pagedata, // données $pagedata brutes
		));
		$aeReponse = $this->get('ensemble01services.aeReponse');

		$action = $this->_fm->Retablir_LOT_Rapport_Apres_Serveur($numlot);
		// $action = 'test';
		// Si erreur
		if(is_string($action)) {
			$aeReponse->addErrorMessage($action);
		} else {
			foreach ($action as $key => $rapport) {
				if($this->_fm->effaceRapportFile($rapport) === true) $addRep = ' Fichier effacé.';
					else $addRep = ' Fichier introuvable.';
				$aeReponse->addMessage("Rapport rétabli : ".$rapport->getField('id')." (version ".$rapport->getField('version').")".$addRep);
			}
		}
		$aeReponse->putAllMessagesInFlashbag();
		// page à afficher
		if(!isset($ctrlData['redirect'])) $ctrlData['redirect'] = 'liste-rapports-lots';
		return $this->pagewebAction($ctrlData['redirect'], 'all');
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
		$this->initFmData();
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

	// protected function verifAndGoDossier($type) {
	// 	$rootpath = $this->container->getParameter('pathrapports');
	// 	$path = $rootpath.$type.'/';
	// 	$this->aetools = $this->get('ensemble01services.aetools');
	// 	// vérifie la présence du dossier pathrapports et pointe dessus
	// 	$this->aetools->verifDossierAndCreate($rootpath);
	// 	$this->aetools->setWebPath($rootpath);
	// 	$this->aetools->verifDossierAndCreate($type);
	// 	return $path;
	// }

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
		if(is_string($pagedata)) {
			$pd = @json_decode($pagedata, true);
			if($pd !== null) $pagedata = $pd;
		}
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
		$texte = "";
		$this->recurs++;
		if($this->recurs <= $this->recursMAX) {
			$style = " style='margin:4px 0px 8px 20px;padding-left:4px;border-left:1px solid #666;'";
			$istyle = " style='color:#999;font-style:italic;'";
			if(is_string($nom)) {
				$affNom = "[\"".$nom."\"] ";
			} else if(is_int($nom)) {
				$affNom = "[".$nom."] ";
			} else {
				$affNom = "[type ".gettype($data)."] ";
				$nom = null;
			}
			switch (strtolower(gettype($data))) {
				case 'array':
					$texte .= ("<div".$style.">");
					$texte .= ($affNom."<i".$istyle.">".gettype($data)."</i> (".count($data).")");
					foreach($data as $nom2 => $dat2) $texte .= $this->affPreData($dat2, $nom2);
					$texte .= ("</div>");
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
					$texte .= ("<div".$style.">");
					$texte .= ($affNom." <i".$istyle.">".gettype($data)." > ".get_class($data)."</i> ".$affdata); // [ ".implode(" ; ", $tab)." ]
					foreach($tab as $nom2 => $dat2) $this->affPreData($dat2, $nom2);
					$texte .= ("</div>");
					break;
				case 'string':
				case 'integer':
					$texte .= ("<div".$style.">");
					$texte .= ($affNom." <i".$istyle.">".gettype($data)."</i> \"".$data."\"");
					$texte .= ("</div>");
					break;
				case 'boolean':
					$texte .= ("<div".$style.">");
					if($data === true) $databis = 'true';
						else $databis = 'false';
					$texte .= ($affNom." <i".$istyle.">".gettype($data)."</i> ".$databis);
					$texte .= ("</div>");
					break;
				case 'null':
					$texte .= ("<div".$style.">");
					$texte .= ($affNom." <i".$istyle.">type ".strtolower(gettype($data))."</i> ".gettype($data));
					$texte .= ("</div>");
					break;
				default:
					$texte .= ("<div".$style.">");
					$texte .= ($affNom." <i".$istyle.">".gettype($data)."</i> ");
					$texte .= ("</div>");
					break;
			}
		}
		$this->recurs--;
		return $texte;
	}


	/**
	 * DEV : affiche $data (uniquement en environnement DEV)
	 * @param mixed $data
	 * @param string $titre = null
	 */
	protected function vardumpDev($data, $titre = null) {
		$texte = "";
		if($this->DEV === true) {
			$texte .= ("<div style='border:1px dotted #666;padding:4px 8px;margin:8px 24px;'>");
			if($titre !== null && is_string($titre) && strlen($titre) > 0) {
				$texte .= ('<h3 style="margin-top:0px;padding-top:0px;border-bottom:1px dotted #999;margin-bottom:4px;">'.$titre.'</h3>');
			}
			$texte .= $this->affPreData($data);
			$texte .= ("</div>");
		}
		$this->DEVdata[] = $texte;
	}

	protected function affAllDev() {
		$env = $this->get('kernel')->getEnvironment();
		if($this->get('security.context')->isGranted('ROLE_SUPER_ADMIN') && ($env == 'dev')) {
			if(count($this->DEVdata) > 0) {
				echo("<h2>Données DEV : </h2>");
				foreach ($this->DEVdata as $key => $value) {
					echo($value);
				}
				echo("<br><br><br><br>");
			}
		}
	}

	protected function getCertificats() {
		return array(
			'cartificats' => array(
				//
				),
			);
	}


}
