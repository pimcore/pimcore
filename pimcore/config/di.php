<?php

use Interop\Container\ContainerInterface;

return [
    'Pimcore\Model\Document\*' => DI\object('Pimcore\Model\Document\*'),
    'Pimcore\Model\Asset\*' => DI\object('Pimcore\Model\Asset\*'),
    'Pimcore\Model\Object\*\Listing' => DI\object('Pimcore\Model\Object\*\Listing'),
    'Pimcore\Model\Object\Data\*' => DI\object('Pimcore\Model\Object\Data\*'),
    'Pimcore\Model\Object\*' => DI\object('Pimcore\Model\Object\*'),

    \Pimcore\Image\Adapter::class => DI\factory([\Pimcore\Image::class, 'create']),

    // define pimcore DB connection as prototype factory (runs through Db::get() on every call instead of caching the result)
    'pimcore.db' => DI\factory(function () {
        return \Pimcore\Db::get();
    })->scope(\DI\Scope::PROTOTYPE),

    // get PDO connection from DB
    'pimcore.db.pdo' => DI\factory(function(ContainerInterface $container) {
        /** @var \Pimcore\Db\Wrapper $db */
        $db = $container->get('pimcore.db');

        return $db
            ->getWriteResource()
            ->getConnection();
    })->scope(\DI\Scope::PROTOTYPE),
];
