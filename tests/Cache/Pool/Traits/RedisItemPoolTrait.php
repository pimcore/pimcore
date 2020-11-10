<?php

namespace Pimcore\Tests\Cache\Pool\Traits;

use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Cache\Pool\Redis;
use Pimcore\Storage\Redis\ConnectionFactory;
use Pimcore\Tests\Cache\Factory;
use Symfony\Component\OptionsResolver\OptionsResolver;

trait RedisItemPoolTrait
{
    protected function getRedisConnectionOptions(): array
    {
        return [];
    }

    protected function getRedisOptions(): array
    {
        return [
            'use_lua' => false,
        ];
    }

    /**
     * @return PimcoreCacheItemPoolInterface|Redis
     */
    protected function buildCachePool()
    {
        $resolver = new OptionsResolver();
        ConnectionFactory::configureOptions($resolver);

        $envOptions = [];
        foreach ($resolver->getDefinedOptions() as $option) {
            $envVarName = sprintf('PIMCORE_TEST_CACHE_REDIS_%s', strtoupper($option));
            if ($envVar = getenv($envVarName)) {
                $envOptions[$option] = $envVar;
            }
        }

        $connectionOptions = array_merge($this->getRedisConnectionOptions(), $envOptions);

        return (new Factory())->createRedisItemPool($this->defaultLifetime, $connectionOptions, $this->getRedisOptions());
    }

    protected function getRedisConnection(Redis $cache): \Credis_Client
    {
        $reflector = new \ReflectionClass($cache);
        $property = $reflector->getProperty('redis');

        $property->setAccessible(true);
        $connection = $property->getValue($cache);
        $property->setAccessible(false);

        return $connection;
    }
}
