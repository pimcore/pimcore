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
    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @return string|null
     */
    public function getKey(): ?string;

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setKey(string $key): static;

    /**
     * @return string|null
     */
    public function getPath(): ?string;

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath(string $path): static;

    /**
     * @return string
     */
    public function getRealPath(): string;

    /**
     * @return string
     */
    public function getFullPath(): string;

    /**
     * @return string
     */
    public function getRealFullPath(): string;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type): static;

    /**
     * @return int|null
     */
    public function getCreationDate(): ?int;

    /**
     * @param int $creationDate
     *
     * @return $this
     */
    public function setCreationDate(int $creationDate): static;

    /**
     * @return int|null
     */
    public function getModificationDate(): ?int;

    /**
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate(int $modificationDate): static;

    /**
     * @return int|null
     */
    public function getUserOwner(): ?int;

    /**
     * @param int $userOwner
     *
     * @return $this
     */
    public function setUserOwner(int $userOwner): static;

    /**
     * @return int|null
     */
    public function getUserModification(): ?int;

    /**
     * @param int $userModification
     *
     * @return $this
     */
    public function setUserModification(int $userModification): static;

    /**
     *
     * @param int $id
     *
     * @return static|null
     */
    public static function getById(int $id): ?static;

    /**
     * get possible types
     *
     * @return array
     */
    public static function getTypes(): array;

    /**
     * @return Property[]
     */
    public function getProperties(): array;

    /**
     * @param Property[]|null $properties
     *
     * @return $this
     */
    public function setProperties(?array $properties): static;

    /**
     * Get specific property data or the property object itself ($asContainer=true) by its name, if the
     * property doesn't exists return null
     *
     * @param string $name
     * @param bool $asContainer
     *
     * @return mixed
     */
    public function getProperty(string $name, bool $asContainer = false): mixed;

    /**
     * @param string $name
     * @param string $type
     * @param mixed $data
     * @param bool $inherited
     * @param bool $inheritable
     *
     * @return $this
     */
    public function setProperty(string $name, string $type, mixed $data, bool $inherited = false, bool $inheritable = false): static;

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasProperty(string $name): bool;

    /**
     * returns true if the element is locked
     *
     * @return bool
     */
    public function isLocked(): bool;

    /**
     * enum('self','propagate') nullable
     *
     * @param string|null $locked
     *
     * @return $this
     */
    public function setLocked(?string $locked): static;

    /**
     * enum('self','propagate') nullable
     *
     * @return string|null
     */
    public function getLocked(): ?string;

    /**
     * @return int|null
     */
    public function getParentId(): ?int;

    /**
     * @param int|null $id
     *
     * @return $this
     */
    public function setParentId(?int $id): static;

    /**
     * @return self|null
     */
    public function getParent(): ?ElementInterface;

    /**
     * @param ElementInterface|null $parent
     *
     * @return $this
     */
    public function setParent(?ElementInterface $parent): static;

    /**
     * @return string
     */
    public function getCacheTag(): string;

    public function getCacheTags(array $tags = []): array;

    /**
     * @return bool
     */
    public function __isBasedOnLatestData(): bool;

    /**
     * @return $this
     */
    public function setVersionCount(?int $versionCount): static;

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
     */
    public function save(array $parameters = []): static;

    public function delete();

    public function clearDependentCache(array $additionalTags = []);

    /**
     * @param int|null $id
     *
     * @return $this
     */
    public function setId(?int $id): static;

    /**
     * This is used for user-permissions, pass a permission type (eg. list, view, save) an you know if the current user is allowed to perform the requested action
     *
     * @param string $type
     * @param null|User $user
     *
     * @return bool
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

    /**
     * @return Dependency
     */
    public function getDependencies(): Dependency;

    /**
     * @return string
     */
    public function __toString();
}
