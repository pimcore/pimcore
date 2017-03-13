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

class SecurityTokenLoaderUserProvidersPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        // add array of security user providers which is already configured on
        // ContextListener to our TokenLoader by copying the argument
        $contextListener = $container->getDefinition('security.context_listener');
        $userProviders = $contextListener->getArgument(1);

        $tokenLoader = $container->getDefinition('pimcore.security.token_loader');
        $tokenLoader->addArgument($userProviders);
    }
}
