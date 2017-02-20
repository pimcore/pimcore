<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class PimcoreAdminFactory extends AbstractFactory
{
    protected $options = array(
        'check_path' => '/admin/login_check',
        'use_forward' => false,
        'require_previous_session' => false,
    );

    protected $defaultSuccessHandlerOptions = array(
        'always_use_default_target_path' => false,
        'default_target_path' => '/admin',
        'login_path' => '/admin/login',
        'target_path_parameter' => '_target_path',
        'use_referer' => false,
    );

    protected $defaultFailureHandlerOptions = array(
        'failure_path' => null,
        'failure_forward' => false,
        'login_path' => '/admin/login',
        'failure_path_parameter' => '_failure_path',
    );

    /**
     * @inheritDoc
     */
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $providerId = 'pimcore_admin.security.authentication.provider.' . $id;

        $container
            ->setDefinition(
                $providerId,
                new DefinitionDecorator('pimcore_admin.security.authentication.provider')
            );

        return $providerId;
    }

    protected function createListener($container, $id, $config, $userProvider)
    {
        $listenerId = $this->getListenerId();
        $listener = new DefinitionDecorator($listenerId);

        $listenerId .= '.'.$id;

        /** @var ContainerBuilder $container */
        $container->setDefinition($listenerId, $listener);

        return $listenerId;
    }

    /**
     * @inheritDoc
     */
    protected function getListenerId()
    {
        return 'pimcore_admin.security.firewall.listener';
    }

    /**
     * @inheritDoc
     */
    public function getPosition()
    {
        return 'pre_auth';
    }

    /**
     * @inheritDoc
     */
    public function getKey()
    {
        return 'pimcore_admin';
    }
}
