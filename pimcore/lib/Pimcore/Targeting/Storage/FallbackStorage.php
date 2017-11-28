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
 *
 * WIP: not working yet!
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

    public function has(VisitorInfo $visitorInfo, string $name, string $scope): bool
    {
        if ($visitorInfo->hasVisitorId()) {
            if (!$this->primaryStorage->has($visitorInfo, $name, $scope)) {
                $this->migrateFromFallback($visitorInfo);
            }

            return $this->primaryStorage->has($visitorInfo, $name, $scope);
        } else {
            return $this->fallbackStorage->has($visitorInfo, $name, $scope);
        }
    }

    public function set(VisitorInfo $visitorInfo, string $scope, string $name, $value)
    {
        if ($visitorInfo->hasVisitorId()) {
            $this->primaryStorage->set($visitorInfo, $scope, $name, $value);
        } else {
            $this->fallbackStorage->set($visitorInfo, $scope, $name, $value);
        }
    }

    public function get(VisitorInfo $visitorInfo, string $scope, string $name, $default = null)
    {
        if ($visitorInfo->hasVisitorId()) {
            if (!$this->primaryStorage->has($visitorInfo, $scope, $name)) {
                $this->migrateFromFallback($visitorInfo);
            }

            return $this->primaryStorage->get($visitorInfo, $scope, $name, $default);
        } else {
            return $this->fallbackStorage->get($visitorInfo, $scope, $name, $default);
        }
    }

    public function all(VisitorInfo $visitorInfo, string $scope): array
    {
        if ($visitorInfo->hasVisitorId()) {
            $this->migrateFromFallback($visitorInfo);

            return $this->primaryStorage->all($visitorInfo, $scope);
        } else {
            return $this->fallbackStorage->all($visitorInfo, $scope);
        }
    }

    public function clear(VisitorInfo $visitorInfo, string $scope = null)
    {
        $this->fallbackStorage->clear($visitorInfo, $scope);

        if ($visitorInfo->hasVisitorId()) {
            $this->primaryStorage->clear($visitorInfo, $scope);
        }
    }

    public function getCreatedAt(VisitorInfo $visitorInfo, string $scope)
    {
        if ($visitorInfo->hasVisitorId()) {
            return $this->primaryStorage->getCreatedAt($visitorInfo, $scope);
        } else {
            return $this->fallbackStorage->getCreatedAt($visitorInfo, $scope);
        }
    }

    public function getUpdatedAt(VisitorInfo $visitorInfo, string $scope)
    {
        if ($visitorInfo->hasVisitorId()) {
            return $this->primaryStorage->getUpdatedAt($visitorInfo, $scope);
        } else {
            return $this->fallbackStorage->getUpdatedAt($visitorInfo, $scope);
        }
    }

    private function migrateFromFallback(VisitorInfo $visitorInfo)
    {
        foreach (self::VALID_SCOPES as $scope) {
            $fallbackData = $this->fallbackStorage->all($visitorInfo, $scope);
            if (empty($fallbackData)) {
                continue;
            }

            foreach ($fallbackData as $key => $value) {
                $this->primaryStorage->set($visitorInfo, $scope, $key, $value);
            }

            $this->fallbackStorage->clear($visitorInfo, $scope);
        }
    }
}
