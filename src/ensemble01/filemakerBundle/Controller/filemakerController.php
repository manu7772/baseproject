<?php

namespace ensemble01\filemakerBundle\Controller;

use filemakerBundle\Controller\filemakerController as fmController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use \ZipArchive;
use \DateTime;
use \Exception;

class filemakerController extends fmController {

	const FORCE_SELECT = true;
	const NORESULT = 'No records match the request';

	protected $_fm;				// service filemakerservice
	protected $selectService;	// service aeSelect
	protected $DEV = false;		// mode DEV
	protected $DEVdata = array();
	protected $recurs = 0;
	protected $recursMAX = 6;
	protected $aetools;

	function __destruct() {
		// $this->affAllDev();
	}

	public function indexAction() {
		$data = array();
		$data['h1'] = "Tableau de bord";
		// liste des affaires
		$this->initGlobalData();
		$this->selectService->addGroupe(array($this->_fm->getCurrentSERVER(), 'GEODIAG_SERVEUR', 'Projet_Liste'));
		$this->selectService
			->setRecherche('intitule', '*')
			->addRecherche('intitule', 'PROJET TEMOIN', '!')
			;
		// $this->vardumpDev($data["new_select"], "New select : ".$this->selectService->getGroupeName());
		$data['affaires'] = $this->_fm->getAffaires($this->selectService->getCurrentSelect(self::FORCE_SELECT));

		return $this->render($this->verifVersionPage('BShomepage2'), $data);
	}

