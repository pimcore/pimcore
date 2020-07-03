<?php

declare(strict_types=1);

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

use Pimcore\Helper\LongRunningHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds tagged navigation renderers to navigation helper
 */
class LongRunningHelperPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has(LongRunningHelper::class)) {
            $helperDefinition = $container->getDefinition(LongRunningHelper::class);
            foreach ($container->getDefinitions() as $serviceId => $definition) {
                if (strpos($serviceId, 'monolog.handler.') === 0) {
                    $class = $container->getParameterBag()->resolveValue($definition->getClass());
                    if (is_a($class, 'Monolog\Handler\BufferHandler', true)
                        || is_a($class, 'Monolog\Handler\FingersCrossedHandler', true)) {
                        $helperDefinition->addMethodCall('addMonologHandler', [new Reference($serviceId)]);
                    }
                }
            }
        }
    }
}
