<?php

namespace Pimcore\Tests\Helper;

use Codeception\Exception\ModuleException;
use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Pimcore\Cache;
use Pimcore\Config;
use Pimcore\Event\TestEvents;
use Pimcore\Kernel;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Tool\Setup;
use Symfony\Component\Filesystem\Filesystem;

class Pimcore extends Module\Symfony
{
    /**
     * @inheritDoc
     */
    public function __construct(ModuleContainer $moduleContainer, $config = null)
    {
        // simple unit tests do not need a test DB and run
        // way faster if no DB has to be initialized first, so
        // we enable DB support on a suite level (connect_db)
        $this->config = array_merge($this->config, [
            // skip DB tests flag
            'skip_db_tests' => getenv('PIMCORE_TEST_SKIP_DB'),

            // try to connect to DB
            'connect_db' => false,

            // initialize DB (drop & re-create) - depends on connect_db
            'initialize_db' => true,

            // purge class directory on boot - depends on connect_db
            'purge_class_directory' => true,
        ]);

        parent::__construct($moduleContainer, $config);
    }

    /**
     * @return Pimcore|Module
     */
    public function getPimcoreModule()
    {
        return $this->getModule('\\' . __CLASS__);
    }

    /**
     * @return \Symfony\Component\HttpKernel\Kernel|Kernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer()
    {
        return $this->kernel->getContainer();
    }

    public function _initialize()
    {
        Config::setEnvironment($this->config['environment']);

        // don't initialize the kernel multiple times if running multiple suites
        // TODO can this lead to side-effects?
        if (null !== $kernel = \Pimcore::getKernel()) {
            $this->kernel = $kernel;
        } else {
            $this->initializeKernel();
        }

        // connect and initialize DB
        $this->setupDbConnection();

        // disable cache
        Cache::disable();
    }

    /**
     * Initialize the kernel (see parent Symfony module)
     */
    protected function initializeKernel()
    {
        $maxNestingLevel   = 200; // Symfony may have very long nesting level
        $xdebugMaxLevelKey = 'xdebug.max_nesting_level';
        if (ini_get($xdebugMaxLevelKey) < $maxNestingLevel) {
            ini_set($xdebugMaxLevelKey, $maxNestingLevel);
        }

        require_once __DIR__ . '/../../../config/constants.php';
        $this->setupPimcoreDirectories();

        $this->kernel = require_once __DIR__ . '/../../../config/startup.php';
        $this->kernel->boot();

        if ($this->config['cache_router'] === true) {
            $this->persistService('router', true);
        }

        // dispatch kernel booted event - will be used from services which need to reset state between tests
        $this->kernel->getContainer()->get('event_dispatcher')->dispatch(TestEvents::KERNEL_BOOTED);
    }

    protected function setupPimcoreDirectories()
    {
        $directories = [
            PIMCORE_CLASS_DIRECTORY,
            PIMCORE_ASSET_DIRECTORY
        ];

        $filesystem = new Filesystem();
        foreach ($directories as $directory) {
            if (!$filesystem->exists($directory)) {
                $filesystem->mkdir($directory, 0755);
            }
        }
    }

    /**
     * @return Connection
     */
    protected function getDbConnection()
    {
        return $this->getContainer()->get('database_connection');
    }

    /**
     * @param Connection $connection
     *
     * @return string
     */
    protected function getDbName(Connection $connection)
    {
        return $connection->getParams()['dbname'];
    }

    /**
     * Connect to DB and optionally initialize a new DB
     */
    protected function setupDbConnection()
    {
        if (!$this->config['connect_db']) {
            return;
        }

        if ($this->config['skip_db_tests']) {
            $this->debug('[DB] Not connecting to DB as skip_db_tests is set');

            return;
        }

        $connection = $this->getDbConnection();

        $connected  = false;
        if ($this->config['initialize_db']) {
            // (re-)initialize DB
            $connected = $this->initializeDb($connection);
        } else {
            // just try to connect without initializing the DB
            $this->connectDb($connection);
            $connected = true;
        }

        if ($connected) {
            !defined('PIMCORE_TEST_DB_INITIALIZED') && define('PIMCORE_TEST_DB_INITIALIZED', true);
        }

        if ($this->config['purge_class_directory']) {
            $this->purgeClassDirectory();
        }
    }

