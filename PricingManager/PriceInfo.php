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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPrice;

class PriceInfo implements IPriceInfo
{
    /**
     * @var IPriceInfo
     */
    protected $priceInfo;

    /**
     * @var float
     */
    protected $amount = 0;

    /**
     * @var IRule[]
     */
    protected $rules = array();

    /**
     * @var IRule[]
     */
    protected $validRules = null;

    /**
     * @var bool
     */
    protected $rulesApplied = false;

    /**
     * @var string
     */
    protected $priceEnvironmentHash = null;

    /**
     * @var IEnvironment
     */
    protected $environment;


    /**
     * @param IPriceInfo           $priceInfo
     * @param IEnvironment $environment
     */
    public function __construct(IPriceInfo $priceInfo, IEnvironment $environment)
    {
        $this->priceInfo = $priceInfo;
        $this->environment = $environment;
    }


    /**
     * @param IRule $rule
     *
     * @return IPriceInfo
     */
    public function addRule(IRule $rule)
    {
        $this->rules[] = $rule;
    }

    /**
     * @return IEnvironment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @param IEnvironment $environment
     *
     * @return $this
     */
    public function setEnvironment(IEnvironment $environment)
    {
        $this->environment = $environment;

        return $this;
    }


    /**
     * checks if environment changed based on hash
     * if so, resets valid rules
     *
     * @return bool
     */
    protected function environmentHashChanged() {
        $hash = $this->getEnvironment() ? $this->getEnvironment()->getHash() : "";
        if($this->priceEnvironmentHash != $hash) {
            $this->validRules = null;
            return true;
        }
        return false;
    }


    /**
     * returns all valid rules, if forceRecalc, recalculation of valid rules is forced
     *
     * @param bool $forceRecalc
     * @return array|IRule
     */
    public function getRules($forceRecalc = false)
    {

        if($forceRecalc || $this->validRules === NULL)
        {
            $env = $this->getEnvironment();
            $this->validRules = array();
            foreach($this->rules as $rule)
            {
                $env->setRule( $rule );

                if($rule->check($env) === true)
                {
                    $this->validRules[] = $rule;

                    // is this a stop rule?
                    if($rule->getBehavior() == 'stopExecute')
                    {
                        break;
                    }
                }
            }
        }

        return $this->validRules;
    }

    /**
     * @return IPrice
     */
    public function getPrice()
    {
        $price = clone $this->priceInfo->getPrice();
        if($price == null) {
            return null;
        }

        if(!$this->rulesApplied || $this->environmentHashChanged()) {
            $this->setAmount( $price->getAmount() );
            $env = $this->getEnvironment();

            foreach($this->getRules() as $rule)
            {
                /* @var IRule $rule */
                $env->setRule($rule);

                // execute rule
                $rule->executeOnProduct( $env );
            }
            $this->rulesApplied = true;

            if($this->getAmount() < 0)
            {
                $this->setAmount( 0 );
            }
        }


        $price->setAmount( $this->getAmount(), IPrice::PRICE_MODE_GROSS, true );
        return $price;
    }

    /**
     * @return IPrice
     */
    public function getTotalPrice()
    {
        if($this->priceInfo->getPrice() == null) {
            return null;
        }

        $price = clone $this->priceInfo->getPrice();
        $price->setAmount( $this->getPrice()->getAmount() * $this->getQuantity(), IPrice::PRICE_MODE_GROSS, true );
        return $price;
    }

    /**
     * @return bool
     */
    public function isMinPrice()
    {
        return $this->priceInfo->isMinPrice();
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->priceInfo->getQuantity();
    }

    /**
     * @param int|string $quantity
     * numeric quantity or constant IPriceInfo::MIN_PRICE
     */
    public function setQuantity($quantity)
    {
        return $this->priceInfo->setQuantity($quantity);
    }

    /**
     * @param IPriceSystem $priceSystem
     *
     * @return IPriceInfo
     */
    public function setPriceSystem($priceSystem)
    {
        $this->priceInfo->setPriceSystem($priceSystem);

        return $this;
    }

    /**
     * @param ICheckoutable $product
     *
     * @return IPriceInfo
     */
    public function setProduct(ICheckoutable $product)
    {
        $this->priceInfo->setProduct($product);

        return $this;
    }

    /**
     * @return ICheckoutable
     */
    public function getProduct()
    {
        return $this->priceInfo->getProduct();
    }

    /**
     * @param float $amount
     *
     * @return IPriceInfo
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * loop through any other calls
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->priceInfo, $name), $arguments);
    }

    /**
     * @return IPrice
     */
    public function getOriginalPrice()
    {
        return $this->priceInfo->getPrice();
    }

    /**
     * @return IPrice
     */
    public function getOriginalTotalPrice()
    {
        return $this->priceInfo->getTotalPrice();
    }


    /**
     * @return bool
     */
    public function hasDiscount()
    {
        return $this->getPrice()->getAmount() < $this->getOriginalPrice()->getAmount();
    }


    /**
     * get discount rate
     * @return IPrice
     */
    public function getDiscount()
    {
        $discount = $this->getPrice()->getAmount() - $this->getOriginalPrice()->getAmount();
        $price = clone $this->priceInfo->getPrice();

        $price->setAmount( $discount );

        return $price;
    }


    /**
     * get total discount rate
     * @return IPrice
     */
    public function getTotalDiscount()
    {
        $discount = $this->getTotalPrice()->getAmount() - $this->getOriginalTotalPrice()->getAmount();
        $price = clone $this->priceInfo->getPrice();

        $price->setAmount( $discount );

        return $price;
    }


    /**
     * get discount in percent
     * @return float
     */
    public function getDiscountPercent()
    {
        $org = $this->getOriginalPrice()->getAmount() / 100;
        $new = $this->getPrice()->getAmount();

        $percent = 100 - ($new / $org);

        return round($percent, 2);
    }


    /**
     * @return bool
     */
    public function hasRulesApplied()
    {
        return (bool)$this->rulesApplied;
    }
}
