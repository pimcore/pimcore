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

namespace Pimcore\Tests\Unit\Helper;

use Doctrine\Persistence\ConnectionRegistry;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Helper\LongRunningHelper;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;

class LongRunningHelperTest extends TestCase
{
    private const KEY_KEEP = 'long_running_helper_test_keep';

    private const KEY_REMOVE = 'long_running_helper_test_remove';

    private LongRunningHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();

        $connectionRegistry = $this->createMock(ConnectionRegistry::class);
        $connectionRegistry->method('getConnections')->willReturn([]);

        $this->helper = new LongRunningHelper($connectionRegistry);
        $this->helper->setLogger(new NullLogger());
    }

    protected function tearDown(): void
    {
        foreach ([self::KEY_KEEP, self::KEY_REMOVE] as $key) {
            if (RuntimeCache::isRegistered($key)) {
                RuntimeCache::getInstance()->offsetUnset($key);
            }
        }

        parent::tearDown();
    }

    public function testRemoveProtectedItemsOnlyRemovesTheGivenItems(): void
    {
        $this->helper->addPimcoreRuntimeCacheProtectedItems([self::KEY_KEEP, self::KEY_REMOVE]);
        $this->helper->removePimcoreRuntimeCacheProtectedItems([self::KEY_REMOVE]);

        RuntimeCache::set(self::KEY_KEEP, 'keep');
        RuntimeCache::set(self::KEY_REMOVE, 'remove');

        $this->helper->cleanUp();

        $this->assertTrue(
            RuntimeCache::isRegistered(self::KEY_KEEP),
            'A protected item that was not removed must survive the clean-up'
        );
        $this->assertSame('keep', RuntimeCache::get(self::KEY_KEEP));
        $this->assertFalse(
            RuntimeCache::isRegistered(self::KEY_REMOVE),
            'An item removed from the protected list must be cleared'
        );
    }

    public function testRemoveProtectedItemsWithEmptyListKeepsAllProtectedItems(): void
    {
        $this->helper->addPimcoreRuntimeCacheProtectedItems([self::KEY_KEEP]);
        $this->helper->removePimcoreRuntimeCacheProtectedItems([]);

        RuntimeCache::set(self::KEY_KEEP, 'keep');

        $this->helper->cleanUp();

        $this->assertTrue(RuntimeCache::isRegistered(self::KEY_KEEP));
    }

    public function testRemoveProtectedItemsIgnoresUnknownItems(): void
    {
        $this->helper->addPimcoreRuntimeCacheProtectedItems([self::KEY_KEEP]);
        $this->helper->removePimcoreRuntimeCacheProtectedItems(['long_running_helper_test_unknown']);

        RuntimeCache::set(self::KEY_KEEP, 'keep');

        $this->helper->cleanUp();

        $this->assertTrue(RuntimeCache::isRegistered(self::KEY_KEEP));
    }
}
