<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


class OnlineShop_Framework_Impl_Pricing_Rule extends \Pimcore\Model\AbstractModel implements OnlineShop_Framework_Pricing_IRule
{

    /**
     * @param int $id
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public static function getById($id)
    {
        $cacheKey = OnlineShop_Framework_Impl_Pricing_Rule_Resource::TABLE_NAME . "_" . $id;
        try {
            $rule = Zend_Registry::get($cacheKey);
        }
        catch (Exception $e) {

            try {
                $ruleClass = get_called_class();
                $rule = new $ruleClass;
                $rule->getResource()->getById($id);

                Zend_Registry::set($cacheKey, $rule);
            } catch (Exception $ex) {

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
    protected $label;

    /**
     * @var string[]
     */
    protected $description;

    /**
     * @var OnlineShop_Framework_Pricing_Condition_IBracket
     */
    protected $condition;

    /**
     * @var array|OnlineShop_Framework_Pricing_IAction
     */
    protected $action = array();

    /**
     * @var string
     */
    protected $behavior;

    /**
     * @var boolean
     */
    protected $active;

    /**
     * @var int
     */
    protected $prio;


    /**
     * load model with serializes data from db
     * @param  $key
     * @param  $value
     * @return \Pimcore\Model\AbstractModel
     */
    public function setValue($key, $value)
    {
        $method = "set" . $key;
        if (method_exists($this, $method))
        {
            switch($method)
            {
                // localized fields
                case 'setlabel':
                case 'setdescription':
                    $value = unserialize($value);
                    if($value === false)
                    {
                        return $this;
                    }
                    else
                    {
                        $this->$key = $value;
                    }
                    return $this;

                // objects
                case 'setactions':
                case 'setcondition':
                    $value = unserialize($value);
                    if($value === false)
                    {
                        return $this;
                    }
            }
            $this->$method($value);
        }
        return $this;
    }

    /**
     * @param $id
     *
     * @return $this|OnlineShop_Framework_Pricing_IRule
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
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function setLabel($label, $locale = null)
    {
        if($locale === NULL)
        {
            $locale = Zend_Registry::get('Zend_Locale')->toString();
        }

        $this->label[ $locale ] = $label;
        return $this;
    }

    /**
     * @param string $locale
     *
     * @return string
     */
    public function getLabel($locale = null)
    {
        if($locale === NULL)
        {
            $locale = Zend_Registry::get('Zend_Locale')->toString();
        }
        return $this->label[ $locale ];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @param string $locale
     *
     * @return OnlineShop_Framework_Pricing_IRule
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
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function setDescription($description, $locale = null)
    {
        if($locale === NULL)
        {
            $locale = Zend_Registry::get('Zend_Locale')->toString();
        }

        $this->description[ $locale ] = $description;
        return $this;
    }

    /**
     * @param string $locale
     *
     * @return string
     */
    public function getDescription($locale = null)
    {
        if($locale === NULL)
        {
            $locale = Zend_Registry::get('Zend_Locale')->toString();
        }
        return $this->description[ $locale ];
    }

    /**
     * @param string $behavior
     * @return OnlineShop_Framework_Pricing_IRule
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
     * @param boolean $active
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param OnlineShop_Framework_Pricing_ICondition $condition
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function setCondition(OnlineShop_Framework_Pricing_ICondition $condition)
    {
        $this->condition = $condition;
    }

    /**
     * @return OnlineShop_Framework_Pricing_ICondition
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param array $action
     *
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function setActions(array $action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return array|OnlineShop_Framework_Pricing_IAction
     */
    public function getActions()
    {
        return $this->action;
    }

    /**
     * @param int $prio
     * @return OnlineShop_Framework_Pricing_IRule
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
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function save()
    {
        $this->getResource()->save();
        return $this;
    }

    /**
     * delete item
     */
    public function delete()
    {
        $this->getResource()->delete();
    }

    /**
     * test all conditions if this rule is valid
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return boolean
     */
    public function check(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        $condition = $this->getCondition();
        if($condition) {
            return $condition->check($environment);
        }

        return true;
    }

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function executeOnProduct(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        foreach($this->getActions() as $action)
        {
            /* @var OnlineShop_Framework_Pricing_IAction $action */
            $action->executeOnProduct( $environment );
        }

        return $this;
    }

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return OnlineShop_Framework_Pricing_IRule
     */
    public function executeOnCart(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        foreach($this->getActions() as $action)
        {
            /* @var OnlineShop_Framework_Pricing_IAction $action */
            $action->executeOnCart( $environment );
        }

        return $this;
    }


}