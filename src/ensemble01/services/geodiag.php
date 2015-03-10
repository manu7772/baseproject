<?php
// ensemble01/services/geodiag.php

namespace ensemble01\services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use filemakerBundle\services\filemakerservice as fms;

class geodiag extends fms {

	const STATUT_COMPLET = 'complet';
	const STATUT_TRAITER = 'à traiter';
	const STATUT_PARTIEL = 'partiel';
	const STATUT_INCONNU = 'inconnu';

	protected $aetools;
	protected $rootpath;

	public function __construct(ContainerInterface $container) {
		parent::__construct($container);
		$this->aetools = $this->container->get('ensemble01services.aetools');
		$this->verifAndGoDossier();
	}

	// *************************
	// METHODES GÉODEM
	// *************************
	
	/**
	 * Renvoie le nom de fichier d'un rapport
	 * @param mixed $rapport - id ou objet rapport
	 * @return array
	 */
	public function getRapportFileName($rapport) {
		if(!is_object($rapport)) $rapport = $this->getOneRapport(strval($rapport));
		// echo("Rapport ".$rapport->getField('id')." = ".get_class($rapport)."<br>");
		return $rapport->getField('Fk_Id_Lieu')."-".$rapport->getField('id')."-".$rapport->getField('num_porte')."-".$rapport->getField('local_adresse')."-".$rapport->getField('local_ville')."-".$rapport->getField('local_cp')."-".$rapport->getField('type_rapport')."-v".$rapport->getField('version');
	}

	/**
	 * Initialise le gestionnaire de fichiers
	 * Si type est précisé, crée le dossier (si inexistant) et le met en chemin courant
	 * Renvoie le chemin courant
	 * @param string $type - type de rapport
	 * @return string - chemin courant
	 */
	public function verifAndGoDossier($type = null) {
		$this->rootpath = $this->container->getParameter('pathrapports');
		// vérifie la présence du dossier pathrapports et pointe dessus
		$this->aetools->setWebPath();
		$this->aetools->verifDossierAndCreate($this->rootpath);
		$this->aetools->setWebPath($this->rootpath);
		if(is_string($type)) {
			$path = $this->rootpath.$type.'/';
			$this->aetools->verifDossierAndCreate($type);
			$this->aetools->setWebPath($path);
			// echo('Current path : '.$this->aetools->getCurrentPath().'<br>');
			return $path;
		}
		return $this->rootpath;
	}

	/**
	 * Vérifie si le fichier pdf d'un rapport existe
	 * @param mixed $rapport - id ou objet rapport
	 * @param string $ext - (optionnel - 'pdf' par défaut) extension du nom du fichier
	 * @return boolean
	 */
	public function verifRapportFile($rapport, $ext = 'pdf') {
		if(!is_object($rapport)) $rapport = $this->getOneRapport(strval($rapport));
		// dossiers etc.
		if(is_object($rapport)) {
			$dossier = $rapport->getField('type_rapport');
			$this->verifAndGoDossier($dossier);
			if(@file_exists($this->aetools->getCurrentPath().$this->getRapportFileName($rapport).'.'.$ext)) {
				$r = true;
			} else {
				$r = false;
			}
		} else $r = false;
		return $r;
	}

	/**
	 * Renvoie le nom de fichier d'un rapport
	 * @param mixed $rapport - id ou objet rapport
	 * @param string $ext - (optionnel - 'pdf' par défaut) extension du nom du fichier
	 * @return boolean
	 */
	public function effaceRapportFile($rapport, $ext = 'pdf') {
		if(!is_object($rapport)) $rapport = $this->getOneRapport(strval($rapport));
		// dossiers etc.
		if(is_object($rapport)) {
			$dossier = $rapport->getField('type_rapport');
			$this->verifAndGoDossier($dossier);
			$r = $this->aetools->deleteFile($this->aetools->getCurrentPath().$this->getRapportFileName($rapport).'.'.$ext);
			if(is_string($r)) {
				// echo('fichier effacé : '.$r.'('.$this->getRapportFileName($rapport).')<br>');
				$r = true;
			} else {
				// echo('Fichier à effacer non trouvé : '.$this->getRapportFileName($rapport));
			}
		} else $r = false;
		return $r;
	}

