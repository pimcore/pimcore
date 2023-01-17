<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ConditionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;
use Pimcore\Model\DataObject\Concrete;

class CatalogProduct extends AbstractObjectListCondition implements CatalogProductInterface
{
    /**
     * @var AbstractProduct[]
     */
    protected array $products = [];

    /**
     * Serialized product IDs
     *
     * @var array
     */
    protected array $productIds = [];

    public function check(EnvironmentInterface $environment): bool
    {
        // init
        $productsPool = [];

        // get current product if we have one
        if ($environment->getProduct()) {
            $productsPool[] = $environment->getProduct();
        }

        // products from cart
        if ($environment->getExecutionMode() === EnvironmentInterface::EXECUTION_MODE_CART && $environment->getCart()) {
            foreach ($environment->getCart()->getItems() as $item) {
                $productsPool[] = $item->getProduct();
            }
        }

        // test
        foreach ($productsPool as $currentProduct) {
            // check all valid products
            foreach ($this->getProducts() as $product) {
                /** @var Concrete $currentProductCheck */
                $currentProductCheck = $currentProduct;
                while ($currentProductCheck instanceof CheckoutableInterface) {
                    if ($currentProductCheck->getId() === $product->getId()) {
                        return true;
                    }
                    $currentProductCheck = $currentProductCheck->getParent();
                }
            }
        }

        return false;
    }

    public function toJSON(): string
    {
        // basic
        $json = [
            'type' => 'CatalogProduct',
            'products' => [],
        ];

        // add categories
        foreach ($this->getProducts() as $product) {
            $json['products'][] = [
                $product->getId(),
                $product->getFullPath(),
            ];
        }

        return json_encode($json);
    }

    public function fromJSON(string $string): ConditionInterface
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
     *
     * @internal
     */
    public function __sleep(): array
    {
        return $this->handleSleep('products', 'productIds');
    }

    /**
     * Restore products from serialized ID list
     */
    public function __wakeup(): void
    {
        $this->handleWakeup('products', 'productIds');
    }

    /**
     * @param AbstractProduct[] $products
     *
     * @return CatalogProductInterface
     */
    public function setProducts(array $products): CatalogProductInterface
    {
        $this->products = $products;

        return $this;
    }

    /** @inheritDoc */
    public function getProducts(): array
    {
        return $this->products;
    }
}
