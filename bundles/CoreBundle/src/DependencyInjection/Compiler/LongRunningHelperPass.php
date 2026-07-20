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

use Pimcore\Helper\LongRunningHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds tagged navigation renderers to navigation helper
 *
 * @internal
 */
final class LongRunningHelperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $helperDefinition = $container->getDefinition(LongRunningHelper::class);
        foreach ($container->getDefinitions() as $serviceId => $definition) {
            if (str_starts_with($serviceId, 'monolog.handler.')) {
                $class = $container->getParameterBag()->resolveValue($definition->getClass());
                if (is_a($class, 'Monolog\Handler\BufferHandler', true)
                    || is_a($class, 'Monolog\Handler\FingersCrossedHandler', true)) {
                    $helperDefinition->addMethodCall('addMonologHandler', [new Reference($serviceId)]);
                }
            }
        }
    }
}
