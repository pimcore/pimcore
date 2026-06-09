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

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Maintenance\TaskInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\PruneableInterface;

final class PruningCacheItemsTask implements TaskInterface
{
    public function __construct(private CacheItemPoolInterface $pool)
    {
    }

    public function execute(): void
    {
        if ($this->pool instanceof PruneableInterface) {
            $this->pool->prune();
        }
    }
}