    /**
     * Initialize (drop, re-create and setup) the test DB
     *
     * @param Connection $connection
     *
     * @return bool
     *
     * @throws ModuleException
     */
    protected function initializeDb(Connection $connection)
    {
        $dbName = $this->getDbName($connection);

        $this->debug(sprintf('[DB] Initializing DB %s', $dbName));

        $connection = $this->getDbConnection();
        $this->dropAndCreateDb($connection);

        $this->connectDb($connection);

        /** @var Setup|Setup\Dao $setup */
        $setup = new Setup();
        $setup->database();

        $setup->contents([
            'username' => 'admin',
            'password' => microtime()
        ]);

        $this->debug(sprintf('[DB] Initialized the test DB %s', $dbName));

        return true;
    }

    /**
     * Drop and re-create the DB
     *
     * @param Connection $connection
     */
    protected function dropAndCreateDb(Connection $connection)
    {
        $dbName = $this->getDbName($connection);
        $params = $connection->getParams();
        $config = $connection->getConfiguration();

        unset($params['url']);
        unset($params['dbname']);

        // use a dedicated setup connection as the framework connection is bound to the DB and will
        // fail if the DB doesn't exist
        $setupConnection = DriverManager::getConnection($params, $config);
        $schemaManager   = $setupConnection->getSchemaManager();

        $databases = $schemaManager->listDatabases();
        if (in_array($dbName, $databases)) {
            $this->debug(sprintf('[DB] Dropping DB %s', $dbName));
            $schemaManager->dropDatabase($connection->quoteIdentifier($dbName));
        }

        $this->debug(sprintf('[DB] Creating DB %s', $dbName));
        $schemaManager->createDatabase($connection->quoteIdentifier($dbName));
    }

    /**
     * Try to connect to the DB and set constant if connection was successful.
     *
     * @param Connection $connection
     */
    protected function connectDb(Connection $connection)
    {
        if (!$connection->isConnected()) {
            $connection->connect();
        }

        $this->debug(sprintf('[DB] Successfully connected to DB %s', $connection->getDatabase()));
    }

    /**
     * Remove and re-create class directory
     */
    protected function purgeClassDirectory()
    {
        $filesystem = new Filesystem();
        if (file_exists(PIMCORE_CLASS_DIRECTORY)) {
            $this->debug('[INIT] Purging class directory ' . PIMCORE_CLASS_DIRECTORY);

            $filesystem->remove(PIMCORE_CLASS_DIRECTORY);
            $filesystem->mkdir(PIMCORE_CLASS_DIRECTORY, 0755);
        }
    }

    /**
     * @inheritDoc
     */
    public function _before(\Codeception\TestInterface $test)
    {
        parent::_before($test);

        // default pimcore state is non-admin
        $this->unsetAdminMode();
    }

    /**
     * Set pimcore into admin state
     */
    public function setAdminMode()
    {
        \Pimcore::setAdminMode();
        Document::setHideUnpublished(false);
        DataObject\AbstractObject::setHideUnpublished(false);
        DataObject\AbstractObject::setGetInheritedValues(false);
        DataObject\Localizedfield::setGetFallbackValues(false);
    }

    /**
     * Set pimcore into non-admin state
     */
    public function unsetAdminMode()
    {
        \Pimcore::unsetAdminMode();
        Document::setHideUnpublished(true);
        DataObject\AbstractObject::setHideUnpublished(true);
        DataObject\AbstractObject::setGetInheritedValues(true);
        DataObject\Localizedfield::setGetFallbackValues(true);
    }
}
