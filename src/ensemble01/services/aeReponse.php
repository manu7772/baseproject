<?php
// labo/ensemble01/services/aeReponse.php

namespace ensemble01\services;

class aeReponse {

	private $data = array();

	public function __construct($result, $data = null, $message = "") {
		$this->data["result"] 	= $result;
		$this->data["message"] 	= $message;
		$this->data["data"] 	= $data;
	}

	// GETTERS

	public function getResult() {
		return $this->data["result"];
	}

	public function getMessage() {
		return $this->data["message"];
	}

	public function getData() {
		return $this->data["data"];
	}

	public function getDataType() {
		return gettype($this->data["data"]);
	}

	public function getJSONreponse() {
		return json_encode($this->data);
	}

	// SETTERS

	public function setResult($result) {
		if(!is_bool($result)) $result = false;
		$this->data["result"] = $result;
		return $this;
	}

	public function setMessage($message = null) {
		$this->data["message"] = $message;
		return $this;
	}

	public function setData($data = null) {
		$this->data["data"] = $data;
		return $this;
	}



}
?>