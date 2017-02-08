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
        $areaManagerDefinition = $container->getDefinition('pimcore.area.brick_manager');
        $taggedServices        = $container->findTaggedServiceIds('pimcore.area.brick');

        foreach ($taggedServices as $id => $tags) {
            $areaManagerDefinition->addMethodCall('register', [new Reference($id)]);
        }
    }
}
