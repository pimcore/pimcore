<?php
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

use Pimcore\Maintenance\Executor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterMaintenanceTaskPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(Executor::class)) {
            return;
        }

        $definition = $container->getDefinition(Executor::class);

        foreach ($container->findTaggedServiceIds('pimcore.maintenance.task') as $id => $tags) {
            if (!isset($tags[0]['type'])) {
                throw new \InvalidArgumentException('Tagged Maintenance Task `'.$id.'` needs to a `type` attribute.');
            }

            $definition->addMethodCall('registerTask', [$tags[0]['type'], new Reference($id)]);
        }
    }
}
