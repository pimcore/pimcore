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

use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\OutputWriter as DoctrineOutputWriter;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Db\Connection;
use Pimcore\Extension\Bundle\Installer\Exception\InstallationException;
use Pimcore\Extension\Bundle\Installer\Exception\UpdateException;
use Pimcore\Migrations\Configuration\Configuration;
use Pimcore\Migrations\Configuration\InstallConfiguration;
use Pimcore\Migrations\InstallVersion;
use Pimcore\Migrations\MigrationManager;
use Pimcore\Migrations\Version;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

abstract class MigrationInstaller extends AbstractInstaller implements MigrationInstallerInterface
{
    /**
     * @var BundleInterface
     */
    protected $bundle;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var MigrationManager
     */
    protected $migrationManager;

    /**
     * @var InstallConfiguration
     */
    protected $installConfiguration;

    /**
     * @var InstallVersion
     */
    protected $installVersion;

    /**
     * @var DoctrineOutputWriter
     */
    protected $migrationOutputWriter;

    /**
     * @var bool
     */
    protected $runUpdateAfterInstall = true;

    public function __construct(
        BundleInterface $bundle,
        Connection $connection,
        MigrationManager $migrationManager
    ) {
        parent::__construct();

        $this->bundle = $bundle;
        $this->connection = $connection;
        $this->migrationManager = $migrationManager;
    }

    public function setOutputWriter(OutputWriterInterface $outputWriter)
    {
        parent::setOutputWriter($outputWriter);

        // create an outputwriter which we can set on the configuration
        $this->migrationOutputWriter = new DoctrineOutputWriter(function ($message) use ($outputWriter) {
            $outputWriter->write($message);
        });
    }

    /**
     * @inheritdoc
     */
    public function getMigrationVersion(): string
    {
        return InstallVersion::INSTALL_VERSION;
    }

