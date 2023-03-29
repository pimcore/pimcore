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

namespace Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting;

use Pimcore\Bundle\PersonalizationBundle\Event\Model\TargetGroupEvent;
use Pimcore\Bundle\PersonalizationBundle\Event\TargetGroupEvents;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Model;

/**
 * @internal
 *
 * @method TargetGroup\Dao getDao()
 */
class TargetGroup extends Model\AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    protected ?int $id = null;

    protected string $name;

    protected string $description = '';

    protected int $threshold = 1;

    protected bool $active = true;

    public static function getById(int $id): ?TargetGroup
    {
        try {
            $targetGroup = new self();
            $targetGroup->getDao()->getById((int)$id);

            return $targetGroup;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public static function getByName(string $name): ?TargetGroup
    {
        try {
            $target = new self();
            $target->getDao()->getByName($name);

            return $target;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    public static function isIdActive(int $id): bool
    {
        $targetGroup = TargetGroup::getById($id);

        if ($targetGroup) {
            return $targetGroup->getActive();
        }

        return false;
    }

    /**
     * @return $this
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setThreshold(int $threshold): void
    {
        $this->threshold = $threshold;
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }

    public function setActive(bool $active): void
    {
        $this->active = (bool)$active;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function delete(): void
    {
        $this->getDao()->delete();
        $this->dispatchEvent(new TargetGroupEvent($this), TargetGroupEvents::POST_DELETE);
    }

    public function save(): void
    {
        $isUpdate = false;
        if ($this->getId()) {
            $isUpdate = true;
        }

        $this->getDao()->save();

        if ($isUpdate) {
            $this->dispatchEvent(new TargetGroupEvent($this), TargetGroupEvents::POST_UPDATE);
        } else {
            $this->dispatchEvent(new TargetGroupEvent($this), TargetGroupEvents::POST_ADD);
        }
    }
}
