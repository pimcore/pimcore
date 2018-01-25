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

namespace Pimcore\Install;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Pimcore\Config;
use Pimcore\Console\Style\PimcoreStyle;
use Pimcore\Db\Connection;
use Pimcore\Install\Profile\FileInstaller;
use Pimcore\Install\Profile\Profile;
use Pimcore\Install\Profile\ProfileLocator;
use Pimcore\Install\SystemConfig\ConfigWriter;
use Pimcore\Model\Tool\Setup;
use Pimcore\Tool\AssetsInstaller;
use Pimcore\Tool\Requirements;
use Pimcore\Tool\Requirements\Check;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Installer
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProfileLocator
     */
    private $profileLocator;

    /**
     * @var FileInstaller
     */
    private $fileInstaller;

    /**
     * If false, profile files won't be copied/symlinked
     *
     * @var bool
     */
    private $installProfileFiles = true;

    /**
     * @var bool
     */
    private $overwriteExistingFiles = true;

    /**
     * @var bool
     */
    private $symlink = false;

    /**
     * Predefined profile from config
     *
     * @var string
     */
    private $profile;

    /**
     * Predefined DB credentials from config
     *
     * @var array
     */
    private $dbCredentials;

    /**
     * @var PimcoreStyle
     */
    private $commandLineOutput;

    public function __construct(
        LoggerInterface $logger,
        ProfileLocator $profileLocator,
        FileInstaller $fileInstaller
    ) {
        $this->logger         = $logger;
        $this->profileLocator = $profileLocator;
        $this->fileInstaller  = $fileInstaller;
    }

    public function setInstallProfileFiles(bool $installFiles)
    {
        $this->installProfileFiles = $installFiles;
    }

    public function setProfile(string $profile = null)
    {
        $this->profile = $profile;
    }

    public function setDbCredentials(array $dbCredentials = [])
    {
        $this->dbCredentials = $dbCredentials;
    }

    public function setOverwriteExistingFiles(bool $overwriteExistingFiles)
    {
        $this->overwriteExistingFiles = $overwriteExistingFiles;
    }

    public function setSymlink(bool $symlink)
    {
        $this->symlink = $symlink;
    }

    public function setCommandLineOutput(PimcoreStyle $commandLineOutput)
    {
        $this->commandLineOutput = $commandLineOutput;
    }

    public function needsProfile(): bool
    {
        return null === $this->profile;
    }

    public function needsDbCredentials(): bool
    {
        return empty($this->dbCredentials);
    }

    public function checkPrerequisites(): array
    {
        /** @var Check[] $checks */
        $checks = array_merge(
            Requirements::checkFilesystem(),
            Requirements::checkPhp()
        );

        $errors = [];
        foreach ($checks as $check) {
            if ($check->getState() === Check::STATE_ERROR) {
                if ($check->getLink()) {
                    if ('cli' === php_sapi_name()) {
                        $errors[] = sprintf('%s (see %s)', $check->getMessage(), $check->getLink());
                    } else {
                        $errors[] = sprintf('<a href="%s" target="_blank">%s</a>', $check->getLink(), $check->getMessage());
                    }
                } else {
                    $errors[] = $check->getMessage();
                }
            }
        }

        return $errors;
    }

    /**
     * @param array $params
     *
     * @return array Array of errors
     */
    public function install(array $params): array
    {
        $dbConfig = $this->resolveDbConfig($params);

        // try to establish a mysql connection
        try {
            $config = new Configuration();

            /** @var Connection $db */
            $db = DriverManager::getConnection($dbConfig, $config);

            // check utf-8 encoding
            $result = $db->fetchRow('SHOW VARIABLES LIKE "character\_set\_database"');
            if (!in_array($result['Value'], ['utf8mb4'])) {
                $errors[] = 'Database charset is not utf8mb4';
            }
        } catch (\Exception $e) {
            $errors[] = sprintf('Couldn\'t establish connection to MySQL: %s', $e->getMessage());
        }

        // check username & password
        $adminUser = $params['admin_username'] ?? '';
        $adminPass = $params['admin_password'] ?? '';

        if (strlen($adminPass) < 4 || strlen($adminUser) < 4) {
            $errors[] = 'Username and password should have at least 4 characters';
        }

        $profileId = null;
        if (null !== $this->profile) {
            $profileId = $this->profile;
        } else {
            $profileId = 'empty';
            if (isset($params['profile'])) {
                $profileId = $params['profile'];
            }
        }

        if (empty($profileId)) {
            $errors[] = sprintf('Invalid profile ID');

            return $errors;
        }

        $profile = null;

        try {
            $profile = $this->profileLocator->getProfile($profileId);
        } catch (\Exception $e) {
            $errors[] = sprintf(
                htmlentities($e->getMessage(), ENT_QUOTES, 'UTF-8')
            );
        }

        if (!empty($errors)) {
            return $errors;
        }

        try {
            return $this->runInstall(
                $profile,
                $dbConfig,
                [
                    'username' => $adminUser,
                    'password' => $adminPass
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error($e);

            return [
                $e->getMessage()
            ];
        }
    }

    public function resolveDbConfig(array $params): array
    {
        $dbConfig = [
            'host'         => 'localhost',
            'port'         => 3306,
            'driver'       => 'pdo_mysql',
            'wrapperClass' => Connection::class,
        ];

        // do not handle parameters if db credentials are set via config
        if (!empty($this->dbCredentials)) {
            return array_merge(
                $dbConfig,
                $this->dbCredentials
            );
        }

        // database configuration host/unix socket
        $dbConfig = array_merge($dbConfig, [
            'user'     => $params['mysql_username'],
            'password' => $params['mysql_password'],
            'dbname'   => $params['mysql_database'],
        ]);

        $hostSocketValue = $params['mysql_host_socket'];

        // use value as unix socket if file exists
        if (file_exists($hostSocketValue)) {
            $dbConfig['unix_socket'] = $hostSocketValue;
        } else {
            $dbConfig['host'] = $hostSocketValue;
            $dbConfig['port'] = $params['mysql_port'];
        }

        return $dbConfig;
    }

    private function runInstall(Profile $profile, array $dbConfig, array $userCredentials): array
    {
        $this->logger->info('Running installation with profile {profile}', [
            'profile' => $profile->getName()
        ]);

        $errors = [];
        if ($this->installProfileFiles) {
            $errors = $this->fileInstaller->installFiles($profile, $this->overwriteExistingFiles, $this->symlink);
            if (count($errors) > 0) {
                return $errors;
            }
        }

        $dbConfig['username'] = $dbConfig['user'];
        unset($dbConfig['user']);
        unset($dbConfig['driver']);
        unset($dbConfig['wrapperClass']);

        $this->createConfigFiles([
            'database' => [
                'params' => $dbConfig
            ],
        ]);

        // resolve environment with default=dev here as we set debug mode to true and want to
        // load the kernel for the same environment as the app.php would do. the kernel booted here
        // will always be in "dev" with the exception of an environment set via env vars
        $environment = Config::getEnvironment(true, 'dev');
        $kernel = new \AppKernel($environment, true);

        $this->clearKernelCacheDir($kernel);

        \Pimcore::setKernel($kernel);

        $kernel->boot();

        $setup = new Setup();
        $setup->database();

        $errors = $this->setupProfileDatabase($setup, $profile, $userCredentials, $errors);

        $this->installAssets($kernel);

        $this->clearKernelCacheDir($kernel);

        return $errors;
    }

    private function installAssets(KernelInterface $kernel)
    {
        $this->logger->info('Running {command} command', ['command' => 'assets:install']);

        $assetsInstaller = $kernel->getContainer()->get(AssetsInstaller::class);
        $io              = $this->commandLineOutput;

        try {
            $ansi = null !== $io && $io->isDecorated();

            $process = $assetsInstaller->install([
                'ansi' => $ansi
            ]);

            if (null !== $io) {
                $io->writeln($process->getOutput());
            }
        } catch (ProcessFailedException $e) {
            $this->logger->error($e->getMessage());

            if (null === $io) {
                return;
            }

            $process = $e->getProcess();

            $io->writeln($process->getOutput());

            $io->note('Installing assets failed. Please run the following command manually:');
            $io->writeln('  ' . $process->getCommandLine());
        }
    }

    private function createConfigFiles(array $config)
    {
        $writer = new ConfigWriter();
        $writer->writeSystemConfig($config);
        $writer->writeDebugModeConfig();
        $writer->generateParametersFile();
    }

    private function clearKernelCacheDir(KernelInterface $kernel)
    {
        $cacheDir = $kernel->getCacheDir();

        if (!file_exists($cacheDir)) {
            return;
        }

        // see Symfony's cache:clear command
        $oldCacheDir = substr($cacheDir, 0, -1) . ('~' === substr($cacheDir, -1) ? '+' : '~');

        $filesystem = new Filesystem();
        if ($filesystem->exists($oldCacheDir)) {
            $filesystem->remove($oldCacheDir);
        }

        $filesystem->rename($cacheDir, $oldCacheDir);
        $filesystem->remove($oldCacheDir);
    }

    private function setupProfileDatabase(Setup $setup, Profile $profile, array $userCredentials, array $errors = []): array
    {
        try {
            if (empty($profile->getDbDataFiles())) {
                // empty installation
                $setup->contents($userCredentials);
            } else {
                foreach ($profile->getDbDataFiles() as $dbFile) {
                    $this->logger->info('Importing DB file {dbFile}', ['dbFile' => $dbFile]);
                    $setup->insertDump($dbFile);
                }

                $setup->createOrUpdateUser($userCredentials);
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
            $errors[] = $e->getMessage();
        }

        return $errors;
    }
}