	/**
	 * Renvoie la liste des lieux
	 * @return array
	 */
	public function getLieux($data = null) {
		if($data === null || !is_array($data)) $data = array();
		// force les données relatives au modèle
		$data['server'] 		= $this->getCurrentSERVER();
		$data['base'] 			= 'GEODIAG_SERVEUR';
		$data['modele'] 		= 'Lieu';
		return $this->getData($data);
	}

	/**
	 * Renvoie la liste des lieux
	 * @return array
	 */
	public function getLocaux($data = null) {
		if($data === null || !is_array($data)) $data = array();
		// force les données relatives au modèle
		$data['server'] 		= $this->getCurrentSERVER();
		$data['base'] 			= 'GEODIAG_SERVEUR';
		$data['modele'] 		= 'Local';
		return $this->getData($data);
	}

	/**
	 * Renvoie la liste des affaires
	 * @param array $data - données de recherche et autres données
	 * @return array
	 */
	public function getAffaires__SAVE($data = null) {
		if($data === null || !is_array($data)) $data = array();
		// force les données relatives au modèle
		$data['server'] 		= $this->getCurrentSERVER();
		$data['base'] 			= 'GEODIAG_SERVEUR';
		$data['modele'] 		= 'Projet_Liste';
		// autres données, par défaut :
		if(!isset($data['column'])) {
			$data['search'][0]['column'] 	= 'intitule';
			$data['search'][0]['value']		= 'Marché Evreux';
		}
		$data['sort'][1]['column'] 			= 'date_projet';
		$data['sort'][1]['way'] 			= 'DESC';
		return $this->getData($data);
	}

	/**
	 * Renvoie la liste des affaires
	 * @param array $data - données de recherche et autres données
	 * @return array
	 */
	public function getAffaires($data = null) {
		if($data === null || !is_array($data)) $data = array();
		// force les données relatives au modèle
		$data2 = $data['recherche'];
		$data2['server'] 		= $this->getCurrentSERVER();
		$data2['base'] 			= $data['groupes'][1];
		$data2['modele'] 		= $data['groupes'][2];
		// autres données, par défaut :
		// if(!isset($data['column'])) {
		// 	$data['search'][0]['column'] 	= 'intitule';
		// 	$data['search'][0]['value']		= 'Marché Evreux';
		// }
		// $data['sort'][1]['column'] 		= 'date_projet';
		// $data['sort'][1]['way'] 			= 'DESC';
		return $this->getData($data2);
	}

	/**
	 * Renvoie la liste des tiers
	 * @return array
	 */
	public function getTiers($data = null) {
		if($data === null || !is_array($data)) $data = array();
		// force les données relatives au modèle
		$data['server'] 		= $this->getCurrentSERVER();
		$data['base'] 			= 'GEODIAG_SERVEUR';
		$data['modele'] 		= 'Tiers_01';
		return $this->getData($data);
	}

	/**
	 * Renvoie la liste des affaires
	 * @param array $data - données de recherche et autres données
	 * @return array
	 */
	public function getRapports($data) {
		if(is_array($data)) {
			$data2 = $data['recherche'];
			$data2['server'] 		= $data['groupes'][0];
			$data2['base'] 			= $data['groupes'][1];
			$data2['modele'] 		= $data['groupes'][2];
		} else {
			$data2['server'] 					= $this->getCurrentSERVER();
			$data2['base'] 						= 'GEODIAG_Rapports';
			$data2['modele'] 					= 'Rapports_Local_Web';
			$data2['search'][1]['column'] 		= 'a_traiter';
			$data2['search'][1]['value']		= $data;
		}
		return $this->getData($data2);
	}

