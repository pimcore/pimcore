<?php

use Interop\Container\ContainerInterface;
use Pimcore\Cache\Backend\PimcoreCacheItemPool;
use Pimcore\Cache\Core\CoreHandler;
use Pimcore\Cache\Core\WriteLock;
use Pimcore\Cache\Core\ZendCacheHandler;
use Pimcore\Cache\Pool\PdoMysqlCacheItemPool;
use Pimcore\Logger;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

return [
    // config
    'pimcore.cache.config.core.namespace'       => 'pimcore',
    'pimcore.cache.config.core.defaultLifetime' => 2419200, // 28 days

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
    // you can either define your own adapter (must implement PimcoreCacheItemPoolInterface or use one of the predefined
    // ones (see below)
    'pimcore.cache.pool.core' => DI\get('pimcore.cache.pool.core.pdo'),

    // PDO cache adapter
    'pimcore.cache.pool.core.pdo' => DI\object(PdoMysqlCacheItemPool::class)
        ->constructor(
            DI\get('pimcore.db.pdo'),
            DI\get('pimcore.cache.config.core.defaultLifetime')
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // write lock handles locking between processes
    'pimcore.cache.write_lock' => DI\object(WriteLock::class)
        ->constructor(
            DI\get('pimcore.cache.pool.core')
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // the actual core handler which is used from Pimcore\Cache
    'pimcore.cache.handler.core' => DI\object(CoreHandler::class)
        ->constructor(
            DI\get('pimcore.cache.pool.core'),
            DI\get('pimcore.cache.write_lock')
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // Zend Cache
    // the SymfonyCache backend delegates caching to the core cache adapter
    'pimcore.cache.zend.backend' => DI\object(PimcoreCacheItemPool::class)
        ->constructor(
            DI\get('pimcore.cache.pool.core')
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
