<?php

namespace Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds support to register context initializers via pimcore.context_initializer tag.
 */
class ContextInitializerPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $delegatingInitializer = $container->getDefinition('pimcore.context_initializer');
        $taggedServices        = $container->findTaggedServiceIds('pimcore.context_initializer');

        foreach ($taggedServices as $id => $tags) {
            $delegatingInitializer->addMethodCall('addInitializer', [new Reference($id)]);
        }
    }
}
