<?php

namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter;

use OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter\AbstractItem;
use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderListFilter;

class Product implements IOrderListFilter
{
    /**
     * @var \Object_Concrete
     */
    protected $product;

    /**
     * @param \Object_Concrete $product
     */
    public function __construct(\Object_Concrete $product)
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
            \Object_Concrete::OBJECT_TYPE_VARIANT
        ]);

        /** @var \Object_Concrete $variant */
        foreach ($variants as $variant) {
            $ids[] = $variant->getId();
        }

        $orderList->addCondition('orderItem.product__id IN (?)', $ids);

        return $this;
    }
}