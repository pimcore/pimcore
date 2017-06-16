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
use Pimcore\Model\Tool\Setup;
use Pimcore\Tool;
use Pimcore\Tool\Requirements;
use Pimcore\Tool\Requirements\Check;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

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
     * @var bool
     */
    private $overwriteExistingFiles = true;

    /**
     * @var bool
     */
    private $symlink = false;

    /**
     * @param LoggerInterface $logger
     * @param ProfileLocator $profileLocator
     */
    public function __construct(LoggerInterface $logger, ProfileLocator $profileLocator)
    {
        $this->logger         = $logger;
        $this->profileLocator = $profileLocator;
    }

    /**
     * @param bool $overwriteExistingFiles
     */
    public function setOverwriteExistingFiles(bool $overwriteExistingFiles)
    {
        $this->overwriteExistingFiles = $overwriteExistingFiles;
    }

    /**
     * @param bool $symlink
     */
    public function setSymlink(bool $symlink)
    {
        $this->symlink = $symlink;
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
                    $errors[] = sprintf('<a href="%s" target="_blank">%s</a>', $check->getLink(), $check->getMessage());
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
        $dbConfig = $this->normalizeDbConfig($params);

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

        $profileId = 'empty';
        if (isset($params['profile'])) {
            $profileId = $params['profile'];
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

    private function normalizeDbConfig(array $params): array
    {
        // database configuration host/unix socket
        $dbConfig = [
            'user'         => $params['mysql_username'],
            'password'     => $params['mysql_password'],
            'dbname'       => $params['mysql_database'],
            'driver'       => 'pdo_mysql',
            'wrapperClass' => 'Pimcore\Db\Connection',
        ];

        $hostSocketValue = $params['mysql_host_socket'];
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

        $setup = new Setup();

        $dbConfig['username'] = $dbConfig['user'];
        unset($dbConfig['user']);
        unset($dbConfig['driver']);
        unset($dbConfig['wrapperClass']);

        $setup->config([
            'database' => [
                'params' => $dbConfig
            ],
        ]);

        $errors = $this->copyProfileFiles($profile, $errors);
        if (count($errors) > 0) {
            return $errors;
        }

        $kernel = new \AppKernel(Config::getEnvironment(), true);
        \Pimcore::setKernel($kernel);

        $kernel->boot();
        $setup->database();

        $errors = $this->setupProfileDatabase($setup, $profile, $userCredentials, $errors);

        Tool::clearSymfonyCache($kernel->getContainer());

        return $errors;
    }

    private function copyProfileFiles(Profile $profile, array $errors = []): array
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
