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

namespace Pimcore\Tests\Unit\Telemetry;

use Doctrine\DBAL\Connection;
use LogicException;
use Pimcore\Telemetry\Snapshot\CacheAdapterKind;
use Pimcore\Telemetry\Snapshot\CacheAdapterKindInterface;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use Symfony\Component\Cache\Adapter\TraceableTagAwareAdapter;

/**
 * The cache adapter behind `pimcore.cache.pool`, reported as a kind. Never a class name.
 */
class CacheAdapterKindTest extends TestCase
{
    /**
     * The value set is closed: redis, filesystem, database, array, null, other. A Symfony adapter outside
     * the kinds Pimcore's pool is realistically backed by is `other` as well - it is classified by class
     * name, so a name that would spell another kind must still not produce one.
     */
    public function testClassifiesSymfonyAdaptersByKind(): void
    {
        $this->assertSame('array', $this->kind()->of(new ArrayAdapter()));
        $this->assertSame('null', $this->kind()->of(new NullAdapter()));
        $this->assertSame(
            'database',
            $this->kind()->of(new DoctrineDbalAdapter($this->createStub(Connection::class))),
        );
    }

    /**
     * Pimcore's own pool definitions wrap the real adapter in a generic `TagAwareAdapter`, and the
     * debug container wraps everything in a tracing adapter; the kind is the innermost adapter's.
     */
    public function testLooksThroughTheTaggingAndTracingWrappers(): void
    {
        $this->assertSame('array', $this->kind()->of(new TagAwareAdapter(new ArrayAdapter())));
        $this->assertSame('null', $this->kind()->of(new TraceableAdapter(new NullAdapter())));
        $this->assertSame(
            'array',
            $this->kind()->of(new TraceableTagAwareAdapter(new TagAwareAdapter(new ArrayAdapter()))),
        );
    }

    /**
     * A pool that is not one of Symfony's adapters is `other`: its class name is somebody's code and is
     * neither inspected for keywords nor emitted.
     */
    public function testAnythingOutsideSymfonyIsOther(): void
    {
        $foreign = new class() implements CacheItemPoolInterface {
            public function getItem(string $key): CacheItemInterface
            {
                throw new LogicException('not needed');
            }

            public function getItems(array $keys = []): iterable
            {
                return [];
            }

            public function hasItem(string $key): bool
            {
                return false;
            }

            public function clear(): bool
            {
                return true;
            }

            public function deleteItem(string $key): bool
            {
                return true;
            }

            public function deleteItems(array $keys): bool
            {
                return true;
            }

            public function save(CacheItemInterface $item): bool
            {
                return true;
            }

            public function saveDeferred(CacheItemInterface $item): bool
            {
                return true;
            }

            public function commit(): bool
            {
                return true;
            }
        };

        $this->assertSame('other', $this->kind()->of($foreign));
    }

    private function kind(): CacheAdapterKindInterface
    {
        return new CacheAdapterKind();
    }
}
