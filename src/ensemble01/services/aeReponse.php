<?php
// ensemble01/services/aeReponse.php

namespace ensemble01\services;

use Symfony\Component\DependencyInjection\ContainerInterface;

class aeReponse {

	const NOM_MESSAGES = 'info';
	const NOM_ERRORMESSAGES = 'error';

	protected $container;		// container
	protected $flashBag;		// session
	protected $data = array();	// data

	public function __construct(ContainerInterface $container) {
		$this->container 	= $container;
		$this->flashBag 	= $this->container->get('request')->getSession()->getFlashBag();
		// if(!is_array($data)) $data = array($data);
		// if(!is_array($messages)) $messages = array($messages);
		// if(!is_array($ERRORmessages)) $ERRORmessages = array($ERRORmessages);
		$this->data["data"] = array();
		$this->data["messages"] = array();
		$this->data["ERRORmessages"] = array();

		$this->setResult(true);
		// $this->setMessage($messages);
		// $this->setErrorMessage($ERRORmessages);
		// $this->setResult($result);
	}

	protected function computeData() {
		if($this->hasErrors() === true) $this->setUnvalid();
	}

	// GETTERS

	public function getResult() {
		return $this->data["result"];
	}

	public function getMessages() {
		return $this->data["messages"];
	}

	public function getErrorMessages() {
		return $this->data["ERRORmessages"];
	}

	public function getAllMessages($mix = false) {
		if($mix === false) return array("messages" => $this->data["messages"], "ERRORmessages" => $this->data["ERRORmessages"]);
		return array_merge($this->data["messages"], $this->data["ERRORmessages"]);
	}

	public function getData($nom = null) {
		if(is_string($nom) && isset($this->data["data"][$nom])) {
			$result = $this->data["data"][$nom];
		} else $result = $this->data["data"];
		return $result;
	}

	public function getDataType($nom) {
		if(isset($this->data["data"][$nom])) return gettype($this->data["data"][$nom]);
			else return false;
	}

	public function getDataKeys() {
		return array_keys($this->data["data"]);
	}

	public function isDataKey($key) {
		return (array_key_exists($key, array_keys($this->data["data"])) ? true : false );
	}

	public function getJSONreponse() {
		return json_encode($this->data);
	}

	public function isValid() {
		return ($this->data["result"] === true ? true : false );
	}

	public function hasData() {
		return (count($this->data["data"]) > 0 ? true : false );
	}

	public function hasMessages() {
		return (count($this->data["messages"]) > 0 ? true : false );
	}

	public function hasErrors() {
		return (count($this->data["ERRORmessages"]) > 0 ? true : false );
	}

	// SETTERS

	public function setValid() {
		$this->setResult(true);
		return $this;
	}

	public function setUnvalid() {
		$this->setResult(false);
		return $this;
	}

	public function setResult($result = true) {
		if(!is_bool($result)) $result = false;
		$this->data["result"] = $result;
		return $this;
	}

	public function addMessage($messages) {
		if(!is_array($messages)) $messages = array($messages);
		foreach ($messages as $message) {
			$this->data["messages"][] = $message;
		}
		return $this;
	}

	public function addErrorMessage($ERRORmessages, $unvalidate = true) {
		if(!is_array($ERRORmessages)) $ERRORmessages = array($ERRORmessages);
		foreach ($ERRORmessages as $ERRORmessage) {
			$this->data["ERRORmessages"][] = $ERRORmessage;
		}
		if($unvalidate === true) $this->computeData();
		return $this;
	}

	public function addData($data, $nom = null) {
		if($nom === null) $this->data["data"][] = $data;
			else $this->data["data"][$nom] = $data;
		return $this;
	}

	// AUTRES

	public function putAllMessagesInFlashbag() {
		$this->putMessagesInFlashbag();
		$this->putErrorMessagesInFlashbag();
		return $this;
	}

	public function putErrorMessagesInFlashbag() {
		foreach ($this->data['ERRORmessages'] as $value) {
			$this->flashBag->add(self::NOM_ERRORMESSAGES, $value);
		}
		return $this;
	}

	public function putMessagesInFlashbag() {
		foreach ($this->data['messages'] as $value) {
			$this->flashBag->add(self::NOM_MESSAGES, $value);
		}
		return $this;
	}


}
?>