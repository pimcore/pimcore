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

use Pimcore\Telemetry\Snapshot\CountMap;
use Pimcore\Telemetry\Snapshot\QueueCollector;
use Pimcore\Tests\Support\Test\TestCase;
use RuntimeException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use function json_encode;

class QueueCollectorTest extends TestCase
{
    private const DOCTRINE = 'doctrine://default?queue_name=';

    private const DEPTH_KEYS = ['depth_total', 'depth_by_queue'];

    public function testNamespaceIsQueue(): void
    {
        $this->assertSame('queue', $this->collector()->getNamespace());
    }

    /**
     * Only the scheme of the transport DSN prefix is reported, as one of a fixed set of names.
     */
    public function testReportsTheTransportSchemeOnly(): void
    {
        $this->assertSame('doctrine', $this->collector()->collect()['transport'] ?? null);
        $this->assertSame(
            'redis',
            $this->collector(dsnPrefix: 'redis://user:secret@cache.internal:6379/messages?auto_setup=false')
                ->collect()['transport'] ?? null,
        );
        $this->assertSame(
            'amqp',
            $this->collector(dsnPrefix: 'amqp://guest:guest@rabbit:5672/%2f/')->collect()['transport'] ?? null,
        );
        // the TLS variants are the same kind of transport
        $this->assertSame(
            'amqp',
            $this->collector(dsnPrefix: 'amqps://rabbit:5671/%2f/')->collect()['transport'] ?? null,
        );
        $this->assertSame(
            'redis',
            $this->collector(dsnPrefix: 'rediss://cache:6380/messages')->collect()['transport'] ?? null,
        );
        $this->assertSame('in_memory', $this->collector(dsnPrefix: 'in-memory://')->collect()['transport'] ?? null);
        $this->assertSame('other', $this->collector(dsnPrefix: 'enqueue://default')->collect()['transport'] ?? null);
    }

    public function testNothingOfTheDsnBeyondTheSchemeLeaves(): void
    {
        $metrics = $this->collector(dsnPrefix: 'redis://user:secret@cache.internal:6379/messages')->collect();

        $this->assertStringNotContainsString('secret', (string) json_encode($metrics));
        $this->assertStringNotContainsString('cache.internal', (string) json_encode($metrics));
    }

    /**
     * Depth is what every transport reports about itself, so it follows the transport's own connection
     * and table, and works for Redis and AMQP the same way. Failed messages are not singled out: which
     * transports hold them is Symfony compile-time metadata, and a name is no proof.
     */
    public function testReportsDepthPerTransportAndInTotal(): void
    {
        $metrics = $this->collector(
            transports: [
                'pimcore_core' => $this->countable(3),
                'pimcore_asset_update' => $this->countable(5),
                'pimcore_generic_data_index_failed' => $this->countable(2),
            ],
        )->collect();

        $this->assertSame(10, $metrics['depth_total'] ?? null);
        $this->assertSame(
            ['pimcore_asset_update' => 5, 'pimcore_core' => 3, 'other' => 2],
            $metrics['depth_by_queue'] ?? null,
            'ranked by depth, ties by name; a bundle transport is not core and folds into other',
        );
        $this->assertArrayNotHasKey('failed_count', $metrics);
    }

    /**
     * Only the transports core itself configures and Symfony's conventional `failed` transport are
     * named. A `pimcore_` prefix is no proof of ownership - a project can call its own transport
     * `pimcore_customer_import` - so everything else, bundle or project, is one `other` figure.
     */
    public function testOnlyCoreTransportsAndTheFailedTransportAreNamed(): void
    {
        $metrics = $this->collector(
            transports: [
                'pimcore_core' => $this->countable(1),
                'pimcore_customer_import' => $this->countable(4),
                'failed' => $this->countable(2),
                'acme_orders_failed' => $this->countable(1),
            ],
        )->collect();

        $this->assertSame(['other' => 5, 'failed' => 2, 'pimcore_core' => 1], $metrics['depth_by_queue'] ?? null);
        $this->assertStringNotContainsString('customer', (string) json_encode($metrics));
        $this->assertStringNotContainsString('acme', (string) json_encode($metrics));
    }

    public function testEmptyTransportsReportZero(): void
    {
        $metrics = $this->collector(transports: ['pimcore_core' => $this->countable(0)])->collect();

        $this->assertSame(0, $metrics['depth_total'] ?? null);
        $this->assertSame(['pimcore_core' => 0], $metrics['depth_by_queue'] ?? null);
    }

    /**
     * A transport that cannot count (sync, in-memory) is simply not part of the depth; with none that
     * can, the depth is unknown and absent.
     */
    public function testTransportsThatCannotCountAreLeftOut(): void
    {
        $metrics = $this->collector(
            transports: ['pimcore_core' => $this->countable(3), 'sync' => $this->createStub(ReceiverInterface::class)],
        )->collect();
        $this->assertSame(3, $metrics['depth_total'] ?? null);
        $this->assertSame(['pimcore_core' => 3], $metrics['depth_by_queue'] ?? null);

        $none = $this->collector(transports: ['sync' => $this->createStub(ReceiverInterface::class)])->collect();
        foreach (self::DEPTH_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $none);
        }
        $this->assertSame('doctrine', $none['transport'] ?? null);
    }

    /**
     * The depth is all-or-nothing: one transport that fails to count would make every sum read as a
     * smaller backlog, so the whole depth is unknown instead. The transport kind stands.
     */
    public function testAFailingCountLeavesTheWholeDepthUnknown(): void
    {
        $broken = $this->createStub(MessageCountAwareInterface::class);
        $broken->method('getMessageCount')->willThrowException(new RuntimeException('connection refused'));

        $metrics = $this->collector(
            transports: ['pimcore_core' => $this->countable(3), 'pimcore_maintenance' => $broken],
        )->collect();

        $this->assertSame('doctrine', $metrics['transport'] ?? null);
        foreach (self::DEPTH_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $metrics);
        }
    }

    public function testWithoutAnyTransportTheDepthIsUnknown(): void
    {
        $metrics = $this->collector(transports: [])->collect();

        foreach (self::DEPTH_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $metrics);
        }
    }

    private function countable(int $count): MessageCountAwareInterface
    {
        $transport = $this->createStub(MessageCountAwareInterface::class);
        $transport->method('getMessageCount')->willReturn($count);

        return $transport;
    }

    /**
     * @param array<string, object|null> $transports transport name => transport service as the tagged
     *                                               locator serves them; null stands for one waiting message
     */
    private function collector(
        string $dsnPrefix = self::DOCTRINE,
        array $transports = ['pimcore_core' => null],
    ): QueueCollector {
        $factories = [];
        foreach ($transports as $name => $transport) {
            $service = $transport ?? $this->countable(1);
            $factories[$name] = static fn (): object => $service;
        }

        return new QueueCollector(new ServiceLocator($factories), new CountMap(), $dsnPrefix);
    }
}
