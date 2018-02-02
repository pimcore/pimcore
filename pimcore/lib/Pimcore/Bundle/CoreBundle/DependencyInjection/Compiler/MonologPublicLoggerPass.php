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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MonologPublicLoggerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $loggerPrefix = 'monolog.logger.';
        $serviceIds = array_filter($container->getServiceIds(), function (string $id) use ($loggerPrefix) {
            return 0 === strpos($id, $loggerPrefix);
        });

        foreach ($serviceIds as $serviceId) {
            $container
                ->findDefinition($serviceId)
                ->setPublic(true);
        }
    }
}
