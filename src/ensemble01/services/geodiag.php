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
	public function getLieux($crit) {
		$model = $this->useModel('Lieu', 'GEODIAG_SERVEUR');
		// erreur ?
		if(is_string($model)) return $model;

		// Create FileMaker_Command_Find on layout to search
		$this->FMfind = $this->FMbaseUser->newFindCommand($model['nom']);

		if(is_array($crit['search'])) {
			if(count($crit['search']) > 0) foreach ($crit['search'] as $key => $value) {
				$this->FMfind->addFindCriterion($key, $value);
			}
		}

		if(is_array($crit['sort'])) {
			if(count($crit['sort']) > 0) foreach ($crit['sort'] as $key => $value) {
				if(strtoupper($value) === "ASC") $value = FILEMAKER_SORT_ASCEND;
					else $value = FILEMAKER_SORT_DESCEND;
				$this->FMfind->addSortRule($key, 1, $value);
			}
		}

		// $this->FMfind->addSortRule('cle', 1, FILEMAKER_SORT_DESCEND);
		$result = $this->getRecords($this->FMfind->execute());
		return $result;
	}

	/**
	 * Renvoie la liste des lieux
	 * @return array
	 */
	public function getLocaux($crit) {
		$model = $this->useModel('Local', 'GEODIAG_SERVEUR');
		// erreur ?
		if(is_string($model)) return $model;

		// Create FileMaker_Command_Find on layout to search
		$this->FMfind = $this->FMbaseUser->newFindCommand($model['nom']);
		
		if(is_array($crit['search'])) {
			if(count($crit['search']) > 0) foreach ($crit['search'] as $key => $value) {
				$this->FMfind->addFindCriterion($key, $value);
			}
		}

		if(is_array($crit['sort'])) {
			if(count($crit['sort']) > 0) foreach ($crit['sort'] as $key => $value) {
				if(strtoupper($value) === "ASC") $value = FILEMAKER_SORT_ASCEND;
					else $value = FILEMAKER_SORT_DESCEND;
				$this->FMfind->addSortRule($key, 1, $value);
			}
		}

		// $this->FMfind->addSortRule('cle_lieux', 1, FILEMAKER_SORT_DESCEND);
		$result = $this->getRecords($this->FMfind->execute());
		return $result;
	}

	/**
	 * Renvoie la liste des affaires
	 * @param array $data - données de recherche et autres données
	 * @return array
	 */
	public function getAffaires($data = null) {
		if(!isset($data['select'])) $data['select'] = array();
		// force les données relatives au modèle
		$data['select']['server'] 		= $this->getCurrentSERVER();
		// $data['select']['base'] 		= 'GEODIAG_SERVEUR';
		$data['select']['base'] 		= 'GEODIAG_REF_WEB';
		$data['select']['modele'] 		= 'Projet_Liste';
		// autres données, par défaut :
		if(!isset($data['select']['column'])) {
			$data['select']['column'] 	= 'intitule';
			$data['select']['value']	= 'Marché Evreux';
		}
		if(!isset($data['select']['order'])) {
			$data['select']['order'] 	= 'date_projet'."|"."DESC";
		}
		return $this->getData($data['select']);
	}

	/**
	 * Renvoie la liste des tiers
	 * @return array
	 */
	public function getTiers($selection = null, $tri = null) {
		// $model = $this->useModel('Tiers_Liste', 'GEODIAG_SERVEUR');
		$model = $this->useModel('Tiers_01', 'GEODIAG_SERVEUR');
		// erreur ?
		if(is_string($model)) return $model;

		// Create FileMaker_Command_Find on layout to search
		$this->FMfind = $this->FMbaseUser->newFindAllCommand($model['nom']);
		if(is_array($selection) && (count($selection) > 0)) {
			foreach ($selection as $key => $value) {
				$this->FMfind->addFindCriterion($key, trim($value));
			}
		}
		// $this->FMfind->addFindCriterion('type_tiers', '01-Client');
		$this->FMfind->addSortRule('prenom', 1, FILEMAKER_SORT_ASCEND);
		$this->FMfind->addSortRule('nom', 1, FILEMAKER_SORT_ASCEND);
		$result = $this->getRecords($this->FMfind->execute());
		return $result;
	}

	/**
	 * Renvoie la liste des rapports
	 * @return array
	 */
	public function getRapports($etat = 'all') {
		$model = $this->useModel('Rapports_Local_Web', 'GEODIAG_Rapports');
		// erreur ?
		if(is_string($model)) return $model;

		$vals = array(0 => "0", 1 => "1");
		// Create FileMaker_Command_Find on layout to search
		$this->FMfind = $this->FMbaseUser->newFindCommand($model['nom']);
		if(in_array($etat, $vals)) {
			$this->FMfind->addFindCriterion('a_traiter', intval($etat));
		}
		$this->FMfind->addSortRule('id', 1, FILEMAKER_SORT_DESCEND);
		$result = $this->getRecords($this->FMfind->execute());

		// Test getRelatedSet
		$n = 0;
		foreach($result as $currentRecord) {
			$relatedSet = $currentRecord->getRelatedSet('Local_Pieces');
			$o = 0;
			foreach ($relatedSet as $nextRow) {
				$list = array('nom_piece', 'num_piece');
				foreach($list as $nom) {
					$nameField[$n][$o][$nom] = $nextRow->getField('Local_Pieces::'.$nom);
				}
				$o++;
			}
			$n++;
		}
		// echo("<pre>");
		// var_dump($nameField);
		// echo("</pre>");

		return $result;
	}

	/**
	 * Renvoie un rapport $id
	 * @param string $id - id du rapport
	 * @return FileMaker
	 */
	public function getOneRapport($id) {
		$model = $this->setCurrentModel('Rapports_Local_Web', 'GEODIAG_Rapports', "Géodem mac-mini");
		// erreur ?
		if(is_string($model)) return $model;
		// Create FileMaker_Command_Find on layout to search
		$this->FMfind = $this->FMbaseUser->newFindCommand($model['nom']);
		$this->FMfind->addFindCriterion('id', $id."");
		$result = $this->getRecords($this->FMfind->execute());
		if(count($result) < 1) return "Aucun logement ".$id." trouvé.";
		return $result;
	}

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



}