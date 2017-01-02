<?php

namespace PimcoreBundle;

use PimcoreBundle\DependencyInjection\Compiler\OverrideTemplatingPhpEngineCompilerPass;
use PimcoreBundle\DependencyInjection\Compiler\TemplatingNameResolversCompilerPass;
use PimcoreBundle\DependencyInjection\PimcoreExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PimcoreBundle extends Bundle
{
    /**
     * Returns the bundle's container extension.
     *
     * @return ExtensionInterface|null The container extension
     *
     * @throws \LogicException
     */
    public function getContainerExtension()
    {
        return new PimcoreExtension();
    }

    /**
     * Builds the bundle.
     *
     * It is only ever called once when the cache is empty.
     *
     * This method can be overridden to register compilation passes,
     * other extensions, ...
     *
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TemplatingNameResolversCompilerPass());
        $container->addCompilerPass(new OverrideTemplatingPhpEngineCompilerPass());
    }
}
