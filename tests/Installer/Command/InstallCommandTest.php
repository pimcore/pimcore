<?php
declare(strict_types=1);

namespace Pimcore\Tests\Installer\Command;

use Pimcore\Bundle\InstallBundle\InstallerKernel;
use Pimcore\Config;
use Pimcore\Console\Application;
use Pimcore\Db;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Tests\Test\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * This test covers InstallCommand from the installer bundle. The real command is instantiated
 * similarly to how bin/pimcore-install does it. The command is executed triggering actual pimcore
 * installation.
 *
 * ATTENTION: This test is expected to be run in isolation from other test suits because it
 * significantly changes the execution environment. These changes are hard to contain and revert
 * to avoid affecting other tests.
 */
class InstallCommandTest extends TestCase
{
    /**
     * @var \Pimcore\Db\Connection
     */
    private $db;

    protected function setUp(): void
    {
        parent::setUp();

        // Installer needs access to pimcore core migrations. Migrations paths are configured in
        // such way that they can only be executed when pimcore is in vendor folder. In our case
        // pimcore is at the root of the repository and the migration paths are incorrect. To work
        // around this issue we create a symlink to the correct migrations folder under the path
        // that is configured in the config file.
        // @See bundles/CoreBundle/Resources/config/pimcore/default.yml:269
        $this->symlink(
            PIMCORE_PROJECT_ROOT."/vendor/pimcore/pimcore/bundles/CoreBundle/Migrations",
            PIMCORE_PROJECT_ROOT.'/bundles/CoreBundle/Migrations'
        );

        // These are the files that installer creates during installation. We throw them into a
        // recycle bin to clean up the environment for the tests. These files will be restored
        // during tear down.
        array_map(
            [$this, 'disposeFile'],
            [
                PIMCORE_APP_ROOT.'/config/local/database.yml',
                // If this file is present, install command will abort installation assuming pimcore is
                // already installed.
                Config::locateConfigFile('system.yml'),
            ]
        );

        // Get info in current database connection settings. We need these to pass database config
        // to the installer.
        $this->db = Db::get();
    }

    /**
     * Ensure that installer handles creation of the local database config as configured. By
     * default config file is created during installation. It is not created when opted out via
     * `--skip-database-config` command option.
     *
     * @test
     */
    public function installer_creates_db_config_file(): void
    {
        // Ensure that installer runs with no errors and creates system files as expected.
        $systemFile = Config::locateConfigFile('system.yml');
        $dbConfigFile = PIMCORE_APP_ROOT.'/config/local/database.yml';

        $this->assertInstallerCommandRunsSuccessfully();

        self::assertFileExists($systemFile);
        self::assertFileExists($dbConfigFile);

        // Ensure that local database config file is not created when opted out.
        unlink($systemFile);
        unlink($dbConfigFile);

        $this->assertInstallerCommandRunsSuccessfully(['--skip-database-config' => true]);

        self::assertFileExists($systemFile);
        self::assertFileNotExists($dbConfigFile);
    }

    protected function tearDown(): void
    {
        // Clean up the symlink for migrations.
        unlink(PIMCORE_PROJECT_ROOT.'/vendor/pimcore/pimcore/bundles/CoreBundle/Migrations');

        // Restore system files.
        array_map(
            [$this, 'restoreFile'],
            [
                Config::locateConfigFile('system.yml'),
                PIMCORE_APP_ROOT.'/config/local/database.yml',
                PIMCORE_PROJECT_ROOT.'/vendor/pimcore/pimcore/bundles/CoreBundle/Migrations',
            ]
        );

        parent::tearDown();
    }

    private function executeInstallerCliCommand(string $command, array $options = []): string
    {
        // @See bin/pimcore-install:50
        $kernel = new InstallerKernel(PIMCORE_PROJECT_ROOT, Config::getEnvironment(), true);

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $applicationTester = new ApplicationTester($application);
        $applicationTester->run(
            array_merge(['command' => $command], $options),
            ['capture_stderr_separately' => true]
        );

        return $applicationTester->getDisplay();
    }

    /**
     * Remove a file keeping the ability to restore it with self::restoreFile()
     */
    private function disposeFile(string $filePath): void
    {
        if (is_file($filePath)) {
            rename($filePath, $filePath.'.backup');
        }
    }

    /**
     * Restores a file disposed by self::disposeFile()
     *
     * @See self::disposeFile()
     */
    private function restoreFile(string $filePath): void
    {
        if (is_file($filePath.'.backup')) {
            rename($filePath.'.backup', $filePath);
        }
    }

    /**
     * Creates a symlink ($link) for a $target. If the link folder does not exist it will be
     * recursively craeted.
     */
    private function symlink(string $link, string $target): void
    {
        // Drop link / file if it already exists.
        if (is_dir($link) || is_file($link)) {
            $this->disposeFile($link);
        }

        $dir = dirname($link);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        symlink($target, $link);
    }

    /**
     * Executes installer cli command and assures that the output does not contain errors.
     *
     * @param array $mixinOptions
     * @return string command output as string
     */
    private function assertInstallerCommandRunsSuccessfully(array $mixinOptions = []): string
    {
        $output = $this->executeInstallerCliCommand(
            'pimcore:install',
            array_merge(
                [
                    '--admin-username' => 'admin',
                    '--admin-password' => microtime(),
                    '--mysql-database' => $this->db->getDatabase(),
                    '--mysql-username' => $this->db->getParams()['user'],
                    '--mysql-password' => $this->db->getParams()['password'],
                    '--mysql-host-socket' => $this->db->getParams()['host'],
                    '--no-interaction' => true,
                ],
                $mixinOptions
            )
        );
        self::assertStringNotContainsString('ERROR', $output);

        return $output;
    }
}
