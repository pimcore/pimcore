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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Filter;

use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListFilterInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\OrderListInterface;

class Product implements OrderListFilterInterface
{
    /**
     * @var \Pimcore\Model\DataObject\Concrete
     */
    protected $product;

    /**
     * @param \Pimcore\Model\DataObject\Concrete $product
     */
    public function __construct(\Pimcore\Model\DataObject\Concrete $product)
    {
        $this->product = $product;
    }

    /**
     * @param OrderListInterface $orderList
     *
     * @return OrderListFilterInterface
     */
    public function apply(OrderListInterface $orderList)
    {
        $db = \Pimcore\Db::get();
        $ids = [
            $db->quote($this->product->getId()),
        ];

        $variants = $this->product->getChildren([
            \Pimcore\Model\DataObject\Concrete::OBJECT_TYPE_VARIANT,
        ]);

        /** @var \Pimcore\Model\DataObject\Concrete $variant */
        foreach ($variants as $variant) {
            $ids[] = $db->quote($variant->getId());
        }

        $orderList->addCondition('orderItem.product__id IN (' . implode(',', $ids) . ')');

        return $this;
    }
}
