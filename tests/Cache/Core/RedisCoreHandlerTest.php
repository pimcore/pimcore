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

namespace Pimcore\Tests\Cache\Core;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;

/**
 * @group cache.core.redis
 */
class RedisCoreHandlerTest extends AbstractCoreHandlerTest
{
    /**
     * Initializes item pool
     *
     */
    protected function createCachePool(): RedisTagAwareAdapter
    {
        $dsn = getenv('PIMCORE_TEST_REDIS_DSN');
        $client = RedisAdapter::createConnection($dsn);
        $adapter = new RedisTagAwareAdapter($client, '', $this->defaultLifetime);

        return $adapter;
    }
}
