<?php

namespace PimcoreAdminBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class PimcoreAdminExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        // TODO extension is only here as ZF autoloader tries to load it and throws an error if not existing
    }
}
