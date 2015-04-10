<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 09.04.2015
 * Time: 16:23
 */

namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing\Filter;

use OnlineShop\Framework\OrderManager\IOrderList;
use OnlineShop\Framework\OrderManager\IOrderListFilter;

use Pimcore\Model\Object\OnlineShopOrder;
use Pimcore\Model\Object\OnlineShopOrderItem;


class Product implements IOrderListFilter
{
    /**
     * @var int
     */
    protected $classId;

    /**
     * @param IOrderList $orderList
     *
     * @return IOrderListFilter
     */
    public function apply(IOrderList $orderList)
    {
        // TODO: Implement apply() method.
        $orderList->joinProduct( $this->getClassId()  );
        $query = $orderList->getQuery();

//        $query->where(
//            sprintf('product.o_className in("%s")'
//                , implode('","', $this->getTypes())
//            )
//        );

    }


    /**
     * @return int|null
     */
    protected function findClassId()
    {
        // auto load class id
        $item = new OnlineShopOrderItem;
        $field = $item->getClass()->getFieldDefinition('product');
        if($field instanceof \Pimcore\Model\Object\ClassDefinition\Data\Href)
        {
            if(count($field->getClasses()) == 1)
            {
                $class = 'Pimcore\Model\Object\\' . reset($field->getClasses())['classes'];
                /* @var \Pimcore\Model\Object\Concrete $class */

                return $class::classId();
            }
        }
    }

    /**
     * @return int
     */
    public function getClassId()
    {
        if(!$this->classId)
        {
            $this->setClassId( $this->findClassId() );
        }

        return (int)$this->classId;
    }

    /**
     * @param int $classId
     *
     * @return $this
     */
    public function setClassId($classId)
    {
        $this->classId = $classId;
        return $this;
    }





    public function setProducts(array $products)
    {

    }
}