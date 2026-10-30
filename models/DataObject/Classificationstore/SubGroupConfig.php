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

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Model;
use Pimcore\Model\DataObject\Classificationstore\Traits\IdentifierTrait;

/**
 * The sub group vocabulary of a store. A key assignment carries one of these entries by its
 * identifier, see {@see KeyGroupRelation::setSubGroup()}.
 *
 * @method \Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Dao getDao()
 */
final class SubGroupConfig extends Model\AbstractModel
{
    use IdentifierTrait;

    protected ?int $id = null;

    /**
     * Store ID
     *
     */
    protected int $storeId = 1;

    /**
     * The sub group name.
     *
     */
    protected string $name;

    /**
     * The sub group description.
     *
     */
    protected ?string $description = null;

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    public static function getById(int $id): ?SubGroupConfig
    {
        try {
            $config = new self();
            $config->getDao()->getById($id);

            return $config;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public static function getByName(string $name, int $storeId = 1): ?SubGroupConfig
    {
        try {
            $config = new self();
            $config->setStoreId($storeId);
            $config->getDao()->getByName($name);

            return $config;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public static function getByIdentifier(string $identifier, int $storeId = 1): ?SubGroupConfig
    {
        try {
            $config = new self();
            $config->setStoreId($storeId);
            $config->getDao()->getByIdentifier($identifier);

            return $config;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public static function create(): SubGroupConfig
    {
        $config = new self();
        $config->save();

        return $config;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the description.
     *
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets the description.
     *
     *
     * @return $this
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function setStoreId(int $storeId): void
    {
        $this->storeId = $storeId;
    }

    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setCreationDate(int $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    /**
     * Saves the sub group config
     *
     * @throws Model\Exception\InvalidConfigException
     */
    public function save(): void
    {
        if ($this->getId() === null) {
            $this->generateIdentifier();
        }
        $this->getDao()->assertIdentifierIsUnique();

        $this->getDao()->save();
    }

    /**
     * Deletes the sub group config, and the tag it leaves behind on the key assignments of its store
     */
    public function delete(): void
    {
        $this->getDao()->delete();
    }
}
