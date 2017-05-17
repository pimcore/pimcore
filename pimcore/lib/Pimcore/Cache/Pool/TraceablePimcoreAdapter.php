<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Cache\Pool;

use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\TraceableAdapter;

class TraceablePimcoreAdapter extends TraceableAdapter implements PimcoreCacheItemPoolInterface
{
    /**
     * @var PimcoreCacheItemPoolInterface
     */
    private $pool;

    public function __construct(PimcoreCacheItemPoolInterface $pool)
    {
        parent::__construct($pool);

        $this->pool = $pool;
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->pool->setLogger($logger);
    }

    /**
     * @inheritDoc
     */
    public function createCacheItem($key, $value = null, array $tags = [], $isHit = false)
    {
        return $this->pool->createCacheItem($key, $value, $tags, $isHit);
    }

    /**
     * @inheritDoc
     */
    public function invalidateTag($tag)
    {
        return $this->pool->invalidateTag($tag);
    }

    /**
     * @inheritDoc
     */
    public function invalidateTags(array $tags)
    {
        return $this->pool->invalidateTags($tags);
    }
}
