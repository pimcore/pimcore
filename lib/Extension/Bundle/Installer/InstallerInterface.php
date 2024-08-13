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

namespace Pimcore\Extension\Bundle\Installer;

use Pimcore\Extension\Bundle\Installer\Exception\InstallationException;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

interface InstallerInterface
{
    /**
     * Installs the bundle
     *
     * @throws InstallationException
     */
    public function install(): void;

    /**
     * Uninstalls the bundle
     *
     * @throws InstallationException
     */
    public function uninstall(): void;

    /**
     * Determine if bundle is installed
     *
     */
    public function isInstalled(): bool;

    /**
     * Determine if bundle is ready to be installed. Can be used to check prerequisites
     *
     */
    public function canBeInstalled(): bool;

    /**
     * Determine if bundle can be uninstalled
     *
     */
    public function canBeUninstalled(): bool;

    /**
     * Determines if admin interface should be reloaded after installation/uninstallation
     *
     */
    public function needsReloadAfterInstall(): bool;

    public function getOutput(): BufferedOutput | NullOutput;
}
