<?php

use Interop\Container\ContainerInterface;
use Monolog\Logger;
use Pimcore\Cache\Symfony\Handler\CoreHandler;
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

    // CACHE

    // hack - hijack the core logger and create a cache logger with the same handlers/processors
    'pimcore.cache.logger' => function (ContainerInterface $container) {
        $cacheLogger = null;
        foreach (\Pimcore\Logger::getLogger() as $logger) {
            if ($logger instanceof Logger && $logger->getName() === 'core') {
                $cacheLogger = new Logger(
                    'cache',
                    $logger->getHandlers(),
                    $logger->getProcessors()
                );
            }
        }

        // initialize a null logger to make sure cache has a logger to write
        if (null === $cacheLogger) {
            $cacheLogger = new \Psr\Log\NullLogger();
        }

        return $cacheLogger;
    },

    'pimcore.cache.redis.connection.core' => function (ContainerInterface $container) {
        $dsn = $container->get('pimcore.cache.redis.dsn');

        $options = [];
        $optionsKey = 'pimcore.cache.redis.options';

        if ($container->has($optionsKey)) {
            $options = $container->get($optionsKey);
        }

        return RedisAdapter::createConnection($dsn, $options);
    },

    'pimcore.cache.adapter.core.redis' => DI\object(RedisAdapter::class)
        ->constructor(
            DI\get('pimcore.cache.redis.connection.core'),
            DI\get('pimcore.cache.config.core.namespace'),
            DI\get('pimcore.cache.config.core.defaultLifetime')
        )
        ->method('setLogger', DI\get('pimcore.cache.logger')),

    'pimcore.cache.handler.core' => DI\object(CoreHandler::class)
        ->constructor(
            DI\get('pimcore.cache.adapter.core.redis')
        )
        ->method('setLogger', DI\get('pimcore.cache.logger')),
];
