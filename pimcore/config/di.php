<?php

use Interop\Container\ContainerInterface;
use Pimcore\Cache\Symfony\Handler\CoreHandler;
use Symfony\Component\Cache as SymfonyCache;
use Symfony\Component\Cache\Adapter\RedisAdapter;

return [
    'pimcore.cache.config.core.namespace'       => 'pimcore',
    'pimcore.cache.config.core.defaultLifetime' => 2419200, // 28 days

    'Pimcore\Model\Document\*' => DI\object('Pimcore\Model\Document\*'),
    'Pimcore\Model\Asset\*' => DI\object('Pimcore\Model\Asset\*'),
    'Pimcore\Model\Object\*\Listing' => DI\object('Pimcore\Model\Object\*\Listing'),
    'Pimcore\Model\Object\Data\*' => DI\object('Pimcore\Model\Object\Data\*'),
    'Pimcore\Model\Object\*' => DI\object('Pimcore\Model\Object\*'),

    \Pimcore\Image\Adapter::class => DI\factory([\Pimcore\Image::class, 'create']),

    // CACHE
    'pimcore.cache.redis.connection.core' => function (ContainerInterface $container) {
        $dsn     = $container->get('pimcore.cache.redis.dsn');
        $options = $container->get('pimcore.cache.redis.options') || [];

        return RedisAdapter::createConnection($dsn, $options);
    },

    'pimcore.cache.adapter.core.redis' => DI\object(RedisAdapter::class)
        ->constructor(
            DI\get('pimcore.cache.redis.connection.core'),
            DI\get('pimcore.cache.config.core.namespace'),
            DI\get('pimcore.cache.config.core.defaultLifetime')
        ),

    'pimcore.cache.handler.core' => DI\object(CoreHandler::class)
        ->constructor(
            DI\object('pimcore.cache.adapter.core.redis')
        )
];