	public function diagrammeAction($projet = null, $height = '600px') {
		switch ($projet) {
			case 'OPH93':
				$data['h1'] = 'Diagramme marché OPH93';
				$data['code'] = '#G0B4R_hfVNf1bjbGdrSlJKUGplVWs';
				break;
			default:
				$data['h1'] = 'Diagramme';
				$data['message'] = 'Auncun projet pour cette appellation.';
				break;
		}
		$data['height'] = $height;
		return $this->render($this->verifVersionPage('BSdiagrammes'), $data);
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
		unset($datt);
		if(!isset($data['pagedata'])) $data['pagedata'] = array();

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
		$this->initFmData();
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
						if($request->get($param) == $commande) {
							// echo('&gt; Rechargement : '.$request->get($param).'<br>');
							$result = $this->_fm->reinitService();
						}
						break;
					case 'serverchange':
						// Change server
						$result = $this->_fm->setCurrentSERVER($request->get($param));
						break;
					case 'basechange':
						// Change base
						// echo('&gt; Nouvelle base : '.$request->get($param).'<br>');
						$result = $this->_fm->setCurrentBASE($request->get($param), null);
						break;
					default:
						# code...
						break;
				}
				if($result !== 'no') {
					$data[$param]['commande'] = $request->get($param);
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
		// echo("UrlI : <br>".$this->getRequest()->getRequestUri()."<br><br>");

		// données en fonction de la page
		// $this->vardumpDev($ctrlData, "ctrlData : ");
		switch ($ctrlData['template']) {
			case 'liste-rapports-lots':
				switch (strval($ctrlData['pagedata']["from_url"])) {
					case '0': $ctrlData['h1'] = "Lots en attente"; break;
					case '1': $ctrlData['h1'] = "Lots incomplets"; break;
					case '2': $ctrlData['h1'] = "Lots générés"; break;
					case 'all': $ctrlData['h1'] = "Lots liste complète"; break;
					default: $ctrlData['h1'] = "Lots liste complète";$ctrlData['pagedata']["from_url"] = 'all'; break;
				}
				$this->selectService->addGroupe(array($this->_fm->getCurrentSERVER(), 'GEODIAG_Rapports', 'Rapports_Local_Web_Light'));
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
				if($ctrlData['rapports'] == self::NORESULT) {
					$ctrlData['rapports'] = array();
				}
				break;
			case 'liste-rapports-complete':
				$selecValues = array('0','1');
				switch (strval($ctrlData['pagedata']["from_url"])) {
					case '0': $ctrlData['h1'] = "Rapports en attente"; break;
					case '1': $ctrlData['h1'] = "Rapports générés"; break;
					case 'all': $ctrlData['h1'] = "Rapports liste complète"; break;
					default: $ctrlData['h1'] = "Rapports liste complète";$ctrlData['pagedata']["from_url"] = 'all'; break;
				}
				$this->selectService->addGroupe(array($this->_fm->getCurrentSERVER(), 'GEODIAG_Rapports', 'Rapports_Local_Web_Light'));
				if(in_array(strval($ctrlData['pagedata']["from_url"]), $selecValues)) {
					$this->selectService
						// ->setRecherche('num_lot', $ctrlData['pagedata']['numlot'])
						->setRecherche('a_traiter', $ctrlData['pagedata']["from_url"])
						;
				}
				$ctrlData["new_select"] = $this->selectService->getCurrentSelect(self::FORCE_SELECT);
				$ctrlData['rapports'] = $this->_fm->getRapports($ctrlData["new_select"]);
				// ajout présence fichiers PDF
				if(is_array($ctrlData['rapports'])) foreach($ctrlData['rapports'] as $rapport) {
					if($this->_fm->verifRapportFile($rapport) === true) $ctrlData['pdf_file'][$rapport->getField('id')] = true;
						else $ctrlData['pdf_file'][$rapport->getField('id')] = false;
				} else if($ctrlData['rapports'] == self::NORESULT) {
					$ctrlData['rapports'] = array();
				}
				// echo("<pre>");
				// var_dump($ctrlData['pdf_file']);
				// die("</pre>");
				break;
			case 'detail-rapport-sadmin':
			case 'detail-rapport':
				$ctrlData['h1'] = "Détail rapport";
				// die($ctrlData['pagedata_raw']);
				$ctrlData['listchamps'] = $this->_fm->getFields('Rapports_Local_Web', 'GEODIAG_Rapports'); // GEODIAG_Rapports Rapports_Local_Web
				$ctrlData['rapport'] = $this->_fm->getOneRapport($ctrlData['pagedata_raw']);
				break;
			case 'liste-lieux':
				$corresp = array(
					'AGIRE'		=> 'Proj0000001',
					'SECOMILE'	=> 'Proj0000002',
					'SILOGE'	=> 'Proj0000003',
					);
				// $this->vardumpDev($ctrlData['pagedata']["from_url"], 'Liste lieux :');
				$ctrlData['h1'] = "Lieux";
				if(isset($corresp[$ctrlData['pagedata']["from_url"]])) {
					$ctrlData['h1'] .= ' - '.$ctrlData['pagedata']["from_url"];
					$data['recherche']['search'][0] = array(
							'column'	=> 'Fk_IdProjet',
							'value'		=> $corresp[$ctrlData['pagedata']["from_url"]]
						);
				}
				$data['recherche']['sort'][1] = array(
						'column' 	=> 'cle',
						'way' 		=> 'ASC'
					);
				$ctrlData['lieux'] = $this->_fm->getLieux($data['recherche']);
				if($ctrlData['lieux'] == self::NORESULT) {
					$ctrlData['lieux'] = array();
				}
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
				$this->selectService->setRecherche('intitule', '*');
				if(!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
					$this->selectService->addRecherche('intitule', 'PROJET TEMOIN', '!');
				}
				// $this->selectService->emptyRecherche();
				// $this->selectService->setSort('date_projet', 'DESC');

				$ctrlData["new_select"] = $this->selectService->getCurrentSelect(self::FORCE_SELECT);
				// $this->vardumpDev($ctrlData["new_select"], "New select : ".$this->selectService->getGroupeName());
				$ctrlData['affaires'] = $this->_fm->getAffaires($ctrlData["new_select"]);

				break;
			case 'dev_Param_Societe':
				// paramètres de société
				$ctrlData['h1'] = "Paramètres société";
				$this->selectService->addGroupe(array($this->_fm->getCurrentSERVER(), 'GEODIAG_SERVEUR', 'dev_Param_Societe'));
				// $this->selectService->setRecherche('id_local', 'loc0000011620');
				// $this->selectService->setSort('Fk_Id_Local', 'ASC');
				$ctrlData['params'] = $this->_fm->getLocalPiecesDetail($this->selectService->getCurrentSelect(self::FORCE_SELECT));
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
			case 'liste-media':
				// liste des médias
				$ctrlData['h1'] = "Liste des média";
				$ctrlData['medias'] = $this->_fm->getListMedia();
				break;
			case 'liste-scripts':
				// liste des scripts - regroupés par dossiers
				$newserv = $ctrlData['pagedata']["from_url"]['server'];
				$newbase = $ctrlData['pagedata']["from_url"]['base'];

				$ctrlData['h1'] = "Scripts de ".$newbase;
				$ctrlData['forceload'] = true;
				$ctrlData['scripts'] = array_merge(
					$ctrlData,
					array('liste' => $this->_fm->getScripts($newserv, $newbase, $ctrlData['forceload'], false)),
					array('group' => $this->_fm->getScripts($newserv, $newbase, $ctrlData['forceload'], true))
					);
				break;
			case 'liste-layouts':
				// liste des modèles
				$newserv = $ctrlData['pagedata']["from_url"]['server'];
				$newbase = $ctrlData['pagedata']["from_url"]['base'];
				// 
				$ctrlData['h1'] = "Modèles de ".$newbase;
				$ctrlData['layouts'] = $this->_fm->getLayouts($newserv, $newbase, true);
				break;
			case 'liste-fields':
				// liste des champs
				$ctrlData['server'] = $ctrlData['pagedata']["from_url"]['server'];
				$ctrlData['base'] = $ctrlData['pagedata']["from_url"]['base'];
				$ctrlData['layout'] = $ctrlData['pagedata']["from_url"]['layout'];
				// 
				$ctrlData['h1'] = "Champs de ".$ctrlData['layout'];
				$r = $this->_fm->getDetailFields($ctrlData['layout'], $ctrlData['base'], $ctrlData['server']);
				if(is_array($r)) $ctrlData = array_merge($ctrlData, $r);
					else $ctrlData['fields'] = $r;
				// echo("<pre>");
				// var_dump($ctrlData);
				// echo("</pre>");
				// die("Layout : ".$ctrlData['layout']);
				break;
			case 'liste-databases':
				// liste des bases de données FM
				$ctrlData['h1'] = "Databases FM disponibles";
				$ctrlData['databases'] = $this->_fm->getDatabases();
				break;
			case 'liste-servers':
				// liste des bases de données FM
				$ctrlData['h1'] = "Serveurs FileMaker";
				// $ctrlData['servers'] = $this->_fm->getListOfServersNames();
				$ctrlData['SERVER'] = $this->_fm->getGlobalData();

				$ctrlData['forceload'] = false;
				$ctrlData['scripts'] = array();
				foreach ($ctrlData['SERVER']['servers'] as $nomServer => $server) {
					if($server['statut'] === true) {
						$ctrlData['scripts'][$nomServer] = array();
						foreach ($server['bases'] as $nomBase => $base) {
							$ctrlData['scripts'][$nomServer][$nomBase] = array_merge(
								$ctrlData,
								array('liste' => $this->_fm->getScripts($nomServer, $nomBase, $ctrlData['forceload'], false)),
								array('group' => $this->_fm->getScripts($nomServer, $nomBase, $ctrlData['forceload'], true))
								);
						}
					}
				}
				// echo('<pre>');
				// var_dump($ctrlData['SERVER']);
				// echo('</pre>');
				// die("OK !!! :-)");
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
	 * Renvoie le template en fonction du type de rapport et du code nature
	 * @param string $type
	 * @return string
	 */
	protected function getRapportTemplate($type) {
		$templates = array(
			"RDM-DAPP" 			=> "ensemble01filemakerBundle:pdf:rapport_RDM-DAPP-AP_001.html.twig",
			"RDM-DAPP-SP"		=> "ensemble01filemakerBundle:pdf:rapport_RDM-DAPP-AP_001.html.twig",
			"RDM-DAPP-AP"		=> "ensemble01filemakerBundle:pdf:rapport_RDM-DAPP-AP_001.html.twig",
			"RDM-ListeA-SP"		=> "ensemble01filemakerBundle:pdf:rapport_RDM-ListeA_001.html.twig",
			"RDM-ListeA-AP"		=> "ensemble01filemakerBundle:pdf:rapport_RDM-ListeA_001.html.twig",
			"RDM-OPH93-SP"		=> "ensemble01filemakerBundle:pdf:rapport_RDM-OPH93_001.html.twig",
		);
		if(isset($templates[$type])) return $templates[$type];
			else return false;
	}

	/**
	 * Renvoie un tableau des médias du rapport (base64)
	 * @param objet $rapport - objet rapport
	 * @return array
	 */
	protected function getMediasFromRapport($rapport) {
		return array();
	}

	/**
	 * Génère un rapport d'id $id ou objet
	 * @param mixed $id - id du rapport ou objet rapport
	 * @param string $format - type de retour -> "pdf" ou "html"
	 * @return aeReponse
	 */
	public function prepare_un_rapport($id_rapport = null, $format = "pdf", $aeReponse = null) {
		set_time_limit(600); // délai pour le script : 10 min
		// renvoie un objet aeReponse
		// data[id] => array()
		// 		['pdf'] => objet HTML2PDF
		// 		['html'] => code HTML du rapport à générer
		// 		["rapport"] => array()
		// 			['rapport'] => objet rapport
		// 			['format'] => format (pdf|html)
		// 			['type'] => type de rapport
		// 			['template'] => nom du template

		$this->initFmData();
		$this->_fm->effaceRapportFile($id_rapport);
		// $this->_fm->addRapportGeneration($id);
		if($aeReponse === null) $aeReponse = $this->get('ensemble01services.aeReponse');
		if($aeReponse->isValid()) {
			// transforme l'ID en objet
			if(!is_object($id_rapport)) {				
				$RAPP["rapport"] = $this->_fm->getOneRapport($id_rapport);
				// Rapport non trouvé…
				if(is_string($RAPP["rapport"])) return $aeReponse->addErrorMessage($RAPP["rapport"]);
			} else {
				$RAPP["rapport"] = $id_rapport;
			}
			unset($id_rapport);
			// données globales
			$RAPP["date"] = new DateTime;
			$RAPP["format"] = $format;
			$RAPP["type"] = $RAPP["rapport"]->getField('type_rapport');
			// Template du rapport
			// $RAPP["template"] = "ensemble01filemakerBundle:pdf:rapport_".$RAPP["type"]."_002.html.twig";
			// $RAPP["template"] = $this->_fm->getRapportTwigTemplate($RAPP["rapport"]);
			$RAPP["template"] = $this->getRapportTemplate($RAPP["type"]);
			// rapport de test
			// $RAPP["template"] = "ensemble01filemakerBundle:pdf:testEmply.html.twig";
			if(!$this->get('templating')->exists($RAPP["template"])) {
				$aeReponse->addErrorMessage('Modèle de rapport introuvable : '.$RAPP["template"], true);
			}
			// nom du fichier du rapport
			$RAPP["ref_rapport"] = $this->_fm->getRapportFileName($RAPP["rapport"]);
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
						} catch (Exception $e) {
							$aeReponse->addErrorMessage('Erreur génération html : '.$e->getMessage());
						}
						break;
					case 'tcpdf':
						$RAPP['imgpath'] = __DIR__.'../../../../../web/bundles/ensemble01filemaker/images/';
						$html = null;
						$pdf = $this->get('tcpdf');
						$pdf->SetCreator(PDF_CREATOR);
						$pdf->setPrintHeader(false);
						$pdf->setPrintFooter(false);
						$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
						$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
						// $pdf->SetFont('times', '', 12);
						$pdf->setFont('helvetica', '', 10, '', 'false');
						// $pdf->addFont('helvetica', 'B', 10, '', 'false');
						// $pdf->addFont('helvetica', 'I', 10, '', 'false');
						// $pdf->addFont('helvetica', 'BI', 10, '', 'false');
						// $pdf->addFont('zapfdingbats', '', 12, '', 'false');
						$pdf->addFont('symbol', '', 12, '', 'false');
						$pdf->AddPage();
						$aeReponse->addData(array('pdf' => $pdf, 'html' => $html, "rapport" => $RAPP), $RAPP["rapport"]->getField('id'));
						$txt = "TCPDF Example 002\nDefault page header and footer are disabled using setPrintHeader() and setPrintFooter() methods.";
						$pdf->Write(0, $txt, '', 0, '', true, 0, false, false, 0);
						// $pdf->Output('example_001.pdf', 'I');
						unset($tcpdf);
						unset($RAPP);
						break;
					default: // PDF ou autre
						$RAPP['imgpath'] = __DIR__.'../../../../../web/bundles/ensemble01filemaker/images/';
						$html = null;
						try {
							$html = $this->renderView($RAPP["template"], $RAPP);
						} catch (Exception $e){
							$aeReponse->addErrorMessage('Erreur génération template : '.$e->getMessage());
						}
						if(is_string($html)) try {
							$html2pdf = $this->get('html2pdf_factory')->create();
							$html2pdf->pdf->SetDisplayMode('fullpage');
							$html2pdf->pdf->setFont('helvetica', '', 10, '', 'false');
							$html2pdf->pdf->addFont('helvetica', 'B', 10, '', 'false');
							$html2pdf->pdf->addFont('helvetica', 'I', 10, '', 'false');
							$html2pdf->pdf->addFont('helvetica', 'BI', 10, '', 'false');
							$html2pdf->pdf->addFont('zapfdingbats', '', 12, '', 'false');
							$html2pdf->pdf->addFont('symbol', '', 12, '', 'false');
							// $html2pdf->pdf->addFont('ZapfDingbats', '', 12, '', 'false');
							// $fonts = array('Arial Black.ttf');
							// foreach ($fonts as $font) {
							// 	$html2pdf->pdf->addTTFfont(__DIR__.'../../../../../web/bundles/ensemble01filemaker/images/'.$font, 'TrueTypeUnicode', '', 32);
							// }
							$html2pdf->setTestIsImage(false);
							$html2pdf->setTestTdInOnePage(true);
							// $html2pdf->pdf->SetProtection(array('modify'), $this->container->getParameter('pdf_protect_passwrd'));
							$html2pdf->pdf->SetAuthor('Société GÉODEM - Agence Normandie');
							$html2pdf->pdf->SetTitle('Rapport réf.'.$RAPP['rapport']->getField('type_rapport').' '.$RAPP['rapport']->getField('id').' du '.$RAPP["date"]->format($this->container->getParameter('formatDateTwig')));
							$html2pdf->getHtmlFromPage($html);
							$html2pdf->writeHTML($html, false);
							$html2pdf->createIndex("Sommaire", 25, 10, false, true, 2, 'helvetica');
							$aeReponse->addData(array('pdf' => $html2pdf, 'html' => $html, "rapport" => $RAPP), $RAPP["rapport"]->getField('id'));
						} catch (HTML2PDF_exception $e){
							$aeReponse->addErrorMessage('Erreur génération PDF : '.$e->getMessage());
						}
						unset($html2pdf);
						unset($RAPP);
						break;
				}
			}
		} else {
			$aeReponse->addErrorMessage("l'objet aeReponse est invalide (Erreurs présentes).");
		}
		return $aeReponse;
	}

	/**
	 * Génération d'un rapport depuis requête FM server
	 * @param string $rapport_id - id du rapport
	 * @return string
	 */
	public function generate_rapport_fmAction($rapport_id) {
		$format = 'pdf';
		// $rapport = $this->_fm->getOneRapport($id);
		$this->initFmData();
		$this->_fm->addRapportGeneration($rapport_id); // x 1 !
		$aeReponse = $this->prepare_un_rapport($rapport_id);
		if($aeReponse->isValid()) {
			$datassup = $aeReponse->getDataAndSupp();
			// echo('Nombre de retours de rapports ('.key($datassup).') : '.count($datassup)."<br>");
			$oneRapport = current($datassup);
			// echo('<pre>');
			// var_dump($oneRapport);
			// die('</pre>');
			$path = $this->_fm->verifAndGoDossier($oneRapport["rapport"]["type"]);
			// echo("Path : ".$path."<br>");
			try {
				$oneRapport['pdf']->Output($path.$oneRapport["rapport"]["ref_rapport"].'.'.$format, "F");
				$aeReponse->addMessage("Le rapport ".$oneRapport["rapport"]["type"]." réf.".$oneRapport["rapport"]["ref_rapport"]." a été généré.");
			} catch (HTML2PDF_exception $e) {
				$aeReponse->addErrorMessage('Erreur génération PDF : '.$e->getMessage());
			}
			$repss = implode('<br>', $aeReponse->getAllMessages(true));
			if($aeReponse->isValid()) {
				// génération ok
				$add = "Génération OK";
				$this->_fm->suppRapportGeneration($rapport_id);
				$messtest = $this->_fm->Cloture_UN_Rapport_Apres_Serveur($rapport_id); // ????? demander à SEB si c'est toujours utile
				// $aeReponse->addErrorMessage($messtest);
				// $repss = implode('<br>', $aeReponse->getAllMessages(true));
			} else {
				// génération echec
				$add = "Echec génération/output PDF";
				$this->_fm->Cloture_UN_Rapport_Apres_Serveur($rapport_id, $repss);
			}
		} else {
			$repss = implode('<br>', $aeReponse->getAllMessages(true));
			// $this->_fm->Cloture_UN_Rapport_Apres_Serveur($rapport_id, $repss);
		}
		$this->_fm->suppRapportGeneration($rapport_id);
		return new Response($repss);
	}

	// public function retablish_rapport_fmAction($rapport_id) {
	// 	return new Response('Rapport rétabli : '.$rapport_id);
	// }

	/**
	 * Génération d'un rapport sur un appel AJAX
	 * @param string $id - id du rapport
	 * @return aeReponse
	 */
	public function generate_ajax_pdf_rapportAction($id) {
		$format = 'pdf';
		$aeReponse = $this->get('ensemble01services.aeReponse');
		$this->initFmData();
		$this->_fm->addRapportGeneration($id); // x 1 !
		$rapport = $this->_fm->getOneRapport($id);
		if(is_object($rapport)) {
			$this->_fm->addRapportGeneration($id); // x 2 !!
			$aeReponse = $this->prepare_un_rapport($rapport, 'pdf', $aeReponse); // HTML2PDF
			// $aeReponse = $this->prepare_un_rapport($rapport, 'tcpdf', $aeReponse); // TCPDF
			$this->_fm->addRapportGeneration($id); // x 3 !!!
			if($aeReponse->isValid()) {
				$datassup = $aeReponse->getDataAndSupp();
				$oneRapport = current($datassup);
				$path = $this->_fm->verifAndGoDossier($oneRapport["rapport"]["type"]);
				try {
					$oneRapport['pdf']->Output($path.$oneRapport["rapport"]["ref_rapport"].'.'.$format, "F");
					$aeReponse->addMessage("Rapport généré :");
					$aeReponse->addMessage("Le rapport ".$oneRapport["rapport"]["type"]." réf.".$oneRapport["rapport"]["ref_rapport"]);
				} catch (HTML2PDF_exception $e) {
					$this->_fm->effaceRapportFile($rapport);
					$aeReponse->addErrorMessage('Erreur génération PDF : '.$e->getMessage());
				}
				$repss = implode('<br>', $aeReponse->getAllMessages(true));
				// if($aeReponse->isUnvalid()) {
				// 	$aeReponse->setUnvalid("Echec génération/output PDF");
				// }
			}
		} else {
			$aeReponse->setUnvalid($rapport);
		}
		$this->_fm->suppRapportGeneration($id);
		return new JsonResponse($aeReponse->getJSONreponse());
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
		// Si erreur
		if(is_string($rapports)) {
			$aeReponse->addErrorMessage($rapports);
		// sinon
		} else foreach($rapports as $rapport) {
			$aeReponse = $this->prepare_un_rapport($rapport, $format, $aeReponse);
		}
		if($aeReponse->isValid()) {
			foreach ($aeReponse->getDataAndSupp() as $key => $oneRapport) {
				switch ($mode) {
					case 'screen':
						if($format === "html") {
							return new Response($oneRapport['html']);
							// http://localhost:8888/GitHub/baseproject/web/app_dev.php/rapportfm/0000012583/screen.html
						} else if ($format === "pdf") {
							try {
								$oneRapport['pdf']->Output($oneRapport['rapport']["ref_rapport"].'.'.$format);
								$aeReponse->addMessage("Le rapport ".$oneRapport['rapport']["type"]." réf.".$oneRapport['rapport']["ref_rapport"]." a été généré.");
							} catch (HTML2PDF_exception $e) {
								$aeReponse->addErrorMessage('Erreur génération PDF : '.$e->getMessage());
							}
						}
						break 2; // --> break 2 parce qu'il ne peut y avoir qu'un rapport
					case 'load':
						if ($format === "pdf") {
							$path = $this->_fm->verifAndGoDossier("TEMP");
							try {
								$tempfile = $path.$oneRapport['rapport']["ref_rapport"].'.'.$format;
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
						break 2; // --> break 2 parce qu'il ne peut y avoir qu'un rapport
					default: // file (sur disque dur)
						$path = $this->_fm->getRapportFilePath($oneRapport['rapport']["rapport"]);
						try {
							$oneRapport['pdf']->Output($path['pathfile'], "F");
							$aeReponse->addMessage("Le rapport ".$oneRapport['rapport']["type"]." réf.".$oneRapport['rapport']["ref_rapport"]." a été généré.");
						} catch (HTML2PDF_exception $e) {
							$aeReponse->addErrorMessage('Erreur génération PDF : '.$e->getMessage());
						}
						if($aeReponse->isValid()) {
							$idr = $oneRapport['rapport']["rapport"]->getField('id');
							// $r = $this->_fm->Cloture_UN_Rapport_Apres_Serveur($idr);
							// if(is_string($r)) {
							// 	$aeReponse->addErrorMessage('Le rapport '.$path.$oneRapport['rapport']["ref_rapport"].'('.$idr.') n\'a pu être traité en BDD FM');
							// }
						}
						$aeReponse->putAllMessagesInFlashbag();
						break;
				}
			}
		} else {
			$aeReponse->addErrorMessage('Opération de génération de rapports échouée');
		}
		$aeReponse->putErrorMessagesInFlashbag();
		// echo('<pre>');
		// var_dump($aeReponse->getAllMessages());
		// die('</pre>');
		// page à afficher
		if(!isset($ctrlData['redirect'])) $ctrlData['redirect'] = 'liste-rapports-complete';
		// return $this->pagewebAction($ctrlData['redirect'], $ctrlData['pagedata_raw']);
		return $this->redirect($this->generateUrl("ensemble01filemaker_pageweb", array("page" => $ctrlData['redirect'], "pagedata" => '1')));
	}

	// http://localhost:8888/GitHub/baseproject/web/app_dev.php/fm/rapportfm-by-lot/000000147205-02-2015-17-42

	/**
	 * Télécharge le PDF d'un rapport
	 * @param string $id - id du rapport
	 * @return Response
	 */
	public function file_pdf_rapportAction($id) {
		$this->initFmData();
		$file = $this->_fm->getRapportFilePath($id);
		if(!file_exists($file['pathfile'])) return new Response('Fichier PDF absent.');
		if($file !== false) {
			$response = new Response(file_get_contents($file['pathfile']));
			$response->headers->set('Content-Type', 'application/pdf'); // modification du content-type pour forcer le téléchargement (sinon le navigateur internet essaie d'afficher le document)
			$response->headers->set('Content-Length', filesize($file['pathfile']));
			$response->headers->set('Content-Disposition', 'attachment;filename='.$file['file']);
			return $response;
		}
		return new Response('Rapport manquant.');
	}

	/**
	 * Visualise le PDF d'un rapport
	 * @param string $id - id du rapport
	 * @return Response
	 */
	public function screen_pdf_rapportAction($id) {
		$this->initFmData();
		$file = $this->_fm->getRapportFilePath($id);
		if(!file_exists($file['pathfile'])) return new Response('Fichier PDF absent.');
		if($file !== false) {
			$response = new Response(file_get_contents($file['pathfile']));
			$response->headers->set('Content-Type', 'application/pdf'); // modification du content-type pour forcer le téléchargement (sinon le navigateur internet essaie d'afficher le document)
			$response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
			$response->headers->set('Pragma', 'public');
			$response->headers->set('Content-Length', filesize($file['pathfile']));
			$response->headers->set('Content-Disposition', 'inline;filename='.$file['file']);
			return $response;
		}
		return new Response('Rapport manquant.');
	}

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
		$test = $this->_fm->Recherche_Rapport_Serveur($numlot);
		if(is_array($test)) foreach ($test as $rapport) {
			if(is_object($rapport)) {
				$resltRapports[$rapport->getField('id')] = $rapport->getField('id');
			} else {
				$aeReponse->addErrorMessage($rapport);
			}
		} else {
			return new Response('Erreur : '.$test);
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
				$aeReponse = $this->prepare_un_rapport($oneRapport, $format, $aeReponse);
				$one2Rapport = $aeReponse->getDataAndSupp($id_rapport);
				// $one2Rapport = $one2Rapport["rapport"];
				if(is_array($one2Rapport)) {
					$type = $one2Rapport["rapport"]["type"];
					$path = $this->_fm->verifAndGoDossier($type);
					// $templt = "ensemble01filemakerBundle:pdf:rapport_".$type."_002.html.twig";
					// $templt = $this->_fm->getRapportTwigTemplate($RAPP["rapport"]);
					$templt = $this->getRapportTemplate($type);
					// 
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
		// return new Response('Test');
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
		$data["rapports"] = $this->initFmData()->Recherche_Rapport_Serveur($numlot);
		if(is_array($data["rapports"])) {
			foreach($data["rapports"] as $rapport) {
				// fichier PDF
				if($this->_fm->verifRapportFile($rapport) === true) {
					$data['pdf'][$rapport->getField('id')] = $this->_fm->getRapportFileName($rapport);
				} else {
					$data['pdf'][$rapport->getField('id')] = false;
				}
			}
		} else {
			//
		}
		$data["numlot"] = $numlot;
		return $this->render($this->verifVersionPage("live-rapports-by-lots", "public-views"), $data);
	}

	/**
	 * Zippe et télécharge les rapports d'un lot, présents sur le disque
	 * 
	 * @param string $numlot - référence du lot
	 * @return Response
	 */
	public function ZIP_listeRapportsLotsAction($numlot = null) {
		// dossier 
		$aetools = $this->get('ensemble01services.aetools');
		$FMparams = $this->container->getParameter('fmparameters');
		$rootpath = $FMparams['dossiers']['pathrapports'];
		$aetools->setWebPath();
		$aetools->verifDossierAndCreate($rootpath);
		$nomDossierZip = $FMparams['dossiers']['zipfiles'];
		$aetools->setWebPath($rootpath);
		$aetools->verifDossierAndCreate($nomDossierZip);
		// $aetools->setWebPath($rootpath.$nomDossierZip.'/');

		$data = array();
		$data['error'] = null;
		$data['numlot'] = $numlot;
		$data['fichierZip'] = 'rapports_lot_'.$numlot.'.zip';
		$rapports = $this->initFmData()->Recherche_Rapport_Serveur($numlot);
		$data["nombre"] = count($rapports);
		$data['pdf_ok'] = array();
		$data['pdf_no'] = array();
		if(is_array($rapports)) {
			foreach($rapports as $rapport) {
				// fichier PDF
				$filePath = $this->_fm->getRapportFilePath($rapport);
				if(is_array($filePath)) {
					$data['pdf_ok'][$rapport->getField('id')] = $filePath;
				} else {
					$data['pdf_no'][$rapport->getField('id')] = false;
				}
			}
			$data["nombrePDF"] = count($data['pdf_ok']);
		} else {
			// aucun rapport trouvé
			$data["nombrePDF"] = 0;
		}
		// ZIP
		if($data["nombrePDF"] > 0) {
			$zip = new ZipArchive();
			$aetools->setWebPath($rootpath.$nomDossierZip);
			// On crée l’archive.
			$pathfileZip = $aetools->getCurrentPath().$data['fichierZip'];
			// die(filesize($pathfileZip));
			if($zip->open($pathfileZip, ZipArchive::CREATE) == TRUE) {
				$aetools->setWebPath();
				foreach ($data['pdf_ok'] as $id => $fichier) {
					# $fichier['pathfile']
					$zip->addFile($fichier['pathfile'], '/'.$fichier['file']);
				}
				$zip->close();
				$response = new Response();
				$response->setContent(file_get_contents($pathfileZip));
				// $response->headers->set('Content-Type', 'application/octet-stream'); // modification du content-type pour forcer le téléchargement (sinon le navigateur internet essaie d'afficher le document)
				// $response->headers->set('Content-Type', 'application/force-download'); // modification du content-type pour forcer le téléchargement (sinon le navigateur internet essaie d'afficher le document)
				$response->headers->set('Content-Type', 'application/zip'); // modification du content-type pour forcer le téléchargement (sinon le navigateur internet essaie d'afficher le document)
				$response->headers->set('Content-Length', filesize($pathfileZip));
				$response->headers->set('Content-Disposition', 'attachment;filename='.$data['fichierZip']);
				// return new JsonResponse(json_encode($data, true));
				// return new Response(
				// 	filesize($pathfileZip)."Ko<br>Fichier : ".$data['fichierZip']
				// 	);
				// --> http://localhost:8888/GitHub/baseproject/web/app_dev.php/zip-rapports-lot/… (num du lot)
				return $response;
			} else $data['error'] = 'Création archive zip impossible.';
		} else $data['error'] = 'Il n\'y a aucun rapport à compresser.';
		return new Response($data['error']);
	}

	/**
	 * CHECKE les rapports d'un lot (LIVE Ajax)
	 * 
	 * @param string $numlot - référence du lot
	 * @return Response
	 */
	public function check_listeRapportsLotsAction($numlot = null) {
		$add = '';
		if($numlot !== null) {
			$data = array();
			$data["rapports"] = $this->initFmData()->Recherche_Rapport_Serveur($numlot);
			$generations = $this->_fm->getGenerations();
			if(is_array($data["rapports"])) {
				foreach($data["rapports"] as $rapport) {
					$id = $rapport->getField('id');
					// $data['pdf'][$id]['fichier'] = $this->_fm->getRapportFileName($rapport);
					if($this->_fm->verifRapportFile($rapport) === true) {
						// fichier PDF présent
						$data['pdf'][$id]['statut'] = 1;
					} else {
						// fichier PDF absent
						$data['pdf'][$id]['statut'] = 2;
					}
					// en cours de génération…
					if(isset($generations[$id])) {
						$data['pdf'][$id]['statut'] = 3;
						// $data['pdf'][$id]['data'] = $generations[$id];
					}
				}
			} else {
				// $html = '<p>'.$data["rapports"].'</p>';
			}
			$data["numlot"] = $numlot;
			$html = $this->renderView($this->verifVersionPage("liste-rapports-in-bloc", "public-views"), $data);
		} else {
			$html = '<p>Numéro de lot absent… impossible de recevoir les données sur les rapports.</p>';
		}
		return new Response($html);
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

		$action = $this->_fm->Recherche_Rapport_Serveur($numlot);
		// $action = 'test';
		// Si erreur
		if(is_string($action)) {
			$aeReponse->addErrorMessage($action);
		} else {
			foreach ($action as $key => $rapport) {
				if($this->_fm->effaceRapportFile($rapport) === true) $addRep = ' Fichier effacé.';
					else $addRep = ' Fichier introuvable.';
				$rep = $this->_fm->Retablir_UN_Rapport_Apres_Serveur($rapport->getField('id'));
				if(is_string($rep)) $addRep1 = 'non rétabli';
					else $addRep1 = 'rétabli';
				$aeReponse->addMessage("Rapport ".$addRep1." : ".$rapport->getField('id')." (version ".$rapport->getField('version').")".$addRep);
				unset($rep);
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


	/**
	 *
	 */
	public function test_refreshAction() {
		return $this->render('ensemble01filemakerBundle:test:testrefresh.html.twig');
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
		$CS = $this->_fm->getCurrentSERVER();
		$CB = $this->_fm->getCurrentBASE();
		// echo('1 - Current : '.$CS." / ".$CB."<br>");
		// sélection
		$this->selectService = $this->get('ensemble01services.selection');
		$this->selectService->setSelectName('BSsideBar');
		$this->selectService->addGroupe(array($this->_fm->getCurrentSERVER(), 'GEODIAG_SERVEUR', 'Projet_Liste'));
		// $this->selectService->emptyRecherche();
		$this->selectService->setRecherche('intitule', '*');
		if(!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) {
			$this->selectService->addRecherche('intitule', 'PROJET TEMOIN', '!');
		}
		// data
		$data['title'] = $title;
		$data['icon'] = $icon;
		$data['affaires'] = $this->_fm->getAffaires($this->selectService->getCurrentSelect(self::FORCE_SELECT));

		// $CS2 = $this->_fm->getCurrentSERVER();
		// $CB2 = $this->_fm->getCurrentBASE();
		// echo('2 - Current : '.$CS2." / ".$CB2."<br>");

		$this->_fm->setCurrentBASE($CB, $CS);

		// $CS3 = $this->_fm->getCurrentSERVER();
		// $CB3 = $this->_fm->getCurrentBASE();
		// echo('3 - Current : '.$CS3." / ".$CB3."<br>");
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
		// $blocdata['module']['title'] = $template;
		if(isset($blocdata['title'])) $blocdata['module']['title'] = $blocdata['title'];
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
			case 'timeline':
				$blocdata['module']['title'] = 'Time line';
				$blocdata['module']['icon'] = 'fa-clock-o';
				break;
			case 'areachart':
				$blocdata['module']['title'] = 'Graphique surface';
				$blocdata['module']['icon'] = 'fa-bar-chart-o';
				break;
			case 'donutchart':
				if(!isset($blocdata['module']['title'])) $blocdata['module']['title'] = 'Graphique circulaire';
				$blocdata['module']['icon'] = 'fa-bar-chart-o';
				$blocdata['module']['datadonut'] = array(
					array('label' => 'Logements non effectués', 'value' => rand(20,100)),
					array('label' => 'Logements en cours', 'value' => rand(20,100)),
					array('label' => 'Logements terminés', 'value' => rand(20,100)),
					);
				break;
			case 'comment':
				$blocdata['module']['title'] = 'Commentaires';
				$blocdata['module']['icon'] = 'fa-comments';
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
							// if($val instanceOf DateTime) $val = $val->format("Y-m-d H:i:s");
							$tab[$nomtest] = $val;
						}
					}
					if($data instanceOf DateTime) $affdata = $data->format("Y-m-d H:i:s");
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
		if($this->get('security.context')->isGranted('ROLE_SUPER_ADMIN') && (in_array($env, array('dev', 'test'))) && ($this->DEV === true)) {
			if(count($this->DEVdata) > 0) {
				echo("<h2>Données DEV : </h2>");
				foreach ($this->DEVdata as $key => $value) {
					echo($value);
				}
				echo("<br><br><br><br>");
			}
		}
	}

	public function datatables_statesaveAction() {
		$error = array(
			"result"	=> false,
			"message"	=> "Utilisateur non trouvé",
			"data"		=> "Utilisateur non trouvé",
		);
		$user = $this->get('security.context')->getToken()->getUser();
		if(is_object($user)) {
			$userManager = $this->get('fos_user.user_manager');
			$post = $this->getRequest()->request->all();
			$r = $user->addDtselection_withID($post['UrlI'], $post['DtId'], json_encode($post['data'], true));
			if($r !== false) {
				$userManager->updateUser($user);
				$data = array(
					"result"	=> true,
					"message"	=> "Enregistrement réussi",
					"data"		=> json_encode($post['data'], true),
				);
			} else $data = $error;
		} else $data = $error;
		return new JsonResponse(json_encode($data, true));
	}





	/****************************************/
	/*** TESTS IMAGES
	/****************************************/

	public function mediaAction($id, $nom, $ext) {
		$dossier = 'images/tmp/';
		$nomfichier = $dossier.$nom.'.'.$ext;
		$nomfichierBMP = $dossier.$nom.'.bmp';

		$this->initFmData();
		$data = $this->_fm->getMedia($id);

		if(!is_string($data)) {
			if(count($data) > 0) {
				reset($data);
				$data = current($data)->getField('conteneur_miniature_base64');
				$data = base64_decode($data);
				$formatBMP = $this->isBMPformat($data);
				if($formatBMP === true) {
					// format BMP
					$file = fopen($nomfichierBMP, 'w+');
					fwrite($file, $data);
					fclose($file);
					unset($file);
					$im = $this->imagecreatefrombmp($nomfichierBMP);
				} else {
					// autres formats
					$im = imagecreatefromstring($data);
				}
				if ($im !== false) {
					if(file_exists($nomfichierBMP)) unlink($nomfichierBMP);
					$response = new Response();
					header('Content-Type: image/png');
					// header('Content-Disposition: attachment;filename='.$nom.'.png');
					imagepng($im);
					imagedestroy($im);
				}
			}
		}
		return new Response("aucune image…");
	}

	protected function isBMPformat($data) {
		$header = unpack("vtype/Vsize/v2reserved/Voffset", substr($data, 0, 14));
		extract($header);
		// if($type != 0x4D42) echo("<h4>Format non BMP</h4>"); else echo("<h4>Format BMP !!!</h4>");
		return ($type != 0x4D42) ? false : true ;
	}

	/**
	 * Credit goes to mgutt 
	 * http://www.programmierer-forum.de/function-imagecreatefrombmp-welche-variante-laeuft-t143137.htm
	 * Modified by Fabien Menager to support RGB555 BMP format
	 */
	protected function imagecreatefrombmp($filename) {
		// version 1.1
		if (!($fh = fopen($filename, 'rb'))) {
			trigger_error('imagecreatefrombmp: Can not open ' . $filename, E_USER_WARNING);
			return false;
		}
		
		// read file header
		$meta = unpack('vtype/Vfilesize/Vreserved/Voffset', fread($fh, 14));
		
		// check for bitmap
		if ($meta['type'] != 19778) {
			trigger_error('imagecreatefrombmp: ' . $filename . ' is not a bitmap!', E_USER_WARNING);
			return false;
		}
		
		// read image header
		$meta += unpack('Vheadersize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vcolors/Vimportant', fread($fh, 40));
		$bytes_read = 40;
		
		// read additional bitfield header
		if ($meta['compression'] == 3) {
			$meta += unpack('VrMask/VgMask/VbMask', fread($fh, 12));
			$bytes_read += 12;
		}
		
		// set bytes and padding
		$meta['bytes'] = $meta['bits'] / 8;
		$meta['decal'] = 4 - (4 * (($meta['width'] * $meta['bytes'] / 4)- floor($meta['width'] * $meta['bytes'] / 4)));
		if ($meta['decal'] == 4) {
			$meta['decal'] = 0;
		}
		
		// obtain imagesize
		if ($meta['imagesize'] < 1) {
			$meta['imagesize'] = $meta['filesize'] - $meta['offset'];
			// in rare cases filesize is equal to offset so we need to read physical size
			if ($meta['imagesize'] < 1) {
				$meta['imagesize'] = @filesize($filename) - $meta['offset'];
				if ($meta['imagesize'] < 1) {
					trigger_error('imagecreatefrombmp: Can not obtain filesize of ' . $filename . '!', E_USER_WARNING);
					return false;
				}
			}
		}
		
		// calculate colors
		$meta['colors'] = !$meta['colors'] ? pow(2, $meta['bits']) : $meta['colors'];
		
		// read color palette
		$palette = array();
		if ($meta['bits'] < 16) {
			$palette = unpack('l' . $meta['colors'], fread($fh, $meta['colors'] * 4));
			// in rare cases the color value is signed
			if ($palette[1] < 0) {
				foreach ($palette as $i => $color) {
					$palette[$i] = $color + 16777216;
				}
			}
		}
		
		// ignore extra bitmap headers
		if ($meta['headersize'] > $bytes_read) {
			fread($fh, $meta['headersize'] - $bytes_read);
		}
		
		// create gd image
		$im = imagecreatetruecolor($meta['width'], $meta['height']);
		$data = fread($fh, $meta['imagesize']);
		
		// uncompress data
		switch ($meta['compression']) {
			case 1: $data = rle8_decode($data, $meta['width']); break;
			case 2: $data = rle4_decode($data, $meta['width']); break;
		}

		$p = 0;
		$vide = chr(0);
		$y = $meta['height'] - 1;
		$error = 'imagecreatefrombmp: ' . $filename . ' has not enough data!';

		// loop through the image data beginning with the lower left corner
		while ($y >= 0) {
			$x = 0;
			while ($x < $meta['width']) {
				switch ($meta['bits']) {
					case 32:
					case 24:
						if (!($part = substr($data, $p, 3 /*$meta['bytes']*/))) {
							trigger_error($error, E_USER_WARNING);
							return $im;
						}
						$color = unpack('V', $part . $vide);
						break;
					case 16:
						if (!($part = substr($data, $p, 2 /*$meta['bytes']*/))) {
							trigger_error($error, E_USER_WARNING);
							return $im;
						}
						$color = unpack('v', $part);

						if (empty($meta['rMask']) || $meta['rMask'] != 0xf800) {
							$color[1] = (($color[1] & 0x7c00) >> 7) * 65536 + (($color[1] & 0x03e0) >> 2) * 256 + (($color[1] & 0x001f) << 3); // 555
						}
						else { 
							$color[1] = (($color[1] & 0xf800) >> 8) * 65536 + (($color[1] & 0x07e0) >> 3) * 256 + (($color[1] & 0x001f) << 3); // 565
						}
						break;
					case 8:
						$color = unpack('n', $vide . substr($data, $p, 1));
						$color[1] = $palette[ $color[1] + 1 ];
						break;
					case 4:
						$color = unpack('n', $vide . substr($data, floor($p), 1));
						$color[1] = ($p * 2) % 2 == 0 ? $color[1] >> 4 : $color[1] & 0x0F;
						$color[1] = $palette[ $color[1] + 1 ];
						break;
					case 1:
						$color = unpack('n', $vide . substr($data, floor($p), 1));
						switch (($p * 8) % 8) {
							case 0: $color[1] =  $color[1] >> 7; break;
							case 1: $color[1] = ($color[1] & 0x40) >> 6; break;
							case 2: $color[1] = ($color[1] & 0x20) >> 5; break;
							case 3: $color[1] = ($color[1] & 0x10) >> 4; break;
							case 4: $color[1] = ($color[1] & 0x8 ) >> 3; break;
							case 5: $color[1] = ($color[1] & 0x4 ) >> 2; break;
							case 6: $color[1] = ($color[1] & 0x2 ) >> 1; break;
							case 7: $color[1] = ($color[1] & 0x1 );      break;
						}
						$color[1] = $palette[ $color[1] + 1 ];
						break;
					default:
						trigger_error('imagecreatefrombmp: ' . $filename . ' has ' . $meta['bits'] . ' bits and this is not supported!', E_USER_WARNING);
						return false;
				}
				imagesetpixel($im, $x, $y, $color[1]);
				$x++;
				$p += $meta['bytes'];
			}
			$y--;
			$p += $meta['decal'];
		}
		fclose($fh);
		return $im;
	}

	protected function convertBMPtoGD($file) {
		$image = "";
		// $code = base64_decode($code64);
		// $header = unpack("vtype/Vsize/v2reserved/Voffset", substr($code, 0, 14));
		$header = unpack("vtype/Vsize/v2reserved/Voffset", fread($file, 14));
		// $info = unpack("Vsize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vncolor/Vimportant", substr($code, 15, 40));
		$info = unpack("Vsize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vncolor/Vimportant", fread($file, 40));
		extract($info);
		extract($header);
		$palette_size = $offset - 54;
		$ncolor = $palette_size / 4;
		$gd_header = "";

		$gd_header .= ($palette_size == 0) ? "\xFF\xFE" : "\xFF\xFF";
		$gd_header .= pack("n2", $width, $height);
		$gd_header .= ($palette_size == 0) ? "\x01" : "\x00";
		if($palette_size) {
			$gd_header .= pack("n", $ncolor);
		}
		// no transparency
		$gd_header .= "\xFF\xFF\xFF\xFF";

		if($palette_size) {
			$palette = fread($file, $palette_size);
			$gd_palette = "";
			$j = 0;
			while($j < $palette_size) {
				$b = $palette{$j++};
				$g = $palette{$j++};
				$r = $palette{$j++};
				$a = $palette{$j++};
				$gd_palette .= "$r$g$b$a";
			}
			$gd_palette .= str_repeat("\x00\x00\x00\x00", 256 - $ncolor);
			$image .= $gd_palette;
		}

		$scan_line_size = (($bits * $width) + 7) >> 3;
		$scan_line_align = ($scan_line_size & 0x03) ? 4 - ($scan_line_size &0x03) : 0;

		for($i = 0, $l = $height - 1; $i < $height; $i++, $l--) {
			// BMP stores scan lines starting from bottom
			fseek($file, $offset + (($scan_line_size + $scan_line_align) * $l));
			$scan_line = fread($file, $scan_line_size);
			if($bits == 32) {
				$gd_scan_line = "";
			} else if($bits == 24) {
				$gd_scan_line = "";
				$j = 0;
				while($j < $scan_line_size) {
					$b = $scan_line{$j++};
					$g = $scan_line{$j++};
					$r = $scan_line{$j++};
					$gd_scan_line .= "\x00$r$g$b";
				}
			} else if($bits == 16) {
				// À REVOIR…
				$gd_scan_line = "";
				$j = 0;
				while($j < $scan_line_size) {
					$b = ($scan_line{$j} & 0x001f) << 3;
					$g = ($scan_line{$j} & 0x07e0) >> 3;
					$r = ($scan_line{$j} & 0xf800) >> 8;
					$gd_scan_line .= $r * 65536 + $g * 256 + $b;
				}
			} else if($bits == 8) {
				$gd_scan_line = $scan_line;
			} else if($bits == 4) {
				$gd_scan_line = "";
				$j = 0;
				while($j < $scan_line_size) {
					$byte = ord($scan_line{$j++});
					$p1 = chr($byte >> 4);
					$p2 = chr($byte & 0x0F);
					$gd_scan_line .= "$p1$p2";
				}
				$gd_scan_line = substr($gd_scan_line, 0, $width);
			} else if($bits == 1) {
				$gd_scan_line = "";
				$j = 0;
				while($j < $scan_line_size) {
					$byte = ord($scan_line{$j++});
					$p1 = chr((int) (($byte & 0x80) != 0));
					$p2 = chr((int) (($byte & 0x40) != 0));
					$p3 = chr((int) (($byte & 0x20) != 0));
					$p4 = chr((int) (($byte & 0x10) != 0));
					$p5 = chr((int) (($byte & 0x08) != 0));
					$p6 = chr((int) (($byte & 0x04) != 0));
					$p7 = chr((int) (($byte & 0x02) != 0));
					$p8 = chr((int) (($byte & 0x01) != 0));
					$gd_scan_line .= "$p1$p2$p3$p4$p5$p6$p7$p8";
				}
				$gd_scan_line = substr($gd_scan_line, 0, $width);
			}
			
			// $image .= $gd_scan_line;
		}

		// écrit l'image
		// $fileDest = fopen("images_result.jpg", 'w+');
		// fwrite($fileDest, $image);
		// fclose($fileDest);

		echo('<pre>');
		if($type != 0x4D42) echo("<h4>Format non BMP</h4>"); else echo("<h4>Format BMP !!!</h4>");
		echo("<h4>Palette size : ".$palette_size."</h4>");
		echo("<h4>Ncolor : ".$ncolor."</h4>");
		echo("<h4>Bits : ".$bits."</h4>");
		echo("<h4>GDheader : ".$gd_header."</h4>");
		echo('<h3>INFO</h3>');
		var_dump($info);
		echo('<h3>HEADER</h3>');
		var_dump($header);
		die('</pre>');
	}

}
