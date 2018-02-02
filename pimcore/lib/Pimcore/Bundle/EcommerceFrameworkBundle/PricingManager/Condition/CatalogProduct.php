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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment;

class CatalogProduct extends AbstractObjectListCondition implements ICatalogProduct
{
    /**
     * @var AbstractProduct[]
     */
    protected $products = [];

    /**
     * Serialized product IDs
     *
     * @var array
     */
    protected $productIds = [];

    /**
     * @param IEnvironment $environment
     *
     * @return bool
     */
    public function check(IEnvironment $environment)
    {
        // init
        $productsPool = [];

        // get current product if we have one
        if ($environment->getProduct()) {
            $productsPool[] = $environment->getProduct();
        }

        // products from cart
        if ($environment->getExecutionMode() == IEnvironment::EXECUTION_MODE_CART && $environment->getCart()) {
            foreach ($environment->getCart()->getItems() as $item) {
                $productsPool[] = $item->getProduct();
            }
        }

        // test
        foreach ($productsPool as $currentProduct) {
            // check all valid products
            foreach ($this->getProducts() as $product) {
                /* @var AbstractProduct $allow */

                $currentProductCheck = $currentProduct;
                while ($currentProductCheck instanceof ICheckoutable) {
                    if ($currentProductCheck->getId() === $product->getId()) {
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
        $json = [
            'type' => 'CatalogProduct',
            'products' => []
        ];

        // add categories
        foreach ($this->getProducts() as $product) {
            /* @var AbstractProduct $product */
            $json['products'][] = [
                $product->getId(),
                $product->getFullPath()
            ];
        }

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $products = [];
        foreach ($json->products as $cat) {
            $product = $this->loadObject($cat->id);
            if ($product) {
                $products[] = $product;
            }
        }
        $this->setProducts($products);

        return $this;
    }

    /**
     * Don't cache the entire product object
     *
     * @return array
     */
    public function __sleep()
    {
        return $this->handleSleep('products', 'productIds');
    }

    /**
     * Restore products from serialized ID list
     */
    public function __wakeup()
    {
        $this->handleWakeup('products', 'productIds');
    }

    /**
     * @param AbstractProduct[] $products
     *
     * @return ICatalogProduct
     */
    public function setProducts(array $products)
    {
        $this->products = $products;

        return $this;
    }

    /**
     * @return AbstractProduct[]
     */
    public function getProducts()
    {
        return $this->products;
    }
}
