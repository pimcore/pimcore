<?php

namespace Pimcore\Cache\Core;

use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Pimcore\Event\Cache\Core\ResultEvent;
use Pimcore\Event\CoreCacheEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventDispatchingCoreHandler extends CoreHandler
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @inheritDoc
     */
    public function __construct(PimcoreCacheItemPoolInterface $adapter, WriteLockInterface $writeLock, EventDispatcherInterface $dispatcher)
    {
        parent::__construct($adapter, $writeLock);

        $this->dispatcher = $dispatcher;
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        $this->dispatcher->dispatch(CoreCacheEvents::INIT, new Event());
    }

    /**
     * @inheritDoc
     */
    public function setEnabled($enabled)
    {
        $result = parent::setEnabled($enabled);

        $this->dispatcher->dispatch(
            $this->isEnabled()
                ? CoreCacheEvents::ENABLE
                : CoreCacheEvents::DISABLE,
            new Event()
        );

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function purge()
    {
        $result     = parent::purge();
        $purgeEvent = new ResultEvent($result);

        $this->dispatcher->dispatch(CoreCacheEvents::PURGE, $purgeEvent);

        return $purgeEvent->getResult();
    }
}
