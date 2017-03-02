<?php

namespace Pimcore\Tests\Helper;

use Codeception\Module;
use Doctrine\DBAL\Exception\ConnectionException;
use Pimcore\Config;

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

        // try to establish DB connection
        $this->connectDb();

        if ($this->config['cache_router'] === true) {
            $this->persistService('router', true);
        }
    }

    private function connectDb()
    {
        $connection = $this->kernel->getContainer()->get('database_connection');

        $dbConnected = false;

        try {
            if (!$connection->isConnected()) {
                $connection->connect();
            }

            $dbConnected = true;
        } catch (ConnectionException $e) {
        }

        define('PIMCORE_TEST_DB_CONNECTED', $dbConnected);
    }
}
