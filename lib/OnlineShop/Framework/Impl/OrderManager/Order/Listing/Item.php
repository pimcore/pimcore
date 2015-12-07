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


namespace OnlineShop\Framework\Impl\OrderManager\Order\Listing;

use OnlineShop\Framework\OrderManager;
use OnlineShop\Framework\OrderManager\IOrderListItem;
use OnlineShop\Framework\Impl\OrderManager\AbstractOrderListItem;
use \OnlineShop\Framework\Model\AbstractOrder as Order;
use \OnlineShop\Framework\Model\AbstractOrderItem as OrderItem;

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