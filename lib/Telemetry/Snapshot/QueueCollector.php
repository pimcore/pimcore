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

namespace Pimcore\Telemetry\Snapshot;

use Exception;
use Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler\MessengerFailureTransportsPass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;
use function array_keys;
use function in_array;
use function strstr;
use function strtolower;

/**
 * The `queue.*` namespace: how Pimcore's asynchronous work is transported and how much of it is waiting.
 *
 * The transport is the scheme of the DSN prefix Pimcore's queues are configured with, reported as one of
 * a fixed set of names - nothing else of the DSN (host, credentials, database) is looked at.
 *
 * Depth is what every configured transport reports about itself through Symfony's
 * {@see MessageCountAwareInterface}, exactly as `messenger:stats` does: the Doctrine transport counts in
 * its own connection and table, Redis and AMQP in theirs, and a transport that cannot count (sync,
 * in-memory) is simply not part of the sum. Nothing here assumes where a queue is stored. The depth is
 * all-or-nothing: one transport that fails to count would make every sum read as a smaller backlog, so
 * the whole depth is unknown instead.
 *
 * The per-transport map names only the transports core itself configures and Symfony's conventional
 * `failed` transport. A `pimcore_` prefix is no proof of ownership - a project can call its own
 * transport `pimcore_customer_import` - so every other transport, from a bundle or a project, is folded
 * into `other`. Failed messages are counted wherever they sit: which transports are failure transports
 * comes from the metadata Symfony puts on them, collected by {@see MessengerFailureTransportsPass},
 * never from their names.
 *
 * @internal
 */
final readonly class QueueCollector implements SnapshotCollectorInterface
{
    private const SCHEMA_VERSION = 1;

    /**
     * The transports core configures in `config/pimcore/default.yaml`, plus Symfony's default failure
     * transport name. Nothing outside this list is ever named.
     */
    private const NAMED_QUEUES = [
        'pimcore_core',
        'pimcore_maintenance',
        'pimcore_scheduled_tasks',
        'pimcore_image_optimize',
        'pimcore_asset_update',
        'pimcore_cdn_purge',
        'pimcore_cdn_purge_failed',
        'failed',
    ];

    /**
     * @param ServiceProviderInterface<object> $transports every messenger transport, keyed by its name
     * @param string[] $failureTransports the names Symfony marked as failure transports
     */
    public function __construct(
        #[AutowireLocator('messenger.receiver', indexAttribute: 'alias')]
        private ServiceProviderInterface $transports,
        private CountMapInterface $countMap,
        #[Autowire('%pimcore.messenger.transport_dsn_prefix%')]
        private string $transportDsnPrefix,
        #[Autowire('%pimcore.telemetry.messenger_failure_transports%')]
        private array $failureTransports = [],
    ) {
    }

    public function getNamespace(): string
    {
        return 'queue';
    }

    public function collect(): array
    {
        $metrics = [
            'schema_version' => self::SCHEMA_VERSION,
            'transport' => $this->transport(),
        ];

        return $metrics + ($this->depth() ?? []);
    }

    private function transport(): string
    {
        $scheme = strtolower((string) strstr($this->transportDsnPrefix, '://', true));

        return match ($scheme) {
            'doctrine', 'redis', 'amqp', 'sqs', 'beanstalkd', 'sync' => $scheme,
            // the TLS variants Pimcore's installer and Symfony accept are the same kind of transport
            'rediss' => 'redis',
            'amqps' => 'amqp',
            'in-memory' => 'in_memory',
            default => 'other',
        };
    }

    /**
     * @return array<string, int|array<string, int>>|null null when no transport can count, or one fails to
     */
    private function depth(): ?array
    {
        $byQueue = [];
        $total = 0;
        $failed = 0;
        $counted = false;

        foreach (array_keys($this->transports->getProvidedServices()) as $name) {
            try {
                $transport = $this->transports->get($name);

                if (!$transport instanceof MessageCountAwareInterface) {
                    continue;
                }

                $count = $transport->getMessageCount();
            } catch (Exception) {
                return null;
            }

            $counted = true;
            $total += $count;

            if ($this->isFailureQueue($name)) {
                $failed += $count;
            }

            $key = $this->queueKey($name);
            $byQueue[$key] = ($byQueue[$key] ?? 0) + $count;
        }

        if (!$counted) {
            return null;
        }

        return [
            'depth_total' => $total,
            'depth_by_queue' => $this->countMap->ranked($byQueue),
            'failed_count' => $failed,
        ];
    }

    private function isFailureQueue(string $queue): bool
    {
        return in_array($queue, $this->failureTransports, true);
    }

    private function queueKey(string $queue): string
    {
        return in_array($queue, self::NAMED_QUEUES, true) ? $queue : 'other';
    }
}
