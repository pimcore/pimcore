<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\ProductDiscountInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\BracketInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Rule\Dao;
use Pimcore\Cache\Runtime;
use Pimcore\Logger;
use Pimcore\Model\AbstractModel;

/**
 * @method Dao getDao()
 */
class Rule extends AbstractModel implements RuleInterface
{
    /**
     * @param int $id
     *
     * @return RuleInterface
     */
    public static function getById($id)
    {
        $cacheKey = Dao::TABLE_NAME . '_' . $id;
        try {
            $rule = Runtime::get($cacheKey);
        } catch (\Exception $e) {
            try {
                $ruleClass = get_called_class();
                $rule = new $ruleClass;
                $rule->getDao()->getById($id);

                Runtime::set($cacheKey, $rule);
            } catch (\Exception $ex) {
                Logger::debug($ex->getMessage());

                return null;
            }
        }

        return $rule;
    }

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string[]
     */
    protected $label = [];

    /**
     * @var string[]
     */
    protected $description = [];

    /**
     * @var BracketInterface
     */
    protected $condition;

    /**
     * @var array|ActionInterface
     */
    protected $action = [];

    /**
     * @var string
     */
    protected $behavior;

    /**
     * @var bool
     */
    protected $active;

    /**
     * @var int
     */
    protected $prio;

    /**
     * load model with serializes data from db
     *
     * @param string $key
     * @param mixed $value
     *
     * @return AbstractModel
     */
    public function setValue($key, $value)
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

    /**
     * @param int $id
     *
     * @return $this|RuleInterface
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $label
     * @param string $locale
     *
     * @return RuleInterface
     */
    public function setLabel($label, $locale = null)
    {
        $this->label[$this->getLanguage($locale)] = $label;

        return $this;
    }

    /**
     * @param string $locale
     *
     * @return string|null
     */
    public function getLabel($locale = null)
    {
        return $this->label[$this->getLanguage($locale)] ?? null;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @param string|null $locale
     *
     * @return RuleInterface
     */
    public function setName($name, $locale = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $description
     * @param string $locale
     *
     * @return RuleInterface
     */
    public function setDescription($description, $locale = null)
    {
        $this->description[$this->getLanguage($locale)] = $description;

        return $this;
    }

    /**
     * @param string $locale
     *
     * @return string|null
     */
    public function getDescription($locale = null)
    {
        return $this->description[$this->getLanguage($locale)] ?? null;
    }

    /**
     * @param string $behavior
     *
     * @return RuleInterface
     */
    public function setBehavior($behavior)
    {
        $this->behavior = $behavior;

        return $this;
    }

    /**
     * @return string
     */
    public function getBehavior()
    {
        return $this->behavior;
    }

    /**
     * @param bool $active
     *
     * @return RuleInterface
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param ConditionInterface $condition
     *
     * @return RuleInterface
     */
    public function setCondition(ConditionInterface $condition)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * @return ConditionInterface
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param array $action
     *
     * @return RuleInterface
     */
    public function setActions(array $action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return array|ActionInterface
     */
    public function getActions()
    {
        return $this->action;
    }

    /**
     * @param int $prio
     *
     * @return RuleInterface
     */
    public function setPrio($prio)
    {
        $this->prio = (int)$prio;

        return $this;
    }

    /**
     * @return int
     */
    public function getPrio()
    {
        return $this->prio;
    }

    /**
     * @return RuleInterface
     */
    public function save()
    {
        $this->getDao()->save();

        return $this;
    }

    /**
     * delete item
     */
    public function delete()
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
    public function check(EnvironmentInterface $environment)
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
    public function hasProductActions()
    {
        foreach ($this->getActions() as $action) {
            if ($action instanceof ProductDiscountInterface) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param EnvironmentInterface $environment
     *
     * @return RuleInterface
     */
    public function executeOnProduct(EnvironmentInterface $environment)
    {
        foreach ($this->getActions() as $action) {
            /* @var ActionInterface $action */
            $action->executeOnProduct($environment);
        }

        return $this;
    }

    /**
     * @param EnvironmentInterface $environment
     *
     * @return RuleInterface
     */
    public function executeOnCart(EnvironmentInterface $environment)
    {
        foreach ($this->getActions() as $action) {
            /* @var ActionInterface $action */
            $action->executeOnCart($environment);
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
    protected function getLanguage($language = null)
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
