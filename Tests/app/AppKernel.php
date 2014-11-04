<?php

/*
 * Symfony Test Edition
 * ====================
 * Stripped down edition of Symfony2 for executing bundle tests.
 */

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new CourseHero\SymfonyCronBundle\CourseHeroSymfonyCronBundle(), // MODIFIED FROM ORIGINAL
        );

        // This small hack allows to register custom
        // bundles required for executing tests. Make sure
        // that registerBundles function returns an array.
        if (function_exists('registerBundles')) {
            $bundles = array_merge($bundles, registerBundles());
        }

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');

        // This small hack allows to register custom
        // configuration files for executing tests.
        if (function_exists('registerContainerConfiguration')) {
            registerContainerConfiguration($loader);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return sys_get_temp_dir().'/SymfonyTestEdition/cache';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return sys_get_temp_dir().'/SymfonyTestEdition/logs';
    }
}
