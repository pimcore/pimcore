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
use Pimcore\Extension;
use Pimcore\Extension\Bundle\Config\StateConfig;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Install\Event\InstallerStepEvent;
use Pimcore\Install\Profile\FileInstaller;
use Pimcore\Install\Profile\Profile;
use Pimcore\Install\Profile\ProfileLocator;
use Pimcore\Install\SystemConfig\ConfigWriter;
use Pimcore\Model\Tool\Setup;
use Pimcore\Tool\AssetsInstaller;
use Pimcore\Tool\Requirements;
use Pimcore\Tool\Requirements\Check;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Installer
{
    const EVENT_NAME_STEP = 'pimcore.installer.step';

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
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

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

    /**
     * @var array
     */
    private $stepEvents = [
        'validate_parameters' => 'Validating input parameters...',
        'check_prerequisites' => 'Checking prerequisites...',
        'load_profile'        => 'Loading install profile...',
        'start_install'       => 'Starting installation...',
        'copy_files'          => 'Copying profile files...',
        'create_config_files' => 'Creating config files...',
        'boot_kernel'         => 'Booting new kernel...',
        'setup_database'      => 'Running database setup...',
        'install_assets'      => 'Installing assets...',
        'install_bundles'     => 'Installing bundles...',
        'complete'            => 'Install complete!'
    ];

    public function __construct(
        LoggerInterface $logger,
        ProfileLocator $profileLocator,
        FileInstaller $fileInstaller,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->logger          = $logger;
        $this->profileLocator  = $profileLocator;
        $this->fileInstaller   = $fileInstaller;
        $this->eventDispatcher = $eventDispatcher;
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

    public function checkPrerequisites(Connection $db = null): array
    {
        $checks = array_merge(
            Requirements::checkFilesystem(),
            Requirements::checkPhp(),
            null !== $db ? Requirements::checkMysql($db) : []
        );

        return $this->formatPrerequisiteMessages($checks, [Check::STATE_ERROR]);
    }

    /**
     * @param Check[] $checks
     * @param array $filterStates
     *
     * @return array
     */
    public function formatPrerequisiteMessages(array $checks, array $filterStates = [Check::STATE_ERROR])
    {
        $messages = [];
        foreach ($checks as $check) {
            if (empty($filterStates) || !in_array($check->getState(), $filterStates)) {
                continue;
            }

            if ($check->getLink()) {
                if ('cli' === php_sapi_name()) {
                    $messages[] = sprintf('%s (see %s)', $check->getMessage(), $check->getLink());
                } else {
                    $messages[] = sprintf('<a href="%s" target="_blank">%s</a>', $check->getLink(), $check->getMessage());
                }
            } else {
                $messages[] = $check->getMessage();
            }
        }

        return $messages;
    }

    public function getStepEventCount(): int
    {
        return count($this->stepEvents);
    }

    private function dispatchStepEvent(string $type, string $message = null)
    {
        if (!isset($this->stepEvents[$type])) {
            throw new \InvalidArgumentException(sprintf('Trying to dispatch unsupported event type "%s"', $type));
        }

        $message = $message ?? $this->stepEvents[$type];
        $step    = array_search($type, array_keys($this->stepEvents)) + 1;

        $event = new InstallerStepEvent($type, $message, $step, $this->getStepEventCount());

        $this->eventDispatcher->dispatch(self::EVENT_NAME_STEP, $event);

        return $event;
    }

    /**
     * @param array $params
     *
     * @return array Array of errors
     */
    public function install(array $params): array
    {
        $this->dispatchStepEvent('validate_parameters');

        $dbConfig = $this->resolveDbConfig($params);
        $errors   = [];

        // try to establish a mysql connection
        try {
            $config = new Configuration();

            /** @var Connection $db */
            $db = DriverManager::getConnection($dbConfig, $config);

            $this->dispatchStepEvent('check_prerequisites');

            // check all db-requirements before install
            $errors = $this->checkPrerequisites($db);

            if (count($errors) > 0) {
                return $errors;
            }
        } catch (\Exception $e) {
            $errors[] = sprintf('Couldn\'t establish connection to MySQL: %s', $e->getMessage());

            return $errors;
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

        $this->dispatchStepEvent('load_profile');

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

        $this->dispatchStepEvent('start_install');

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

        $this->dispatchStepEvent('copy_files');

        $errors = [];
        if ($this->installProfileFiles) {
            $errors = $this->fileInstaller->installFiles($profile, $this->overwriteExistingFiles, $this->symlink);
            if (count($errors) > 0) {
                return $errors;
            }
        }

        $this->dispatchStepEvent('create_config_files');

        $dbConfig['username'] = $dbConfig['user'];
        unset($dbConfig['user']);
        unset($dbConfig['driver']);
        unset($dbConfig['wrapperClass']);

        $this->createConfigFiles([
            'database' => [
                'params' => $dbConfig
            ],
        ]);

        $this->enableBundles($profile);

        $this->dispatchStepEvent('boot_kernel');

        // resolve environment with default=dev here as we set debug mode to true and want to
        // load the kernel for the same environment as the app.php would do. the kernel booted here
        // will always be in "dev" with the exception of an environment set via env vars
        $environment = Config::getEnvironment(true, 'dev');
        $kernel      = new \AppKernel($environment, true);

        $this->clearKernelCacheDir($kernel);

        \Pimcore::setKernel($kernel);

        $kernel->boot();

        $this->dispatchStepEvent('setup_database');

        $setup = new Setup();
        $setup->database();

        $errors = $this->setupProfileDatabase($setup, $profile, $userCredentials, $errors);

        $this->dispatchStepEvent('install_assets');
        $this->installAssets($kernel);

        $this->dispatchStepEvent('install_bundles');
        $errors = array_merge($this->installBundles($kernel, $profile), $errors);

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

            $stdErr  = $io->getErrorStyle();
            $process = $e->getProcess();

            $errorOutput = trim($process->getErrorOutput());
            if (!empty($errorOutput)) {
                $stdErr->write($errorOutput);
            }

            $stdErr->write($process->getOutput());
            $stdErr->write($process->getErrorOutput());
            $stdErr->note('Installing assets failed. Please run the following command manually:');
            $stdErr->writeln('  ' . str_replace("'", '', $process->getCommandLine()));
        }
    }

    private function enableBundles(Profile $profile)
    {
        $stateConfig = new StateConfig(new Extension\Config());

        foreach ($profile->getBundlesToEnable() as $id => $config) {
            $stateConfig->setState($id, $config);
        }
    }

    private function installBundles(KernelInterface $kernel, Profile $profile): array
    {
        if (empty($installBundles = $profile->getBundlesToInstall())) {
            return [];
        }

        $bundleManager = $kernel->getContainer()->get(PimcoreBundleManager::class);
        $errors        = [];

        foreach ($installBundles as $installBundle) {
            if (null !== $this->commandLineOutput) {
                $this->commandLineOutput->writeln(sprintf('  <comment>*</comment> Installing bundle <info>%s</info>', $installBundle));
            }

            try {
                $bundle = $bundleManager->getActiveBundle($installBundle, false);
                if ($bundleManager->canBeInstalled($bundle)) {
                    $bundleManager->install($bundle);
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

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
