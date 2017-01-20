<?php

namespace Test\Cache\Traits;

use Pimcore\Cache\Adapter\PdoMysqlAdapter;

trait PdoMysqlAdapterTrait
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
     * @return PdoMysqlAdapter
     */
    protected function createPdoAdapter()
    {
        return new PdoMysqlAdapter(static::$pdo, 3600);
    }
}
