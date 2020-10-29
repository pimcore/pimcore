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

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Cache\Core\CoreHandlerInterface;
use Pimcore\Maintenance\TaskInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\Factory as LockFactory;
use Symfony\Component\Lock\LockInterface;

final class CacheCleanupTask implements TaskInterface
{
    /**
     * @var CoreHandlerInterface
     */
    private $cacheHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LockInterface
     */
    private $lock;

    /**
     * @param CoreHandlerInterface $cacheHandler
     * @param LoggerInterface $logger
     * @param LockFactory $lockFactory
     */
    public function __construct(CoreHandlerInterface $cacheHandler, LoggerInterface $logger, LockFactory $lockFactory)
    {
        $this->cacheHandler = $cacheHandler;
        $this->logger = $logger;
        $this->lock = $lockFactory->createLock(self::class, 86400);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if (date('H') <= 4 && $this->lock->acquire()) {
            // execution should be only sometime between 0:00 and 4:59 -> less load expected
            $this->logger->debug('Execute purge() on cache handler');
            $this->cacheHandler->purge();
        } else {
            $this->logger->debug('Skip purge execution, was done within the last 24 hours');
        }
    }
}
