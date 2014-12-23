<?php
// src/ensemble01/LaboBundle/Classes/paramForRepo.php

namespace ensemble01\LaboBundle\Classes;

use Symfony\Component\DependencyInjection\ContainerInterface;

class paramForRepo {
	
	private $container;
	private $versionOnly;
	private $statutActif;
	// compteur et paramètres
	private $params = array();
	private $count = 0;


	/**
	 * constructeur
	 * @param boolean $versionOnly
	 * @param boolean $statutActif
	 */
	public function __construct($versionOnly = true, $statutActif = true) {
		// $this->container = new ContainerInterface();
		$this->resetAll();
	}

	public function resetAll() {
		$this->count = 0;
		$this->params = array();
	}

	public function addParam($champ, $valeur, $entiteExt = null, $champExt = null) {
		$params[$this->count]["champ"] = $champ;
		$params[$this->count]["valeur"] = $valeur;
		$params[$this->count]["entiteExt"] = $entiteExt;
		$params[$this->count]["champExt"] = $champExt;

		$this->count++;
	}

	public function addOrderBy($champ) {
		
	}

	public function getNumberOfParams() {
		return $this->count - 1;
	}

}


?>