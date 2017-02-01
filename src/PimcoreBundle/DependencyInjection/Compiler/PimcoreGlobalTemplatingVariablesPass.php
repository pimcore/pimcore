<?php

namespace PimcoreBundle\DependencyInjection\Compiler;

use PimcoreBundle\Templating\GlobalVariables\GlobalVariables;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PimcoreGlobalTemplatingVariablesPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        // set templating globals to our implementation
        if ($container->hasDefinition('templating.globals')) {
            $definition = $container->getDefinition('templating.globals');
            $definition->setClass(GlobalVariables::class);
        }
    }
}
