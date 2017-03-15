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

use Pimcore\Bundle\PimcoreBundle\EventListener\WebDebugToolbarListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides the core web debug toolbar listener
 */
class WebDebugToolbarListenerPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('web_profiler.debug_toolbar')) {
            return;
        }

        $definition = $container->getDefinition('web_profiler.debug_toolbar');
        $definition->setClass(WebDebugToolbarListener::class);
        $definition->addMethodCall('setRequestHelper', [
            new Reference('pimcore.http.request_helper')
        ]);
    }
}
