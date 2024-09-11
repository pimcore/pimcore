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

namespace Pimcore\Model\Element;

use Pimcore\Model\Dependency;
use Pimcore\Model\ModelInterface;
use Pimcore\Model\Property;
use Pimcore\Model\Schedule\Task;
use Pimcore\Model\User;
use Pimcore\Model\Version;

interface ElementInterface extends ModelInterface
{
    public function getId(): ?int;

    public function getKey(): ?string;

    /**
     * @return $this
     */
    public function setKey(string $key): static;

    public function getPath(): ?string;

    /**
     * @return $this
     */
    public function setPath(string $path): static;

    public function getRealPath(): ?string;

    public function getFullPath(): string;

    public function getRealFullPath(): string;

    public function getType(): string;

    /**
     * @return $this
     */
    public function setType(string $type): static;

    public function getCreationDate(): ?int;

    /**
     * @return $this
     */
    public function setCreationDate(int $creationDate): static;

    public function getModificationDate(): ?int;

    /**
     * @return $this
     */
    public function setModificationDate(int $modificationDate): static;

    public function getUserOwner(): ?int;

    /**
     * @return $this
     */
    public function setUserOwner(int $userOwner): static;

    public function getUserModification(): ?int;

    /**
     * @return $this
     */
    public function setUserModification(int $userModification): static;

    //TODO add $params parameter in Pimcore 12
    public static function getById(int $id /*, array $params = [] */): ?static;

    /**
     * get possible types
     *
     * @return string[]
     */
    public static function getTypes(): array;

    /**
     * @return array<string, Property>
     */
    public function getProperties(): array;

    /**
     * @param array<string, Property>|null $properties
     *
     * @return $this
     */
    public function setProperties(?array $properties): static;

    /**
     * Get specific property data or the property object itself ($asContainer=true) by its name, if the
     * property doesn't exists return null
     */
    public function getProperty(string $name, bool $asContainer = false): mixed;

    /**
     * @return $this
     */
    public function setProperty(string $name, string $type, mixed $data, bool $inherited = false, bool $inheritable = false): static;

    public function hasProperty(string $name): bool;

    /**
     * returns true if the element is locked
     */
    public function isLocked(): bool;

    /**
     * @param 'self'|'propagate'|null $locked
     *
     * @return $this
     */
    public function setLocked(?string $locked): static;

    /**
     * @return 'self'|'propagate'|null
     */
    public function getLocked(): ?string;

    public function getParentId(): ?int;

    /**
     * @return $this
     */
    public function setParentId(?int $id): static;

    public function getParent(): ?ElementInterface;

    /**
     * @return $this
     */
    public function setParent(?ElementInterface $parent): static;

    public function getCacheTag(): string;

    /**
     * @param string[] $tags
     *
     * @return string[]
     */
    public function getCacheTags(array $tags = []): array;

    public function __isBasedOnLatestData(): bool;

    /**
     * @return $this
     */
    public function setVersionCount(int $versionCount): static;

    public function getVersionCount(): int;

    /**
     * Save this Element.
     *
     * Items in the $parameters array are also passed on to Events triggered during this method's execution.
     *
     * @param array{versionNote?: string} $parameters Optional. Associative array currently using these keys:
     *  - versionNote: Optional. Descriptive text saved alongside versioned data
     *
     * @return $this
     *
     * @throws DuplicateFullPathException
     */
    public function save(array $parameters = []): static;

    public function delete(): void;

    public function clearDependentCache(array $additionalTags = []): void;

    /**
     * @return $this
     */
    public function setId(?int $id): static;

    /**
     * This is used for user-permissions, pass a permission type (eg. list, view, save) an you know if the current user is allowed to perform the requested action
     */
    public function isAllowed(string $type, ?User $user = null): bool;

    /**
     * @return Task[]
     */
    public function getScheduledTasks(): array;

    /**
     * @return Version[]
     */
    public function getVersions(): array;

    public function getDependencies(): Dependency;

    public function __toString(): string;
}
