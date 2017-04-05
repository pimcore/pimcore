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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager;

/**
 * Class \Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\DeliveryAddress
 *
 * sample implementation for delivery address
 */
class DeliveryAddress extends AbstractStep implements ICheckoutStep
{

    /**
     * namespace key
     */
    const PRIVATE_NAMESPACE = 'delivery_address';


    /**
     * @return string
     */
    public function getName()
    {
        return "deliveryaddress";
    }

    /**
     * sets delivered data and commits step
     *
     * @param  $data
     * @return bool
     */
    public function commit($data)
    {
        $this->cart->setCheckoutData(self::PRIVATE_NAMESPACE, json_encode($data));

        return true;
    }

    /**
     * returns saved data of step
     *
     * @return mixed
     */
    public function getData()
    {
        $data = json_decode($this->cart->getCheckoutData(self::PRIVATE_NAMESPACE));

        return $data;
    }
}
