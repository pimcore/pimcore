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

namespace Pimcore\Bundle\AdminBundle\Model;

use Pimcore\Model\AbstractModel;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @method GridConfig\Dao getDao()
 *
 * @internal
 */
class GridConfig extends AbstractModel
{
    protected ?int $id = null;

    protected ?int $ownerId = null;

    protected string $classId;

    protected string $name;

    protected string $searchType;

    protected string $config;

    protected ?string $description = null;

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    protected bool $shareGlobally = false;

    protected bool $setAsFavourite = false;

    protected bool $saveFilters = false;

    protected string $type = 'object';

    public static function getById(int $id): ?GridConfig
    {
        if (!$id) {
            return null;
        }

        try {
            $config = new self();
            $config->getDao()->getById($id);

            return $config;
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * @throws \Exception
     */
    public function save(): void
    {
        if (!$this->getId()) {
            $this->setCreationDate(time());
        }

        $this->setModificationDate(time());

        $this->getDao()->save();
    }

    /**
     * Delete this GridConfig
     */
    public function delete(): void
    {
        $this->getDao()->delete();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getOwnerId(): ?int
    {
        return $this->ownerId;
    }

    public function setOwnerId(int $ownerId): void
    {
        $this->ownerId = $ownerId;
    }

    public function getClassId(): string
    {
        return $this->classId;
    }

    public function setClassId(string $classId): void
    {
        $this->classId = $classId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSearchType(): string
    {
        return $this->searchType;
    }

    public function setSearchType(string $searchType): void
    {
        $this->searchType = $searchType;
    }

    public function getConfig(): string
    {
        return $this->config;
    }

    public function setConfig(string $config): void
    {
        $this->config = $config;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function setCreationDate(int $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setModificationDate(int $modificationDate): void
    {
        $this->modificationDate = $modificationDate;
    }

    public function isShareGlobally(): bool
    {
        return $this->shareGlobally;
    }

    public function setShareGlobally(bool $shareGlobally): void
    {
        $this->shareGlobally = $shareGlobally;
    }

    public function isSetAsFavourite(): bool
    {
        return $this->setAsFavourite;
    }

    public function setSetAsFavourite(bool $setAsFavourite): void
    {
        $this->setAsFavourite = $setAsFavourite;
    }

    public function isSaveFilters(): bool
    {
        return $this->saveFilters;
    }

    public function setSaveFilters(bool $saveFilters): void
    {
        $this->saveFilters = $saveFilters;
    }

    /**
     * enum('asset','object')
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * enum('asset','object')
     *
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
