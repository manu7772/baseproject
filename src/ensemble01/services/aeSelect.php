<?php
// labo/ensemble01/services/aeSelect.php

namespace ensemble01\services;

use Symfony\Component\DependencyInjection\ContainerInterface;

class aeSelect {

	/*

	structure de $this->data :

	['_current'] (const 'self::CURRENT' = '_current')
		['select'] = nom de la sélection courante
		['groupe'] = nom du groupe courant
		['id_user'] = id de l'utilisateur courant ou ANON
		['username'] = username de l'utilisateur courant ou ANON
	[nom_de_route] ($this->selectName)
		[nom_de_groupe] (nom_de_groupe = valeur1 + @ + valeur2 + @ + etc.)
			['groupes']
				[nom_groupe1] = valeur1
				[nom_groupe2] = valeur2
				[nom_groupe3] = valeur3
				[etc.]
			['recherche']
				['search'] ( --> recherche)
					[0]
						['column'] = nom de la colonne0
						['operator'] = élément de comparaison
						['value'] = valeur de recherche
					[1]
						['column'] = nom de la colonne1
						['operator'] = élément de comparaison
						['value'] = valeur de recherche
				['sort'] ( --> tri)
					[0]
						['column'] = nom de la colonne0
						['way'] = sens de tri (ASC|DESC)
					[1]
						['column'] = nom de la colonne1
						['way'] = sens de tri (ASC|DESC)


	*/

	const GLUE = "@@@";
	const ANON = "anon.";
	const CURRENT = '_current';

	protected $container;	// container
	protected $serviceSess;	// session
	protected $route;		// route
	protected $em;			// EntityManager
	protected $um;			// UserManager
	protected $user;		// objet user ou ANON
	protected $selectName;	// Nom de la sélection courante
	protected $groupeName;	// Nom du groupe courant dans la sélection
	protected $data = array();

	protected $DEV = true;		// mode DEV
	protected $recurs = 0;
	protected $recursMAX = 8;

	function __construct(ContainerInterface $container) {
		$this->container 	= $container;
		$this->serviceSess 	= $this->container->get('request')->getSession();
		$this->route 		= $this->container->get("request")->attributes->get('_route');
		$this->em 			= $this->container->get('doctrine')->getManager();
		$this->um 			= $this->container->get('fos_user.user_manager');
		// $this->selectName 	= $this->setSelectName();
		$this->initData();
	}

	function __destruct() {
		// Enregistre en session
		// echo('DESTRUCT BEGIN<br>');
		if(is_array($this->data)) {
			// echo('Enregistrement Data Select en session…<br>');
			// $this->serviceSess->set($this->getName(), $this->data);
			// enregistre BD user
			if($this->user !== self::ANON && is_object($this->user)) {
				// echo('Enregistrement Data Select en DB user<br>');
				$this->user->setFmselection($this->data);
				$this->um->updateUser($this->user);
			}
		}
		// $this->vardumpDev($this->data, "Contenu de data :");
		// echo('DESTRUCT END<br>');
	}

	/**
	 * Initialise les données $this->data
	 * Récupère les données select de l'utilisateur (si elles existent)
	 * 
	 * @return aeSelect
	 */
	protected function initData() {
		// charge les données de session
		if($this->loadAllSelectInData() === false) {
			// si aucune donnée en session
			$this->formateBasicData();
		}
		// récupère l'utilisateur
		// et compare l'user / ancien user
		if($this->getUser() !== self::ANON) {
			// nouveau user ou changé
			if($this->data[self::CURRENT]['id_user'] !== $this->user->getId()) {
				// chargement des données du nouveau user
				$us = $this->user->getFmselection();
				if(is_array($us)) {
					// OK l'user a déjà des données
					$this->data = $us;
					$this->saveAllSelectInSession();
					unset($us);
				} else {
					$this->formateBasicData();
				}
			} else {
				// l'utilisateur est le même… on n'a rien de plus à faire
				// $this->loadAllSelectInData()
			}
		} else {
			// user anonyme - non connecté
			$this->formateBasicData();
		}
		$this->saveAllSelectInSession(false);
		return $this;
	}

