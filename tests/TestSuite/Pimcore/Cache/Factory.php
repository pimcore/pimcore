<?php

namespace TestSuite\Pimcore\Cache;

use Pimcore\Cache\Pool\PdoMysqlCacheItemPool;
use Pimcore\Cache\Pool\SymfonyAdapterProxyCacheItemPool;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class Factory
{
    /**
     * @var \PDO
     */
    protected static $pdo;

    /**
     * @param bool $forceRefresh
     * @return \PDO
     */
    protected static function getPdo($forceRefresh = false)
    {
        if ($forceRefresh || null === static::$pdo) {
            /** @var \PDO $pdo */
            static::$pdo = \Pimcore::getDiContainer()->get('pimcore.db.pdo');
        }

        return static::$pdo;
    }

    /**
     * @param int $defaultLifetime
     * @param bool $forceFreshPdo
     * @return PdoMysqlCacheItemPool
     */
    public function createPdoMysqlItemPool($defaultLifetime = 0, $forceFreshPdo = false)
    {
        return new PdoMysqlCacheItemPool(
            static::getPdo($forceFreshPdo),
            $defaultLifetime
        );
    }

    /**
     * @param int $defaultLifetime
     * @return SymfonyAdapterProxyCacheItemPool
     */
    public function createArrayAdapterProxyItemPool($defaultLifetime = 0)
    {
        $arrayAdapter = new ArrayAdapter($defaultLifetime, false);

        return $this->createSymfonyProxyItemPool($arrayAdapter);
    }

    /**
     * @param int $defaultLifetime
     * @return SymfonyAdapterProxyCacheItemPool
     */
    public function createFilesystemAdapterProxyItemPool($defaultLifetime = 0)
    {
        $filesystemAdapter = new FilesystemAdapter('', $defaultLifetime);

        return $this->createSymfonyProxyItemPool($filesystemAdapter);
    }

    /**
     * @param AdapterInterface $adapter
     * @return SymfonyAdapterProxyCacheItemPool
     */
    protected function createSymfonyProxyItemPool(AdapterInterface $adapter)
    {
        $tagAdapter = new TagAwareAdapter($adapter);
        $itemPool   = new SymfonyAdapterProxyCacheItemPool($tagAdapter);

        return $itemPool;
    }
}
