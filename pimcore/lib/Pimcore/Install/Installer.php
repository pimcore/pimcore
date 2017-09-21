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
use Pimcore\Db\Connection;
use Pimcore\Install\Profile\Profile;
use Pimcore\Install\Profile\ProfileLocator;
use Pimcore\Install\SystemConfig\ConfigWriter;
use Pimcore\Model\Tool\Setup;
use Pimcore\Tool\Requirements;
use Pimcore\Tool\Requirements\Check;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

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
     * If false, profile files won't be copied
     *
     * @var bool
     */
    private $copyProfileFiles = true;

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

    public function __construct(
        LoggerInterface $logger,
        ProfileLocator $profileLocator
    )
    {
        $this->logger         = $logger;
        $this->profileLocator = $profileLocator;
    }

    public function setCopyProfileFiles(bool $copyProfile)
    {
        $this->copyProfileFiles = $copyProfile;
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
        $adminUser = $params['admin_username'];
        $adminPass = $params['admin_password'];

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
        if ($this->copyProfileFiles) {
            $errors = $this->doCopyProfileFiles($profile);
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

        $this->clearKernelCacheDir($kernel);

        return $errors;
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

    private function doCopyProfileFiles(Profile $profile, array $errors = []): array
    {
        $fs = new Filesystem();

        $symlink = $this->symlink;
        if ($symlink && '\\' === DIRECTORY_SEPARATOR) {
            $this->logger->warning('Symlink was chosen as installation method, but the installer can\'t symlink installation files on Windows. Copying selected files instead');
            $symlink = false;
        }

        $logAction = $symlink ? 'Symlinking' : 'Copying';

        foreach ($profile->getFilesToAdd() as $source => $target) {
            $target = PIMCORE_PROJECT_ROOT . '/' . $target;

            try {
                if ($fs->exists($target)) {
                    if ($this->overwriteExistingFiles) {
                        $this->logger->warning('Removing existing file {file}', [
                            'file' => $target
                        ]);

                        $fs->remove($target);
                    } else {
                        $this->logger->info('Skipping ' . $logAction . ' {source} to {target}. The target path already exists.');
                        continue;
                    }
                }

                $this->logger->info($logAction . ' {source} to {target}', [
                    'source' => $source,
                    'target' => $target
                ]);

                if ($symlink) {
                    // create symlinks as relative links to make them portable
                    $relativeSource = rtrim($fs->makePathRelative($source, dirname($target)), '/');

                    $fs->symlink($relativeSource, $target);
                } else {
                    if (is_dir($source)) {
                        $fs->mirror($source, $target);
                    } else {
                        $fs->copy($source, $target);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error($e);
                $errors[] = $e->getMessage();
            }
        }

        return $errors;
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
