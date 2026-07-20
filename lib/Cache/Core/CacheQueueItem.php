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

namespace Pimcore\Cache\Core;

use DateInterval;

/**
 * @internal
 */
class CacheQueueItem
{
    protected string $key;

    protected mixed $data = null;

    /**
     * @var string[]
     */
    protected array $tags = [];

    /**
     * @param int|DateInterval|null $lifetime
     */
    protected int|null|DateInterval $lifetime = null;

    protected int $priority = 0;

    protected bool $force = false;

    /**
     * @param string[] $tags
     */
    public function __construct(string $key, mixed $data, array $tags = [], DateInterval|int|null $lifetime = null, ?int $priority = 0, bool $force = false)
    {
        $this->key = $key;
        $this->data = $data;
        $this->tags = $tags;
        $this->lifetime = $lifetime;
        $this->priority = (int)$priority;
        $this->force = $force;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getLifetime(): DateInterval|int|null
    {
        return $this->lifetime;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isForce(): bool
    {
        return $this->force;
    }
}
