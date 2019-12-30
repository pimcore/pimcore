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
use Pimcore\Model\Tool\Lock;
use Psr\Log\LoggerInterface;

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
     * CacheCleanupTask constructor.
     *
     * @param CoreHandlerInterface $cacheHandler
     * @param LoggerInterface $logger
     */
    public function __construct(CoreHandlerInterface $cacheHandler, LoggerInterface $logger)
    {
        $this->cacheHandler = $cacheHandler;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $lockId = self::class;
        if(!Lock::isLocked($lockId, 86400)) {
            Lock::lock($lockId);
            $this->logger->debug('Execute purge() on cache handler');
            $this->cacheHandler->purge();
        } else {
            $this->logger->debug('Skip purge execution, was done within the last 24 hours');
        }
    }
}
