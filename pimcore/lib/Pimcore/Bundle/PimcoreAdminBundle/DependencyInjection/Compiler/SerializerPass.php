<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\DependencyInjection\Compiler;

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
 * @see \Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\SerializerPass
 */
class SerializerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('pimcore_admin.serializer')) {
            return;
        }

        // Looks for all the services tagged "serializer.normalizer" and adds them to the Serializer service
        $normalizers = $this->findAndSortTaggedServices('pimcore_admin.serializer.normalizer', $container);

        if (empty($normalizers)) {
            throw new RuntimeException('You must tag at least one service as "pimcore_admin.serializer.normalizer" to use the Admin Serializer service');
        }
        $container->getDefinition('pimcore_admin.serializer')->replaceArgument(0, $normalizers);

        // Looks for all the services tagged "serializer.encoders" and adds them to the Serializer service
        $encoders = $this->findAndSortTaggedServices('pimcore_admin.serializer.encoder', $container);
        if (empty($encoders)) {
            throw new RuntimeException('You must tag at least one service as "pimcore_admin.serializer.encoder" to use the Admin Serializer service');
        }
        $container->getDefinition('pimcore_admin.serializer')->replaceArgument(1, $encoders);
    }
}
