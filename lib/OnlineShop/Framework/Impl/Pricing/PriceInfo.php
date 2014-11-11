<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 11.04.13
 * Time: 14:18
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Impl_Pricing_PriceInfo implements OnlineShop_Framework_Pricing_IPriceInfo
{
    /**
     * @var OnlineShop_Framework_Pricing_IPriceInfo
     */
    protected $priceInfo;

    /**
     * @var float
     */
    protected $amount = 0;

    /**
     * @var array|OnlineShop_Framework_Pricing_IRule
     */
    protected $rules = array();

    /**
     * @var array|OnlineShop_Framework_Pricing_IRule
     */
    protected $validRules = array();


    protected $rulesApplied = false;


    /**
     * @param OnlineShop_Framework_IPriceInfo $priceInfo
     */
    public function __construct(OnlineShop_Framework_IPriceInfo $priceInfo)
    {
        $this->priceInfo = $priceInfo;
        $this->setAmount(  $priceInfo->getPrice() ? $priceInfo->getPrice()->getAmount() : 0  );
    }


    /**
     * @param OnlineShop_Framework_Pricing_IRule $rule
     *
     * @return OnlineShop_Framework_IPriceInfo
     */
    public function addRule(OnlineShop_Framework_Pricing_IRule $rule)
    {
        $this->rules[] = $rule;
    }

    private function getEnvironment() {
        $env = OnlineShop_Framework_Factory::getInstance()->getPricingManager()->getEnvironment();
        $env->setProduct( $this->getProduct() )
            ->setPriceInfo( $this );
        if(method_exists($this->getProduct(), "getCategories")) {
            $env->setCategories( (array)$this->getProduct()->getCategories() );
        }

        return $env;
    }

    /**
     * returns all valid rules, if forceRecalc, recalculation of valid rules is forced
     *
     * @param bool $forceRecalc
     * @return array|OnlineShop_Framework_Pricing_IRule
     */
    public function getRules($forceRecalc = false)
    {

        if($forceRecalc || empty($this->validRules))
        {
            $env = $this->getEnvironment();
            $this->validRules = array();
            foreach($this->rules as $rule)
            {
                $env->setRule( $rule );

                if($rule->check($env) === true) {
                    $this->validRules[] = $rule;
                }
            }
        }

        return $this->validRules;
    }

    /**
     * @return OnlineShop_Framework_IPrice
     */
    public function getPrice()
    {
        if($this->priceInfo->getPrice() == null) {
            return null;
        }

        if(!$this->rulesApplied) {
            $env = $this->getEnvironment();

            foreach($this->getRules() as $rule)
            {
                /* @var OnlineShop_Framework_Pricing_IRule $rule */
                $env->setRule($rule);

                // execute rule
                $rule->executeOnProduct( $env );
            }
            $this->rulesApplied = true;
        }


        $price = $this->priceInfo->getPrice();
        $price->setAmount($this->getAmount());
        return $price;
    }

    /**
     * @return OnlineShop_Framework_IPrice
     */
    public function getTotalPrice()
    {
        if($this->priceInfo->getPrice() == null) {
            return null;
        }

        $price = $this->priceInfo->getPrice();
        $price->setAmount($this->getPrice()->getAmount() * $this->getQuantity());
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
     * numeric quantity or constant OnlineShop_Framework_IPriceInfo::MIN_PRICE
     */
    public function setQuantity($quantity)
    {
        return $this->priceInfo->setQuantity($quantity);
    }

    /**
     * @param OnlineShop_Framework_IPriceSystem $priceSystem
     *
     * @return void
     */
    public function setPriceSystem($priceSystem)
    {
        return $this->priceInfo->setPriceSystem($priceSystem);
    }

    /**
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     *
     * @return void
     */
    public function setProduct(OnlineShop_Framework_ProductInterfaces_ICheckoutable $product)
    {
        return $this->priceInfo->setProduct($product);
    }

    /**
     * @return OnlineShop_Framework_ProductInterfaces_ICheckoutable
     */
    public function getProduct()
    {
        return $this->priceInfo->getProduct();
    }

    /**
     * @param float $amount
     *
     * @return OnlineShop_Framework_Pricing_IPriceInfo
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
     * @return OnlineShop_Framework_IPrice
     */
    public function getOriginalPrice()
    {
        return $this->priceInfo->getPrice();
    }

    /**
     * @return OnlineShop_Framework_IPrice
     */
    public function getOriginalTotalPrice()
    {
        return $this->priceInfo->getTotalPrice();
    }
}
