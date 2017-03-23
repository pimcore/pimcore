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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager;

/**
 * Class \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CheckoutManager\DeliveryDate
 *
 * sample implementation for delivery date
 */
class DeliveryDate extends AbstractStep implements ICheckoutStep {

    CONST INSTANTLY = "delivery_instantly";
    CONST DATE = "delivery_date";

    /**
     * commits step and sets delivered data
     * @param  $data
     * @return bool
     */
    public function commit($data) {
        if(empty($data->instantly) && empty($data->date)) {
            throw new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\InvalidConfigException("Instantly or Date not set.");
        }

        $this->cart->setCheckoutData(self::INSTANTLY, $data->instantly);
        $date = null;
        if($data->date instanceof \DateTime) {
            $date = $data->date->getTimestamp();
        }
        $this->cart->setCheckoutData(self::DATE, $date);
        return true;
    }

    /**
     * @return mixed
     */
    public function getData() {
        $data = new \stdClass();
        $data->instantly = $this->cart->getCheckoutData(self::INSTANTLY);
        if($this->cart->getCheckoutData(self::DATE)) {
            $data->date = new \DateTime();
            $data->date->setTimestamp($this->cart->getCheckoutData(self::DATE));
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
