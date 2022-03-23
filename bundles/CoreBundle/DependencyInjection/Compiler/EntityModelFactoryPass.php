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

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Profiler\Profiler;

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
        $classes = [
            Asset::class,
            Asset\Archive::class,
            Asset\Audio::class,
            Asset\Folder::class,
            Asset\Image::class,
            Asset\Text::class,
            Asset\Unknown::class,
            Asset\Video::class,

            Document::class,
            Document\Email::class,
            Document\Folder::class,
            Document\Hardlink::class,
            Document\Link::class,
            Document\Newsletter::class,
            Document\Page::class,
            Document\Printcontainer::class,
            Document\Printpage::class,
            Document\Snippet::class,

            DataObject\Folder::class,
        ];

        $objectClassesFolder = PIMCORE_CLASS_DEFINITION_DIRECTORY;
        $files = glob($objectClassesFolder.'/*.php');

        foreach ($files as $file) {
            $className = \preg_replace('/.*definition_(.*)\.php$/', '$1', $file);
            $classes[] = DataObject::class . '\\' . $className;
        }

        $config = $container->getParameter('pimcore.config');
        foreach($config['models']['class_overrides'] as $source => $target) {
            $source = $this->normalizeName($source);
            $target = $this->normalizeName($target);
            $classes[$source] = $target;
            $classes[] = $target;
        }

        foreach($classes as $serviceId => $class) {

            if(!class_exists($class)) {
                continue;
            }

            $serviceId = is_numeric($serviceId) ? $class : $serviceId;
            $definition = new Definition($class);
            $definition->setPublic(true)
                ->setShared(false)
                ->setAutowired(true)
                ->setAutoconfigured(true);

            $container->setDefinition($serviceId, $definition);
        }
    }

    private function normalizeName(string $name): string
    {
        return ltrim($name, '\\');
    }
}
