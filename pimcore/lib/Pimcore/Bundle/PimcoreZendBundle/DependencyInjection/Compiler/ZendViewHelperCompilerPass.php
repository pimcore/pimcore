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
        $pluginManagerDefinition = $container->getDefinition('pimcore.zend.templating.helper_plugin_manager');
        $taggedServices          = $container->findTaggedServiceIds('pimcore.zend.view_helper');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['alias'])) {
                    continue;
                }

                $pluginManagerDefinition->addMethodCall('setService', [$attributes['alias'], new Reference($id)]);
            }
        }
    }
}
