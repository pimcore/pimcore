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
 * @method \Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Dao getDao()
 * @method void save()
 * @method void delete()
 */
final class KeyGroupRelation extends Model\AbstractModel
{
    /**
     * @var int
     */
    protected int $keyId;

    /**
     * @var int
     */
    protected int $groupId;

    /** The key
     * @var string
     */
    protected string $name;

    /**
     * The key description.
     *
     * @var string
     */
    protected string $description;

    /**
     * Field definition
     *
     * @var string
     */
    protected string $definition;

    /**
     * Field type
     *
     * @var string
     */
    protected string $type;

    /** @var int */
    protected int $sorter;

    /** The group name
     * @var string
     */
    protected string $groupName;

    /** @var bool */
    protected bool $mandatory;

    /** @var bool */
    protected bool $enabled;

    /**
     * @return Model\DataObject\Classificationstore\KeyGroupRelation
     */
    public static function create(): KeyGroupRelation
    {
        return new self();
    }

    /**
     * @return int
     */
    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function setGroupId(int $groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * @return int
     */
    public function getKeyId(): int
    {
        return $this->keyId;
    }

    public function setKeyId(int $keyId)
    {
        $this->keyId = $keyId;
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
    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDefinition(): string
    {
        return $this->definition;
    }

    public function setDefinition(string $definition)
    {
        $this->definition = $definition;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getSorter(): int
    {
        return $this->sorter;
    }

    public function setSorter(int $sorter)
    {
        $this->sorter = (int) $sorter;
    }

    /**
     * @return bool
     */
    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    public function setMandatory(bool $mandatory)
    {
        $this->mandatory = (bool)$mandatory;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param int $groupId
     * @param int $keyId
     *
     * @return KeyGroupRelation|null
     */
    public static function getByGroupAndKeyId(int $groupId, int $keyId): ?KeyGroupRelation
    {
        try {
            $relation = new self();
            $relation->getDao()->getById((int)$keyId, (int)$groupId);

            return $relation;
        } catch (NotFoundException) {
            return null;
        }
    }
}
