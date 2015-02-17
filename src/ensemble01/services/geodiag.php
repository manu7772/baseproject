<?php
// ensemble01/services/geodiag.php

namespace ensemble01\services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use filemakerBundle\services\filemakerservice as fms;

class geodiag extends fms {

	// *************************
	// METHODES GÉODEM
	// *************************


	public function getRapportFileName($rapport) {
		return $rapport->getField('id')."-".$rapport->getField('type_rapport')."-v".$rapport->getField('version');
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
		// $data['sort'][1]['column'] 			= 'date_projet';
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
		$data2 = $data['recherche'];
		$data2['server'] 		= $data['groupes'][0];
		$data2['base'] 			= $data['groupes'][1];
		$data2['modele'] 		= $data['groupes'][2];
		return $this->getData($data2);
	}

	/**
	 * Renvoie la liste des différents lots et la quatité de rapports qu'il contient
	 * array [nom du lot] = nombre de rapports
	 * @param boolean $all - false par défaut : uniquement les 'a_traiter' à 0 / true = tous
	 * @return array
	 */
	public function getListeRapportsByLot($data) {
		// force les données relatives au modèle
		$data2 = $data['recherche'];
		$data2['server'] 		= $data['groupes'][0];
		$data2['base'] 			= $data['groupes'][1];
		$data2['modele'] 		= $data['groupes'][2];
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
		$result = $this->getData($data2);
		// opération de tri
		$mem = array();
		$r2 = array();
		if(is_array($result)) {
			foreach($result as $rapport) {
				$numlot = $rapport->getField('num_lot');
				if(in_array($numlot, $mem)) {
					// lot déjà trouvé
					$r2[$numlot]['nb']++;
				} else {
					// nouveau lot
					$mem[] = $numlot;
					$r2[$numlot]['nb'] = 1;
				}
			}
		} else $r2 = $result;
		unset($result);
		return $r2;
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
		// $data['server'] 		= $this->getCurrentSERVER();
		$data['server'] 		= "Géodem mac-mini";
		// $data['base'] 		= 'GEODIAG_SERVEUR';
		$data['base'] 			= 'GEODIAG_Rapports';
		$data['modele'] 		= 'Rapports_Local_Web';
		// autres données, par défaut :
		$data['search'][0]['column'] 	= 'id';
		$data['search'][0]['value']		= $id."";

		return $this->getData($data);
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


	public function Cloture_Rapport_Apres_Serveur($num_lot) {
		$this->setCurrentBASE('GEODIAG_Rapports');
		$newPerformScript = $this->FMbaseUser->newPerformScriptCommand('Rapports_Local_Web', 'Cloture_Rapport_Apres_Serveur(num_lot)', $num_lot);
		return $this->getRecords($newPerformScript->execute());
	}

	public function Retablir_Rapport_Apres_Serveur($num_lot) {
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