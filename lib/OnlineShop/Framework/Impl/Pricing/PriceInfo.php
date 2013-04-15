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
     * @param OnlineShop_Framework_IPriceInfo $priceInfo
     */
    public function __construct(OnlineShop_Framework_IPriceInfo $priceInfo)
    {
        $this->priceInfo = $priceInfo;
        $this->setAmount( $priceInfo->getPrice()->getAmount() );
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

    /**
     * @return array|OnlineShop_Framework_Pricing_IRule
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * @return OnlineShop_Framework_IPrice
     */
    public function getPrice()
    {
        $env = OnlineShop_Framework_Factory::getInstance()->getPricingManager()->getEnvironment();
        $env->setProduct( $this->getProduct() )
            ->setPriceInfo( $this );

        foreach($this->rules as $rule)
        {
            /* @var OnlineShop_Framework_Pricing_IRule $rule */

            // execute rule
            $rule->executeOnProduct( $env );
        }

        return new OnlineShop_Framework_Impl_Price($this->getAmount(), $this->priceInfo->getPrice()->getCurrency() , true);
    }

    /**
     * @return OnlineShop_Framework_IPrice
     */
    public function getTotalPrice()
    {
        // TODO: Implement getTotalPrice() method.
        return $this->priceInfo->getTotalPrice();
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
     * @param OnlineShop_Framework_AbstractProduct $product
     *
     * @return void
     */
    public function setProduct(OnlineShop_Framework_AbstractProduct $product)
    {
        return $this->priceInfo->setProduct($product);
    }

    /**
     * @return OnlineShop_Framework_AbstractProduct
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
        return $this->priceInfo->$name($arguments);
    }
}
