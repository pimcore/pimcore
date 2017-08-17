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
use Pimcore\Db\Connection;
use Pimcore\Extension\Bundle\Installer\Exception\InstallationException;
use Pimcore\Extension\Bundle\Installer\Exception\UpdateException;
use Pimcore\Migrations\Configuration\Configuration;
use Pimcore\Migrations\MigrationManager;
use Pimcore\Migrations\Version;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Stopwatch\Stopwatch;

abstract class MigrationAwareInstaller extends AbstractInstaller implements MigrationAwareInstallerInterface
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

    public function __construct(
        BundleInterface $bundle,
        Connection $connection,
        MigrationManager $migrationManager
    )
    {
        $this->bundle           = $bundle;
        $this->connection       = $connection;
        $this->migrationManager = $migrationManager;
    }

    /**
     * @inheritdoc
     */
    public function getMigrationConfiguration(): Configuration
    {
        return $this->migrationManager->getBundleConfiguration($this->bundle);
    }

    public function install()
    {
        if (!$this->canBeInstalled()) {
            throw new InstallationException(sprintf('Bundle "%s" can\'t be installed', $this->bundle->getName()));
        }

        /** @var Version $installMigrationVersion */
        $installMigrationVersion   = null;
        $installMigrationVersionId = $this->getMigrationVersion();

        if (!empty($installMigrationVersionId)) {
            $installMigrationVersion = $this->migrationManager->getBundleVersion(
                $this->bundle,
                $installMigrationVersionId
            );
        }

        // install initial schema
        $this->installSchema();

        // custom install logic
        $this->doInstall();

        // mark migrated version
        if (null !== $installMigrationVersion) {
            $this->migrationManager->markVersionAsMigrated($installMigrationVersion);
        }

        // run update (remaining migrations, ...)
        $this->updateAfterInstall();
    }

    protected function doInstall()
    {
        // noop - to be implemented on demand for custom installation logic
    }

    protected function updateAfterInstall()
    {
        if ($this->canBeUpdated()) {
            $this->update();
        }
    }

    public function uninstall()
    {
        if (!$this->canBeUninstalled()) {
            throw new InstallationException(sprintf('Bundle "%s" can\'t be uninstalled', $this->bundle->getName()));
        }

        $this->uninstallSchema();
        $this->doUninstall();

        if ($this->clearMigratedVersionsOnUninstall()) {
            $configuration = $this->migrationManager->getBundleConfiguration($this->bundle);
            $configuration->clearMigratedVersions();
        }
    }

    protected function doUninstall()
    {
        // noop - to be implemented on demand for custom uninstallation logic
    }

    public function update()
    {
        if (!$this->canBeUpdated()) {
            throw new UpdateException(sprintf('Bundle "%s" can\'t be updated', $this->bundle->getName()));
        }

        $configuration = $this->getMigrationConfiguration();

        $latestVersion  = $configuration->getLatestVersion();
        $currentVersion = $configuration->getCurrentVersion();

        // check if there's a latest version > 0
        if ('0' === (string)$latestVersion) {
            throw new UpdateException('There\'s no version to migrate to');
        }

        // check if we would migrate down
        if ($currentVersion > $latestVersion) {
            throw new UpdateException(sprintf(
                'Can\'t migrate down (current version: "%s", latest version: "%s")',
                $currentVersion, $latestVersion
            ));
        }

        // migrate to the latest version
        $migration = new Migration($this->getMigrationConfiguration());
        $migration->migrate($latestVersion);

        $this->doUpdate();
    }

    protected function doUpdate()
    {
        // noop - to be implemented on demand for custom update logic
    }

    public function canBeUpdated(): bool
    {
        return $this->isInstalled() && $this->getMigrationConfiguration()->getNumberOfNewMigrations() > 0;
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
        $version = $this->migrationManager->getBundleVersion($this->bundle, $versionId);

        return $this->migrationManager->executeVersion($version, $up, $dryRun);
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
     * Updates database with custom schema on installation
     */
    protected function installSchema()
    {
        $this->runSchemaMigration(true);
    }

    /**
     * Removes custom schema from database on uninstallation
     */
    protected function uninstallSchema()
    {
        $this->runSchemaMigration(false);
    }

    /**
     * Executes schema changes
     *
     * @param bool $up
     */
    protected function runSchemaMigration(bool $up = true)
    {
        $outputWriter = $this->getMigrationConfiguration()->getOutputWriter();

        $fromSchema = $this->connection->getSchemaManager()->createSchema();
        $toSchema   = clone $fromSchema;

        $output = $up ? 'Installing' : 'Uninstalling';
        $output .= ' bundle <comment>%s</comment>' . "\n";

        $outputWriter->write(sprintf($output, $this->bundle->getName()));

        if ($up) {
            $this->populateSchema($toSchema);
        } else {
            $this->unpopulateSchema($toSchema);
        }

        $sql = $fromSchema->getMigrateToSql($toSchema, $this->connection->getDatabasePlatform());

        if (0 === count($sql)) {
            $outputWriter->write('<comment>No migrations to execute.</comment>');
        }

        $i = 0;
        $time = 0;
        $stopwatch = new Stopwatch();

        foreach ($sql as $query) {
            $evt = $stopwatch->start('query_' . $i++);

            $outputWriter->write('     <comment>-></comment> ' . $query);
            $this->connection->executeQuery($query);

            $evt->stop();
            $time += $evt->getDuration();
        }

        $time = round($time/1000, 2);

        $outputWriter->write("\n  <comment>------------------------</comment>\n");
        $outputWriter->write(sprintf("  <info>++</info> finished in %ss", $time));
        $outputWriter->write(sprintf("  <info>++</info> %s sql queries", count($sql)));
        $outputWriter->write("\n");
    }
}
