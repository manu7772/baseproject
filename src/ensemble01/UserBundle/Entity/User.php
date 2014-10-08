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
	* @ORM\Column(name="preferences", type="array", nullable=true)
	*/
	private $preferences;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="nom", type="string", length=100, nullable=true, unique=false)
	 */
	private $nom;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="fmlogin", type="string", length=100, nullable=true, unique=false)
	 */
	private $fmlogin;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="fmlpass", type="string", length=100, nullable=true, unique=false)
	 */
	private $fmlpass;


	public function __construct() {
		parent::__construct();

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
	 * Set nom
	 *
	 * @param string $nom
	 * @return User
	 */
	public function setNom($nom) {
		$this->nom = $nom;
	
		return $this;
	}

	/**
	 * Get nom
	 *
	 * @return string
	 */
	public function getSom() {
		return $this->nom;
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
	 * Set fmlpass
	 *
	 * @param string $fmlpass
	 * @return User
	 */
	public function setFmlpass($fmlpass) {
		$this->fmlpass = $fmlpass;
	
		return $this;
	}

	/**
	 * Get fmlpass
	 *
	 * @return string 
	 */
	public function getfmlpass() {
		return $this->fmlpass;
	}



}