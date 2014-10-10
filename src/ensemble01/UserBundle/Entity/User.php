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



}