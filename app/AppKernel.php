<?php

class AppKernel extends \Pimcore\Kernel
{
    public function registerBundles()
    {
        $bundles = array_merge(parent::registerBundles(), [
            new \AppBundle\AppBundle()
        ]);

        return $bundles;
    }
}