	/**
	 * Initialise $this->data
	 * 
	 * @return aeSelect
	 */
	protected function formateBasicData() {
		// pas d'user courant défini : on initialise totalement les données
		$this->data = array();
		$this->data[self::CURRENT] = array();
		$this->data[self::CURRENT]['select'] = null;
		$this->data[self::CURRENT]['groupe'] = null;
		if($this->getUser() === self::ANON) {
			$this->data[self::CURRENT]['id_user'] = self::ANON;
			$this->data[self::CURRENT]['username'] = self::ANON;
		} else {
			$this->data[self::CURRENT]['id_user'] = $this->getUser()->getId();
			$this->data[self::CURRENT]['username'] = $this->getUser()->getUsername();
		}
		// $this->saveAllSelectInSession();
		return $this;
	}

	/**
	 * Renvoie l'utilisateur courant
	 * 
	 * @return user / ANON
	 */
	protected function getUser() {
		if(!isset($this->user)) {
			// initialise user ça non existant
			$user = $this->container->get('security.context')->getToken()->getUser();
			if(is_object($user)) {
				$this->user = $this->um->findUserByUsername($user->getUsername());
				// echo("User : ".$this->user->getUsername()."<br>");
			} else {
				$this->user = self::ANON;
				// echo("User : ".$this->user."<br>");
			}
		}
		return $this->user;
	}


	// GETTERS

	/**
	 * Renvoie le nom du service
	 * 
	 * @return string
	 */
	public function getName() {
		return 'aeSelect';
	}

	/**
	 * Renvoie le nom de la sélection actuelle / null si aucun select défini
	 * 
	 * @return string / null
	 */
	public function getSelectName() {
		$this->selectName = $this->data[self::CURRENT]['select'];
		return $this->selectName;
	}

	/**
	 * Renvoie la sélection courante
	 * 
	 * @return array / null si aucune
	 */
	public function getCurrentSelect() {
		if(isset($this->data[$this->selectName][$this->groupeName])) return $this->data[$this->selectName][$this->groupeName];
			else return null;
	}

	/**
	 * Définit comme groupe actuel
	 * 
	 * @return string / null
	 */
	public function setGroupeName($groupeName) {
		$this->data[self::CURRENT]['groupe'] = $this->groupeName = $groupeName;
		return $this;
	}

	/**
	 * Renvoie le nom du groupe actuel / null si aucun groupe défini
	 * 
	 * @return string / null
	 */
	public function getGroupeName() {
		return $this->data[self::CURRENT]['groupe'];
	}

	/**
	 * Renvoie le array du groupe actuel / null si aucun groupe défini
	 * 
	 * @return string / null
	 */
	public function getGroupeArray() {
		if($this->data[self::CURRENT]['groupe'] !== null) {
			return $this->data[$this->selectName][$this->data[self::CURRENT]['groupe']]['groupes'];
		} else return null;
	}

	/**
	 * Renvoie toutes les données de sélection en session
	 * 
	 * @param boolean $reload - récupère les données de session avant (si true)
	 * @return array
	 */
	public function getAllSelects($reload = false) {
		if($reload === true) $this->loadAllSelectInData();
		return $this->data;
	}

	/**
	 * Renvoie tous les noms des sélections / false si aucun nom
	 * 
	 * @param boolean $reload - récupère les données de session avant (si true)
	 * @return array
	 */
	public function getAllSelectNames($reload = false) {
		if($reload === true) $this->loadAllSelectInData();
		$selectNames = array();
		$listNames = array_keys($this->data);
		if(count($listNames) > 0) {
			foreach ($listNames as $key => $value) {
				$selectNames[] = $this->recupShortSelectName($value);
			}
			return $selectNames;
		}
		return false;
	}

	/**
	 * est-ce qu'un nom de sélection existe ?
	 * 
	 * @param string $selectName - nom de la sélection
	 * @return boolean
	 */
	public function existsSelectName($selectName) {
		if(in_array($selectName, $this->getAllSelectNames())) return true;
		return false;
	}

	// SETTERS

	/**
	 * Enregistre les sélections dans l'entité user (en BDD)
	 * 
	 * @param boolean $reload - récupère les données de session avant (si true)
	 * @return aeSelect
	 */
	public function updateUserSelect($reload = false) {
		$this->saveUser($reload);
		return $this;
	}

