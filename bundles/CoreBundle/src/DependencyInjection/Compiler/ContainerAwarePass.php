<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Pimcore\Bundle\CoreBundle\DependencyInjection\ContainerAwareInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Throwable;

/**
 * Automatically injects the service container into all services implementing ContainerAwareInterface.
 *
 * This compiler pass provides backward compatibility for services that need access to the
 * service container, similar to Symfony 6's behavior with ContainerAwareInterface.
 *
 * Note: This is deprecated by design. New code should use dependency injection instead.
 *
 * @internal
 */
final class ContainerAwarePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definitions = $container->getDefinitions();
        if (empty($definitions)) {
            return;
        }

        $containerAwareInterface = ContainerAwareInterface::class;
        $serviceContainerRef = new Reference('service_container');

        // Iterate through all service definitions
        foreach ($definitions as $definition) {
            $class = $definition->getClass();

            // Skip if class is not set
            if (!$class) {
                continue;
            }

            try {
                // Use ReflectionClass directly - it will throw if class doesn't exist
                $reflector = new ReflectionClass($class);

                if ($reflector->implementsInterface($containerAwareInterface)) {
                    $definition->addMethodCall('setContainer', [$serviceContainerRef]);
                }
            } catch (Throwable $e) {
                continue;
            }
        }
    }
}
