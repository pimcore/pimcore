<?php

namespace TestSuite\Pimcore\Cache\Core;

use Pimcore\Cache\Adapter\PdoMysqlAdapter;

class PdoMysqlCoreHandlerTest extends AbstractCoreHandlerTest
{
    /**
     * @var \PDO
     */
    protected static $pdo;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        /** @var \PDO $pdo */
        static::$pdo = \Pimcore::getDiContainer()->get('pimcore.db.pdo');
    }

    protected function setUpCacheAdapters()
    {
        $cacheAdapter = new PdoMysqlAdapter(static::$pdo, 3600);
        $cacheAdapter->setLogger(static::$logger);

        // make sure we start with a clean state
        $cacheAdapter->clear();

        $this->cacheAdapter = $cacheAdapter;
        $this->tagAdapter   = $cacheAdapter;
    }
}
