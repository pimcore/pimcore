<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 15:03
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Impl_Pricing_Action_FreeShipping implements OnlineShop_Framework_Pricing_IAction
{
    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return OnlineShop_Framework_Pricing_IAction
     */
    public function executeOnProduct(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        // TODO: Implement executeOnCart() method.
        return $this;
    }

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return OnlineShop_Framework_Pricing_IAction
     */
    public function executeOnCart(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        // TODO: Implement executeOnCart() method.
        $priceCalculator = $environment->getCart()->getPriceCalculator();

        $list = $priceCalculator->getModificators();
        foreach($list as &$modificator)
        {
            /* @var OnlineShop_Framework_ICartPriceModificator $modificator_ */

            // remove shipping charge
            if($modificator instanceof OnlineShop_Framework_CartPriceModificator_IShipping)
                $modificator->setCharge(0);
        }

        return $this;
    }


    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode(array(
                                'type' => 'FreeShipping'
                           ));
    }

    /**
     * @param string $string
     *
     * @return OnlineShop_Framework_Pricing_ICondition
     */
    public function fromJSON($string)
    {
        return $this;
    }
}
