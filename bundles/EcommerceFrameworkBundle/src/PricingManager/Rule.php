<?php

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\CartActionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\ProductActionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\BracketInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule\Dao;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Logger;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @method Dao getDao()
 */
class Rule extends AbstractModel implements RuleInterface
{
    const ATTRIBUTE_BEHAVIOR_ADDITIVE = 'additiv';

    const ATTRIBUTE_BEHAVIOR_LASTRULE = 'stopExecute';

    protected ?int $id = null;

    protected string $name;

    /**
     * @var string[]
     */
    protected array $label = [];

    /**
     * @var string[]
     */
    protected array $description = [];

    protected ?ConditionInterface $condition = null;

    /**
     * @var ActionInterface[]
     */
    protected array $action = [];

    protected string $behavior = self::ATTRIBUTE_BEHAVIOR_ADDITIVE;

    protected bool $active = false;

    protected int $prio = 0;

    public static function getById(int $id): Rule|RuleInterface|null
    {
        $cacheKey = Dao::TABLE_NAME . '_' . $id;

        try {
            $rule = RuntimeCache::get($cacheKey);
        } catch (\Exception $e) {
            try {
                $ruleClass = get_called_class();
                /** @var Rule $rule */
                $rule = new $ruleClass();
                $rule->getDao()->getById($id);

                RuntimeCache::set($cacheKey, $rule);
            } catch (NotFoundException $ex) {
                Logger::debug($ex->getMessage());

                return null;
            }
        }

        return $rule;
    }

    /**
     * load model with serializes data from db
     *
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     *
     * @internal
     */
    public function setValue(string $key, mixed $value, bool $ignoreEmptyValues = false): static
    {
        $method = 'set' . $key;
        if (method_exists($this, $method)) {
            switch ($method) {
                // localized fields
                case 'setlabel':
                case 'setdescription':
                    $value = unserialize($value);
                    if ($value === false) {
                        return $this;
                    } else {
                        $this->$key = $value;
                    }

                    return $this;

                    // objects
                case 'setactions':
                case 'setcondition':
                    $value = unserialize($value);
                    if ($value === false) {
                        return $this;
                    }
            }
            $this->$method($value);
        }

        return $this;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string $label
     * @param string|null $locale
     *
     * @return $this
     */
    public function setLabel(string $label, ?string $locale = null): static
    {
        $this->label[$this->getLanguage($locale)] = $label;

        return $this;
    }

    /**
     * @param string|null $locale
     *
     * @return string
     */
    public function getLabel(?string $locale = null): string
    {
        return $this->label[$this->getLanguage($locale)] ?? '';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $description
     * @param string|null $locale
     *
     * @return $this
     */
    public function setDescription(string $description, ?string $locale = null): static
    {
        $this->description[$this->getLanguage($locale)] = $description;

        return $this;
    }

    /**
     * @param string|null $locale
     *
     * @return string|null
     */
    public function getDescription(?string $locale = null): ?string
    {
        return $this->description[$this->getLanguage($locale)] ?? null;
    }

    public function setBehavior(string $behavior): static
    {
        $this->behavior = $behavior;

        return $this;
    }

    public function getBehavior(): string
    {
        return $this->behavior;
    }

    public function setActive(bool $active): static
    {
        $this->active = (bool) $active;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setCondition(ConditionInterface $condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    public function getCondition(): ?ConditionInterface
    {
        return $this->condition;
    }

    /**
     * @param ActionInterface[] $action
     *
     * @return $this
     */
    public function setActions(array $action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return ActionInterface[]
     */
    public function getActions(): array
    {
        return $this->action;
    }

    public function setPrio(int $prio): static
    {
        $this->prio = (int)$prio;

        return $this;
    }

    public function getPrio(): int
    {
        return $this->prio;
    }

    /**
     * @return $this
     */
    public function save(): static
    {
        $this->getDao()->save();

        return $this;
    }

    /**
     * delete item
     */
    public function delete(): void
    {
        $this->getDao()->delete();
    }

    /**
     * test all conditions if this rule is valid
     *
     * @param EnvironmentInterface $environment
     *
     * @return bool
     */
    public function check(EnvironmentInterface $environment): bool
    {
        $condition = $this->getCondition();
        if ($condition) {
            return $condition->check($environment);
        }

        return true;
    }

    /**
     * checks if rule has at least one action that changes product price (and not cart price)
     *
     * @return bool
     */
    public function hasProductActions(): bool
    {
        foreach ($this->getActions() as $action) {
            if ($action instanceof ProductActionInterface) {
                return true;
            }
        }

        return false;
    }

    /**
     * checks if rule has at least one action that changes cart price
     *
     * @return bool
     */
    public function hasCartActions(): bool
    {
        foreach ($this->getActions() as $action) {
            if ($action instanceof CartActionInterface) {
                return true;
            }
        }

        return false;
    }

    public function executeOnProduct(EnvironmentInterface $environment): static
    {
        foreach ($this->getActions() as $action) {
            if ($action instanceof ProductActionInterface) {
                $action->executeOnProduct($environment);
            }
        }

        return $this;
    }

    public function executeOnCart(EnvironmentInterface $environment): static
    {
        foreach ($this->getActions() as $action) {
            if ($action instanceof CartActionInterface) {
                $action->executeOnCart($environment);
            }
        }

        return $this;
    }

    /**
     * gets current language
     *
     * @param string|null $language
     *
     * @return string
     */
    protected function getLanguage(string $language = null): string
    {
        if ($language) {
            return (string) $language;
        }

        return Factory::getInstance()->getEnvironment()->getSystemLocale();
    }

    /**
     * @param string $typeClass
     *
     * @return ConditionInterface[]
     */
    public function getConditionsByType(string $typeClass): array
    {
        $conditions = [];

        $rootCondition = $this->getCondition();
        if ($rootCondition instanceof BracketInterface) {
            $conditions = $rootCondition->getConditionsByType($typeClass);
        } elseif ($rootCondition instanceof $typeClass) {
            $conditions[] = $rootCondition;
        }

        return $conditions;
    }
}
