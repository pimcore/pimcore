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


/**
 * Class OnlineShop_Framework_Impl_Checkout_DeliveryDate
 *
 * sample implementation for delivery date
 */
class OnlineShop_Framework_Impl_Checkout_DeliveryDate extends OnlineShop_Framework_Impl_Checkout_AbstractStep implements OnlineShop_Framework_ICheckoutStep {

    CONST INSTANTLY = "delivery_instantly";
    CONST DATE = "delivery_date";

    /**
     * commits step and sets delivered data
     * @param  $data
     * @return bool
     */
    public function commit($data) {
        if(empty($data->instantly) && empty($data->date)) {
            throw new OnlineShop_Framework_Exception_InvalidConfigException("Instantly or Date not set.");
        }

        $this->cart->setCheckoutData(self::INSTANTLY, $data->instantly);
        $date = null;
        if($data->date instanceof Zend_Date) {
            $date = $data->date->get(Zend_Date::TIMESTAMP);
        }
        $this->cart->setCheckoutData(self::DATE, $date);
        return true;
    }

    /**
     * @return mixed
     */
    public function getData() {
        $data = new stdClass();
        $data->instantly = $this->cart->getCheckoutData(self::INSTANTLY);
        if($this->cart->getCheckoutData(self::DATE)) {
            $data->date = new Zend_Date($this->cart->getCheckoutData(self::DATE), Zend_Date::TIMESTAMP);
        } else {
            $data->instantly = true;
        }
        return $data;
    }    

    /**
     * @return string
     */
    public function getName() {
        return "deliverydate";
    }


}
