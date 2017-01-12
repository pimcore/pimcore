<?php

use Interop\Container\ContainerInterface;
use Pimcore\Cache\CacheItemFactory;
use Pimcore\Cache\Core\CoreHandler;
use Pimcore\Cache\Core\WriteLock;
use Pimcore\Logger;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\RedisAdapter;

return [
    'pimcore.cache.redis.dsn' => 'redis://localhost/6',
    'pimcore.cache.config.core.namespace'       => 'pimcore',
    'pimcore.cache.config.core.defaultLifetime' => 2419200, // 28 days

    'Pimcore\Model\Document\*' => DI\object('Pimcore\Model\Document\*'),
    'Pimcore\Model\Asset\*' => DI\object('Pimcore\Model\Asset\*'),
    'Pimcore\Model\Object\*\Listing' => DI\object('Pimcore\Model\Object\*\Listing'),
    'Pimcore\Model\Object\Data\*' => DI\object('Pimcore\Model\Object\Data\*'),
    'Pimcore\Model\Object\*' => DI\object('Pimcore\Model\Object\*'),

    \Pimcore\Image\Adapter::class => DI\factory([\Pimcore\Image::class, 'create']),

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
    'pimcore.cache.adapter.core' => DI\get('pimcore.cache.adapter.core.redis'),

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
