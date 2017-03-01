<?php

namespace PimcoreLegacyBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FallbackRouterPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        // add fallback router to router chain wiht lowest priority
        $chainRouter = $container->getDefinition('cmf_routing.router');
        $chainRouter->addMethodCall('add', [new Reference('pimcore.legacy.fallback_router'), -200]);
    }
}
