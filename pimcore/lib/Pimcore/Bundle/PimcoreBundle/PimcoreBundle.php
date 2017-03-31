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

namespace Pimcore\Bundle\PimcoreBundle;

use Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler\AreabrickPass;
use Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler\PimcoreContextResolverAwarePass;
use Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler\PimcoreGlobalTemplatingVariablesPass;
use Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler\PhpTemplatingPass;
use Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler\SessionConfiguratorPass;
use Pimcore\Bundle\PimcoreBundle\DependencyInjection\Compiler\WebDebugToolbarListenerPass;
use Pimcore\Cache;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PimcoreBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PimcoreContextResolverAwarePass());
        $container->addCompilerPass(new PhpTemplatingPass());
        $container->addCompilerPass(new AreabrickPass());
        $container->addCompilerPass(new PimcoreGlobalTemplatingVariablesPass());
        $container->addCompilerPass(new SessionConfiguratorPass());
        $container->addCompilerPass(new WebDebugToolbarListenerPass());
    }

    /**
     * @inheritDoc
     */
    public function boot()
    {
        Cache::setHandler($this->container->get('pimcore.cache.core.handler'));
    }
}
