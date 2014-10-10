<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ensemble01\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Validator\Constraint\UserPassword as OldUserPassword;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class ProfileFormType extends AbstractType
{
    private $class;

    /**
     * @param string $class The User class name
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        // if (class_exists('Symfony\Component\Security\Core\Validator\Constraints\UserPassword')) {
        //     $constraint = new UserPassword();
        // } else {
        //     // Symfony 2.1 support with the old constraint class
        //     $constraint = new OldUserPassword();
        // }

        $this->buildUserForm($builder, $options);

        $entity = new \ensemble01\UserBundle\Entity\User();

        $builder
            // ->add('current_password', 'password', array(
            //     'label' => 'form.current_password',
            //     'translation_domain' => 'FOSUserBundle',
            //     'mapped' => false,
            //     'constraints' => $constraint,
            //     ))
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

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'ensemble01\UserBundle\Entity\User',
            'intention'  => 'profile',
        ));
    }

    public function getName()
    {
        return 'fos_user_profile';
    }

    /**
     * Builds the embedded form representing the user.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    protected function buildUserForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('username', null, array(
                'label' => 'form.username',
                'translation_domain' => 'FOSUserBundle'
                ))
            ->add('email', 'email', array(
                'label' => 'form.email',
                'translation_domain' => 'FOSUserBundle'
                ))
        ;
    }
}
?>