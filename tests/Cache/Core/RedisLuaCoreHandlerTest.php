<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Cache\Core;

use Pimcore\Cache\Pool\Redis;
use Pimcore\Tests\Cache\Pool\Traits\RedisItemPoolTrait;

/**
 * @group cache.core.redis
 * @group cache.core.redis_lua
 */
class RedisLuaCoreHandlerTest extends AbstractCoreHandlerTest
{
    use RedisItemPoolTrait;

    protected function getRedisOptions(): array
    {
        return [
            'use_lua' => true,
        ];
    }

    /**
     * Initializes item pool
     *
     * @return Redis
     */
    protected function createCachePool()
    {
        return $this->buildCachePool();
    }
}
