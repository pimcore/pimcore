<?php

namespace Pimcore\Tests\Helper;

use Codeception\Module;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Pimcore\Cache;
use Pimcore\Config;
use Pimcore\Model\Tool\Setup;

class Pimcore extends Module\Symfony
{
    /**
     * @return Pimcore|Module
     */
    public function getPimcoreModule()
    {
        return $this->getModule(__CLASS__);
    }

    public function _initialize()
    {
        Config::setEnvironment($this->config['environment']);

        $maxNestingLevel = 200; // Symfony may have very long nesting level
        $xdebugMaxLevelKey = 'xdebug.max_nesting_level';
        if (ini_get($xdebugMaxLevelKey) < $maxNestingLevel) {
            ini_set($xdebugMaxLevelKey, $maxNestingLevel);
        }

        $this->kernel = require_once __DIR__ . '/../../../pimcore/config/startup.php';
        $this->kernel->boot();

        if ($this->config['cache_router'] === true) {
            $this->persistService('router', true);
        }

        // disable cache
        Cache::disable();

        // try to establish DB connection
        $this->initializeDb();
    }

    protected function initializeDb()
    {
        $connection = $this->connectDb();

        if (!($connection instanceof Connection)) {
            $this->debug('[DB] Not initializing DB as the connection failed');
            return;
        }

        $this->debug(sprintf('[DB] Initializing DB %s', $connection->getDatabase()));

        $connection
            ->getSchemaManager()
            ->dropAndCreateDatabase($connection->getDatabase());

        $this->debug(sprintf('[DB] Successfully dropped and re-created DB %s', $connection->getDatabase()));

        /** @var Setup|Setup\Dao $setup */
        $setup = new Setup();
        $setup->database();

        $setup->contents([
            'username' => 'admin',
            'password' => microtime()
        ]);

        $this->debug(sprintf('[DB] Set up the test DB %s', $connection->getDatabase()));
    }

    /**
     * @return bool|\Doctrine\DBAL\Connection
     */
    protected function connectDb()
    {
        $connection = $this->kernel->getContainer()->get('database_connection');
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

        define('PIMCORE_TEST_DB_CONNECTED', $connected);

        if ($connected) {
            return $connection;
        }
    }
}
