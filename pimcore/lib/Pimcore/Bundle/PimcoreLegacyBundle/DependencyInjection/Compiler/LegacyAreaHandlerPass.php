<?php

namespace Pimcore\Bundle\PimcoreLegacyBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LegacyAreaHandlerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // add legacy handler strategy to area handler
        $rendererDefinition = $container->getDefinition('pimcore.area.handler');
        $rendererDefinition->addMethodCall('addStrategy', [new Reference('pimcore.area.handler_strategy.legacy')]);
    }
}
