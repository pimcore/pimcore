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


class Customer implements IOrderListFilter
{
    /**
     * @var int
     */
    protected $classId;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $email;


    /**
     * @param IOrderList $orderList
     *
     * @return IOrderListFilter
     */
    public function apply(IOrderList $orderList)
    {
        // init
        $orderList->joinCustomer( $this->getClassId() );
        $query = $orderList->getQuery();


        if($this->getName())
        {
            $query->where('customer.firstName like ? OR customer.lastName like ?', '%' . $this->getName() . '%');
        }

        if($this->getEmail())
        {
            $query->where('customer.email like ?', '%' . $this->getEmail() . '%');
        }
    }


    /**
     * @return int|null
     */
    protected function findClassId()
    {
        // auto load class id
        $order = new OnlineShopOrder;
        $field = $order->getClass()->getFieldDefinition('customer');
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
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
        $this->classId = (int)$classId;
        return $this;
    }
}