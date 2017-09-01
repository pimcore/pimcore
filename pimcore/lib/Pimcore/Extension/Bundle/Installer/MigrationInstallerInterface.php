<?php

declare(strict_types=1);

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

namespace Pimcore\Extension\Bundle\Installer;

use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\Configuration\Configuration;
use Pimcore\Migrations\Configuration\InstallConfiguration;

interface MigrationInstallerInterface extends InstallerInterface
{
    /**
     * The version to mark as migrated after installation. This can be set to something else than
     * InstallVersion::INSTALL_VERSION to force a the recorded migration version to something else. This allows to
     * provide migrations for existing installations while keeping the install routine up to date as new installations
     * won't do any migrations before the one specified here.
     *
     * @return string|null
     */
    public function getMigrationVersion();

    /**
     * Executes install migration. Used during installation for initial creation of database tables and other data
     * structures (e.g. pimcore classes). The version object is the version object which can be used to add raw SQL
     * queries via `addSql`.
     *
     * If possible, use the Schema object to manipulate DB state (see Doctrine Migrations)
     *
     * @param Schema $schema
     * @param Version $version
     */
    public function migrateInstall(Schema $schema, Version $version);

    /**
     * Opposite of migrateInstall called on uninstallation of a bundle.
     *
     * @param Schema $schema
     * @param Version $version
     */
    public function migrateUninstall(Schema $schema, Version $version);

    /**
     * Returns the migration configuration for this bundle
     *
     * @return Configuration
     */
    public function getMigrationConfiguration(): Configuration;

    /**
     * Returns the install migration configuration for this bundle (handles only the install migration)
     *
     * @return InstallConfiguration
     */
    public function getInstallMigrationConfiguration(): InstallConfiguration;
}
