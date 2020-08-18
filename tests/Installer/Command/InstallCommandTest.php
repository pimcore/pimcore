<?php
declare(strict_types=1);

namespace Pimcore\Tests\Installer\Command;

use Pimcore\Bundle\InstallBundle\InstallerKernel;
use Pimcore\Config;
use Pimcore\Console\Application;
use Pimcore\Tests\Test\TestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

class InstallCommandTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        // @See bundles/CoreBundle/Resources/config/pimcore/default.yml:269
        mkdir(PIMCORE_PROJECT_ROOT.'/vendor/pimcore/pimcore/bundles/CoreBundle/', 0777, true);
        symlink(
            PIMCORE_PROJECT_ROOT.'/bundles/CoreBundle/Migrations',
            PIMCORE_PROJECT_ROOT.'/vendor/pimcore/pimcore/bundles/CoreBundle/Migrations'
        );

        $dbConfigFile = PIMCORE_APP_ROOT.'/config/local/database.yml';
        if (is_file($dbConfigFile)) {
            rename($dbConfigFile, $dbConfigFile . '.backup');
        }

        $configFile = Config::locateConfigFile('system.yml');
        if (is_file($configFile)) {
            rename($configFile, $configFile . '.backup');
        }
    }

    protected function tearDown()
    {
        unlink(PIMCORE_PROJECT_ROOT.'/vendor/pimcore/pimcore/bundles/CoreBundle/Migrations');
        rmdir(PIMCORE_PROJECT_ROOT.'/vendor/pimcore/pimcore/bundles/CoreBundle/');
        rmdir(PIMCORE_PROJECT_ROOT.'/vendor/pimcore/pimcore/bundles/');
        rmdir(PIMCORE_PROJECT_ROOT.'/vendor/pimcore/pimcore/');
        rmdir(PIMCORE_PROJECT_ROOT.'/vendor/pimcore');

        $configFile = Config::locateConfigFile('system.yml');
        if (is_file($configFile . '.backup')) {
            rename($configFile.'.backup', $configFile);
        }

        $dbConfigFile = PIMCORE_APP_ROOT.'/config/local/database.yml';
        if (is_file($dbConfigFile . '.backup')) {
            rename($dbConfigFile.'.backup', $dbConfigFile);
        }

        parent::tearDown();
    }

    /**
     * @test
     */
    public function installer_creates_db_config_file(): void
    {
        $configFile = Config::locateConfigFile('system.yml');
        $dbConfigFile = PIMCORE_APP_ROOT.'/config/local/database.yml';

        $output = $this->executeCliCommand(
            'pimcore:install',
            [
                '--admin-username' => 'admin',
                '--admin-password' => 'admin',
                '--mysql-database' => 'pimcore_test',
                '--mysql-username' => 'root',
                '--mysql-password' => 'pimcore',
                '--mysql-host-socket' => 'db',
                '--no-interaction' => true,
            ]
        );
        self::assertStringNotContainsString('ERROR', $output);

        self::assertFileExists($configFile);
        self::assertFileExists($dbConfigFile);

        unlink($configFile);
        unlink($dbConfigFile);

        $this->executeCliCommand(
            'pimcore-install',
            [
                '--admin-username' => 'admin',
                '--admin-password' => 'admin',
                '--mysql-database' => 'pimcore_test',
                '--mysql-username' => 'root',
                '--mysql-password' => 'pimcore',
                '--mysql-host-socket' => 'db',
                '--no-interaction' => true,
                '--skip-database-config' => true,
            ]
        );

        self::assertFileNotExists($dbConfigFile);
    }

    private function executeCliCommand(string $command, array $options = []): string
    {
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
}
