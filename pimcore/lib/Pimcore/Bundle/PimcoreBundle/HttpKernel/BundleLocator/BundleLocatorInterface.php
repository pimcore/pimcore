<?php
namespace Pimcore\Bundle\PimcoreBundle\HttpKernel\BundleLocator;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

interface BundleLocatorInterface
{
    /**
     * Loads bundle for a class name. Returns the AppBundle for AppBundle\Controller\FooController
     *
     * @param string $className
     * @return BundleInterface
     */
    public function getBundle($className);

    /**
     * Resolves bundle directory from a class name.
     * AppBundle\Controller\FooController returns src/AppBundle
     *
     * @param string $className
     * @return string
     */
    public function resolveBundlePath($className);
}
