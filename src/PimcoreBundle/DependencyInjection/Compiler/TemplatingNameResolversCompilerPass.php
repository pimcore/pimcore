<?php

namespace PimcoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TemplatingNameResolversCompilerPass implements CompilerPassInterface
{
    /**
     * Add PHP templating name resolvers tagged with 'templating.name_resolver' to delegating name resolver
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definitionId = 'pimcore.templating.name_resolver';

        if ($container->has($definitionId)) {
            $definition = $container->findDefinition($definitionId);

            foreach ($container->findTaggedServiceIds('templating.name_resolver') as $id => $tags) {
                $definition->addMethodCall('addResolver', [new Reference($id)]);
            }
        }
    }
}
