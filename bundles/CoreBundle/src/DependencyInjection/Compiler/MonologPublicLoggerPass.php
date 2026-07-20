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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class MonologPublicLoggerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $loggerPrefix = 'monolog.logger.';
        $serviceIds = array_filter($container->getServiceIds(), function (string $id) use ($loggerPrefix) {
            return str_starts_with($id, $loggerPrefix);
        });

        foreach ($serviceIds as $serviceId) {
            $container
                ->findDefinition($serviceId)
                ->setPublic(true);
        }
    }
}
