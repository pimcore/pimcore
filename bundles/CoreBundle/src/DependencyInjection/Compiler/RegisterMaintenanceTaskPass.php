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

use InvalidArgumentException;
use Pimcore\Maintenance\Executor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class RegisterMaintenanceTaskPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(Executor::class)) {
            return;
        }

        $definition = $container->getDefinition(Executor::class);

        foreach ($container->findTaggedServiceIds('pimcore.maintenance.task') as $id => $tags) {
            if (!isset($tags[0]['type'])) {
                throw new InvalidArgumentException('Tagged Maintenance Task `'.$id.'` needs to a `type` attribute.');
            }

            $definition->addMethodCall('registerTask', [$tags[0]['type'], new Reference($id), $tags[0]['messengerMessageClass'] ?? null]);
        }
    }
}
