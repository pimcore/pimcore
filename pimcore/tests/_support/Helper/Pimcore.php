<?php

namespace Pimcore\Tests\Helper;

use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Codeception\Step;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Pimcore\Cache;
use Pimcore\Config;
use Pimcore\Kernel;
use Pimcore\Model\Document;
use Pimcore\Model\Object;
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
        // we enable DB support on a suite level
        $this->config = array_merge($this->config, [
            'connect_db'            => false, // try to connect to DB
            'initialize_db'         => true,  // initialize DB (drop & re-create)
            'purge_class_directory' => true,  // purge class directory on boot
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

        if ($this->config['connect_db']) {
            // (re-)initialize DB if DB support was requested when
            // loading the module
            if ($this->config['initialize_db']) {
                if ($this->initializeDb()) {
                    !defined('PIMCORE_TEST_DB_INITIALIZED') && define('PIMCORE_TEST_DB_INITIALIZED', true);
                }
            } else {
                // just try to connect without initializing the DB
                $connection = $this->connectDb();
                if ($connection) {
                    define('PIMCORE_TEST_DB_INITIALIZED', true);
                }
            }

            if ($this->config['purge_class_directory']) {
                $this->purgeClassDirectory();
            }
        }

        // disable cache
        Cache::disable();
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
        Object\AbstractObject::setHideUnpublished(false);
        Object\AbstractObject::setGetInheritedValues(false);
        Object\Localizedfield::setGetFallbackValues(false);
    }

    /**
     * Set pimcore into non-admin state
     */
    public function unsetAdminMode()
    {
        \Pimcore::unsetAdminMode();
        Document::setHideUnpublished(true);
        Object\AbstractObject::setHideUnpublished(true);
        Object\AbstractObject::setGetInheritedValues(true);
        Object\Localizedfield::setGetFallbackValues(true);
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
     * Initialize the kernel (see parent Symfony module)
     */
    protected function initializeKernel()
    {
        $maxNestingLevel   = 200; // Symfony may have very long nesting level
        $xdebugMaxLevelKey = 'xdebug.max_nesting_level';
        if (ini_get($xdebugMaxLevelKey) < $maxNestingLevel) {
            ini_set($xdebugMaxLevelKey, $maxNestingLevel);
        }

        $this->kernel = require_once __DIR__ . '/../../../config/startup.php';
        $this->kernel->boot();

        if ($this->config['cache_router'] === true) {
            $this->persistService('router', true);
        }
    }

    /**
     * Initialize the test DB
     *
     * TODO this currently fails if the DB does not exist. Find a way
     * to drop and re-create the DB with doctrine, even if the DB does
     * not exist yet.
     */
    protected function initializeDb()
    {
        $connection = $this->connectDb();

        if (!($connection instanceof Connection)) {
            $this->debug('[DB] Not initializing DB as the connection failed');
            return;
        }

        $dbName = $connection->getDatabase();

        $this->debug(sprintf('[DB] Initializing DB %s', $dbName));

        $connection
            ->getSchemaManager()
            ->dropAndCreateDatabase($dbName);

        // make sure the connection used the newly created database
        $connection->executeQuery('USE ' . $dbName);

        $this->debug(sprintf('[DB] Successfully dropped and re-created DB %s', $dbName));

        /** @var Setup|Setup\Dao $setup */
        $setup = new Setup();
        $setup->database();

        $setup->contents([
            'username' => 'admin',
            'password' => microtime()
        ]);

        $this->debug(sprintf('[DB] Set up the test DB %s', $dbName));

        return true;
    }

    /**
     * Try to connect to the DB and set constant if connection was successful.
     *
     * @return bool|\Doctrine\DBAL\Connection
     */
    protected function connectDb()
    {
        $container  = \Pimcore::getContainer();
        $connection = $container->get('database_connection');
        $connected  = false;

        try {
            if (!$connection->isConnected()) {
                $connection->connect();
            }

            $this->debug(sprintf('[DB] Successfully connected to DB %s', $connection->getDatabase()));

            $connected = true;
        } catch (ConnectionException $e) {
            $this->debug(sprintf('[DB] Failed to connect to DB: %s', $e->getMessage()));
        }

        if ($connected) {
            return $connection;
        }
    }
}
