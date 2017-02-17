<?php
namespace Pimcore\API\Bundle\Installer;

use Pimcore\API\Bundle\Installer\Exception\InstallationException;

interface InstallerInterface
{
    /**
     * Installs the plugin
     *
     * @throws InstallationException
     * @return bool
     */
    public function install();

    /**
     * Uninstalls the plugin
     *
     * @throws InstallationException
     * @return bool
     */
    public function uninstall();

    /**
     * Determine if plugin is installed
     *
     * @return bool
     */
    public function isInstalled();

    /**
     * Determine if plugin is ready to be installed. Can be used to check prerequisites
     *
     * @return bool
     */
    public function canBeInstalled();

    /**
     * Determine if plugin can be uninstalled
     *
     * @return bool
     */
    public function canBeUninstalled();

    /**
     * Determines if admin interface should be reloaded after installation/uninstallation
     *
     * @return bool
     */
    public function needsReloadAfterInstall();
}
