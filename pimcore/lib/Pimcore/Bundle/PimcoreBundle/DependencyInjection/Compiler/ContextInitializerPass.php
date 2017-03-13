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

namespace Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds support to register context initializers via pimcore.context_initializer tag.
 */
class ContextInitializerPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $delegatingInitializer = $container->getDefinition('pimcore.context_initializer');
        $taggedServices        = $container->findTaggedServiceIds('pimcore.context_initializer');

        foreach ($taggedServices as $id => $tags) {
            $delegatingInitializer->addMethodCall('addInitializer', [new Reference($id)]);
        }
    }
}
