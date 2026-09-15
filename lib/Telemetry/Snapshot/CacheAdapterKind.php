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
use Psr\Cache\CacheItemPoolInterface;
use ReflectionProperty;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;
use function str_contains;
use function str_starts_with;

/**
 * The kind of cache adapter behind a pool, as one of a fixed set of names: redis, filesystem, database,
 * array, null or other. Only Symfony's own adapters are classified, by class, and only the kinds Pimcore's
 * pool is realistically backed by; every other Symfony adapter and any pool from elsewhere is `other`, so
 * the value set is closed and no third-party or project class name is inspected or emitted.
 *
 * Two wrappers are looked through, because they hide the real adapter without being one: the tracing
 * adapter the debug container puts around every pool, and the generic `TagAwareAdapter` Pimcore's own
 * pool definitions wrap the storage adapter in (`pimcore.cache.adapter.*_tag_aware`). The latter keeps its
 * inner pool private, so it is read by reflection; when that fails the wrapper itself is classified.
 *
 * @internal
 */
final readonly class CacheAdapterKind implements CacheAdapterKindInterface
{
    private const SYMFONY_ADAPTERS = 'Symfony\\Component\\Cache\\Adapter\\';

    public function of(CacheItemPoolInterface $pool): string
    {
        $pool = $this->unwrap($pool);
        $class = $pool::class;

        if (!str_starts_with($class, self::SYMFONY_ADAPTERS)) {
            return 'other';
        }

        // the kinds Pimcore's cache pool is realistically backed by; anything else Symfony ships
        // (Memcached, APCu, chains, ...) is `other`, so the value set stays exactly the documented one
        return match (true) {
            str_contains($class, 'Redis') => 'redis',
            str_contains($class, 'Filesystem') => 'filesystem',
            str_contains($class, 'Doctrine'), str_contains($class, 'Pdo') => 'database',
            str_contains($class, 'Array') => 'array',
            str_contains($class, 'Null') => 'null',
            default => 'other',
        };
    }

    private function unwrap(CacheItemPoolInterface $pool): CacheItemPoolInterface
    {
        if ($pool instanceof TraceableAdapter) {
            $pool = $pool->getPool();
        }

        if ($pool instanceof TagAwareAdapter) {
            try {
                $inner = (new ReflectionProperty(TagAwareAdapter::class, 'pool'))->getValue($pool);
                if ($inner instanceof CacheItemPoolInterface) {
                    return $inner;
                }
            } catch (Exception) {
                // the wrapper is classified instead
            }
        }

        return $pool;
    }
}
