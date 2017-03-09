<?php

namespace Pimcore\Event;

final class CoreCacheEvents
{
    /**
     * @Event("Symfony\Component\EventDispatcher\Event")
     * @var string
     */
    const INIT = 'pimcore.cache.core.init';

    /**
     * @Event("Symfony\Component\EventDispatcher\Event")
     * @var string
     */
    const ENABLE = 'pimcore.cache.core.enable';

    /**
     * @Event("Symfony\Component\EventDispatcher\Event")
     * @var string
     */
    const DISABLE = 'pimcore.cache.core.disable';

    /**
     * @Event("Pimcore\Event\Cache\Core\ResultEvent")
     * @var string
     */
    const PURGE = 'pimcore.cache.core.purge';
}
