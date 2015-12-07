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


namespace OnlineShop\Framework\OrderManager\Order\Listing\Filter;

use OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\AbstractItem;
use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderListFilter;

class Product implements IOrderListFilter
{
    /**
     * @var \Pimcore\Model\Object\Concrete
     */
    protected $product;

    /**
     * @param \Pimcore\Model\Object\Concrete $product
     */
    public function __construct(\Pimcore\Model\Object\Concrete $product)
    {
        $this->product = $product;
    }

    /**
     * @param IOrderList $orderList
     * @return IOrderListFilter
     */
    public function apply(IOrderList $orderList)
    {
        $ids = [
            $this->product->getId()
        ];

        $variants = $this->product->getChilds([
            \Pimcore\Model\Object\Concrete::OBJECT_TYPE_VARIANT
        ]);

        /** @var \Pimcore\Model\Object\Concrete $variant */
        foreach ($variants as $variant) {
            $ids[] = $variant->getId();
        }

        $orderList->addCondition('orderItem.product__id IN (?)', $ids);

        return $this;
    }
}