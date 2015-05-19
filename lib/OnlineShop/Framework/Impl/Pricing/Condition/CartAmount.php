<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 13:41
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Impl_Pricing_Condition_CartAmount implements OnlineShop_Framework_Pricing_Condition_ICartAmount
{
    /**
     * @var float
     */
    protected $limit;

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return boolean
     */
    public function check(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        if(!$environment->getCart() || $environment->getProduct() !== null) {
            return false;
        }


        return $environment->getCart()->getPriceCalculator()->getSubTotal()->getAmount() >= $this->getLimit();
    }

    /**
     * @param float $limit
     *
     * @return OnlineShop_Framework_Pricing_Condition_ICartAmount
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return float
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode(array(
            'type' => 'CartAmount',
            'limit' => $this->getLimit()
        ));
    }

    /**
     * @param string $string
     *
     * @return OnlineShop_Framework_Pricing_ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);
        $this->setLimit($json->limit);

        return $this;
    }
}