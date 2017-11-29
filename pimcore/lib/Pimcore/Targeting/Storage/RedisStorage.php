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
use Pimcore\Targeting\Storage\Traits\TimestampsTrait;

class RedisStorage implements TargetingStorageInterface
{
    use TimestampsTrait;

    const STORAGE_KEY_CREATED_AT = '_c';
    const STORAGE_KEY_UPDATED_AT = '_u';

    /**
     * @var \Credis_Client
     */
    private $redis;

    public function __construct(\Credis_Client $redis = null)
    {
        // TODO remove
        if (null === $redis) {
            $redis = new \Credis_Client('127.0.0.1', 6379, null, '', 5);
        }

        $this->redis = $redis;
    }

    public function all(VisitorInfo $visitorInfo, string $scope): array
    {
        if (!$visitorInfo->hasVisitorId()) {
            return [];
        }

        $key    = $this->buildKey($visitorInfo, $scope);
        $result = $this->redis->hGetAll($key);

        $blacklist = [
            self::STORAGE_KEY_CREATED_AT,
            self::STORAGE_KEY_UPDATED_AT,
            self::STORAGE_KEY_META_ENTRY,
        ];

        $data = [];
        foreach ($result as $key => $value) {
            // filter internal values
            if (in_array($key, $blacklist, true)) {
                continue;
            }

            $data[$key] = json_decode($value, true);
        }

        return $data;
    }

    public function has(VisitorInfo $visitorInfo, string $scope, string $name): bool
    {
        if (!$visitorInfo->hasVisitorId()) {
            return false;
        }

        $key    = $this->buildKey($visitorInfo, $scope);
        $result = $this->redis->hExists($key, $name);

        return 1 === $result;
    }

    public function set(VisitorInfo $visitorInfo, string $scope, string $name, $value)
    {
        if (!$visitorInfo->hasVisitorId()) {
            return false;
        }

        $json = json_encode($value);

        $key = $this->buildKey($visitorInfo, $scope);
        $this->redis->hSet($key, $name, $json);

        // TODO pipeline
        $this->updateTimestamps($visitorInfo, $scope);
    }

    public function get(VisitorInfo $visitorInfo, string $scope, string $name, $default = null)
    {
        if (!$visitorInfo->hasVisitorId()) {
            return $default;
        }

        $key    = $this->buildKey($visitorInfo, $scope);
        $result = $this->redis->hGet($key, $name);

        if (!$result) {
            return $default;
        }

        $decoded = json_decode($result, true);
        if (!$decoded) {
            return $default;
        }

        return $decoded;
    }

    public function clear(VisitorInfo $visitorInfo, string $scope = null)
    {
        $scopes = [];
        if (null !== $scope) {
            $scopes = [$scope];
        } else {
            $scopes = self::VALID_SCOPES;
        }

        foreach ($scopes as $sc) {
            $key = $this->buildKey($visitorInfo, $sc);
            $this->redis->del($key);
        }
    }

    public function migrateFromStorage(TargetingStorageInterface $storage, VisitorInfo $visitorInfo, string $scope)
    {
        // only allow migration if a visitor ID is available as otherwise the fallback
        // would clear the original storage although data was not stored
        if (!$visitorInfo->hasVisitorId()) {
            throw new \LogicException('Can\'t migrate to Redis storage as no visitor ID is set');
        }

        $values    = $storage->all($visitorInfo, $scope);
        $createdAt = $storage->getCreatedAt($visitorInfo, $scope);
        $updatedAt = $storage->getUpdatedAt($visitorInfo, $scope);

        // nothing to migrate
        if (empty($values) && null === $createdAt && null === $updatedAt) {
            return;
        }

        if (!empty($values)) {
            $data = [];
            foreach ($values as $name => $value) {
                $data[$name] = json_encode($value);
            }

            // TODO pipeline with timestamps
            $key = $this->buildKey($visitorInfo, $scope);
            $this->redis->hMSet($key, $data);
        }

        // update created/updated at from storage
        $this->updateTimestamps(
            $visitorInfo,
            $scope,
            $storage->getCreatedAt($visitorInfo, $scope),
            $storage->getUpdatedAt($visitorInfo, $scope)
        );
    }

    public function getCreatedAt(VisitorInfo $visitorInfo, string $scope)
    {
        return $this->loadDate($visitorInfo, $scope, self::STORAGE_KEY_CREATED_AT);
    }

    public function getUpdatedAt(VisitorInfo $visitorInfo, string $scope)
    {
        return $this->loadDate($visitorInfo, $scope, self::STORAGE_KEY_UPDATED_AT);
    }

    private function loadDate(VisitorInfo $visitorInfo, string $scope, string $storageKey)
    {
        if (!$visitorInfo->hasVisitorId()) {
            return null;
        }

        $key       = $this->buildKey($visitorInfo, $scope);
        $timestamp = $this->redis->hGet($key, $storageKey);

        if (empty($timestamp)) {
            return null;
        }

        return \DateTimeImmutable::createFromFormat('U', $timestamp);
    }

    private function buildKey(VisitorInfo $visitorInfo, string $scope): string
    {
        return sprintf('%s:%s', $visitorInfo->getVisitorId(), $scope);
    }

    private function updateTimestamps(
        VisitorInfo $visitorInfo,
        string $scope,
        \DateTimeInterface $createdAt = null,
        \DateTimeInterface $updatedAt = null
    )
    {
        $timestamps = $this->normalizeTimestamps($createdAt, $updatedAt);
        $key        = $this->buildKey($visitorInfo, $scope);

        // TODO pipeline
        if (!$this->redis->hGet($key, self::STORAGE_KEY_CREATED_AT)) {
            $this->redis->hSet($key, self::STORAGE_KEY_CREATED_AT, (string)($timestamps['createdAt']->getTimestamp()));
            $this->redis->hSet($key, self::STORAGE_KEY_UPDATED_AT, (string)($timestamps['updatedAt']->getTimestamp()));
        } else {
            $this->redis->hSet($key, self::STORAGE_KEY_UPDATED_AT, (string)($timestamps['updatedAt']->getTimestamp()));
        }
    }
}