	/**
	 * Définit un groupe pour la recherche courante (et le définit en groupe courant)
	 * Si le groupe existe déjà, il est simplement défini en groupe courant
	 * -> pratique pour définir plusieurs entités dans la même page
	 * -> les données de $groupes peuvent être mises dans un array pour permettre de sous partitionner
	 * 
	 * @param mixed $groupes - nom du groupe sous forme d'array
	 * @param boolean $force - remplace si existant
	 * @return aeSelect
	 */
	public function addGroupe($groupes, $force = false) {
		$arrayGroupes = $groupes;
		if($this->getSelectName() !== null) {
			if(is_string($groupes)) $groupes = array($groupes);
			$groupes = $this->makeGroupeName($groupes);
			if(($this->groupesExists($groupes, true) === false) || ($force === true)) {
				$this->setGroupeName($groupes);
				$this->data[$this->getSelectName()][$this->getGroupeName()]['groupes'] = $arrayGroupes;
				$this->data[$this->getSelectName()][$this->getGroupeName()]['recherche'] = array();
				$this->saveAllSelectInSession();
			}
			$this->data[self::CURRENT]['groupe'] = $this->getGroupeName();
		}
		return $this;
	}

	/**
	 * Supprime un groupe (du select courant)
	 * si le groupe n'existe pas (dans le select courant !) => renvoie false
	 * 
	 * @param mixed $groupes - groupeName du groupe sous forme d'array
	 * @return aeSelect
	 */
	public function deleteGroupe($groupes) {
		if(is_string($groupes)) $groupeName = $groupes;
			else $groupeName = $this->makeGroupeName($groupes);
		if($this->groupesExists($groupeName, true) === true) {
			unset($this->data[$this->getSelectName()][$groupeName]);
			if($this->data[self::CURRENT]['groupe'] === $groupeName) $this->getNewCurrentGroupe();
			$this->saveAllSelectInSession();
		}
		return $this;
	}

	/**
	 * Renvoie la liste des groupes 
	 * de toutes les sélections ou de la sélection courante uniquement
	 * 
	 * @param boolean $currentOnly - renvoie la liste de la sélection courante uniquement, si true (false par défaut)
	 * @param boolean $reload - récupère les données de session avant (si true)
	 * @return array
	 */
	public function getListOfGroupes($currentOnly = false, $reload = false) {
		$listGroupes = array();
		if($reload === true) $this->loadAllSelectInData();
		$search = array();
		if($currentOnly === true) $search[$this->data[self::CURRENT]['select']] = $this->data[$this->data[self::CURRENT]['select']];
			else $search = $this->data;
		foreach ($search as $selectName => $groupe) {
			if($selectName !== self::CURRENT) {
				if(is_array($groupe)) foreach ($groupe as $nom => $detail) {
					$listGroupes[$nom]['nom'] = $nom;
					$listGroupes[$nom]['selectName'] = $selectName;
					$listGroupes[$nom]['groupes'] = $detail['groupes'];
				}
			}
		}
		return $listGroupes;
	}

	/**
	 * Renvoie true si le groupe existe 
	 * teste dans toutes les sélections ou de la sélection courante uniquement
	 * 
	 * @param mixed $groupes - nom du groupe ou array de noms
	 * @param boolean $currentOnly - renvoie la liste de la sélection courante uniquement, si true (false par défaut)
	 * @param boolean $reload - récupère les données de session avant (si true)
	 * @return array
	 */
	public function groupesExists($groupes, $currentOnly = false, $reload = false) {
		if($reload === true) $this->loadAllSelectInData();
		if(is_array($groupes)) $groupes = $this->makeGroupeName($groupes);
		if(array_key_exists($groupes, $this->getListOfGroupes($currentOnly, $reload))) return true;
		return false;
	}

	/**
	 * Vide la recherche
	 * @return aeSelect
	 */
	public function emptyRecherche() {
		// réinitialise search courant
		$this->data[$this->getSelectName()][$this->getGroupeName()]['recherche']['search'] = array();
		$this->saveAllSelectInSession();
		return $this;
	}

	/**
	 * Vide la recherche et ajoute des paramètres de recherche (nom courant - groupe courant)
	 * 
	 * @param string $column - nom de la colonne
	 * @param string $value - valeur de recherche
	 * @param string $operator - genre de recherche
	 * @return aeSelect
	 */
	public function setRecherche($column, $value, $operator = null) {
		// réinitialise search courant
		$this->emptyRecherche()->addRecherche($column, $value, $operator);
		return $this;
	}

