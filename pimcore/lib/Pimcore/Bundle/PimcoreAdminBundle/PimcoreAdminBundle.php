<?php

namespace Pimcore\Bundle\PimcoreAdminBundle;

use Pimcore\Bundle\PimcoreAdminBundle\Security\Factory\PimcoreAdminFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PimcoreAdminBundle extends Bundle
{
    /**
     * @inheritDoc
     */
    public function build(ContainerBuilder $container)
    {
        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new PimcoreAdminFactory());
    }
}
