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

namespace Pimcore\Targeting\Storage;

use Pimcore\Targeting\Model\VisitorInfo;

/**
 * Implements a 2-step storage handling a primary storage which needs a visitor ID (e.g. external DB)
 * and a fallback storage which is able to save data without a visitor ID (e.g. session or cookie).
 */
class FallbackStorage implements TargetingStorageInterface
{
    /**
     * @var TargetingStorageInterface
     */
    private $primaryStorage;

    /**
     * @var TargetingStorageInterface
     */
    private $fallbackStorage;

    public function __construct(
        TargetingStorageInterface $primaryStorage,
        TargetingStorageInterface $fallbackStorage
    )
    {
        $this->primaryStorage  = $primaryStorage;
        $this->fallbackStorage = $fallbackStorage;
    }

    public function has(VisitorInfo $visitorInfo, string $name): bool
    {
        if ($visitorInfo->hasVisitorId()) {
            if (!$this->primaryStorage->has($visitorInfo, $name)) {
                $this->migrateFromFallback($visitorInfo);
            }

            return $this->primaryStorage->has($visitorInfo, $name);
        } else {
            return $this->fallbackStorage->has($visitorInfo, $name);
        }
    }

    public function set(VisitorInfo $visitorInfo, string $name, $value)
    {
        if ($visitorInfo->hasVisitorId()) {
            $this->primaryStorage->set($visitorInfo, $name, $value);
        } else {
            $this->fallbackStorage->set($visitorInfo, $name, $value);
        }
    }

    public function get(VisitorInfo $visitorInfo, string $name, $default = null)
    {
        if ($visitorInfo->hasVisitorId()) {
            if (!$this->primaryStorage->has($visitorInfo, $name)) {
                $this->migrateFromFallback($visitorInfo);
            }

            return $this->primaryStorage->get($visitorInfo, $name, $default);
        } else {
            return $this->fallbackStorage->get($visitorInfo, $name, $default);
        }
    }

    public function all(VisitorInfo $visitorInfo): array
    {
        if ($visitorInfo->hasVisitorId()) {
            $this->migrateFromFallback($visitorInfo);

            return $this->primaryStorage->all($visitorInfo);
        } else {
            return $this->fallbackStorage->all($visitorInfo);
        }
    }

    public function clear(VisitorInfo $visitorInfo)
    {
        $this->fallbackStorage->clear($visitorInfo);

        if ($visitorInfo->hasVisitorId()) {
            $this->primaryStorage->clear($visitorInfo);
        }
    }

    private function migrateFromFallback(VisitorInfo $visitorInfo)
    {
        $fallbackData = $this->fallbackStorage->all($visitorInfo);
        if (empty($fallbackData)) {
            return;
        }

        foreach ($fallbackData as $key => $value) {
            $this->primaryStorage->set($visitorInfo, $key, $value);
        }

        $this->fallbackStorage->clear($visitorInfo);
    }
}
