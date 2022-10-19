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

namespace Pimcore\Model;

use Pimcore\Model\Exception\NotFoundException;

/**
 * @method \Pimcore\Model\GridConfig\Dao getDao()
 *
 * @internal
 */
class GridConfig extends AbstractModel
{
    /**
     * @var int|null
     */
    protected ?int $id;

    /**
     * @var int|null
     */
    protected ?int $ownerId;

    /**
     * @var string
     */
    protected string $classId;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $searchType;

    /**
     * @var string
     */
    protected string $config;

    /**
     * @var string|null
     */
    protected ?string $description;

    /**
     * @var int|null
     */
    protected ?int $creationDate;

    /**
     * @var int|null
     */
    protected ?int $modificationDate;

    protected bool $shareGlobally = false;

    protected bool $setAsFavourite = false;

    /**
     * @var string
     */
    protected string $type = 'object';

    /**
     * @param int $id
     *
     * @return GridConfig|null
     */
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
    public function save()
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
    public function delete()
    {
        $this->getDao()->delete();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = (int) $id;
    }

    /**
     * @return int|null
     */
    public function getOwnerId(): ?int
    {
        return $this->ownerId;
    }

    public function setOwnerId(int $ownerId)
    {
        $this->ownerId = $ownerId;
    }

    /**
     * @return string
     */
    public function getClassId(): string
    {
        return $this->classId;
    }

    public function setClassId(string $classId)
    {
        $this->classId = $classId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getSearchType(): string
    {
        return $this->searchType;
    }

    public function setSearchType(string $searchType)
    {
        $this->searchType = $searchType;
    }

    /**
     * @return string
     */
    public function getConfig(): string
    {
        return $this->config;
    }

    public function setConfig(string $config)
    {
        $this->config = $config;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
    }

    /**
     * @return int|null
     */
    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function setCreationDate(int $creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return int|null
     */
    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function setModificationDate(int $modificationDate)
    {
        $this->modificationDate = $modificationDate;
    }

    /**
     * @return bool
     */
    public function isShareGlobally(): bool
    {
        return $this->shareGlobally;
    }

    public function setShareGlobally(bool $shareGlobally)
    {
        $this->shareGlobally = (bool) $shareGlobally;
    }

    /**
     * @return bool
     */
    public function isSetAsFavourite(): bool
    {
        return $this->setAsFavourite;
    }

    public function setSetAsFavourite(bool $setAsFavourite)
    {
        $this->setAsFavourite = (bool) $setAsFavourite;
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
    public function setType(string $type)
    {
        $this->type = $type;
    }
}
