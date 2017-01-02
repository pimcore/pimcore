<?php

namespace PimcoreBundle\DependencyInjection\Compiler;

use PimcoreBundle\Templating\PhpEngine;
use PimcoreBundle\Templating\TimedPhpEngine;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverrideTemplatingPhpEngineCompilerPass implements CompilerPassInterface
{
    /**
     * Set PHP templating engine to our implementation supporting wildcard helpers via name resolvers
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('templating.engine.php')) {

            $definition = $container->getDefinition('templating.engine.php');
            $definition->setClass(PhpEngine::class);

            if ($container->getParameter('kernel.debug')) {
                $definition->setClass(TimedPhpEngine::class);
            }

            $definition->addMethodCall('setNameResolver', [new Reference('pimcore.templating.name_resolver')]);
        }
    }
}
