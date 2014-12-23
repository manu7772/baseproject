<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            // site bundle
            new ensemble01\siteBundle\ensemble01siteBundle(),
            // labo bundle
            new labo\Bundle\TestmanuBundle\LaboTestmanuBundle(),
            new ensemble01\LaboBundle\ensemble01LaboBundle(),
            // filemaker bundle
            new filemakerBundle\filemakerBundle(),
            new ensemble01\filemakerBundle\ensemble01filemakerBundle(),
            // user bundle
            new FOS\UserBundle\FOSUserBundle(),
            new ensemble01\UserBundle\ensemble01UserBundle(),
            // stof extensions
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
       );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
