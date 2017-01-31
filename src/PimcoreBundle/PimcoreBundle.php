<?php

namespace PimcoreBundle;

use PimcoreZendBundle\DependencyInjection\Compiler\ZendViewHelperCompilerPass;
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
        // TODO this shouldn't be necessary if naming is correct - check bundle names
        return new PimcoreExtension();
    }
}
