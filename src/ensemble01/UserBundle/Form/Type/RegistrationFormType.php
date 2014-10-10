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
        // $entity = new \ensemble01\UserBundle\Entity\User();
		// add your custom field
		$builder
            ->add('fmlogin', 'text', array(
                'label'     => 'Login FM : ',
                'required'  => true,
                ))
            ->add('fmpass', 'text', array(
                'label'     => 'Pass FM : ',
                'required'  => true,
                ))
        ;
	}

	public function getName() {
		return 'fos_user_registration';
	}

}

?>