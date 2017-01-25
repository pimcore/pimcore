<?php

namespace Test\Cache\Traits;

use Pimcore\Cache\Pool\PdoMysqlCacheItemPool;

trait PdoMysqlCacheItemPoolTrait
{
    /**
     * @var \PDO
     */
    protected static $pdo;

    protected static function fetchPdo()
    {
        /** @var \PDO $pdo */
        static::$pdo = \Pimcore::getDiContainer()->get('pimcore.db.pdo');
    }

    /**
     * @return PdoMysqlCacheItemPool
     */
    protected function createPdoItemPool()
    {
        return new PdoMysqlCacheItemPool(static::$pdo, 3600);
    }
}
