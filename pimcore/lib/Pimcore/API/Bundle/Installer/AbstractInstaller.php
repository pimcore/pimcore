<?php

namespace Pimcore\API\Bundle\Installer;

class AbstractInstaller implements InstallerInterface
{
    /**
     * {@inheritdoc}
     */
    public function install()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeInstalled()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUninstalled()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function needsReloadAfterInstall()
    {
        return false;
    }
}
