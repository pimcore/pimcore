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

use Pimcore\Cache\Pool\TraceablePimcoreAdapter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * See original CacheCollectorPass in FrameworkBundle. This injects our core Pimcore cache into the cache collector.
 */
class CacheCollectorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('data_collector.cache')) {
            return;
        }

        $corePoolServiceId = 'pimcore.cache.core.pool';

        $definition = $container->findDefinition($corePoolServiceId);
        if ($definition->isAbstract()) {
            return;
        }

        $container->register($corePoolServiceId . '.recorder', TraceablePimcoreAdapter::class)
            ->setDecoratedService($corePoolServiceId)
            ->addArgument(new Reference($corePoolServiceId . '.recorder.inner'));

        // tell the collector to add the new instance
        $collectorDefinition = $container->getDefinition('data_collector.cache');
        $collectorDefinition->addMethodCall('addInstance', ['pimcore.cache.core', new Reference($corePoolServiceId)]);
    }
}
