<?php

namespace Pimcore\Bundle\PimcoreZendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ZendViewHelperCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $helpers = [];
        foreach ($container->findTaggedServiceIds('pimcore.zend.view_helper') as $id => $attributes) {
            $alias = $id;
            if (isset($attributes[0]['alias'])) {
                $alias = $attributes[0]['alias'];
            }

            $helpers[$alias] = $id;
        }

        if (count($helpers) > 0) {
            $pluginManagerDefinition = $container->getDefinition('pimcore.zend.templating.helper_plugin_manager');

            foreach ($helpers as $alias => $id) {
                $pluginManagerDefinition->addMethodCall('setService', [$alias, new Reference($id)]);
            }
        }
    }
}
