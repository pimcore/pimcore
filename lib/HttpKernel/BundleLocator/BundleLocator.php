<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\HttpKernel\BundleLocator;

use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class BundleLocator implements BundleLocatorInterface
{
    private KernelInterface $kernel;

    private array $bundleCache = [];

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getBundle(object|string $class): BundleInterface
    {
        return $this->getBundleForClass($class);
    }

    public function getBundlePath(object|string $class): string
    {
        return $this->getBundleForClass($class)->getPath();
    }

    /**
     * @throws ReflectionException
     */
    private function getBundleForClass(object|string $class): BundleInterface
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (!isset($this->bundleCache[$class])) {
            $this->bundleCache[$class] = $this->findBundleForClass($class);
        }

        return $this->bundleCache[$class];
    }

    /**
     * @throws ReflectionException
     */
    private function findBundleForClass(string $class): BundleInterface
    {
        // see TemplateGuesser from SensioFrameworkExtraBundle
        $reflectionClass = new ReflectionClass($class);
        $bundles = $this->kernel->getBundles();

        do {
            $namespace = $reflectionClass->getNamespaceName();

            foreach ($bundles as $bundle) {
                if (str_starts_with($namespace, $bundle->getNamespace())) {
                    return $bundle;
                }
            }

            $reflectionClass = $reflectionClass->getParentClass();
        } while ($reflectionClass);

        throw new NotFoundException(sprintf('Unable to find bundle for class %s', $class));
    }
}
