<?php

namespace PimcoreBundle;

use PimcoreBundle\DependencyInjection\Compiler\PimcoreGlobalTemplatingVariablesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PimcoreBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PimcoreGlobalTemplatingVariablesPass());
    }
}
