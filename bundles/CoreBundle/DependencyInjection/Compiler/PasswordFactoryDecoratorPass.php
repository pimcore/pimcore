<?php

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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class PasswordFactoryDecoratorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $config = $container->getParameter('pimcore.config');

        if ($config['security']['factory_type'] === 'encoder') {
            if ($container->hasDefinition('security.authentication.provider.dao')) {
                $definition = $container->getDefinition('security.authentication.provider.dao');
                $definition->replaceArgument(3, new Reference('security.encoder_factory'));
            }

            $definition = $container->findDefinition('security.validator.user_password');
            $definition->replaceArgument(1, new Reference('security.encoder_factory'));
        }
    }
}
