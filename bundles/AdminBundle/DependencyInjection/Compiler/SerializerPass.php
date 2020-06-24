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

namespace Pimcore\Bundle\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * Adds all services with the tags "pimcore_admin.serializer.encoder" and "pimcore_admin.serializer.normalizer" as
 * encoders and normalizers to the Admin Serializer service.
 *
 * This does exactly the same as the framework serializer pass, but adds encoders/normalizers to our custom admin
 * serializer.
 *
 * @see \Symfony\Component\Serializer\Serializer
 */
class SerializerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('pimcore_admin.serializer')) {
            return;
        }

        $definition = $container->getDefinition('pimcore_admin.serializer');

        // Looks for all the services tagged "serializer.normalizer" and adds them to the Serializer service
        $normalizers = $this->findAndSortTaggedServices('pimcore_admin.serializer.normalizer', $container);

        if (empty($normalizers)) {
            throw new RuntimeException('You must tag at least one service as "pimcore_admin.serializer.normalizer" to use the Admin Serializer service');
        }

        // Looks for all the services tagged "serializer.encoders" and adds them to the Serializer service
        $encoders = $this->findAndSortTaggedServices('pimcore_admin.serializer.encoder', $container);
        if (empty($encoders)) {
            throw new RuntimeException('You must tag at least one service as "pimcore_admin.serializer.encoder" to use the Admin Serializer service');
        }

        $definition->setArguments([
            '$normalizers' => $normalizers,
            '$encoders' => $encoders,
        ]);
    }
}