	/**
	 * Ajoute des paramètres de recherche (nom courant - groupe courant)
	 * 
	 * @param string $column - nom de la colonne
	 * @param string $value - valeur de recherche
	 * @param string $operator - genre de recherche
	 * @return aeSelect
	 */
	public function addRecherche($column, $value, $operator = null) {
		// réinitialise search courant
		if(!isset($this->data[$this->getSelectName()][$this->getGroupeName()]['recherche']['search'])) $this->data[$this->getSelectName()][$this->getGroupeName()]['recherche']['search'] = array();
		$this->data[$this->getSelectName()][$this->getGroupeName()]['recherche']['search'][] = array('column' => $column, 'value' => $value, 'operator' => $operator);
		$this->saveAllSelectInSession();
		return $this;
	}

	/**
	 * Est-ce que la sélection $selectName existe ?
	 * 
	 * @param string $selectName - suffixe du nom
	 * @param string $route - nom de la route (route courante par défaut)
	 * @param boolean $reload - récupère les données de session avant (si true)
	 * @return boolean
	 */
	public function selectExists($selectName, $route = null, $reload = false) {
		if($reload === true) $this->loadAllSelectInData();
		if(array_key_exists($this->generateSelectName($selectName, $route), $this->data)) return true;
		return false;
	}

	/**
	 * Renvoie true si la sélection $selectName possède déjà au moins un groupe
	 * 
	 * @param string $selectName - suffixe du nom
	 * @param string $route - nom de la route (route courante par défaut)
	 * @param boolean $reload - récupère les données de session avant (si true)
	 * @return boolean
	 */
	public function selectHasGroupes($selectName = null, $reload = false) {
		if($reload === true) $this->loadAllSelectInData();
		if($selectName === null) $selectName = $this->getSelectName();
		// $selectName = $this->generateSelectName($selectName, $route);
		if(isset($this->data[$selectName])) {
			if(count($this->data[$selectName]) > 0) return true;
		}
		return false;
	}

	/**
	 * Définit le nom de la sélection. 
	 * Crée la sélection si elle n'existe pas. 
	 * Définit la sélection comme sélection courante. 
	 * 
	 * @param string $selectName - suffixe du nom
	 * @param string $route - nom de la route (route courante par défaut)
	 * @return aeSelect
	 */
	public function setSelectName($selectName = null, $route = null) {
		$selectName = $this->generateSelectName($selectName, $route);

		if($this->selectExists($selectName) !== true) {
			// la sélection n'éxiste pas
			$this->data[$selectName] = array();
		}

		$this->data[self::CURRENT]['select'] = $this->selectName = $selectName;
		// groupe courant par défaut…
		$gc = $this->getListOfGroupes(true, false);
		reset($gc);
		$this->data[self::CURRENT]['groupe'] = key($gc);

		$this->saveAllSelectInSession();
		return $this;
	}

	/**
	 * Supprime la sélection. 
	 * Si la sélection est la courante, détermine une nouvelle sélection courante arbitrairement.
	 * 
	 * @param string $selectName - suffixe du nom
	 * @param string $route - nom de la route (route courante par défaut)
	 * @return aeSelect
	 */
	public function deleteSelect($selectName = null, $route = null) {
		if($selectName !== self::CURRENT) {
			if($selectName === null) $selectName = $this->selectName;
			$selectName = $this->generateSelectName($selectName, $route);
			unset($this->data[$selectName]);
			$this->getNewCurrentSelect();
		}
	}

	/**
	 * Définit arbitrairement une NOUVELLE SÉLECTION par défaut
	 * 
	 * @return string - nom de la nouvelle sélection courante / null si aucune
	 */
	protected function getNewCurrentSelect() {
		reset($this->data);
		if(key($this->data) === self::CURRENT) next($this->data);
		if(key($this->data) !== self::CURRENT) {
			// il reste au moins une sélection…
			$this->data[self::CURRENT]['select'] = $this->selectName = key($this->data);
			$this->getNewCurrentGroupe();
		} else {
			// plus de sélection…
			$this->data[self::CURRENT]['select'] = null;
			$this->data[self::CURRENT]['groupe'] = null;
		}
		return $this->data[self::CURRENT]['select'];
	}

