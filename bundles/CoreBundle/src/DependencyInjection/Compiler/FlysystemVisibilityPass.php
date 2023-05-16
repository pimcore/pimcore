<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use League\Flysystem\Visibility;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @internal
 */
final class FlysystemVisibilityPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $serviceIds = $container->findTaggedServiceIds('flysystem.storage');
        foreach($serviceIds as $serviceId => $tags) {
            if(str_starts_with($serviceId, 'pimcore.')) {
                $definition = $container->findDefinition($serviceId);
                $config = $definition->getArgument(1);
                if (($config['directory_visibility'] ?? null) === Visibility::PUBLIC) {
                    $adapter = $container->findDefinition((string)$definition->getArgument(0));
                    /** @var Definition $visibilityDef */
                    $visibilityDef = $adapter->getArgument(1);
                    $visibilityDef->setArgument(1, Visibility::PUBLIC);
                }
            }
        }
    }
}