	/**
	 * Renvoie la liste des différents lots et la quatité de rapports qu'il contient
	 * array [nom du lot] = nombre de rapports
	 * @param boolean $all - false par défaut : uniquement les 'a_traiter' à 0 / true = tous
	 * @return array
	 */
	public function getListeRapportsByLot($data, $selectStatut = null) {
		// force les données relatives au modèle
		$data2 = $data['recherche'];
		$data2['server'] 		= $data['groupes'][0];
		$data2['base'] 			= $data['groupes'][1];
		$data2['modele'] 		= $data['groupes'][2];
		$result = $this->getData($data2);
		// // $data['server'] 					= $this->getCurrentSERVER();
		// $data['server'] 					= "Géodem mac-mini";
		// // $data['base'] 					= 'GEODIAG_SERVEUR';
		// $data['base'] 						= 'GEODIAG_Rapports';
		// $data['modele'] 					= 'Rapports_Local_Web';
		// $data['search'][0]['column'] 		= 'num_lot';
		// $data['search'][0]['value']			= $numlot;
		// if($all !== true) {
		// 	$data['search'][1]['column'] 	= 'a_traiter';
		// 	$data['search'][1]['value']		= 0;
		// }
		// $data['sort'][1]['column'] 			= 'id';
		// $data['sort'][1]['way'] 			= 'ASC';
		// opération de tri
		$mem = array();
		$r2 = array();
		if(is_array($result)) {
			foreach($result as $rapport) {
				// tri par lots
				$numlot = $rapport->getField('num_lot');
				if(in_array($numlot, $mem)) {
					// lot déjà trouvé
					if($rapport->getField('a_traiter') == 0) {
						$r2[$numlot]['nb0']++;
					} else {
						$r2[$numlot]['nb1']++;
					}
				} else {
					// nouveau lot
					$mem[] = $numlot;
					if($rapport->getField('a_traiter') == 0) {
						$r2[$numlot]['nb0'] = 1;
						$r2[$numlot]['nb1'] = 0;
					} else {
						$r2[$numlot]['nb0'] = 0;
						$r2[$numlot]['nb1'] = 1;
					}
				}
			}
			unset($result);
			// définit le statut du lot
			foreach ($r2 as $numlot => $rapport) {
				$r2[$numlot]['statut'] = self::STATUT_INCONNU;
				if($rapport['nb0'] < 1) $r2[$numlot]['statut'] = self::STATUT_COMPLET;
				if($rapport['nb1'] < 1) $r2[$numlot]['statut'] = self::STATUT_TRAITER;
				if($rapport['nb0'] > 0 && $rapport['nb1'] > 0) $r2[$numlot]['statut'] = self::STATUT_PARTIEL;
			}
			$r3 = array();
			$vals = array("0", "1", "2");
			// insère uniquement les lots dont le statut correspond à ce qui est demandé
			if(in_array(strval($selectStatut), $vals)) {
				$st = strval($selectStatut);
				foreach ($r2 as $numlot => $rapport) {
					switch ($rapport['statut']) {
						case self::STATUT_TRAITER: if($st === "0") $r3[$numlot] = $rapport;break;
						case self::STATUT_PARTIEL: if($st === "1") $r3[$numlot] = $rapport;break;
						case self::STATUT_COMPLET: if($st === "2") $r3[$numlot] = $rapport;break;
					}
				}
			} else $r3 = $r2;
			if(count($r3) < 1) $r3 = 'No records match the request';
		} else $r3 = $result;
		unset($r2);
		unset($mem);
		return $r3;
	}

	/**
	 * Renvoie la liste des rapports d'un lot
	 * @param string $numlot - référence du lot
	 * @param boolean $all - false par défaut : uniquement les 'a_traiter' à 0 / true = tous
	 * @return array
	 */
	public function getRapportsByLot($numlot, $all = false) {
		// force les données relatives au modèle
		// $data['server'] 					= $this->getCurrentSERVER();
		$data['server'] 					= "Géodem mac-mini";
		// $data['base'] 					= 'GEODIAG_SERVEUR';
		$data['base'] 						= 'GEODIAG_Rapports';
		$data['modele'] 					= 'Rapports_Local_Web';
		$data['search'][0]['column'] 		= 'num_lot';
		$data['search'][0]['value']			= "==".$numlot;
		if($all !== true) {
			$data['search'][1]['column'] 	= 'a_traiter';
			$data['search'][1]['value']		= 0;
		}
		$data['sort'][1]['column'] 			= 'id';
		$data['sort'][1]['way'] 			= 'ASC';
		return $this->getData($data);
	}

