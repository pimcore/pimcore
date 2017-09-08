<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Pimcore\Http\Context\PimcoreContextResolverAwareInterface;
use Pimcore\Service\Request\PimcoreContextResolver;
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
        $resolver = new Reference(PimcoreContextResolver::class);
        foreach ($services as $id => $tags) {
            $definition = $container->getDefinition($id);

            // do not do anything for autowired definitions
            if ($definition->isAutowired()) {
                continue;
            }

            if (class_exists($definition->getClass())) {
                $reflector = new \ReflectionClass($definition->getClass());

                if ($reflector->implementsInterface(PimcoreContextResolverAwareInterface::class)) {
                    $definition->addMethodCall('setPimcoreContextResolver', [$resolver]);
                }
            }
        }
    }
}
