<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 15:03
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Impl_Pricing_Action_CartDiscount implements OnlineShop_Framework_Pricing_Action_IDiscount
{
    /**
     * @var float
     */
    protected $amount = 0;

    /**
     * @var float
     */
    protected $percent = 0;

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return OnlineShop_Framework_Pricing_IAction
     */
    public function executeOnProduct(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        return $this;
    }

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return OnlineShop_Framework_Pricing_IAction
     */
    public function executeOnCart(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        $priceCalculator = $environment->getCart()->getPriceCalculator();
        $modDiscount = new OnlineShop_Framework_Impl_CartPriceModificator_Discount($environment->getRule());


        $amount = $this->getAmount() !== 0 ? $this->getAmount() : ($priceCalculator->getGrandTotal()->getAmount() * ($this->getPercent() / 100));
        $modDiscount->setAmount( '-'.$amount );
        $priceCalculator->addModificator( $modDiscount );

        return $this;
    }


    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode(array(
                                'type' => 'CartDiscount',
                                'amount' => $this->getAmount(),
                                'percent' => $this->getPercent()
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
        if($json->amount)
            $this->setAmount( $json->amount );
        if($json->percent)
            $this->setPercent( $json->percent );
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $percent
     */
    public function setPercent($percent)
    {
        $this->percent = $percent;
    }

    /**
     * @return float
     */
    public function getPercent()
    {
        return $this->percent;
    }
}
