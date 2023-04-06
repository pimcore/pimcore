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

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Model;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\CollectionGroupRelation\Dao getDao()
 * @method void save()
 * @method void delete()
 */
final class CollectionGroupRelation extends Model\AbstractModel
{
    protected int $colId;

    protected int $groupId;

    /**
     * The key
     */
    protected string $name;

    /**
     * The key description.
     */
    protected string $description = '';

    protected int $sorter;

    public static function create(): self
    {
        return new self();
    }

    public static function getByGroupAndColId(int $groupId = null, int $colId = null): ?self
    {
        try {
            $config = new self();
            $config->getDao()->getById((int)$colId, (int)$groupId);

            return $config;
        } catch (NotFoundException) {
            return null;
        }
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function setGroupId(int $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getColId(): int
    {
        return $this->colId;
    }

    public function setColId(int $colId): void
    {
        $this->colId = $colId;
    }

    public function getSorter(): int
    {
        return $this->sorter;
    }

    public function setSorter(int $sorter): void
    {
        $this->sorter = $sorter;
    }
}
