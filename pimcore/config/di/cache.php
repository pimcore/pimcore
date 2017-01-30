<?php

use Interop\Container\ContainerInterface;
use Pimcore\Cache\Backend\PimcoreCacheItemPool;
use Pimcore\Cache\Core\CoreHandler;
use Pimcore\Cache\Core\WriteLock;
use Pimcore\Cache\Core\ZendCacheHandler;
use Pimcore\Cache\Pool\PdoMysql;
use Pimcore\Cache\Pool\Redis;
use Pimcore\Cache\Pool\SymfonyAdapterProxy;
use Pimcore\Logger;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

return [
    // config
    'pimcore.cache.config.core.namespace'       => 'pimcore',
    'pimcore.cache.config.core.defaultLifetime' => 2419200, // 28 days
    'pimcore.cache.config.core.redis.options'   => [],

    // ZF internal cache specifics
    'pimcore.cache.config.zend.prefix'          => 'zf_',
    'pimcore.cache.config.zend.defaultLifetime' => DI\get('pimcore.cache.config.core.defaultLifetime'),

    // define a distinct cache logger with the same handlers/processors as the core one
    'pimcore.logger.cache' => function (ContainerInterface $container) {
        $cacheLogger = Logger::createNamedPsrLogger('cache');
        if (null === $cacheLogger) {
            // initialize a null logger to make sure cache has a logger to write
            $cacheLogger = new NullLogger();
        }

        return $cacheLogger;
    },

    // alias for the standard core cache pool - this key will be injected into the core cache
    // if you define your own cache service, make sure you set this alias to your service
    // you can either define your own adapter (must implement PimcoreCacheItemPoolInterface) or use one of the predefined
    // ones (see pimcore.cache.core.pool.* below)
    'pimcore.cache.core.pool' => DI\get('pimcore.cache.core.pool.pdo'),

    // PDO cache pool
    'pimcore.cache.core.pool.pdo' => DI\object(PdoMysql::class)
        ->constructor(
            DI\get('pimcore.db.pdo'),
            DI\get('pimcore.cache.config.core.defaultLifetime')
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // redis cache pool
    'pimcore.cache.core.pool.redis' => DI\object(Redis::class)
        ->constructor(
            DI\get('pimcore.cache.core.redis.connection'),
            DI\get('pimcore.cache.config.core.redis.options'),
            DI\get('pimcore.cache.config.core.defaultLifetime')
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // filesystem cache pool
    'pimcore.cache.core.pool.filesystem' => DI\object(SymfonyAdapterProxy::class)
        ->constructor(
            DI\get('pimcore.cache.core.symfony_adapter.filesystem'),
            DI\get('pimcore.cache.config.core.defaultLifetime')
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // array/in-memory cache pool
    'pimcore.cache.core.pool.array' => DI\object(SymfonyAdapterProxy::class)
        ->constructor(
            DI\get('pimcore.cache.core.symfony_adapter.array'),
            DI\get('pimcore.cache.config.core.defaultLifetime')
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // REDIS CONNECTION - used in redis item pool
    // redis connection
    'pimcore.cache.core.redis.connection' => function (ContainerInterface $container) {
        $configKey = 'pimcore.cache.config.core.redis.connection';

        if (!$container->has($configKey) || !is_array($container->get($configKey))) {
            throw new \DI\DependencyException(
                sprintf(
                    'Need redis options configured as DI key "%s" to use the core redis adapter. Please update your DI configuration.',
                    $configKey
                )
            );
        }

        return Redis\ConnectionFactory::createConnection($container->get($configKey));
    },

    // SYMFONY ADAPTERS - used in combination with SymfonyAdapterProxy
    // filesystem cache adapter
    'pimcore.cache.core.symfony_adapter.filesystem' => DI\object(FilesystemAdapter::class)
        ->constructor(
            'pimcore',
            DI\get('pimcore.cache.config.core.defaultLifetime'),
            PIMCORE_CACHE_DIRECTORY . '/symfony'
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // array/in-memory cache adapter
    'pimcore.cache.core.symfony_adapter.array' => DI\object(ArrayAdapter::class)
        ->constructor(
            DI\get('pimcore.cache.config.core.defaultLifetime'),
            false // do not store serialized
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // write lock handles locking between processes
    'pimcore.cache.core.write_lock' => DI\object(WriteLock::class)
        ->constructor(
            DI\get('pimcore.cache.core.pool')
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // the actual core handler which is used from Pimcore\Cache
    'pimcore.cache.core.handler' => DI\object(CoreHandler::class)
        ->constructor(
            DI\get('pimcore.cache.core.pool'),
            DI\get('pimcore.cache.core.write_lock')
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // Zend Cache
    // the SymfonyCache backend delegates caching to the core cache adapter
    'pimcore.cache.zend.backend' => DI\object(PimcoreCacheItemPool::class)
        ->constructor(
            DI\get('pimcore.cache.core.pool')
        ),

    // set default frontend options including a specific ZF prefix
    'pimcore.cache.zend.frontend.options' => [
        'cache_id_prefix'           => DI\get('pimcore.cache.config.zend.prefix'),
        'lifetime'                  => DI\get('pimcore.cache.config.zend.defaultLifetime'),
        'automatic_serialization'   => true,
        'automatic_cleaning_factor' => 0,
    ],

    // this frontend will be injected into ZF classes if defined
    'pimcore.cache.zend.frontend' => DI\object(Zend_Cache_Core::class)
        ->constructor(
            DI\get('pimcore.cache.zend.frontend.options')
        )
        ->method('setBackend', DI\get('pimcore.cache.zend.backend')),

    'pimcore.cache.zend.handler' => DI\object(ZendCacheHandler::class)
        ->constructor(
            DI\get('pimcore.cache.zend.frontend')
        ),
];
