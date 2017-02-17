<?php

namespace Pimcore\Bundle\PimcoreBundle;

use Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler\AreabrickPass;
use Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler\PimcoreGlobalTemplatingVariablesPass;
use Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler\PhpTemplatingPass;
use Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler\SecurityTokenLoaderUserProvidersPass;
use Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler\SessionConfiguratorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PimcoreBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PhpTemplatingPass());
        $container->addCompilerPass(new AreabrickPass());
        $container->addCompilerPass(new PimcoreGlobalTemplatingVariablesPass());
        $container->addCompilerPass(new SessionConfiguratorPass());
        $container->addCompilerPass(new SecurityTokenLoaderUserProvidersPass());
    }
}