	/**
	 * Renvoie un rapport $id
	 * @param string $id - id du rapport
	 * @return FileMaker
	 */
	public function getOneRapport($id) {
		$data = array();
		// force les données relatives au modèle
		$data['server'] 		= $this->getCurrentSERVER();
		// $data['server'] 		= "Géodem mac-mini";
		$data['base'] 			= 'GEODIAG_Rapports';
		$data['modele'] 		= 'Rapports_Local_Web';
		// autres données, par défaut :
		$data['search'][0]['column'] 	= 'id';
		$data['search'][0]['value']		= $id."";
		$result = $this->getData($data);
		if(is_array($result)) {
			reset($result);
			$result = current($result);
		}
		return $result;
	}

	/**
	 * Renvoie un rapport $id
	 * @param string $id - id du rapport
	 * @return FileMaker
	 */
	// public function getOneRapport($id) {
	// 	$model = $this->setCurrentModel('Rapports_Local_Web', 'GEODIAG_Rapports', "Géodem mac-mini");
	// 	// erreur ?
	// 	if(is_string($model)) return $model;
	// 	// Create FileMaker_Command_Find on layout to search
	// 	$this->FMfind = $this->FMbaseUser->newFindCommand($model['nom']);
	// 	$this->FMfind->addFindCriterion('id', $id."");
	// 	$result = $this->getRecords($this->FMfind->execute());
	// 	if(count($result) < 1) return "Aucun logement ".$id." trouvé.";
	// 	return $result;
	// }

	/**
	 * Informations sur un local, nécessaires à la génération d'un rapport
	 * @param string $idlocal - id du local
	 * @return array
	 */
	public function getOneLocalForRapport($idlocal) {
		$model = $this->useModel('Rapports_Local_Web', 'GEODIAG_Rapports');
		// erreur ?
		if(is_string($model)) return $model;
		// 
	}

	// public function getRelatedSets() {
	// 	if($this->isUserLogged() === true) {
	// 		// Create FileMaker_Command_Find on layout to search
	// 		$this->FMfind = $this->FMbaseUser->newFindCommand('Rapports_Local');
	// 		$this->FMfind->addSortRule('id', 1, FILEMAKER_SORT_DESCEND);
	// 		return $this->getRecords($this->FMfind->execute());
	// 	} else {
	// 		$records = "Utilisateur non connecté.";
	// 		return $records;
	// 	}
	// }

	// Table externe : Local_Pieces

	// Exemple
	// $relatedSet = $currentRecord->getRelatedSet(’customers’); /* Exécuté sur chacune des lignes de la table externe */
	// foreach ($relatedSet as $nextRow) {
	//	$nameField = $nextRow->getField(’customer::name’) if ($nameField == $badName ) {
	//	$result =   $newRow->delete();
	// }

	/**
	 * Renvoie la liste des lieux
	 * @return array
	 */
	public function getRapportsLieux() {
		if($this->isUserLogged() === true && is_object($this->FMbaseUser)) {
			// $vals = array(0 => "0", 1 => "1");
			// Create FileMaker_Command_Find on layout to search
			$this->FMfind = $this->FMbaseUser->newFindAllCommand('Lieu_Liste');
			$this->FMfind->addSortRule('cle', 1, FILEMAKER_SORT_DESCEND);
			$result = $this->FMfind->execute();
			return $this->getRecords($result);
		} else {
			return "Utilisateur non connecté.";
		}
	}

