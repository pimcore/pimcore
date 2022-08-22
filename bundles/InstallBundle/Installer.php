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

namespace Pimcore\Bundle\InstallBundle;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\DriverManager;
use PDO;
use Pimcore\Bundle\InstallBundle\Event\InstallerStepEvent;
use Pimcore\Bundle\InstallBundle\SystemConfig\ConfigWriter;
use Pimcore\Config;
use Pimcore\Console\Style\PimcoreStyle;
use Pimcore\Db\Helper;
use Pimcore\Model\User;
use Pimcore\Tool\AssetsInstaller;
use Pimcore\Tool\Console;
use Pimcore\Tool\Requirements;
use Pimcore\Tool\Requirements\Check;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
class Installer
{
    const EVENT_NAME_STEP = 'pimcore.installer.step';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Predefined DB credentials from config
     *
     * @var array
     */
    private $dbCredentials;

    /**
     * @var PimcoreStyle|null
     */
    private ?PimcoreStyle $commandLineOutput = null;

    /**
     * When false, skips creating database structure during install
     *
     * @var bool
     */
    private $createDatabaseStructure = true;

    /**
     * When false, skips importing all database data during install
     *
     * @var bool
     */
    private $importDatabaseData = true;

    /**
     * When false, skips importing database data dump files (if available) during install
     * only imports needed base data
     *
     * @var bool
     */
    private $importDatabaseDataDump = true;

    /**
     * skip writing database.yml file
     *
     * @var bool
     */
    private $skipDatabaseConfig = false;

    /**
     * @param bool $skipDatabaseConfig
     */
    public function setSkipDatabaseConfig(bool $skipDatabaseConfig): void
    {
        $this->skipDatabaseConfig = $skipDatabaseConfig;
    }

    /**
     * @var array
     */
    private $stepEvents = [
        'validate_parameters' => 'Validating input parameters...',
        'check_prerequisites' => 'Checking prerequisites...',
        'start_install' => 'Starting installation...',
        'create_config_files' => 'Creating config files...',
        'boot_kernel' => 'Booting new kernel...',
        'setup_database' => 'Running database setup...',
        'install_assets' => 'Installing assets...',
        'install_classes' => 'Installing classes ...',
        'install_custom_layouts' => 'Installing custom layouts ...',
        'migrations' => 'Marking all migrations as done ...',
        'complete' => 'Install complete!',
    ];

