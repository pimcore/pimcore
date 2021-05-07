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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Pimcore\Element\MarshallerService;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 */
final class MarshallerLocatorPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $this->processFieldDefinitionMarshallers($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function processFieldDefinitionMarshallers(ContainerBuilder $container)
    {
        $serviceDefinition = $container->getDefinition(MarshallerService::class);
        $this->buildSupportedFielddefinitionMarshaller(
            'query',
            $container,
            $serviceDefinition,
            'field definition marshal',
            'pimcore.dataobject.fielddefinition.marshaller'
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition $definition
     * @param string $type
     * @param string $tag
     * @param string $argument
     */
    private function createLocatorForTaggedServices(
        ContainerBuilder $container,
        Definition $definition,
        string $type,
        string $tag,
        string $argument
    ) {
        $resolvers = $container->findTaggedServiceIds($tag);

        $mapping = [];

        foreach ($resolvers as $id => $tagEntries) {
            foreach ($tagEntries as $tagEntry) {
                if (!isset($tagEntry['id'])) {
                    throw new InvalidDefinitionException(sprintf(
                        'The %s "%s" does not define an ID on the "%s" tag.',
                        $type,
                        $id,
                        $tag
                    ));
                }

                $mapping[$tagEntry['id']] = new Reference($id);
            }
        }

        $serviceLocator = new Definition(ServiceLocator::class, [$mapping]);
        $serviceLocator->setPublic(false);
        $serviceLocator->addTag('container.service_locator');

        $definition->setArgument($argument, $serviceLocator);
    }

    /**
     * @param string $operationType
     * @param ContainerBuilder $container
     * @param Definition $definition
     * @param string $type
     * @param string $tag
     */
    private function buildSupportedFielddefinitionMarshaller(
        $operationType,
        ContainerBuilder $container,
        Definition $definition,
        string $type,
        string $tag
    ) {
        $resolvers = $container->findTaggedServiceIds($tag);

        $mapping = [];

        foreach ($resolvers as $id => $tagEntries) {
            foreach ($tagEntries as $tagEntry) {
                if (!isset($tagEntry['id'])) {
                    throw new InvalidDefinitionException(sprintf(
                        'The %s "%s" does not define an ID on the "%s" tag.',
                        $type,
                        $id,
                        $tag
                    ));
                }

                $key = $tagEntry['key'];
                $mapping[$key] = $id;
            }
        }

        $definition->addMethodCall('setSupportedFieldDefinitionMarshallers', [($mapping)]);
    }
}
