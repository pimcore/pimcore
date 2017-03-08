<?php

namespace TestSuite\Pimcore\Cache;

use Pimcore\Cache\Pool\Doctrine;
use Pimcore\Cache\Pool\Redis;
use Pimcore\Cache\Pool\Redis\ConnectionFactory;
use Pimcore\Cache\Pool\SymfonyAdapterProxy;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

class Factory
{
    /**
     * @param int $defaultLifetime
     *
     * @return Doctrine
     */
    public function createDoctrineItemPool($defaultLifetime = 0)
    {
        return new Doctrine(
            \Pimcore::getContainer()->get('doctrine.dbal.default_connection'),
            $defaultLifetime
        );
    }

    /**
     * @param $defaultLifetime
     * @param array $connectionOptions
     * @param array $options
     * @return Redis
     */
    public function createRedisItemPool($defaultLifetime, array $connectionOptions = [], array $options = [])
    {
        $connectionOptions = array_merge([
            'server' => 'localhost'
        ], $connectionOptions);

        $connection = ConnectionFactory::createConnection($connectionOptions);

        return new Redis(
            $connection,
            $options,
            $defaultLifetime
        );
    }

    /**
     * @param int $defaultLifetime
     * @return SymfonyAdapterProxy
     */
    public function createArrayAdapterProxyItemPool($defaultLifetime = 0)
    {
        $arrayAdapter = new ArrayAdapter($defaultLifetime, false);

        return $this->createSymfonyProxyItemPool($arrayAdapter);
    }

    /**
     * @param int $defaultLifetime
     * @return SymfonyAdapterProxy
     */
    public function createFilesystemAdapterProxyItemPool($defaultLifetime = 0)
    {
        $filesystemAdapter = new FilesystemAdapter('', $defaultLifetime);

        return $this->createSymfonyProxyItemPool($filesystemAdapter);
    }

    /**
     * @param AdapterInterface $adapter
     * @return SymfonyAdapterProxy
     */
    protected function createSymfonyProxyItemPool(AdapterInterface $adapter)
    {
        $tagAdapter = new TagAwareAdapter($adapter);
        $itemPool   = new SymfonyAdapterProxy($tagAdapter);

        return $itemPool;
    }
}
