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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Lock\Store\FlockStore;

/**
 * @deprecated
 */
class LockStoragePass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $pdoStoreClassName = 'Symfony\Component\Lock\Store\PdoStore';
        if (!class_exists($pdoStoreClassName)) {
            // Symfony 3.4 compatibility: use Flock instead of PdoStore
            $definition = $container->getDefinition('Symfony\Component\Lock\PersistingStoreInterface');
            if ($definition->getClass() === $pdoStoreClassName) {
                // ensure it wasn't already overridden
                $definition->setArguments([]);
                $definition->setClass(FlockStore::class);
            }
        }
    }
}
