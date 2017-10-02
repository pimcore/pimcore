<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\HttpKernel\BundleLocator;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class BundleLocator implements BundleLocatorInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var array
     */
    private $bundleCache = [];

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @inheritDoc
     */
    public function getBundle($class): BundleInterface
    {
        return $this->getBundleForClass($class);
    }

    /**
     * @inheritDoc
     */
    public function getBundlePath($class): string
    {
        return $this->getBundleForClass($class)->getPath();
    }

    /**
     * @deprecated Use getBundlePath instead
     */
    public function resolveBundlePath($class)
    {
        return $this->getBundlePath($class);
    }

    private function getBundleForClass($class): BundleInterface
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (!isset($this->bundleCache[$class])) {
            $this->bundleCache[$class] = $this->findBundleForClass($class);
        }

        return $this->bundleCache[$class];
    }

    private function findBundleForClass(string $class): BundleInterface
    {
        // see TemplateGuesser from SensioFrameworkExtraBundle
        $reflectionClass = new \ReflectionClass($class);
        $bundles         = $this->kernel->getBundles();

        do {
            $namespace = $reflectionClass->getNamespaceName();

            foreach ($bundles as $bundle) {
                if (0 === strpos($namespace, $bundle->getNamespace())) {
                    return $bundle;
                }
            }

            $reflectionClass = $reflectionClass->getParentClass();
        } while ($reflectionClass);

        throw new NotFoundException(sprintf('Unable to find bundle for class %s', $class));
    }
}
