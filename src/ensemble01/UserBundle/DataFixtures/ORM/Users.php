<?php

namespace ensemble01\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
// container
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
// Entité
use ensemble01\UserBundle\Entity\User;

class Users extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface {
	private $ord			= 9;			// Ordre de chargement fixtures
	private $entity			= "User";		// nom de l'entité
	private $bundle			= "User";		// Bundle de l'entité
	private $container;
	private $manager;

	public function getOrder() { return $this->ord; } // l'ordre dans lequel les fichiers sont chargés

	/**
	 * {@inheritDoc}
	 */
	public function setContainer(ContainerInterface $container = null) {
		$this->container = $container;
	}

	public function load(ObjectManager $manager) {
		// Remise à zéro de l'auto-incrément
		$this->manager = $manager;
		$connection = $this->manager->getConnection();
		$connection->exec("ALTER TABLE ".$this->entity." AUTO_INCREMENT = 1;");

		$person = array(
			array(
				'setUsername'	=> "manu7772",
				'setEmail'		=> "manu7772@gmail.com",
				'setEnabled'	=> true,
				'setRoles'		=> array("ROLE_USER"),
				'getSalt'		=> md5(uniqid()),
				'setPassword'	=> "azetyu123"
				),
			array(
				'setUsername'	=> "admin7772",
				'setEmail'		=> "manu7772@free.com",
				'setEnabled'	=> true,
				'setRoles'		=> array("ROLE_EDITOR"),
				'getSalt'		=> md5(uniqid()),
				'setPassword'	=> "admin"
				),
			array(
				'setUsername'	=> "sadmin",
				'setEmail'		=> "emmanuel@aequation-webdesign.fr",
				'setEnabled'	=> true,
				'setRoles'		=> array("ROLE_SUPER_ADMIN"),
				'getSalt'		=> md5(uniqid()),
				'setPassword'	=> "sadmin"
				),
			// array(
			// 	'setUsername'	=> "andré",
			// 	'setEmail'		=> "amastroiannakis@gmail.com",
			// 	'setEnabled'	=> false,
			// 	'setRoles'		=> array("ROLE_USER"),
			// 	'getSalt'		=> md5(uniqid()),
			// 	'setPassword'	=> "andré"
			// 	),
			// array(
			// 	'setUsername'	=> "am-admin",
			// 	'setEmail'		=> "andre.mastroiannakis@gmail.com",
			// 	'setEnabled'	=> false,
			// 	'setRoles'		=> array("ROLE_ADMIN"),
			// 	'getSalt'		=> md5(uniqid()),
			// 	'setPassword'	=> "admin"
			// 	),
			array(
				'setUsername'	=> "charlotte",
				'setEmail'		=> "charlottetetau@gmail.com",
				'setEnabled'	=> true,
				'setRoles'		=> array("ROLE_ADMIN"),
				'getSalt'		=> md5(uniqid()),
				'setPassword'	=> "tetau"
				),
			array(
				'setUsername'	=> "philippe",
				'setEmail'		=> "philippe.leruth@singer-distrib.com",
				'setEnabled'	=> true,
				'setRoles'		=> array("ROLE_ADMIN"),
				'getSalt'		=> md5(uniqid()),
				'setPassword'	=> "leruth"
				),
			array(
				'setUsername'	=> "florent",
				'setEmail'		=> "florent2108@hotmail.fr",
				'setEnabled'	=> true,
				'setRoles'		=> array("ROLE_USER"),
				'getSalt'		=> md5(uniqid()),
				'setPassword'	=> "duj"
				),
		);

		$user = array();
		foreach($person as $i => $pers) {
			$user[$i] = new User;
			foreach($pers as $att => $val) {
				if($att == "setPassword") {
					$encoder = $this->container->get('security.encoder_factory')->getEncoder($user[$i]);
					$user[$i]->setPassword($encoder->encodePassword($val, $user[$i]->getSalt()));
				} else {
					$user[$i]->$att($val);
				}
			}

			$manager->persist($user[$i]);
		}

		$manager->flush();
	}

}
