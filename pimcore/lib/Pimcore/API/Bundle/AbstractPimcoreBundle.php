<?php

namespace Pimcore\API\Bundle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

abstract class AbstractPimcoreBundle extends Bundle implements PimcoreBundleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getInstaller(ContainerInterface $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminIframePath()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getJsPaths()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getCssPaths()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getEditmodeJsPaths()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getEditmodeCssPaths()
    {
        return [];
    }
}
