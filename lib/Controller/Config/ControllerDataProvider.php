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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Controller\Config;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Provides bundle/controller/action/template selection options which can be
 * used to configure controller + template for documents or static routes.
 *
 * @internal
 */
class ControllerDataProvider
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * id -> class mapping array of controllers defined as services
     *
     * @var array
     */
    private $serviceControllers;

    /**
     * @var array
     */
    private $bundles;

    /**
     * @var array
     */
    private $templates;

    /**
     * @var array
     */
    private $templateNamePatterns = [
        '*.twig',
    ];

    /**
     * @param KernelInterface $kernel
     * @param array $serviceControllers
     */
    public function __construct(KernelInterface $kernel, array $serviceControllers)
    {
        $this->kernel = $kernel;
        $this->serviceControllers = $serviceControllers;
    }

    /**
     * Returns all eligible bundles
     *
     * @return BundleInterface[]
     */
    private function getBundles(): array
    {
        if (null !== $this->bundles) {
            return $this->bundles;
        }

        $this->bundles = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            if ($this->isValidNamespace(get_class($bundle))) {
                $this->bundles[$bundle->getName()] = $bundle;
            }
        }

        return $this->bundles;
    }

    /**
     * @return array
     *
     * @throws \ReflectionException
     */
    public function getControllerReferences(): array
    {
        $controllerReferences = [];

        foreach ($this->serviceControllers as $id => $className) {
            // exclude controllers from known core namespaces
            if (!$this->isValidNamespace($className)) {
                continue;
            }

            $reflector = new \ReflectionClass($className);
            foreach ($reflector->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC) as $method) {
                if (preg_match('/^(.*)Action$/', $method->getName())) {
                    $controllerReferences[] = sprintf('%s::%s', $id, $method->getName());
                }
            }
        }

        $bundles = $this->getBundles();
        foreach ($bundles as $bundle) {
            $controllerDirectory = rtrim($bundle->getPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Controller';
            if (!file_exists($controllerDirectory)) {
                continue;
            }

            $bundleReflector = new \ReflectionClass(get_class($bundle));

            $finder = new Finder();
            $finder
                ->files()
                ->name('*Controller.php')
                ->in($controllerDirectory);

            foreach ($finder as $controllerFile) {
                $relativeClassName = str_replace(['.php', '/'], ['', '\\'], $controllerFile->getRelativePathname());
                $fullClassName = $bundleReflector->getNamespaceName() . '\\Controller\\' . $relativeClassName;

                if (class_exists($fullClassName)) {
                    $controllerReflector = new \ReflectionClass($fullClassName);
                    if ($controllerReflector->isInstantiable()) {
                        foreach ($controllerReflector->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC) as $method) {
                            if (preg_match('/^(.*)Action$/', $method->getName())) {
                                $controllerReferences[] = sprintf('%s::%s', $fullClassName, $method->getName());
                            }
                        }
                    }
                }
            }
        }

        $controllerReferences = array_unique($controllerReferences);
        sort($controllerReferences);

        return $controllerReferences;
    }

    /**
     * Builds a list of all available templates in bundles, in app/Resources/views, and Symfony locations
     *
     * @return array
     */
    public function getTemplates(): array
    {
        if (null !== $this->templates) {
            return $this->templates;
        }

        $templates = [];

        $symfonyPath = realpath(implode(DIRECTORY_SEPARATOR, [PIMCORE_PROJECT_ROOT, 'templates']));
        if ($symfonyPath && is_dir($symfonyPath)) {
            $templates = array_merge($templates, $this->findTemplates($symfonyPath));
        }

        foreach ($this->getBundles() as $bundle) {
            $bundlePath = realpath(implode(DIRECTORY_SEPARATOR, [$bundle->getPath(), 'Resources', 'views']));
            if ($bundlePath && is_dir($bundlePath)) {
                $templates = array_merge($templates, $this->findTemplates($bundlePath, $bundle->getName()));
            }
        }

        $this->templates = $templates;

        return $this->templates;
    }

    /**
     * Finds templates in a certain path. If bundleName is null, the global notation (app/Resources/views)
     * will be used.
     *
     * @param string $path
     * @param string|null $bundleName
     *
     * @return array
     */
    private function findTemplates(string $path, string $bundleName = null): array
    {
        $fs = new Filesystem();

        $finder = new Finder();
        $finder
            ->files()
            ->in($path);

        foreach ($this->templateNamePatterns as $namePattern) {
            $finder->name($namePattern);
        }

        $templates = [];
        foreach ($finder as $file) {
            $relativePath = $fs->makePathRelative($file->getRealPath(), $path);

            $relativeDir = str_replace($file->getFilename(), '', $relativePath);
            $relativeDir = trim($relativeDir, DIRECTORY_SEPARATOR);
            $relativeDir = trim($relativeDir, '/');

            $template = null;

            if (null === $bundleName) {
                if (empty($relativeDir)) {
                    $template = $file->getFilename();
                } else {
                    $template = sprintf('%s/%s', $relativeDir, $file->getFilename());
                }
            } else {
                $template = sprintf('%s:%s:%s', $bundleName, $relativeDir, $file->getFilename());
            }

            if (!empty($template)) {
                $templates[] = $template;
            }
        }

        return $templates;
    }

    /**
     * Checks if bundle/controller namespace is not excluded (all core bundles should be excluded here)
     *
     * @param string $namespace
     *
     * @return bool
     */
    protected function isValidNamespace(string $namespace): bool
    {
        if (preg_match('/^(Symfony|Doctrine|Pimcore|Sensio)/', $namespace)) {
            return false;
        }

        return true;
    }
}
