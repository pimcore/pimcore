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

use Pimcore\Targeting\DataLoader;
use Pimcore\Targeting\DataProvider\Piwik;
use Pimcore\Targeting\Debug\TargetingDataCollector;
use Pimcore\Targeting\EventListener\TargetingListener;
use Pimcore\Targeting\VisitorInfoResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The debug.stopwatch service is always defined, so we can't just add it to services if defined. This
 * only adds the stopwatch to services if the debug flag is set.
 */
class DebugStopwatchPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $debug = $container->getParameter('kernel.debug');
        if (!$debug) {
            return;
        }

        if (!$container->hasDefinition('debug.stopwatch')) {
            return;
        }

        $services = [
            DataLoader::class,
            VisitorInfoResolver::class,
            TargetingListener::class,
            TargetingDataCollector::class,
            Piwik::class,
        ];

        foreach ($services as $service) {
            if ($container->hasDefinition($service)) {
                $container
                    ->getDefinition($service)
                    ->addMethodCall('setStopwatch', [
                        new Reference(
                            'debug.stopwatch',
                            ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                        ),
                    ]);
            }
        }
    }
}
