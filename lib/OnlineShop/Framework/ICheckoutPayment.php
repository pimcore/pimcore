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
 * Interface for checkout payment provider
 */
interface OnlineShop_Framework_ICheckoutPayment
{
    /**
     * @param Zend_Config                $xml
     * @param \OnlineShop\Framework\CartManager\ICart $cart
     */
    public function __construct(\Zend_Config $xml, \OnlineShop\Framework\CartManager\ICart $cart);


    /**
     * start payment
     * @param array $config
     *
     * @return mixed|bool
     */
    public function initPayment(array $config);


    /**
     * handle response / execute payment
     * @param mixed $response
     * @return OnlineShop_Framework_Impl_Checkout_Payment_Status
     */
    public function handleResponse($response);


    /**
     * @return string|null
     */
    public function getPayReference();


    /**
     * @return bool
     */
    public function isPaid();

    /**
     * @return bool
     */
    public function hasErrors();

    /**
     * @return array
     */
    public function getErrors();
}
