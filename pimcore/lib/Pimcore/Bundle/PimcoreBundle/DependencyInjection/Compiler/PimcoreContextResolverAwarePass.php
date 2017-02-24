<?php

namespace Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler;

use Pimcore\Bundle\PimcoreBundle\Service\Request\PimcoreContextResolverAwareInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds a setPimcoreContextResolver() call on event listeners implementing PimcoreContextResolverAwareInterface.
 */
class PimcoreContextResolverAwarePass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $listeners   = $container->findTaggedServiceIds('kernel.event_listener');
        $subscribers = $container->findTaggedServiceIds('kernel.event_subscriber');

        $this->processList($container, $listeners);
        $this->processList($container, $subscribers);
    }

    /**
     * @param ContainerBuilder $container
     * @param array $services
     */
    protected function processList(ContainerBuilder $container, array $services)
    {
        $resolver = new Reference('pimcore.service.request.pimcore_context_resolver');
        foreach ($services as $id => $tags) {
            $definition = $container->getDefinition($id);

            if (class_exists($definition->getClass())) {
                $reflector = new \ReflectionClass($definition->getClass());

                if ($reflector->implementsInterface(PimcoreContextResolverAwareInterface::class)) {
                    $definition->addMethodCall('setPimcoreContextResolver', [$resolver]);
                }
            }
        }
    }
}
