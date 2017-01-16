<?php

use Interop\Container\ContainerInterface;
use Pimcore\Cache\CacheItemFactory;
use Pimcore\Cache\Core\CoreHandler;
use Pimcore\Cache\Core\WriteLock;
use Pimcore\Logger;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

return [
    // config
    'pimcore.cache.config.core.namespace'       => 'pimcore',
    'pimcore.cache.config.core.defaultLifetime' => 2419200, // 28 days

    // define a distinct cache logger with the same handlers/processors as the core one
    'pimcore.logger.cache' => function (ContainerInterface $container) {
        $cacheLogger = Logger::createNamedPsrLogger('cache');
        if (null === $cacheLogger) {
            // initialize a null logger to make sure cache has a logger to write
            $cacheLogger = new NullLogger();
        }

        return $cacheLogger;
    },

    // alias for the standard core cache adapter - this key will be injected into the core cache
    // if you define your own cache service, make sure you set this alias to your service
    // you can either define your own adapter (must implement AdapterInterface or use one of the predefined ones (see below)
    'pimcore.cache.adapter.core' => DI\get('pimcore.cache.adapter.core.filesystem'),

    // redis connection
    'pimcore.cache.redis.connection.core' => function (ContainerInterface $container) {
        $dsnKey     = 'pimcore.cache.redis.dsn';
        $optionsKey = 'pimcore.cache.redis.options';

        if (!$container->has('pimcore.cache.redis.dsn')) {
            throw new \DI\DependencyException(
                sprintf(
                    'Need a Redis DSN configured as parameter "%s" to use the core redis adapter. Please update your DI configuration.',
                    $dsnKey
                )
            );
        }

        $dsn = $container->get($dsnKey);

        $options = [];
        if ($container->has($optionsKey)) {
            $options = $container->get($optionsKey);
        }

        return RedisAdapter::createConnection($dsn, $options);
    },

    // redis cache adapter
    'pimcore.cache.adapter.core.redis' => DI\object(RedisAdapter::class)
        ->constructor(
            DI\get('pimcore.cache.redis.connection.core'),
            DI\get('pimcore.cache.config.core.namespace'),
            DI\get('pimcore.cache.config.core.defaultLifetime')
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // APCu cache adapter
    'pimcore.cache.adapter.core.apcu' => DI\object(ApcuAdapter::class)
        ->constructor(
            DI\get('pimcore.cache.config.core.namespace'),
            DI\get('pimcore.cache.config.core.defaultLifetime')
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // filesystem cache adapter
    'pimcore.cache.adapter.core.filesystem' => DI\object(FilesystemAdapter::class)
        ->constructor(
            DI\get('pimcore.cache.config.core.namespace'),
            DI\get('pimcore.cache.config.core.defaultLifetime'),
            PIMCORE_CACHE_DIRECTORY . '/symfony'
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // array/in-memory cache adapter
    'pimcore.cache.adapter.core.array' => DI\object(ArrayAdapter::class)
        ->constructor(
            DI\get('pimcore.cache.config.core.defaultLifetime'),
            false // do not store serialized
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // item factory creates cache items
    'pimcore.cache.item_factory' => DI\object(CacheItemFactory::class),

    // write lock handles locking between processes
    'pimcore.cache.write_lock' => DI\object(WriteLock::class)
        ->constructor(
            DI\get('pimcore.cache.adapter.core'),
            DI\get('pimcore.cache.item_factory')
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),

    // the actual core handler which is used from Pimcore\Cache
    'pimcore.cache.handler.core' => DI\object(CoreHandler::class)
        ->constructor(
            DI\get('pimcore.cache.adapter.core'),
            DI\get('pimcore.cache.write_lock'),
            DI\get('pimcore.cache.item_factory')
        )
        ->method('setLogger', DI\get('pimcore.logger.cache')),
];
