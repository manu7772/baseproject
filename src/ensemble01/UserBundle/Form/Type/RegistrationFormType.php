<?php
// src/ensemble01/UserBundle/Form/Type/RegistrationFormType.php

namespace ensemble01\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
// use ensemble01\UserBundle\Form\UserMacType;

class RegistrationFormType extends BaseType {

    public function __construct($class) {
    	parent::__construct($class);
    }

	public function buildForm(FormBuilderInterface $builder, array $options) {
		parent::buildForm($builder, $options);
        $entity = new \ensemble01\UserBundle\Entity\User();
		// add your custom field
		$builder
            ->add('nom', 'text', array(
                'label'     => 'Nom : ',
                'required'  => false,
                ))
            ->add('prenom', 'text', array(
                'label'     => 'Prénom : ',
                'required'  => false,
                ))
            ->add('tel', 'text', array(
                'label'     => 'Téléphone : ',
                'required'  => false,
                ))
            ->add('adresse', 'textarea', array(
                "required"  => false,
                "label"     => 'Adresse : '
                ))
            ->add('cp', 'text', array(
                "required"  => false,
                "label"     => 'Code Postal : '
                ))
            ->add('ville', 'text', array(
                "required"  => false,
                "label"     => 'Ville : '
                ))
            // ->add('mac', new UserMacType(), array(
            //     "required"  => false,
            //     "label"     => "Vous possédez une machine à coudre ?"
            //     ))
            ->add('mactype', 'text', array(
                "required"  => false,
                "label"     => 'Type/Modèle de machine : '
                ))
            ->add('marque', 'entity', array(
                "required"  => false,
                'multiple'  => false,
                "label"     => 'Marque : ',
                'class'     => 'ensemble01LaboBundle:marque',
                'property'  => 'nom',
                ))
            // ->add('macmarque', 'choice', array(
            //     "required"  => false,
            //     'multiple'  => false,
            //     "label"     => 'Marque :',
            //     "choices"   => array(
            //         "Singer"    => "Singer",
            //         "Janome"    => "Janome",
            //         "Brother"   => "Brother",
            //         "Bernina"   => "Bernina",
            //         "PFAFF"     => "PFAFF",
            //         "Elna"      => "Elna",
            //         "Autre"     => "Autre"
            //         )
            //     ))
            ->add('livraison', 'choice', array(
                "required"  => true,
                'multiple'  => false,
                "label"     => 'Mode de livraison : ',
                "choices"   => $entity->getModeslivraison()
                ))
            ->add('macnoserie', 'text', array(
                "required"  => false,
                "label"     => 'Numéro de série : '
                ))
            ->add('macdateachat', 'datepicker2alldates', array( // --> datepicker : élément de formulaire personnalisé passé en service !!!
                "required"  => false,
                "label"     => 'Date d\'achat : '
                ))
            ->add('magasin', 'entity', array(
                "required"  => true,
                'class'     => 'ensemble01LaboBundle:magasin',
                'property'  => 'nomformenu',
                'multiple'  => false,
                "label"     => 'Boutique référente : ',
                "query_builder" => function(\ensemble01\LaboBundle\Entity\magasinRepository $magasin) {
                    return $magasin->findNomformenu();
                    }
                ))
        ;
	}

	public function getName() {
		return 'ensemble01_user_registration';
	}

}

?>