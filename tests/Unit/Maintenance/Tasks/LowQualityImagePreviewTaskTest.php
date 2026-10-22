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

namespace Pimcore\Tests\Unit\Maintenance\Tasks;

use Pimcore\Maintenance\Tasks\LowQualityImagePreviewTask;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\InMemoryStore;

/**
 * The task acquires a lock with a two day TTL to run only once per night. With the lock's
 * default autoRelease it was released as soon as the task (the worker) went away, so the task
 * ran again on every maintenance cycle between 0:00 and 4:59.
 *
 * @internal
 */
final class LowQualityImagePreviewTaskTest extends TestCase
{
    public function testLockIsKeptAfterTheTaskIsDestroyed(): void
    {
        $lockFactory = new LockFactory(new InMemoryStore());

        $task = new LowQualityImagePreviewTask(new NullLogger(), $lockFactory);
        $this->assertTrue($this->getLock($task)->acquire(), 'the first run should get the lock');
        unset($task);

        $nextTask = new LowQualityImagePreviewTask(new NullLogger(), $lockFactory);
        $this->assertFalse(
            $this->getLock($nextTask)->acquire(),
            'a later maintenance cycle must not get the lock again before its TTL expires'
        );
    }

    private function getLock(LowQualityImagePreviewTask $task): LockInterface
    {
        return (new ReflectionProperty($task, 'lock'))->getValue($task);
    }
}
