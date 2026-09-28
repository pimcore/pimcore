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

use Psr\Cache\CacheItemPoolInterface;

/**
 * Classifies the cache adapter behind a pool as one of a fixed set of kind names (redis, filesystem,
 * database, ...), never a class name. Behind an interface so a collector can be tested against a
 * scripted classification instead of real adapters.
 *
 * @internal
 */
interface CacheAdapterKindInterface
{
    public function of(CacheItemPoolInterface $pool): string;
}
