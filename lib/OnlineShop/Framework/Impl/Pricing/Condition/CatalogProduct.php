<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 11.04.13
 * Time: 10:27
 * To change this template use File | Settings | File Templates.
 */

class OnlineShop_Framework_Impl_Pricing_Condition_CatalogProduct implements OnlineShop_Framework_Pricing_Condition_ICatalogProduct
{
    /**
     * @var OnlineShop_Framework_AbstractProduct
     */
    protected $product;

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return boolean
     */
    public function check(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        return $environment->getProduct() == $this->getProduct();
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode(array(
                                'type' => 'CatalogProduct',
                                'product' => $this->getProduct() ? $this->getProduct()->getFullPath() : '',
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

    /**
     * @param OnlineShop_Framework_AbstractProduct $product
     *
     * @return OnlineShop_Framework_Impl_Pricing_Condition_CatalogProduct
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

}