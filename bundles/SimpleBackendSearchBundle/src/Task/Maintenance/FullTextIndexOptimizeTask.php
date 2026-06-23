<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\SimpleBackendSearchBundle\Task\Maintenance;

use Doctrine\DBAL\Exception;
use Pimcore\Db;
use Pimcore\Maintenance\TaskInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * @internal
 */
class FullTextIndexOptimizeTask implements TaskInterface
{
    private LockInterface $lock;

    public function __construct(LockFactory $lockFactory)
    {
        $this->lock = $lockFactory->createLock(self::class, 86400 * 7, false);
    }

    /**
     *
     *
     * @throws Exception
     */
    public function execute(): void
    {
        if ($this->lock->acquire(false)) {
            Db::get()->fetchAllAssociative('OPTIMIZE TABLE search_backend_data');
            Db::get()->fetchAllAssociative('OPTIMIZE TABLE email_log');
        }
    }
}