	/**
	 * Renvoie la liste des locaux d'un ou plusieurs lieux
	 * @param array $lieux
	 * @return array
	 */
	public function getLocauxByLieux($lieux = null) {
		if($this->isUserLogged() === true && is_object($this->FMbaseUser)) {
			// Create FileMaker_Command_Find on layout to search
			$this->FMfind = $this->FMbaseUser->newFindAllCommand('Locaux_IPAD');
			$this->FMfind->addSortRule('ref_local', 1, FILEMAKER_SORT_DESCEND);
			return $this->getRecords($this->FMfind->execute());
		} else {
			$records = "Utilisateur non connecté.";
			return $records;
		}
	}

	public function Cloture_UN_Rapport_Apres_Serveur($num_rapport) {
		$this->setCurrentBASE('GEODIAG_Rapports');
		$newPerformScript = $this->FMbaseUser->newPerformScriptCommand('Rapports_Local_Web', 'Cloture_Rapport_Apres_Serveur(num_rapport)', $num_rapport);
		return $this->getRecords($newPerformScript->execute());
	}

	public function Retablir_UN_Rapport_Apres_Serveur($num_rapport) {
		$this->setCurrentBASE('GEODIAG_Rapports');
		$newPerformScript = $this->FMbaseUser->newPerformScriptCommand('Rapports_Local_Web', 'Retablir_Rapport_Apres_Serveur(num_rapport)', $num_rapport);
		return $this->getRecords($newPerformScript->execute());
	}

	public function Cloture_LOT_Rapport_Apres_Serveur($num_lot) {
		$this->setCurrentBASE('GEODIAG_Rapports');
		$newPerformScript = $this->FMbaseUser->newPerformScriptCommand('Rapports_Local_Web', 'Cloture_Rapport_Apres_Serveur(num_lot)', $num_lot);
		return $this->getRecords($newPerformScript->execute());
	}

	public function Retablir_LOT_Rapport_Apres_Serveur($num_lot) {
		$this->setCurrentBASE('GEODIAG_Rapports');
		$newPerformScript = $this->FMbaseUser->newPerformScriptCommand('Rapports_Local_Web', 'Retablir_Rapport_Apres_Serveur(num_lot)', $num_lot);
		return $this->getRecords($newPerformScript->execute());
	}


/////////////////

	// /**
	//  * Renvoie la liste des données demandées dans le modèle $model
	//  * @param string $model - nom du modèle
	//  * @return array ou string si erreur
	//  */
	// public function getData($data) {
	// 	// public function getData($model, $select = null, $BASEnom = null, $SERVnom = null) {
	// 	// pour $data :
	// 	// server 		= nom_du_serveur
	// 	// base 		= nom_de_la_base
	// 	// modele 		= nom_du_modele
	// 	// column 		= nom_de_la_rubrique
	// 	// value 		= valeur_de_recherche
	// 	// order 		= ordre_de_tri ("ASC" ou "DESC")
	// 	// reset 		= 1 ou 0 (1 pour réinitialiser)
	// 	echo('<pre>');
	// 	var_dump($data);
	// 	echo('</pre>');
	// 	$model = $this->setCurrentModel($data['modele'], $data['base'], $data['server']);
	// 	// erreur ?
	// 	if(is_string($model)) return $model;

	// 	// Create FileMaker_Command_Find on layout to search
	// 	$this->FMfind = $this->FMbaseUser->newFindCommand($model['nom']);

	// 	// reset select
	// 	if(isset($data['reset'])) if($data['reset'] === "1") $this->resetAllSelect();

	// 	if(isset($data['search'])) {
	// 		if(count($data['search']) > 0) foreach ($data['search'] as $key => $value) {
	// 			$this->FMfind->addFindCriterion($value['column'], $value['value']);
	// 		}
	// 	}

	// 	if(isset($data['sort'])) {
	// 		if(count($data['sort']) > 0) foreach ($data['sort'] as $key => $value) {
	// 			if(strtoupper($value['way']) === "ASC") $way = FILEMAKER_SORT_ASCEND;
	// 				else $way = FILEMAKER_SORT_DESCEND;
	// 			$this->FMfind->addSortRule($value['column'], $key, $way);
	// 		}
	// 	}

	// 	return $this->getRecords($this->FMfind->execute());
	// }





}