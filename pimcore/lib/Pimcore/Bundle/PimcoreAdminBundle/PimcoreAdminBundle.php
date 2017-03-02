<?php

namespace Pimcore\Bundle\PimcoreAdminBundle;

use Pimcore\Bundle\PimcoreAdminBundle\DependencyInjection\Compiler\SerializerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PimcoreAdminBundle extends Bundle
{
    /**
     * @inheritDoc
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SerializerPass());
    }
}
