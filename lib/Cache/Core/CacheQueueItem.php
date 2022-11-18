<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Cache\Core;

/**
 * @internal
 */
class CacheQueueItem
{
    protected string $key;

    protected mixed $data = null;

    protected array $tags = [];

    /**
     * @param int|\DateInterval|null $lifetime
     */
    protected int|null|\DateInterval $lifetime = null;

    protected int $priority = 0;

    protected bool $force = false;

    /**
     * @param string $key
     * @param mixed $data
     * @param array $tags
     * @param \DateInterval|int|null $lifetime
     * @param int|null $priority
     * @param bool $force
     */
    public function __construct(string $key, mixed $data, array $tags = [], \DateInterval|int $lifetime = null, ?int $priority = 0, bool $force = false)
    {
        $this->key = $key;
        $this->data = $data;
        $this->tags = $tags;
        $this->lifetime = $lifetime;
        $this->priority = (int)$priority;
        $this->force = (bool)$force;
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

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getLifetime(): \DateInterval|int|null
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
