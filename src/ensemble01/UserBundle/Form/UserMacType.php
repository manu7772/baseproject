<?php

namespace ensemble01\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserMacType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('mactype', 'text', array(
                "required"  => false,
                "label"     => 'Type/Modèle de votre machine'
                ))
            ->add('macmarque', 'entity', array(
                "required"  => false,
                'multiple'  => false,
                "label"     => 'Marque :',
                'class'     => 'ensemble01LaboBundle:marque',
                'property'  => 'nom',
                ))
            // ->add('macmarque', 'choice', array(
            //     "required"  => true,
            //     "label"     => 'Marque de votre machine',
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
            ->add('macnoserie', 'text', array(
                "required"  => false,
                "label"     => 'Numéro de série de votre machine'
                ))
            ->add('macdateachat', 'datepicker2', array( // --> datepicker : élément de formulaire personnalisé passé en service !!!
                "required"  => false,
                "label"     => 'Date d\'achat'
                ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ensemble01\UserBundle\Entity\User'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'fos_user_usermac';
    }
}
