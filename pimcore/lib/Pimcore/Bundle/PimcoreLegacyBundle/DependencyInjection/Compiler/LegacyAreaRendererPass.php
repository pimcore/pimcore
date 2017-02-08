<?php

namespace Pimcore\Bundle\PimcoreLegacyBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LegacyAreaRendererPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // add legacy rendering strategy to area renderer
        $rendererDefinition = $container->getDefinition('pimcore.area.renderer');
        $rendererDefinition->addMethodCall('addStrategy', [new Reference('pimcore.area.rendering_strategy.legacy')]);
    }
}
