<?php
// src/ensemble01/UserBundle/Entity/User.php
 
namespace ensemble01\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="User")
 * @ORM\Entity(repositoryClass="ensemble01\UserBundle\Entity\UserRepository")
 */
class User extends BaseUser {

	private $modeslivraison;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="fmlogin", type="string", length=100, nullable=true, unique=false)
	 */
	private $fmlogin;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="fmpass", type="string", length=100, nullable=true, unique=false)
	 */
	private $fmpass;

	/**
	 * @var string
	 * 
	 * @ORM\Column(name="fmselection", type="text", nullable=true, unique=false)
	 */
	private $fmselection;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="dateMaj", type="datetime", nullable=true)
	 */
	private $dateModifSelection;

	/**
	 * @var string
	 * 
	 * @ORM\Column(name="dtselection", type="text", nullable=true, unique=false)
	 */
	private $dtselection;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="dateDtMaj", type="datetime", nullable=true)
	 */
	private $dateModifDtSelection;


	public function __construct() {
		parent::__construct();
		$this->fmselection = null;
		$this->dtselection = null;
		$this->dateModifSelection = new \Datetime();
		$this->dateModifDtSelection = new \Datetime();
	}


	/**
	 * Get id
	 *
	 * @return integer 
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set fmlogin
	 *
	 * @param string $fmlogin
	 * @return User
	 */
	public function setFmlogin($fmlogin) {
		$this->fmlogin = $fmlogin;
		return $this;
	}

	/**
	 * Get fmlogin
	 *
	 * @return string 
	 */
	public function getFmlogin() {
		return $this->fmlogin;
	}

	/**
	 * Set fmpass
	 *
	 * @param string $fmpass
	 * @return User
	 */
	public function setFmpass($fmpass) {
		$this->fmpass = $fmpass;
		return $this;
	}

	/**
	 * Get fmpass
	 *
	 * @return string 
	 */
	public function getFmpass() {
		return $this->fmpass;
	}

	/**
	 * Set fmselection
	 *
	 * @param array $fmselection
	 * @return User
	 */
	public function setFmselection($fmselection = null) {
		if(is_array($fmselection)) {
			$this->fmselection = serialize($fmselection);
		} else {
			$this->fmselection = $fmselection;
		}
		$this->setDateModifSelection();
		return $this;
	}

	/**
	 * Get fmselection
	 *
	 * @return array
	 */
	public function getFmselection() {
		$a = null;
		if(is_string($this->fmselection)) {
			$a = unserialize($this->fmselection);
			if($a === null) $a = $this->fmselection;
		}
		return $a;
	}

	/**
	 * Set dateModifSelection
	 *
	 * @return User
	 */
	public function setDateModifSelection() {
		$this->dateModifSelection = new \Datetime();
		return $this;
	}

	/**
	 * Get dateModifSelection
	 *
	 * @return Datetime 
	 */
	public function getDateModifSelection() {
		return $this->dateModifSelection;
	}


	/**
	 * Set dtselection
	 *
	 * @param array $dtselection
	 * @return User
	 */
	public function setDtselection($dtselection = null) {
		if(is_array($dtselection)) {
			$this->dtselection = serialize($dtselection);
		} else return false;
		$this->verifDtselection();
		$this->setDateModifDtSelection();
		return $this;
	}

	/**
	 * Add dtselection
	 *
	 * @param string $name
	 * @param mixed $data
	 * @return User / boolean false
	 */
	public function addDtselection($name, $data, $replace = true) {
		if(is_string($name) && ($name."" != "")) {
			$a = $this->getDtselection();
			if(!isset($a[$name]) || $replace === true) $a[$name] = $data;
			$this->setDtselection($a);
		} else return false;
		return $this;
	}

	/**
	 * Add dtselection avec sous-array ID
	 *
	 * @param string $name
	 * @param mixed $data
	 * @return User / boolean false
	 */
	public function addDtselection_withID($name, $id, $data, $replace = true) {
		if(is_string($name) && is_string($id) && ($name."" != "") && ($id."" != "")) {
			$a = $this->getDtselection();
			if(!isset($a[$name])) $a[$name] = array();
			if(!isset($a[$name][$id]) || $replace === true) $a[$name][$id] = $data;
			$this->setDtselection($a);
		} else return false;
		return $this;
	}

	/**
	 * Remove dtselection
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function removeDtselection($name) {
		if(is_string($name)) {
			$a = $this->getDtselection();
			if(isset($a[$name])) {
				unset($a[$name]);
				$this->setDtselection($a);
				return true;
			} else return false;
		} else return false;
	}

	/**
	 * Remove ALL dtselection
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function removeAllDtselection() {
		$this->setDtselection(array());
	}

	/**
	 * Vérifie l'intégrité des informations sur Dtselection
	 *
	 * @return User
	 */
	public function verifDtselection() {
		$a = $this->getDtselection();
		if(is_array($a)) {
			$b = array();
			foreach ($a as $key => $value) {
				if(($key."") != "" && $value !== null) {
					$b[$key] = $value;
				}
			}
			$this->dtselection = serialize($b);
			unset($b);
		}
		unset($a);
		return $this;
	}

	/**
	 * Get dtselection
	 *
	 * @param string $name
	 * @return array
	 */
	public function getDtselection() {
		$a = null;
		if(is_string($this->dtselection)) {
			$a = unserialize($this->dtselection);
			if($a === null) $a = $this->dtselection;
		}
		return $a;
	}

	/**
	 * Set dateModifDtSelection
	 *
	 * @return User
	 */
	public function setDateModifDtSelection() {
		$this->dateModifDtSelection = new \Datetime();
		return $this;
	}

	/**
	 * Get dateModifDtSelection
	 *
	 * @return Datetime 
	 */
	public function getDateModifDtSelection() {
		return $this->dateModifDtSelection;
	}



}