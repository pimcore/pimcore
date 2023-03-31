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
 * @method GridConfigFavourite\Dao getDao()
 *
 * @internal
 */
class GridConfigFavourite extends AbstractModel
{
    protected int $ownerId;

    protected string $classId;

    protected int $objectId;

    protected int $gridConfigId;

    protected string $searchType;

    /**
     * enum('asset','object')
     *
     * @var string
     */
    protected string $type;

    /**
     * @param int $ownerId
     * @param string $classId
     * @param int|null $objectId
     * @param string|null $searchType
     *
     * @return GridConfigFavourite|null
     */
    public static function getByOwnerAndClassAndObjectId(int $ownerId, string $classId, int $objectId = null, ?string $searchType = ''): ?GridConfigFavourite
    {
        try {
            $favourite = new self();
            $favourite->getDao()->getByOwnerAndClassAndObjectId($ownerId, $classId, $objectId, $searchType);

            return $favourite;
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * @throws \Exception
     */
    public function save(): void
    {
        $this->getDao()->save();
    }

    /**
     * Delete this favourite
     */
    public function delete(): void
    {
        $this->getDao()->delete();
    }

    public function getOwnerId(): int
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

    public function getGridConfigId(): int
    {
        return $this->gridConfigId;
    }

    public function setGridConfigId(int $gridConfigId): void
    {
        $this->gridConfigId = $gridConfigId;
    }

    public function getSearchType(): string
    {
        return $this->searchType;
    }

    public function setSearchType(string $searchType): void
    {
        $this->searchType = $searchType;
    }

    public function getObjectId(): int
    {
        return $this->objectId;
    }

    public function setObjectId(int $objectId): void
    {
        $this->objectId = $objectId;
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
