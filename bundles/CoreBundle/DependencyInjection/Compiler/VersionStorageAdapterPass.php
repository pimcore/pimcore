<?php

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Pimcore\Model\Version\Adapter\DelegateVersionStorageAdapter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class VersionStorageAdapterPass implements CompilerPassInterface
{
    const TAG_NAME = 'pimcore.version.storage.adapter';

    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);

        $adapters = [];
        foreach ($taggedServices as $service => $tags) {
            foreach ($tags as $tag) {
                $adapters[$tag['storageType']] = array_merge($tag, ['class' => new Reference($service)]);
            }
        }

        $proxyService = $container->getDefinition(DelegateVersionStorageAdapter::class);
        $proxyService->setArgument('$adapters', $adapters);
    }
}
