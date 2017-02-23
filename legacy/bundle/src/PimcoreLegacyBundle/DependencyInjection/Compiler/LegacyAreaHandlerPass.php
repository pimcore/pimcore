<?php

namespace PimcoreLegacyBundle\DependencyInjection\Compiler;

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
        // add legacy handler to area handler
        $handlerDefinition = $container->getDefinition('pimcore.document.tag.handler');
        $handlerDefinition->addMethodCall('addHandler', [new Reference('pimcore.document.tag.handler.legacy')]);
    }
}
