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
	 * Renvoie la liste des lieux
	 * @return array
	 */
	public function getLieux() {
		if($this->isUserLogged() === true) {
			// Create FileMaker_Command_Find on layout to search
			$this->FMfind =& $this->FMbase->newFindAllCommand('Lieu_Liste');
			$this->FMfind->addSortRule('cle', 1, FILEMAKER_SORT_DESCEND);
			return $this->getRecords($this->FMfind->execute());
		} else {
			$records = "Utilisateur non connecté.";
			return $records;
		}
	}

	/**
	 * Renvoie la liste des lieux
	 * @return array
	 */
	public function getRapports($etat = 'all') {
		if($this->isUserLogged() === true) {
			$vals = array(0 => "0", 1 => "1");
			// Create FileMaker_Command_Find on layout to search
			$this->FMfind =& $this->FMbase->newFindCommand('Rapports_Local');
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
	// 		$this->FMfind =& $this->FMbase->newFindCommand('Rapports_Local');
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
		if($this->isUserLogged() === true) {
			// $vals = array(0 => "0", 1 => "1");
			// Create FileMaker_Command_Find on layout to search
			$this->FMfind =& $this->FMbase->newFindAllCommand('Lieu_Liste');
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
		if($this->isUserLogged() === true) {
			// Create FileMaker_Command_Find on layout to search
			$this->FMfind =& $this->FMbase->newFindAllCommand('Locaux_IPAD');
			$this->FMfind->addSortRule('ref_local', 1, FILEMAKER_SORT_DESCEND);
			return $this->getRecords($this->FMfind->execute());
		} else {
			$records = "Utilisateur non connecté.";
			return $records;
		}
	}

	/**
	 * Renvoie la liste des affaires
	 * @return array
	 */
	public function getAffaires() {
		if($this->isUserLogged() === true) {
			// Create FileMaker_Command_Find on layout to search
			$this->FMfind =& $this->FMbase->newFindAllCommand('Projet_liste');
			$this->FMfind->addSortRule('date_projet', 1, FILEMAKER_SORT_DESCEND);
			return $this->getRecords($this->FMfind->execute());
		} else {
			$records = "Utilisateur non connecté.";
			return $records;
		}
	}

	/**
	 * Renvoie la liste des tiers
	 * @return array
	 */
	public function getTiers() {
		if($this->isUserLogged() === true) {
			// Create FileMaker_Command_Find on layout to search
			$this->FMfind =& $this->FMbase->newFindAllCommand('Tiers_Liste');
			$this->FMfind->addSortRule('ref', 1, FILEMAKER_SORT_DESCEND);
			return $this->getRecords($this->FMfind->execute());
		} else {
			$records = "Utilisateur non connecté.";
			return $records;
		}
	}



}