<?php

declare(strict_types=1);

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

namespace Pimcore\Controller\Config;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Provides bundle/controller/action/template selection options which can be
 * used to configure controller + template for documents or static routes.
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
    private $bundleControllers = [];

    /**
     * @var \ReflectionClass[]
     */
    protected $reflectors = [];

    /**
     * @var array
     */
    private $templates;

    /**
     * @var array
     */
    private $templateNamePatterns = [
        '*.php',
        '*.twig'
    ];

    /**
     * @param KernelInterface $kernel
     * @param array $serviceControllers
     */
    public function __construct(KernelInterface $kernel, array $serviceControllers)
    {
        $this->kernel             = $kernel;
        $this->serviceControllers = $serviceControllers;
    }

    /**
     * Returns all eligible bundles
     *
     * @return BundleInterface[]
     */
    public function getBundles(): array
    {
        if (null !== $this->bundles) {
            return $this->bundles;
        }

        $this->bundles = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            if ($this->isValidBundle($bundle)) {
                $this->bundles[$bundle->getName()] = $bundle;
            }
        }

        return $this->bundles;
    }

    /**
     * @param string $name
     *
     * @return BundleInterface|null
     */
    private function getBundle(string $name)
    {
        $bundles = $this->getBundles();

        if (isset($bundles[$name])) {
            return $bundles[$name];
        }
    }

    /**
     * Returns all service controllers and all controllers matching the selected bundle
     *
     * @param string|null $bundleName
     *
     * @return array
     */
    public function getControllers(string $bundleName = null): array
    {
        $controllers = [];
        $classNames  = [];

        foreach ($this->serviceControllers as $id => $className) {
            $controllers[] = '@' . $id;
            $classNames[]  = $className;
        }

        if (null === $bundleName || null === $bundle = $this->getBundle($bundleName)) {
            return $controllers;
        }

        $bundleControllers = $this->findBundleControllers($bundle);

        /** @var \ReflectionClass $controllerReflector */
        foreach ($bundleControllers as $controllerName => $controllerReflector) {
            // controller is already defined as service -> continue
            if (in_array($controllerReflector->getName(), $classNames)) {
                continue;
            }

            $controllers[] = $controllerName;
        }

        return $controllers;
    }

    /**
     * Builds a list of all available actions. If the controller is a service controller (prefixed with @),
     * the bundle will be ignored.
     *
     * @param string $controller
     * @param string|null $bundleName
     *
     * @return array
     */
    public function getActions(string $controller, string $bundleName = null): array
    {
        $reflector = $this->getControllerReflector($controller, $bundleName);

        if (null === $reflector) {
            return [];
        }

        $actions = [];
        foreach ($reflector->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC) as $method) {
            if (preg_match('/^(.*)Action$/', $method->getName())) {
                $actions[] = preg_replace('/Action$/', '', $method->getName());
            }
        }

        return $actions;
    }

    /**
     * Builds a list of all available templates in bundles and in app/Resources/views
     *
     * @return array
     */
    public function getTemplates(): array
    {
        if (null !== $this->templates) {
            return $this->templates;
        }

        $templates = [];

        $appPath = realpath(implode(DIRECTORY_SEPARATOR, [PIMCORE_APP_ROOT, 'Resources', 'views']));
        if ($appPath && file_exists($appPath) && is_dir($appPath)) {
            $templates = array_merge($templates, $this->findTemplates($appPath));
        }

        foreach ($this->getBundles() as $bundle) {
            $bundlePath = realpath(implode(DIRECTORY_SEPARATOR, [$bundle->getPath(), 'Resources', 'views']));
            if ($bundlePath && file_exists($bundlePath) && is_dir($bundlePath)) {
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
     * @param string $controller
     * @param string|null $bundleName
     *
     * @return \ReflectionClass|null
     */
    private function getControllerReflector(string $controller, string $bundleName = null)
    {
        $reflector = null;
        if ($this->isServiceController($controller)) {
            $serviceId = substr($controller, 1);

            if (isset($this->serviceControllers[$serviceId])) {
                return $this->getReflector($this->serviceControllers[$serviceId]);
            }
        } else {
            if (null === $bundleName || null === $bundle = $this->getBundle($bundleName)) {
                return null;
            }

            $controllers = $this->findBundleControllers($bundle);
            if (isset($controllers[$controller])) {
                return $controllers[$controller];
            }
        }

        return $reflector;
    }

    private function isServiceController(string $controller): bool
    {
        return 0 === strpos($controller, '@');
    }

    /**
     * Fetches a className => reflector mapping for all controllers defined in a bundle
     *
     * @param BundleInterface $bundle
     *
     * @return \ReflectionClass[]
     */
    private function findBundleControllers(BundleInterface $bundle): array
    {
        if (isset($this->bundleControllers[$bundle->getName()])) {
            return $this->bundleControllers[$bundle->getName()];
        }

        $controllers = [];
        $reflector   = $this->getReflector($bundle);

        $controllerDirectory = rtrim($bundle->getPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Controller';
        if (!file_exists($controllerDirectory)) {
            $this->bundleControllers[$bundle->getName()] = [];

            return $this->bundleControllers[$bundle->getName()];
        }

        $finder = new Finder();
        $finder
            ->files()
            ->name('*Controller.php')
            ->in($controllerDirectory);

        foreach ($finder as $controllerFile) {
            $relativeClassName = str_replace(['.php', '/'], ['', '\\'], $controllerFile->getRelativePathname());
            $fullClassName     = $reflector->getNamespaceName() . '\\Controller\\' . $relativeClassName;

            if (class_exists($fullClassName)) {
                $controllerReflector = $this->getReflector($fullClassName);
                if ($controllerReflector->isInstantiable()) {
                    $controllerName               = preg_replace('/Controller$/', '', $relativeClassName);
                    $controllers[$controllerName] = $controllerReflector;
                }
            }
        }

        $this->bundleControllers[$bundle->getName()] = $controllers;

        return $this->bundleControllers[$bundle->getName()];
    }

    /**
     * Determines if bundle should be taken into consideration
     *
     * @param BundleInterface $bundle
     *
     * @return bool
     */
    protected function isValidBundle(BundleInterface $bundle): bool
    {
        $reflector = $this->getReflector($bundle);
        if (preg_match('/^(Symfony|Doctrine|Pimcore|Sensio)/', $reflector->getName())) {
            return false;
        }

        return true;
    }

    /**
     * @param string|mixed $object
     *
     * @return \ReflectionClass
     */
    protected function getReflector($object): \ReflectionClass
    {
        $className = null;
        if (is_object($object)) {
            $className = get_class($object);
        } elseif (is_string($object)) {
            $className = $object;

            if (!class_exists($className)) {
                throw new \InvalidArgumentException(sprintf(
                    'Unable to build reflector as class "%s" does not exist',
                    $className
                ));
            }
        } else {
            throw new \InvalidArgumentException('Expected either class name as string or an object to build a ReflectionClass');
        }

        if (!isset($this->reflectors[$className])) {
            $this->reflectors[$className] = new \ReflectionClass($className);
        }

        return $this->reflectors[$className];
    }
}
