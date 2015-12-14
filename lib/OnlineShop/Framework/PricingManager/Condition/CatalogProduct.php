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

namespace OnlineShop\Framework\PricingManager\Condition;

class CatalogProduct implements ICatalogProduct
{
    /**
     * @var \OnlineShop\Framework\Model\AbstractProduct[]
     */
    protected $products;

    /**
     * @param \OnlineShop\Framework\PricingManager\IEnvironment $environment
     *
     * @return boolean
     */
    public function check(\OnlineShop\Framework\PricingManager\IEnvironment $environment)
    {
        // init
        $productsPool = array();

        // get current product if we have one
        if($environment->getProduct())
        {
            $productsPool[] = $environment->getProduct();
        }

        // products from cart
        if($environment->getCart())
        {
            foreach($environment->getCart()->getItems() as $item)
            {
                $productsPool[] = $item->getProduct();
            }
        }


        // test
        foreach($productsPool as $currentProduct)
        {
            // check all valid products
            foreach($this->getProducts() as $product)
            {
                /* @var \OnlineShop\Framework\Model\AbstractProduct $allow */

                $currentProductCheck = $currentProduct;
                while($currentProductCheck instanceof \OnlineShop\Framework\Model\ICheckoutable) {
                    if($currentProductCheck->getId() === $product->getId())
                    {
                        return true;
                    }
                    $currentProductCheck = $currentProductCheck->getParent();
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
            /* @var \OnlineShop\Framework\Model\AbstractProduct $product */
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
     * @return \OnlineShop\Framework\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $products = array();
        foreach($json->products as $cat)
        {
            $product = $this->loadProduct($cat->id);
            if($product)
            {
                $products[] = $product;
            }
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
            /* @var \OnlineShop\Framework\Model\AbstractProduct $product */
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
            $product = $this->loadProduct($product_id);
            if($product)
            {
                $this->products[ $key ] = $this->loadProduct($product_id);
            }
        }
    }

    /**
     * @param \OnlineShop\Framework\Model\AbstractProduct[] $products
     *
     * @return ICatalogProduct
     */
    public function setProducts(array $products)
    {
        $this->products = $products;
        return $this;
    }

    /**
     * @return \OnlineShop\Framework\Model\AbstractProduct[]
     */
    public function getProducts()
    {
        return $this->products;
    }


    /**
     * @param $id
     *
     * @return \Pimcore\Model\Object\Concrete|null
     */
    protected function loadProduct($id)
    {
        return \Pimcore\Model\Object\Concrete::getById($id);
    }
}