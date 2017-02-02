<?php

namespace Pimcore\Bundle\PimcoreZendBundle;

use Pimcore\Bundle\PimcoreZendBundle\DependencyInjection\Compiler\ZendViewHelperCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PimcoreZendBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ZendViewHelperCompilerPass());
    }
}
