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

namespace Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler;

use Doctrine\Common\Util\Inflector;
use Pimcore\Extension\Document\Areabrick\AreabrickInterface;
use Pimcore\Extension\Document\Areabrick\Exception\ConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Finder\Finder;

class AreabrickPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $config = $container->getParameter('pimcore.config');

        $areaManagerDefinition = $container->getDefinition('pimcore.area.brick_manager');
        $taggedServices        = $container->findTaggedServiceIds('pimcore.area.brick');

        // keep a list of areas loaded via tags - those classes won't be autoloaded
        $taggedAreas = [];

        foreach ($taggedServices as $id => $tags) {
            $definition    = $container->getDefinition($id);
            $taggedAreas[] = $definition->getClass();


            // tags must define the id attribute which will be used to register the brick
            // e.g. { name: pimcore.area.brick, id: blockquote }
            foreach ($tags as $tag) {
                if (!array_key_exists('id', $tag)) {
                    throw new ConfigurationException(sprintf('Missing "id" attribute on areabrick DI tag for service %s', $id));
                }

                $areaManagerDefinition->addMethodCall('registerService', [$tag['id'], $id]);
            }
        }

        // autoload areas from bundles if not yet defined via service config
        if ($config['documents']['areas']['autoload']) {
            $this->autoloadAreabricks($container, $areaManagerDefinition, $taggedAreas);
        }
    }

    /**
     * To be autoloaded, an area must fulfill the following conditions:
     *
     *  - implement AreabrickInterface
     *  - be situated in a bundle in the sub-namespace Document\Areabrick (can be nested into a deeper namespace)
     *  - the class is not already defined as areabrick through manual config (not included in the tagged results above)
     *
     * Valid examples:
     *
     *  - AppBundle\Document\Areabrick\Foo
     *  - AppBundle\Document\Areabrick\Foo\Bar\Baz
     *
     * @param ContainerBuilder $container
     * @param Definition $areaManagerDefinition
     * @param array $excludedClasses
     */
    protected function autoloadAreabricks(ContainerBuilder $container, Definition $areaManagerDefinition, array $excludedClasses = [])
    {
        $bundles = $container->getParameter('kernel.bundles_metadata');
        foreach ($bundles as $bundleName => $bundleMetadata) {
            $bundleAreas = $this->findBundleBricks($bundleName, $bundleMetadata, $excludedClasses);

            foreach ($bundleAreas as $bundleArea) {
                /** @var \ReflectionClass $reflector */
                $reflector = $bundleArea['reflector'];

                // add brick definition to container
                $container->setDefinition($bundleArea['serviceId'], new Definition($reflector->getName()));

                // register brick on the areabrick manager
                $areaManagerDefinition->addMethodCall('registerService', [
                    $bundleArea['brickId'],
                    $bundleArea['serviceId']
                ]);
            }
        }
    }

    /**
     * Look for classes implementing AreabrickInterface in each bundle's Document\Areabrick sub-namespace
     *
     * @param $name
     * @param $metadata
     * @param array $excludedClasses
     *
     * @return array
     */
    protected function findBundleBricks($name, $metadata, array $excludedClasses = [])
    {
        $directory = implode(DIRECTORY_SEPARATOR, [
            $metadata['path'],
            'Document',
            'Areabrick'
        ]);

        if (!file_exists($directory) || !is_dir($directory)) {
            return [];
        }

        $finder = new Finder();
        $finder
            ->files()
            ->in($directory)
            ->name('*.php');

        $areas = [];
        foreach ($finder as $classPath) {
            $shortClassName = $classPath->getBasename('.php');

            // relative path in bundle path
            $relativePath = str_replace($metadata['path'], '', $classPath->getPathInfo());
            $relativePath = trim($relativePath, DIRECTORY_SEPARATOR);

            // namespace starting from bundle path
            $relativeNamespace = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

            // sub-namespace in Document\Areabrick
            $subNamespace = str_replace('Document\\Areabrick', '', $relativeNamespace);
            $subNamespace = trim($subNamespace, '\\');

            // fully qualified class name
            $className = $metadata['namespace'] . '\\' . $relativeNamespace . '\\' . $shortClassName;

            // do not autoload areas which were already defined as service via config
            if (in_array($className, $excludedClasses)) {
                continue;
            }

            if (class_exists($className)) {
                $reflector = new \ReflectionClass($className);
                if ($reflector->isInstantiable() && $reflector->implementsInterface(AreabrickInterface::class)) {
                    $brickId   = $this->generateBrickId($reflector);
                    $serviceId = $this->generateServiceId($name, $subNamespace, $shortClassName);

                    $areas[] = [
                        'brickId'        => $brickId,
                        'serviceId'      => $serviceId,
                        'bundleName'     => $name,
                        'bundleMetadata' => $metadata,
                        'reflector'      => $reflector,
                    ];
                }
            }
        }

        return $areas;
    }

    /**
     * GalleryTeaserRow -> gallery-teaser-row
     *
     * @param \ReflectionClass $reflector
     *
     * @return string
     */
    protected function generateBrickId(\ReflectionClass $reflector)
    {
        $id = Inflector::tableize($reflector->getShortName());
        $id = str_replace('_', '-', $id);

        return $id;
    }

    /**
     * Generate service ID from bundle name and sub-namespace
     *
     *  - AppBundle\Document\Areabrick\Foo         -> app.area.brick.foo
     *  - AppBundle\Document\Areabrick\Foo\Bar\Baz -> app.area.brick.foo.bar.baz
     *
     * @param $bundleName
     * @param $subNamespace
     * @param $className
     *
     * @return string
     */
    protected function generateServiceId($bundleName, $subNamespace, $className)
    {
        $bundleName = str_replace('Bundle', '', $bundleName);
        $bundleName = Inflector::tableize($bundleName);

        if (!empty($subNamespace)) {
            $subNamespaceParts = [];
            foreach (explode('\\', $subNamespace) as $subNamespacePart) {
                $subNamespaceParts[] = Inflector::tableize($subNamespacePart);
            }

            $subNamespace = implode('.', $subNamespaceParts) . '.';
        } else {
            $subNamespace = '';
        }


        $brickName = Inflector::tableize($className);

        return sprintf('%s.area.brick.%s%s', $bundleName, $subNamespace, $brickName);
    }
}
