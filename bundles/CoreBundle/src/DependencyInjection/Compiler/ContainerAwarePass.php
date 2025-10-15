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

use ReflectionClass;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Pimcore\Bundle\CoreBundle\DependencyInjection\ContainerAwareInterface;

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
        // Iterate through all service definitions
        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();

            // Skip if class is not set or is a parameter
            if (!$class || str_starts_with($class, '%')) {
                continue;
            }

            // Resolve class name if it's a parameter
            if (str_contains($class, '%')) {
                try {
                    $class = $container->getParameterBag()->resolveValue($class);
                } catch (\Exception $e) {
                    continue;
                }
            }

            try {
                // Skip if class doesn't exist (wrapped in try-catch to avoid autoload errors)
                if (!class_exists($class) && !interface_exists($class)) {
                    continue;
                }

                $reflector = new ReflectionClass($class);

                // Check if the class implements ContainerAwareInterface
                if ($reflector->implementsInterface(ContainerAwareInterface::class)) {
                    // Add setContainer method call to inject the service container
                    $definition->addMethodCall('setContainer', [new Reference('service_container')]);
                }
            } catch (\Throwable $e) {
                // Skip classes that can't be loaded or reflected
                // This includes missing dependencies, autoload failures, etc.
                continue;
            }
        }
    }
}
