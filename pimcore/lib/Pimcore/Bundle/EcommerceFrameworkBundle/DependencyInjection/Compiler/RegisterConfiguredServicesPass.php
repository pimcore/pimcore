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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\Compiler;

use Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\PimcoreEcommerceFrameworkExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterConfiguredServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $this->registerIndexServiceWorkers($container);
        $this->registerTrackingManagerTrackers($container);
    }

    public function registerIndexServiceWorkers(ContainerBuilder $container)
    {
        $workers = [];
        foreach ($container->findTaggedServiceIds('pimcore_ecommerce.index_service.worker') as $id => $tags) {
            $workers[] = new Reference($id);
        }

        $indexService = $container->findDefinition(PimcoreEcommerceFrameworkExtension::SERVICE_ID_INDEX_SERVICE);
        $indexService->setArgument('$tenantWorkers', $workers);
    }

    public function registerTrackingManagerTrackers(ContainerBuilder $container)
    {
        $trackers = [];

        foreach ($container->findTaggedServiceIds('pimcore_ecommerce.tracking.tracker') as $id => $tags) {
            $trackers[] = new Reference($id);
        }

        $trackingManager = $container->findDefinition(PimcoreEcommerceFrameworkExtension::SERVICE_ID_TRACKING_MANAGER);
        $trackingManager->setArgument('$trackers', $trackers);
    }
}
