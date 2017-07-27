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

class IndexServiceWorkersPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $workers = [];
        foreach ($container->findTaggedServiceIds('pimcore_ecommerce.index_service.worker') as $id => $tags) {
            $workers[] = new Reference($id);
        }

        $indexService = $container->findDefinition(PimcoreEcommerceFrameworkExtension::SERVICE_ID_INDEX_SERVICE);
        $indexService->setArgument('$tenantWorkers', $workers);
    }
}
