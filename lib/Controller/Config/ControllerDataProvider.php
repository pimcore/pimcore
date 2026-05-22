<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Controller\Config;

use Pimcore\Controller\Config\Bundle\BundleProvider;
use Pimcore\Controller\Config\Template\TemplateProviderInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;

/**
 * Provides bundle/controller/action/template selection options which can be
 * used to configure controller + template for documents or static routes.
 *
 * @internal
 */
class ControllerDataProvider
{
    private BundleProvider $bundleProvider;

    /**
     * id -> class mapping array of controllers defined as services
     */
    private array $serviceControllers;

    /**
     * @var iterable<TemplateProviderInterface>
     */
    private iterable $templateProviders;

    private ?array $templates = null;

    /**
     * @param iterable<TemplateProviderInterface> $templateProviders
     */
    public function __construct(
        BundleProvider $bundleProvider,
        array $serviceControllers,
        iterable $templateProviders,
    ) {
        $this->bundleProvider = $bundleProvider;
        $this->serviceControllers = $serviceControllers;
        $this->templateProviders = $templateProviders;
    }

    /**
     * @throws ReflectionException
     */
    public function getControllerReferences(): array
    {
        $controllerReferences = [];

        foreach ($this->serviceControllers as $id => $className) {
            // exclude controllers from known core namespaces
            if (!$this->bundleProvider->isValidNamespace($className)) {
                continue;
            }

            $reflector = new ReflectionClass($className);
            foreach ($reflector->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC) as $method) {
                if (preg_match('/^(.*)Action$/', $method->getName())) {
                    $controllerReferences[] = sprintf('%s::%s', $id, $method->getName());
                }
            }
        }

        foreach ($this->bundleProvider->getBundles() as $bundle) {
            $controllerDirectory = rtrim($bundle->getPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Controller';
            if (!file_exists($controllerDirectory)) {
                continue;
            }

            $bundleReflector = new ReflectionClass(get_class($bundle));

            $finder = new Finder();
            $finder
                ->files()
                ->name('*Controller.php')
                ->in($controllerDirectory);

            foreach ($finder as $controllerFile) {
                $relativeClassName = str_replace(['.php', '/'], ['', '\\'], $controllerFile->getRelativePathname());
                $fullClassName = $bundleReflector->getNamespaceName() . '\\Controller\\' . $relativeClassName;

                if (class_exists($fullClassName)) {
                    $controllerReflector = new ReflectionClass($fullClassName);
                    if ($controllerReflector->isInstantiable()) {
                        foreach ($controllerReflector->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC) as $method) {
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
     * Builds the list of selectable templates from given {@see TemplateProviderInterface} instances.
     *
     * The resulting list is deduplicated.
     *
     * @return string[]
     */
    public function getTemplates(): array
    {
        if (null !== $this->templates) {
            return $this->templates;
        }

        $templates = [];
        foreach ($this->templateProviders as $templateProvider) {
            $templates[] = $templateProvider->getTemplates();
        }

        return $this->templates = array_values(array_unique(array_merge(...$templates)));
    }
}
