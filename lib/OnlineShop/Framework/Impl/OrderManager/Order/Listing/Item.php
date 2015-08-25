<?php
/**
 * Created by PhpStorm.
 * User: tballmann
 * Date: 03.11.14
 * Time: 10:09
 */

namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing;

use OnlineShop\Framework\OrderManager;
use OnlineShop\Framework\OrderManager\IOrderListItem;
use OnlineShop\Framework\Impl\OrderManager\AbstractOrderListItem;
use OnlineShop_Framework_AbstractOrder as Order;
use OnlineShop_Framework_AbstractOrderItem as OrderItem;

class Item extends AbstractOrderListItem implements IOrderListItem
{
    /**
     * @return int
     */
    public function getId()
    {
        return $this->resultRow['Id'];
    }


    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $args)
    {
        $field = substr($method, 3);
        if(substr($method, 0, 3) == 'get' && array_key_exists($field, $this->resultRow))
        {
            return $this->resultRow[ $field ];
        }

        $object = $this->reference();
        if($object)
        {
            return call_user_func_array(array($object, $method), $args);
        }
        else
        {
            throw new \Exception("Object with {$this->getId()} not found.");
        }
    }

    /**
     * @return Order|OrderItem
     */
    public function reference()
    {
        return \Pimcore\Model\Object\Concrete::getById( $this->getId() );
    }
}