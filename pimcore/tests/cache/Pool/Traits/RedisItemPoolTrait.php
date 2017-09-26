<?php

namespace Pimcore\Tests\Cache\Pool\Traits;

use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Cache\Pool\Redis;
use Pimcore\Tests\Cache\Factory;
use Symfony\Component\OptionsResolver\OptionsResolver;

trait RedisItemPoolTrait
{
    /**
     * @var array
     */
    protected $redisConnectionOptions = [];

    /**
     * @var array
     */
    protected $redisOptions = [];

    /**
     * @return PimcoreCacheItemPoolInterface|Redis
     */
    protected function buildCachePool()
    {
        $resolver = new OptionsResolver();
        Redis\ConnectionFactory::configureOptions($resolver);

        $envOptions = [];
        foreach ($resolver->getDefinedOptions() as $option) {
            $envVarName = sprintf('PIMCORE_TEST_CACHE_REDIS_%s', strtoupper($option));
            if ($envVar = getenv($envVarName)) {
                $envOptions[$option] = $envVar;
            }
        }

        $connectionOptions = array_merge($this->redisConnectionOptions, $envOptions);

        return (new Factory())->createRedisItemPool($this->defaultLifetime, $connectionOptions, $this->redisOptions);
    }

    protected function getRedisConnection(Redis $cache): Redis\Connection
    {
        $reflector = new \ReflectionClass($cache);
        $property = $reflector->getProperty('redis');

        $property->setAccessible(true);
        $connection = $property->getValue($cache);
        $property->setAccessible(false);

        return $connection;
    }
}
