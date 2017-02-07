<?php

namespace Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AreabrickPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $areaManagerDefinition = $container->getDefinition('pimcore.areabrick_manager');
        $taggedServices        = $container->findTaggedServiceIds('pimcore.areabrick');

        foreach ($taggedServices as $id => $tags) {
            $areaManagerDefinition->addMethodCall('register', [new Reference($id)]);
        }
    }
}
