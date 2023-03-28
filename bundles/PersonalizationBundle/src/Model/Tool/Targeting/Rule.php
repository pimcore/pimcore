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

use Pimcore\Model;

/**
 * @internal
 *
 * @method Rule\Dao getDao()
 * @method void save()
 * @method void update()
 * @method void delete()
 */
class Rule extends Model\AbstractModel
{
    const SCOPE_HIT = 'hit';

    const SCOPE_SESSION = 'session';

    const SCOPE_SESSION_WITH_VARIABLES = 'session_with_variables';

    const SCOPE_VISITOR = 'visitor';

    protected ?int $id = null;

    protected string $name;

    protected string $description = '';

    protected string $scope = self::SCOPE_HIT;

    protected bool $active = true;

    protected int $prio = 0;

    protected array $conditions = [];

    protected array $actions = [];

    public static function inTarget(mixed $target): bool
    {
        if ($target instanceof Rule) {
            $targetId = $target->getId();
        } elseif (is_string($target)) {
            $target = self::getByName($target);
            if (!$target) {
                return false;
            } else {
                $targetId = $target->getId();
            }
        } else {
            $targetId = (int) $target;
        }

        if (array_key_exists('_ptc', $_GET) && (int)$targetId == (int)$_GET['_ptc']) {
            return true;
        }

        return false;
    }

    /**
     * Static helper to retrieve an instance of Tool\Targeting\Rule by the given ID
     *
     * @param int $id
     *
     * @return self|null
     */
    public static function getById(int $id): ?Rule
    {
        try {
            $target = new self();
            $target->getDao()->getById((int)$id);

            return $target;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
    }

    /**
     * @param string $name
     *
     * @return self|null
     *
     * @throws \Exception
     */
    public static function getByName(string $name): ?Rule
    {
        try {
            $target = new self();
            $target->getDao()->getByName($name);

            return $target;
        } catch (Model\Exception\NotFoundException $e) {
            return null;
        }
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

    /**
     * @return $this
     */
    public function setActions(array $actions): static
    {
        if (!$actions) {
            $actions = [];
        }

        $this->actions = $actions;

        return $this;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @return $this
     */
    public function setConditions(array $conditions): static
    {
        if (!$conditions) {
            $conditions = [];
        }

        $this->conditions = $conditions;

        return $this;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function setScope(string $scope): void
    {
        if (!empty($scope)) {
            $this->scope = $scope;
        }
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setActive(bool $active): void
    {
        $this->active = (bool) $active;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function getPrio(): int
    {
        return $this->prio;
    }

    public function setPrio(int $prio): void
    {
        $this->prio = $prio;
    }
}