	/**
	 * Définit arbitrairement un NOUVEAU GROUPE dans la sélection par défaut
	 * 
	 * @return string - nom du nouveau groupe courant / null si aucun
	 */
	protected function getNewCurrentGroupe() {
		if($this->selectName !== null) {
			if(count($this->data[$this->selectName]) > 0) {
				// il reste au moins un groupe…
				reset($this->data[$this->selectName]);
				$this->data[self::CURRENT]['groupe'] = $this->groupeName = key($this->data[$this->selectName]);
			} else {
				// plus de groupe…
				$this->data[self::CURRENT]['groupe'] = null;
			}
		}
		return $this->data[self::CURRENT]['groupe'];
	}

	/**
	 * Modifie la recherche selon $data
	 * Select et groupes courants
	 * 
	 * @return aeSelect
	 */
	public function computeSelect($data = null) {
		if($data !== null) {
			//
			$this->saveAllSelectInSession();
		}
		return $this;
	}

	/**
	 * Modifie la recherche selon requête POST
	 * Select et groupes courants
	 * 
	 * @return aeSelect
	 */
	public function computeRequestSelect() {
		$dataPOST = null;
		$this->computeSelect($dataPOST);
		return $this;
	}

	/**
	 * Modifie la recherche selon requête GET
	 * Select et groupes courants
	 * 
	 * @return aeSelect
	 */
	public function computeQuerySelect() {
		$dataGET = null;
		$this->computeSelect($dataGET);
		return $this;
	}

	/**
	 * Modifie la recherche selon toutes les requêtes (POST et GET) et $data
	 * Select et groupes courants
	 * 
	 * @return aeSelect
	 */
	public function computeAllQueriesSelect($data = null) {
		$this->computeRequestSelect();
		$this->computeQuerySelect();
		if(is_array($data)) $this->computeQuerySelect($data);
		return $this;
	}


	// MÉTHODES PRIVÉES/PROTECTED

	/**
	 * Charge les données de sélection dans $this->data
	 * s'il n'y as pas de données en session, renvoie false
	 * 
	 * @return boolean
	 */
	protected function loadAllSelectInData() {
		$data = $this->serviceSess->get($this->getName());
		if(is_array($data)) {
			$this->data = $data;
			return true;
		}
		return false;
	}

	/**
	 * Enregistre en session les données de sélection de $this->data
	 * 
	 * @param boolean $inBDD - enregistre aussi en BDD user si true
	 * @return boolean
	 */
	protected function saveAllSelectInSession($inBDD = false) {
		if(is_array($this->data)) {
			$this->serviceSess->set($this->getName(), $this->data);
			// echo('Enregistrement Data Select en session…<br>');
			if($inBDD === true) $this->saveUser();
			return true;
		}
		return false;
	}

	/**
	 * Enregistre les données select de l'utilisateur
	 * 
	 * @param boolean $reload - récupère les données de session avant (si true)
	 * @return aeSelect
	 */
	protected function saveUser($reload = false) {
		if($reload === true) $this->loadAllSelectInData();
		$this->getUser();
		if($this->user !== self::ANON && is_object($this->user)) {
			$this->user->setFmselection($this->data);
			$this->um->updateUser($this->user);
		}
		return $this;
	}

	protected function generateSelectName($selectName = null, $route = null) {
		if($route === null) $route = $this->route;
		if(strlen($selectName."") === 0) {
			do {
				$selectName = "select".rand(10000000, 99999999);
			} while ($this->existsSelectName($route.self::GLUE.$selectName) === true);
		}
		$result = $route.self::GLUE.$selectName;
		// echo("- Auto name : ".$result."<br>");
		return $result;
	}

	protected function shortenSelectName($selectName, $route = null) {

	}

	protected function recupShortSelectName($LONGselectName) {
		if(is_string($LONGselectName)) {
			$result = explode(self::GLUE, $LONGselectName, 2);
			return $result[count($result) - 1];
		} else return null;
	}

	protected function makeGroupeName($groupes) {
		if(is_string($groupes)) $groupes = array($groupes);
		$groupeName = implode(self::GLUE, $groupes);
		// echo('New groupeName : '.$groupeName.'<br>');
		return $groupeName;
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

?>