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
 * This defines the interface for a persistent targeting storage (e.g. Session). The targeting storage needs to define
 * by itself if it needs a unique visitor ID to store data and fetch if from the visitor info itself.
 */
interface TargetingStorageInterface
{
    const SCOPE_SESSION = 'session';
    const SCOPE_VISITOR = 'visitor';

    const VALID_SCOPES = [
        self::SCOPE_SESSION,
        self::SCOPE_VISITOR,
    ];

    /**
     * The meta entry does not store any data, but can be used if a value is needed for metadata handling. Use cases:
     *
     *  - write an entry to make sure the storage has a created/updated date
     *  - set the entry created date to something in the past when migrating from another storage
     */
    const STORAGE_KEY_META_ENTRY = '_m';

    public function all(VisitorInfo $visitorInfo, string $scope): array;

    public function has(VisitorInfo $visitorInfo, string $scope, string $name): bool;

    public function set(VisitorInfo $visitorInfo, string $scope, string $name, $value);

    public function get(VisitorInfo $visitorInfo, string $scope, string $name, $default = null);

    public function clear(VisitorInfo $visitorInfo, string $scope = null);

    public function migrateFromStorage(TargetingStorageInterface $storage, VisitorInfo $visitorInfo, string $scope);

    /**
     * @return \DateTimeImmutable|null
     */
    public function getCreatedAt(VisitorInfo $visitorInfo, string $scope);

    /**
     * @return \DateTimeImmutable|null
     */
    public function getUpdatedAt(VisitorInfo $visitorInfo, string $scope);
}
