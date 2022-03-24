<?php

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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Pimcore\Model\AbstractModel;
use Pimcore\Model\Asset;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\DataObject;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class EntityModelFactoryPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $classes = [];

        // register all models and dao in the /models folder
        $class = new \ReflectionClass(Asset::class);
        $baseDir = dirname($class->getFileName());
        $dir = new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);


        foreach ($iterator as $file) {
            /** @var $file \SplFileInfo */
            if($file->isFile()) {
                $className = preg_replace('@^' . preg_quote($baseDir, '@') . '/(.*)\.php$@', '$1', $file->getPathname());
                $className = 'Pimcore\\Model\\' . str_replace('/', '\\', $className);

                $class = new \ReflectionClass($className);
                if($class->isInterface() || $class->isAbstract() || $class->isTrait()) {
                    continue;
                }

                if($constructor = $class->getConstructor()) {
                    $params = $constructor->getParameters();
                    if(!empty($params)) {
                        continue;
                    }
                }

                if(
                    $class->isSubclassOf(AbstractModel::class) ||
                    $class->isSubclassOf(AbstractDao::class)
                ) {
                    $classes[] = $className;
                }
            }
        }

        // register all data object classes
        $objectClassesFolder = PIMCORE_CLASS_DEFINITION_DIRECTORY;
        $files = glob($objectClassesFolder.'/*.php');

        foreach ($files as $file) {
            $className = \preg_replace('/.*definition_(.*)\.php$/', '$1', $file);
            $classes[] = DataObject::class . '\\' . $className;
        }

        // check for class mappings
        $config = $container->getParameter('pimcore.config');
        foreach($config['models']['class_overrides'] as $source => $target) {
            $source = $this->normalizeName($source);
            $target = $this->normalizeName($target);
            $classes[$source] = $target;
            $classes[] = $target;
        }

        $locatorArguments = [];

        foreach($classes as $serviceId => $class) {
            if(!class_exists($class)) {
                continue;
            }

            $serviceId = is_numeric($serviceId) ? $class : $serviceId;
            $definition = new Definition($class);
            $definition->setPublic(false)
                ->setShared(false)
                ->setAutowired(true)
                ->setAutoconfigured(true);

            $container->setDefinition($serviceId, $definition);
            $locatorArguments[$serviceId] = new Reference($serviceId);
        }


        $builder = $container->getDefinition(\Pimcore\Model\Factory\ContainerBuilder::class);
        $builder->addArgument(ServiceLocatorTagPass::register($container, $locatorArguments));
    }

    private function normalizeName(string $name): string
    {
        return ltrim($name, '\\');
    }
}
