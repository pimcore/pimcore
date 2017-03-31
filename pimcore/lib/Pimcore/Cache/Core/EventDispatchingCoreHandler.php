<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

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
    public function enable()
    {
        parent::enable();

        $this->setEnabled(false);
    }

    /**
     * @inheritDoc
     */
    public function disable()
    {
        parent::disable();

        $this->setEnabled(false);
    }

    /**
     * @param bool $enabled
     */
    protected function setEnabled($enabled)
    {
        $this->dispatcher->dispatch(
            $this->isEnabled()
                ? CoreCacheEvents::ENABLE
                : CoreCacheEvents::DISABLE,
            new Event()
        );
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