    /**
     * @inheritdoc
     */
    public function getMigrationConfiguration(): Configuration
    {
        $configuration = $this->migrationManager->getBundleConfiguration($this->bundle);
        $configuration->setOutputWriter($this->migrationOutputWriter);

        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getInstallMigrationConfiguration(): InstallConfiguration
    {
        $configuration = $this->migrationManager->getInstallConfiguration($this->getMigrationConfiguration(), $this);
        $configuration->setOutputWriter($this->migrationOutputWriter);

        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function install()
    {
        if (!$this->canBeInstalled()) {
            throw new InstallationException(sprintf('Bundle "%s" can\'t be installed', $this->bundle->getName()));
        }

        /** @var Version $installMigrationVersion */
        $installMigrationVersion = null;
        $installMigrationVersionId = $this->getMigrationVersion();

        // load the migration to be marked as installed if it's something else than the install version
        if (InstallVersion::INSTALL_VERSION !== $installMigrationVersionId) {
            $installMigrationVersion = $this->migrationManager->getBundleVersion(
                $this->bundle,
                $installMigrationVersionId
            );
        }

        // install initial schema
        $this->beforeInstallMigration();
        $this->executeInstallMigration(true);
        $this->afterInstallMigration();

        // mark migrated version
        if (null !== $installMigrationVersion) {
            $this->markInstallVersionAsMigrated($installMigrationVersion);
        }

        // run update (remaining migrations, ...)
        $this->updateAfterInstall();
    }

    /**
     * Marks version defined in getMigrationVersion() as migrated
     *
     * @param Version $version
     */
    protected function markInstallVersionAsMigrated(Version $version)
    {
        // this also sets previous migrations lower than the given one as migrated
        // overwrite this method and change the call if only want to mark this specific version
        $this->migrationManager->markVersionAsMigrated($version, true);
    }

    /**
     * Runs update after a successful installation to make sure remaining migrations are applied
     */
    protected function updateAfterInstall()
    {
        if ($this->runUpdateAfterInstall && $this->canBeUpdated()) {
            $this->outputWriter->write("\n" . sprintf('<comment>%s</comment>', str_repeat('#', 70)) . "\n");
            $this->outputWriter->write(sprintf(
                'Running <comment>%s</comment> updates after installation' . "\n",
                $this->bundle->getName()
            ));

            $this->update();
        }
    }

    protected function beforeInstallMigration()
    {
        // noop - to be implemented on demand for custom installation logic
    }

    protected function afterInstallMigration()
    {
        // noop - to be implemented on demand for custom installation logic
    }

    /**
     * @inheritdoc
     */
    public function uninstall()
    {
        if (!$this->canBeUninstalled()) {
            throw new InstallationException(sprintf('Bundle "%s" can\'t be uninstalled', $this->bundle->getName()));
        }

        $this->beforeUninstallMigration();
        $this->executeInstallMigration(false);
        $this->afterUninstallMigration();

        if ($this->clearMigratedVersionsOnUninstall()) {
            $configuration = $this->migrationManager->getBundleConfiguration($this->bundle);
            $configuration->clearMigratedVersions();
        }
    }

    protected function beforeUninstallMigration()
    {
        // noop - to be implemented on demand for custom uninstallation logic
    }

    protected function afterUninstallMigration()
    {
        // noop - to be implemented on demand for custom uninstallation logic
    }

    /**
     * @inheritdoc
     */
    public function update()
    {
        if (!$this->canBeUpdated()) {
            throw new UpdateException(sprintf('Bundle "%s" can\'t be updated', $this->bundle->getName()));
        }

        $configuration = $this->getMigrationConfiguration();

        $latestVersion = $configuration->getLatestVersion();
        $currentVersion = $configuration->getCurrentVersion();

        // check if there's a latest version > 0
        if ('0' === (string)$latestVersion) {
            throw new UpdateException('There\'s no version to migrate to');
        }

        // check if we would migrate down
        if ($currentVersion > $latestVersion) {
            throw new UpdateException(sprintf(
                'Can\'t migrate down (current version: "%s", latest version: "%s")',
                $currentVersion,
                $latestVersion
            ));
        }

        // migrate to the latest version
        $this->beforeUpdateMigration($latestVersion);
        $this->migrateToVersion($latestVersion);
        $this->afterUpdateMigration($latestVersion);
    }

    protected function beforeUpdateMigration(string $version = null)
    {
        // noop - to be implemented on demand for custom update logic
    }

    protected function afterUpdateMigration(string $version = null)
    {
        // noop - to be implemented on demand for custom update logic
    }

    /**
     * @inheritdoc
     */
    public function isInstalled()
    {
        return $this->getInstallMigrationConfiguration()->hasInstallVersionMigrated();
    }

    /**
     * @inheritdoc
     */
    public function canBeInstalled()
    {
        return !$this->isInstalled();
    }

    /**
     * @inheritdoc
     */
    public function canBeUninstalled()
    {
        return $this->isInstalled();
    }

    /**
     * @inheritdoc
     */
    public function canBeUpdated(): bool
    {
        return $this->isInstalled() && $this->getMigrationConfiguration()->getNumberOfNewMigrations() > 0;
    }

    /**
     * Migrates to a specific version
     *
     * @param string $versionId
     * @param bool $dryRun
     */
    protected function migrateToVersion(string $versionId, bool $dryRun = false)
    {
        $configuration = $this->getMigrationConfiguration();

        $migration = new Migration($configuration);
        $migration->migrate($versionId, $dryRun);
    }

    /**
     * Executes a specific migration version. This can be used to execute specific versions (e.g. migrations
     * changing class definitions of other bundles) on every install, even if the migrated version returned
     * from getMigrationVersion() is higher.
     *
     * @param string $versionId
     * @param bool $up
     * @param bool $dryRun
     *
     * @return array
     */
    protected function executeMigration(string $versionId, bool $up = true, bool $dryRun = false): array
    {
        $configuration = $this->getMigrationConfiguration();
        $version = $configuration->getVersion($versionId);

        return $this->migrationManager->executeVersion($version, $up, $dryRun);
    }

    /**
     * Executes install/uninstall migration through InstallConfiguration/InstallVersion/InstallMigration
     *
     * @param bool $up
     * @param bool $dryRun
     */
    protected function executeInstallMigration(bool $up = true, bool $dryRun = false)
    {
        $configuration = $this->getInstallMigrationConfiguration();

        $output = $up ? 'Installing' : 'Uninstalling';
        $output .= ' bundle <comment>%s</comment>' . "\n";

        $this->outputWriter->write(sprintf($output, $this->bundle->getName()));

        $migration = new Migration($configuration);

        if ($up) {
            $migration->migrate(InstallVersion::INSTALL_VERSION, $dryRun);
        } else {
            $migration->migrate('0', $dryRun);
        }
    }

    /**
     * Defines any migrations marked as migrated for this bundle should be removed from the
     * migration status table on uninstallation. If the bundle is installed again, migrations
     * would be re-executed.
     *
     * @return bool
     */
    protected function clearMigratedVersionsOnUninstall(): bool
    {
        return true;
    }

    /**
     * Creates a schema instance for the current database.
     *
     * @return Schema
     */
    protected function createSchema(): Schema
    {
        return $this->connection->getSchemaManager()->createSchema();
    }
}
