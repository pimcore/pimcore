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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * Adds all services with the tags "pimcore.serializer.encoder" and "pimcore.serializer.normalizer" as
 * encoders and normalizers to the pimcore.serializer service.
 *
 * This does exactly the same as the framework serializer pass, but adds encoders/normalizers to our custom pimcore
 * serializer.
 *
 * @see \Symfony\Component\Serializer\Serializer
 *
 * @internal
 */
final class SerializerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('Pimcore\\Serializer\\Serializer')) {
            return;
        }

        $definition = $container->getDefinition('Pimcore\\Serializer\\Serializer');

        // Looks for all the services tagged "serializer.normalizer" and adds them to the Serializer service
        $normalizers = $this->findAndSortTaggedServices('pimcore.serializer.normalizer', $container);

        if (empty($normalizers)) {
            throw new RuntimeException('You must tag at least one service as "pimcore.serializer.normalizer" to use the Pimcore Serializer service');
        }

        // Looks for all the services tagged "serializer.encoders" and adds them to the Serializer service
        $encoders = $this->findAndSortTaggedServices('pimcore.serializer.encoder', $container);
        if (empty($encoders)) {
            throw new RuntimeException('You must tag at least one service as "pimcore.serializer.encoder" to use the Pimcore Serializer service');
        }

        $definition->setArguments([
            '$normalizers' => $normalizers,
            '$encoders' => $encoders,
        ]);
    }
}
