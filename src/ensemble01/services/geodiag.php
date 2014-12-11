<?php
// ensemble01/services/geodiag.php

namespace ensemble01\services;

use Symfony\Component\DependencyInjection\ContainerInterface;
use filemakerBundle\services\filemakerservice as fms;

class geodiag extends fms {

	// *************************
	// METHODES GÉODEM
	// *************************

	/**
	 * Renvoie la liste des données demandées dans le modèle $model
	 * @param string $model - nom du modèle
	 * @return array ou string si erreur
	 */
	public function getData($model, $BASEnom = null, $SERVnom = null) {
		if($BASEnom === null) $BASEnom = $this->getCurrentBASE();
		if($SERVnom === null) $SERVnom = $this->getCurrentSERVER();
		if($this->setCurrentSERVER($SERVnom) === false) return 'Serveur '.$SERVnom." absent. Impossible d'accéder aux données";
		if($this->setCurrentBASE($BASEnom) === false) return 'Base '.$BASEnom." absente. Impossible d'accéder aux données";
		if(!$this->layoutExists($model)) return "Modèle \"".$model."\" absent. Impossible d'accéder aux données.";
		if(!$this->isUserLogged() === true) return "Utilisateur non connecté.";
		if(!is_object($this->FMbaseUser)) return "Objet FileMaker non initialisé.";
		// Create FileMaker_Command_Find on layout to search
		$this->FMfind =& $this->FMbaseUser->newFindAllCommand($model);
		return $this->getRecords($this->FMfind->execute());
	}

	/**
	 * Renvoie la liste des lieux
	 * @return array
	 */
	public function getLieux() {
		// $BASEnom = 'GEODIAG_Rapports';
		// $model = 'Lieu_Liste';
		$BASEnom = 'GEODIAG_SERVEUR';
		$model = 'Lieu';
		if($this->setCurrentBASE($BASEnom) === false) return 'Base '.$BASEnom." absente. Impossible d'accéder aux données";
		if(!$this->layoutExists($model)) return "Modèle \"".$model."\" absent. Impossible d'accéder aux données.";
		if(!$this->isUserLogged() === true) return "Utilisateur non connecté.";
		if(!is_object($this->FMbaseUser)) return "Objet FileMaker non initialisé.";

		// Create FileMaker_Command_Find on layout to search
		$this->FMfind =& $this->FMbaseUser->newFindAllCommand($model);
		$this->FMfind->addSortRule('cle', 1, FILEMAKER_SORT_DESCEND);
		$result = $this->getRecords($this->FMfind->execute());
		return $result;
	}

	/**
	 * Renvoie la liste des affaires
	 * @return array
	 */
	public function getAffaires() {
		$BASEnom = 'GEODIAG_SERVEUR';
		$model = 'Projet_Liste';
		if($this->setCurrentBASE($BASEnom) === false) return 'Base '.$BASEnom." absente. Impossible d'accéder aux données";
		if(!$this->layoutExists($model)) return "Modèle \"".$model."\" absent. Impossible d'accéder aux données.";
		if(!$this->isUserLogged() === true) return "Utilisateur non connecté.";
		if(!is_object($this->FMbaseUser)) return "Objet FileMaker non initialisé.";

		// Create FileMaker_Command_Find on layout to search
		$this->FMfind =& $this->FMbaseUser->newFindAllCommand($model);
		$this->FMfind->addSortRule('date_projet', 1, FILEMAKER_SORT_DESCEND);
		$result = $this->getRecords($this->FMfind->execute());
		return $result;
	}

	/**
	 * Renvoie la liste des tiers
	 * @return array
	 */
	public function getTiers() {
		$BASEnom = 'GEODIAG_SERVEUR';
		$model = 'Tiers_Liste';
		if($this->setCurrentBASE($BASEnom) === false) return 'Base '.$BASEnom." absente. Impossible d'accéder aux données";
		if(!$this->layoutExists($model)) return "Modèle \"".$model."\" absent. Impossible d'accéder aux données.";
		if(!$this->isUserLogged() === true) return "Utilisateur non connecté.";
		if(!is_object($this->FMbaseUser)) return "Objet FileMaker non initialisé.";

		// Create FileMaker_Command_Find on layout to search
		$this->FMfind =& $this->FMbaseUser->newFindAllCommand($model);
		$this->FMfind->addSortRule('ref', 1, FILEMAKER_SORT_DESCEND);
		$result = $this->getRecords($this->FMfind->execute());
		return $result;
	}








	/**
	 * Renvoie la liste des rapports
	 * @return array
	 */
	public function getRapports($etat = 'all') {
		if($this->isUserLogged() === true && is_object($this->FMbaseUser)) {
			$vals = array(0 => "0", 1 => "1");
			// Create FileMaker_Command_Find on layout to search
			$this->FMfind =& $this->FMbaseUser->newFindCommand('Rapports_Local');
			if(in_array($etat, $vals)) {
				$this->FMfind->addFindCriterion('a_traiter', intval($etat));
			}
			$this->FMfind->addSortRule('id', 1, FILEMAKER_SORT_DESCEND);
			return $this->getRecords($this->FMfind->execute());
		} else {
			$records = "Utilisateur non connecté.";
			return $records;
		}
	}

	// public function getRelatedSets() {
	// 	if($this->isUserLogged() === true) {
	// 		// Create FileMaker_Command_Find on layout to search
	// 		$this->FMfind =& $this->FMbaseUser->newFindCommand('Rapports_Local');
	// 		$this->FMfind->addSortRule('id', 1, FILEMAKER_SORT_DESCEND);
	// 		return $this->getRecords($this->FMfind->execute());
	// 	} else {
	// 		$records = "Utilisateur non connecté.";
	// 		return $records;
	// 	}
	// }

	// Exemple
	// $relatedSet = $currentRecord->getRelatedSet(’customers’); /* Exécuté sur chacune des lignes de la table externe */ foreach ($relatedSet as $nextRow) {
	// $nameField = $nextRow->getField(’customer::name’) if ($nameField == $badName ) {
	// 	$result =   $newRow->delete();
	// }

	/**
	 * Renvoie la liste des lieux
	 * @return array
	 */
	public function getRapportsLieux() {
		if($this->isUserLogged() === true && is_object($this->FMbaseUser)) {
			// $vals = array(0 => "0", 1 => "1");
			// Create FileMaker_Command_Find on layout to search
			$this->FMfind =& $this->FMbaseUser->newFindAllCommand('Lieu_Liste');
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
			$this->FMfind =& $this->FMbaseUser->newFindAllCommand('Locaux_IPAD');
			$this->FMfind->addSortRule('ref_local', 1, FILEMAKER_SORT_DESCEND);
			return $this->getRecords($this->FMfind->execute());
		} else {
			$records = "Utilisateur non connecté.";
			return $records;
		}
	}



}