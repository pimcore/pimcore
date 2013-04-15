<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 05.04.13
 * Time: 15:03
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Impl_Pricing_Action_Gift implements OnlineShop_Framework_Pricing_Action_IGift
{
    /**
     * @var OnlineShop_Framework_AbstractProduct
     */
    protected $product;

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return OnlineShop_Framework_Pricing_Action_IGift
     */
    public function executeOnProduct(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        // TODO: Implement executeOnProduct() method.
    }

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return OnlineShop_Framework_Pricing_Action_IGift
     */
    public function executeOnCart(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        $comment = $environment->getRule()->getDescription();
        $environment->getCart()->addGiftItem($this->getProduct(), 1, null, true, array(), array(), $comment);
    }

    /**
     * set gift product
     * @param OnlineShop_Framework_AbstractProduct $product
     *
     * @return OnlineShop_Framework_Pricing_Action_IGift
     */
    public function setProduct(OnlineShop_Framework_AbstractProduct $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return OnlineShop_Framework_AbstractProduct
     */
    public function getProduct()
    {
        return $this->product;
    }


    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode(array(
                                'type' => 'Gift',
                                'product' => $this->getProduct()->getFullPath(),
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
        $product = OnlineShop_Framework_AbstractProduct::getByPath( $json->product );

        if($product)
            $this->setProduct( $product );

        return $this;
    }

    /**
     * dont cache the entire product object
     * @return array
     */
    public function __sleep()
    {
        if($this->product)
            $this->product = $this->product->getFullPath();

        return array('product');
    }

    /**
     * restore product
     */
    public function __wakeup()
    {
        if($this->product != '')
            $this->product = OnlineShop_Framework_AbstractProduct::getByPath( $this->product );

    }
}