    public function __construct(
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setDbCredentials(array $dbCredentials = [])
    {
        $this->dbCredentials = $dbCredentials;
    }

    public function setCommandLineOutput(PimcoreStyle $commandLineOutput)
    {
        $this->commandLineOutput = $commandLineOutput;
    }

    /**
     * @param bool $createDatabaseStructure
     */
    public function setCreateDatabaseStructure(bool $createDatabaseStructure): void
    {
        $this->createDatabaseStructure = $createDatabaseStructure;
    }

    /**
     * @param bool $importDatabaseData
     */
    public function setImportDatabaseData(bool $importDatabaseData): void
    {
        $this->importDatabaseData = $importDatabaseData;
    }

    /**
     * @param bool $importDatabaseDataDump
     */
    public function setImportDatabaseDataDump(bool $importDatabaseDataDump): void
    {
        $this->importDatabaseDataDump = $importDatabaseDataDump;
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
        $step = array_search($type, array_keys($this->stepEvents)) + 1;

        $event = new InstallerStepEvent($type, $message, $step, $this->getStepEventCount());

        $this->eventDispatcher->dispatch($event, self::EVENT_NAME_STEP);

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
        $errors = [];

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

        if (!empty($errors)) {
            return $errors;
        }

        $this->dispatchStepEvent('start_install');

        try {
            return $this->runInstall(
                $dbConfig,
                [
                    'username' => $adminUser,
                    'password' => $adminPass,
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error((string) $e);

            return [
                $e->getMessage(),
            ];
        }
    }

    public function resolveDbConfig(array $params): array
    {
        $dbConfig = [
            'host' => 'localhost',
            'port' => 3306,
            'driver' => 'pdo_mysql',
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
            'user' => $params['mysql_username'],
            'password' => $params['mysql_password'],
            'dbname' => $params['mysql_database'],
        ]);

        $hostSocketValue = $params['mysql_host_socket'];

        // use value as unix socket if file exists
        if (file_exists($hostSocketValue)) {
            $dbConfig['unix_socket'] = $hostSocketValue;
        } else {
            $dbConfig['host'] = $hostSocketValue;
            $dbConfig['port'] = $params['mysql_port'];
        }

        $mysqlSslCertPath = $params['mysql_ssl_cert_path'];
        if (!empty($mysqlSslCertPath)) {
            $dbConfig['driverOptions'] = [
                PDO::MYSQL_ATTR_SSL_CA => $mysqlSslCertPath,
            ];
        }

        return $dbConfig;
    }

    private function runInstall(array $dbConfig, array $userCredentials): array
    {
        $errors = [];

        $this->dispatchStepEvent('create_config_files');

        unset($dbConfig['driver']);
        unset($dbConfig['wrapperClass']);

        if (isset($dbConfig['driverOptions'])) {
            $dbConfig['options'] = $dbConfig['driverOptions'];
            unset($dbConfig['driverOptions']);
        }

        $dbConfig['mapping_types'] = [
            'enum' => 'string',
            'bit' => 'boolean',
        ];

        $doctrineConfig = [
            'doctrine' => [
                'dbal' => [
                    'connections' => [
                        'default' => $dbConfig,
                    ],
                ],
            ],
        ];

        $this->createConfigFiles($doctrineConfig);

        $this->dispatchStepEvent('boot_kernel');

        // resolve environment with default=dev here as we set debug mode to true and want to
        // load the kernel for the same environment as the app.php would do. the kernel booted here
        // will always be in "dev" with the exception of an environment set via env vars
        $environment = Config::getEnvironment();

        $kernel = \App\Kernel::class;

        if (isset($_ENV['PIMCORE_KERNEL_CLASS'])) {
            $kernel = $_ENV['PIMCORE_KERNEL_CLASS'];
        }

        $kernel = new $kernel($environment, true);

        $this->clearKernelCacheDir($kernel);

        \Pimcore::setKernel($kernel);

        $kernel->boot();

        $this->dispatchStepEvent('setup_database');

        $errors = $this->setupDatabase($userCredentials, $errors);

        if (!$this->skipDatabaseConfig) {
            // now we're able to write the server version to the database.yml
            $db = \Pimcore\Db::get();
            if ($db instanceof Connection) {
                $connection = $db->getWrappedConnection();
                if ($connection instanceof ServerInfoAwareConnection) {
                    $writer = new ConfigWriter();
                    $doctrineConfig['doctrine']['dbal']['connections']['default']['server_version'] = $connection->getServerVersion();
                    $writer->writeDbConfig($doctrineConfig);
                }
            }
        }

        $this->dispatchStepEvent('install_assets');
        $this->installAssets($kernel);

        $this->dispatchStepEvent('install_classes');
        $this->installClasses();

        $this->dispatchStepEvent('install_custom_layouts');
        $this->installCustomLayouts();

        $this->dispatchStepEvent('migrations');
        $this->markMigrationsAsDone();

        $this->clearKernelCacheDir($kernel);

        return $errors;
    }

    private function runCommand(array $arguments, string $taskName)
    {
        $io = $this->commandLineOutput;

        try {
            array_splice($arguments, 0, 0, [
                Console::getPhpCli(),
                PIMCORE_PROJECT_ROOT . '/bin/console',
            ]);

            $this->logger->info('Running {command} command', ['command' => $arguments]);

            $process = new Process($arguments);
            $process->setTimeout(0);
            $process->setWorkingDirectory(PIMCORE_PROJECT_ROOT);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            if (null !== $io) {
                $io->writeln($process->getOutput());
            }
        } catch (ProcessFailedException $e) {
            $this->logger->error($e->getMessage());

            if (null === $io) {
                return;
            }

            $stdErr = $io->getErrorStyle();
            $process = $e->getProcess();

            $errorOutput = trim($process->getErrorOutput());
            if (!empty($errorOutput)) {
                $stdErr->write($errorOutput);
            }

            $stdErr->write($process->getOutput());
            $stdErr->write($process->getErrorOutput());
            $stdErr->note($taskName . ' failed. Please run the following command manually:');
            $stdErr->writeln('  ' . str_replace("'", '', $process->getCommandLine()));
        }
    }

    private function markMigrationsAsDone()
    {
        $this->runCommand([
            'doctrine:migrations:sync-metadata-storage',
            '-q',
        ], 'Sync migrations metadata storage');

        $this->runCommand([
            'doctrine:migrations:version',
            '--all', '--add', '--prefix=Pimcore\\Bundle\\CoreBundle', '-n', '-q',
        ], 'Marking all migrations as done');
    }

    private function installClasses()
    {
        $this->runCommand([
            'pimcore:deployment:classes-rebuild',
            '-c',
        ], 'Installing class definitions');
    }

    private function installCustomLayouts()
    {
        $this->runCommand([
            'pimcore:deployment:custom-layouts-rebuild',
            '-c',
        ], 'Installing custom layout definitions');
    }

    private function installAssets(KernelInterface $kernel)
    {
        $this->logger->info('Running {command} command', ['command' => 'assets:install']);

        $assetsInstaller = $kernel->getContainer()->get(AssetsInstaller::class);
        $io = $this->commandLineOutput;

        try {
            $ansi = null !== $io && $io->isDecorated();

            $process = $assetsInstaller->install([
                'ansi' => $ansi,
            ]);

            if (null !== $io) {
                $io->writeln($process->getOutput());
            }
        } catch (ProcessFailedException $e) {
            $this->logger->error($e->getMessage());

            if (null === $io) {
                return;
            }

            $stdErr = $io->getErrorStyle();
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

    private function createConfigFiles(array $config)
    {
        $writer = new ConfigWriter();

        if (!$this->skipDatabaseConfig) {
            $writer->writeDbConfig($config);
        }

        $writer->writeSystemConfig();
    }

    private function clearKernelCacheDir(KernelInterface $kernel)
    {
        // we don't use $kernel->getCacheDir() here, since we want to have a fully clean cache dir at this point
        $cacheDir = PIMCORE_SYMFONY_CACHE_DIRECTORY;

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
        $filesystem->mkdir($cacheDir);

        try {
            $filesystem->remove($oldCacheDir);
        } catch (IOException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function setupDatabase(array $userCredentials, array $errors = []): array
    {
        $db = \Pimcore\Db::get();
        $db->executeQuery('SET FOREIGN_KEY_CHECKS=0;');

        if ($this->createDatabaseStructure) {
            $mysqlInstallScript = file_get_contents(__DIR__ . '/Resources/install.sql');

            // remove comments in SQL script
            $mysqlInstallScript = preg_replace("/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/", '', $mysqlInstallScript);

            // get every command as single part
            $mysqlInstallScripts = explode(';', $mysqlInstallScript);

            // execute every script with a separate call, otherwise this will end in a PDO_Exception "unbufferd queries, ..." seems to be a PDO bug after some googling
            foreach ($mysqlInstallScripts as $m) {
                $sql = trim($m);
                if (strlen($sql) > 0) {
                    $sql .= ';';
                    $db->executeQuery($sql);
                }
            }

            $cacheAdapter = new DoctrineDbalAdapter($db);
            $cacheAdapter->createTable();

            $doctrineTransportConn = new \Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection([], $db);
            $doctrineTransportConn->setup();
        }

        if ($this->importDatabaseData) {
            $dataFiles = $this->getDataFiles();

            try {
                if (empty($dataFiles) || !$this->importDatabaseDataDump) {
                    // empty installation
                    $this->insertDatabaseContents();
                    $this->createOrUpdateUser($userCredentials);
                } else {
                    foreach ($dataFiles as $dbFile) {
                        $this->logger->info('Importing DB file {dbFile}', ['dbFile' => $dbFile]);
                        $this->insertDatabaseDump($dbFile);
                    }

                    $this->createOrUpdateUser($userCredentials);
                }
            } catch (\Exception $e) {
                $this->logger->error((string) $e);
                $errors[] = $e->getMessage();
            }
        }

        $db->executeQuery('SET FOREIGN_KEY_CHECKS=1;');

        return $errors;
    }

    /**
     * @return array
     */
    protected function getDataFiles()
    {
        $files = glob(PIMCORE_PROJECT_ROOT . '/dump/*.sql');

        return $files;
    }

    protected function createOrUpdateUser($config = [])
    {
        $defaultConfig = [
            'username' => 'admin',
            'password' => bin2hex(random_bytes(16)),
        ];

        $settings = array_replace_recursive($defaultConfig, $config);

        if ($user = User::getByName($settings['username'])) {
            /** @var User $user */
            $user->delete();
        }

        $user = User::create([
            'parentId' => 0,
            'username' => $settings['username'],
            'password' => \Pimcore\Tool\Authentication::getPasswordHash($settings['username'], $settings['password']),
            'active' => true,
        ]);
        $user->setAdmin(true);
        $user->save();
    }

    /**
     * @param string $file
     *
     * @throws \Exception
     */
    public function insertDatabaseDump($file)
    {
        $db = \Pimcore\Db::get();
        $dumpFile = file_get_contents($file);

        // remove comments in SQL script
        $dumpFile = preg_replace("/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/", '', $dumpFile);

        if (strpos($file, 'atomic') !== false) {
            $db->executeStatement($dumpFile);
        } else {
            // get every command as single part - ; at end of line
            $singleQueries = explode(";\n", $dumpFile);

            // execute queries in bulk mode to prevent max_packet_size errors
            $batchQueries = [];
            foreach ($singleQueries as $m) {
                $sql = trim($m);
                if (strlen($sql) > 0) {
                    $batchQueries[] = $sql . ';';
                }

                if (count($batchQueries) > 500) {
                    $db->executeStatement(implode("\n", $batchQueries));
                    $batchQueries = [];
                }
            }

            $db->executeStatement(implode("\n", $batchQueries));
        }

        // set the id of the system user to 0
        $db->update('users', ['id' => 0], ['name' => 'system']);
    }

    protected function insertDatabaseContents()
    {
        $db = \Pimcore\Db::get();
        $db->insert('assets', Helper::quoteDataIdentifiers($db, [
            'id' => 1,
            'parentId' => 0,
            'type' => 'folder',
            'filename' => '',
            'path' => '/',
            'creationDate' => time(),
            'modificationDate' => time(),
            'userOwner' => 1,
            'userModification' => 1,
        ]));
        $db->insert('documents', Helper::quoteDataIdentifiers($db, [
            'id' => 1,
            'parentId' => 0,
            'type' => 'page',
            'key' => '',
            'path' => '/',
            'index' => 999999,
            'published' => 1,
            'creationDate' => time(),
            'modificationDate' => time(),
            'userOwner' => 1,
            'userModification' => 1,
        ]));
        $db->insert('documents_page', Helper::quoteDataIdentifiers($db, [
            'id' => 1,
            'controller' => 'App\\Controller\\DefaultController::defaultAction',
            'template' => '',
            'title' => '',
            'description' => '',
        ]));
        $db->insert('objects', Helper::quoteDataIdentifiers($db, [
            'o_id' => 1,
            'o_parentId' => 0,
            'o_type' => 'folder',
            'o_key' => '',
            'o_path' => '/',
            'o_index' => 999999,
            'o_published' => 1,
            'o_creationDate' => time(),
            'o_modificationDate' => time(),
            'o_userOwner' => 1,
            'o_userModification' => 1,
        ]));

        $db->insert('users', Helper::quoteDataIdentifiers($db, [
            'parentId' => 0,
            'name' => 'system',
            'admin' => 1,
            'active' => 1,
        ]));
        $db->update('users', ['id' => 0], ['name' => 'system']);

        $userPermissions = [
            'application_logging',
            'assets',
            'classes',
            'clear_cache',
            'clear_fullpage_cache',
            'clear_temp_files',
            'dashboards',
            'document_types',
            'documents',
            'emails',
            'gdpr_data_extractor',
            'glossary',
            'http_errors',
            'notes_events',
            'objects',
            'plugins', // TODO: to be removed in Pimcore 11
            'predefined_properties',
            'asset_metadata',
            'recyclebin',
            'redirects',
            'reports',
            'reports_config',
            'robots.txt',
            'routes',
            'seemode',
            'seo_document_editor',
            'share_configurations',
            'system_settings',
            'tags_configuration',
            'tags_assignment',
            'tags_search',
            'targeting',
            'thumbnails',
            'translations',
            'users',
            'website_settings',
            'admin_translations',
            'web2print_settings',
            'workflow_details',
            'notifications',
            'notifications_send',
            'sites',
            'objects_sort_method',
        ];

        foreach ($userPermissions as $permission) {
            $db->insert('users_permission_definitions', [
                $db->quoteIdentifier('key') => $permission,
            ]);
        }
    }
}
