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

namespace Pimcore\Bundle\AdminBundle\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PreAuthenticatedAdminSessionFactory implements AuthenticatorFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string
    {
        $authenticatorId = 'pimcore.security.authenticator.admin_pre_auth.' . $firewallName;
        $container
            ->setDefinition($authenticatorId, new ChildDefinition('pimcore.security.authenticator.admin_pre_auth'))
            ->replaceArgument('$userProvider', new Reference($userProviderId))
            ->replaceArgument('$firewallName', $firewallName);

        return $authenticatorId;
    }

    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint): array
    {
        $providerId = 'pimcore.security.authentication.provider.admin_pre_auth.' . $id;
        $listenerId = 'pimcore.security.authentication.listener.admin_pre_auth.' . $id;

        $container
            ->setDefinition(
                $providerId,
                new ChildDefinition('pimcore.security.authentication.provider.admin_pre_auth')
            )
            ->replaceArgument(0, new Reference($userProvider))
            ->replaceArgument(2, $id);

        $container
            ->setDefinition(
                $listenerId,
                new ChildDefinition('pimcore.security.authentication.listener.admin_pre_auth')
            )
            ->replaceArgument(2, $id);

        return [$providerId, $listenerId, $defaultEntryPoint];
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition(): string
    {
        return 'pre_auth';
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return 'pimcore_admin_pre_auth';
    }

    /**
     * @param NodeDefinition $builder
     */
    public function addConfiguration(NodeDefinition $builder)
    {
        // make sure only the pimcore_admin user provider can be used with this authentication provider
        if ($builder instanceof ArrayNodeDefinition) {
            $builder
                ->children()
                    ->scalarNode('provider')
                        ->defaultValue('pimcore_admin')
                        ->validate()
                            ->ifNotInArray(['pimcore_admin'])
                            ->thenInvalid('The pimcore_admin_pre_auth authenticator can only handle Pimcore admin users through the "pimcore_admin" provider')
                        ->end()
                    ->end()
                ->end();
        }
    }
}
