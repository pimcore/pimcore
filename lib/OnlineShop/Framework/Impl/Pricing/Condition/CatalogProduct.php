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
     * @var OnlineShop_Framework_AbstractProduct[]
     */
    protected $products;

    /**
     * @param OnlineShop_Framework_Pricing_IEnvironment $environment
     *
     * @return boolean
     */
    public function check(OnlineShop_Framework_Pricing_IEnvironment $environment)
    {
        // works only if we have a product
        if($environment->getProduct())
        {
            // check all valid products
            foreach($this->getProducts() as $product)
            {
                /* @var OnlineShop_Framework_AbstractProduct $allow */

                $currentProduct = $environment->getProduct();
                while($currentProduct instanceof OnlineShop_Framework_ProductInterfaces_ICheckoutable) {
                    if($currentProduct->getId() === $product->getId())
                    {
                        return true;
                    }
                    $currentProduct = $currentProduct->getParent();
                }
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        // basic
        $json = array(
            'type' => 'CatalogProduct',
            'products' => array()
        );

        // add categories
        foreach($this->getProducts() as $product)
        {
            /* @var OnlineShop_Framework_AbstractProduct $product */
            $json['products'][] = array(
                $product->getId(),
                $product->getFullPath()
            );
        }

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return OnlineShop_Framework_Pricing_ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $products = array();
        foreach($json->products as $cat)
        {
            $products[] = OnlineShop_Framework_AbstractProduct::getById($cat->id);
        }
        $this->setProducts( $products );

        return $this;
    }

    /**
     * dont cache the entire product object
     * @return array
     */
    public function __sleep()
    {
        foreach($this->products as $key => $product)
        {
            /* @var OnlineShop_Framework_AbstractProduct $product */
            $this->products[ $key ] = $product->getId();
        }

        return array('products');
    }

    /**
     * restore product
     */
    public function __wakeup()
    {
        foreach($this->products as $key => $product_id)
        {
            $this->products[ $key ] = OnlineShop_Framework_AbstractProduct::getById($product_id);
        }
    }

    /**
     * @param OnlineShop_Framework_AbstractProduct[] $products
     *
     * @return OnlineShop_Framework_Impl_Pricing_Condition_CatalogProduct
     */
    public function setProducts(array $products)
    {
        $this->products = $products;
        return $this;
    }

    /**
     * @return OnlineShop_Framework_AbstractProduct[]
     */
    public function getProducts()
    {
        return $this->products;
    }

}