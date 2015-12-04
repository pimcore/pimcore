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
interface OnlineShop_Framework_IPayment
{
    /**
     * @param Zend_Config $xml
     */
    public function __construct(\Zend_Config $xml);


    /**
     * @return string
     */
    public function getName();


    /**
     * start payment
     * @param OnlineShop_Framework_IPrice $price
     * @param array                       $config
     *
     * @return mixed - either an url for a link the user has to follow to (e.g. paypal) or
     *                 an zend form which needs to submitted (e.g. datatrans and wirecard)
     */
    public function initPayment(OnlineShop_Framework_IPrice $price, array $config);


    /**
     * Handles response of payment provider and creates payment status object
     *
     * @param OnlineShop_Framework_Payment_IStatus $response
     *
     * @return OnlineShop_Framework_Payment_IStatus
     */
    public function handleResponse($response);


    /**
     * return the authorized data from payment provider
     * @return array
     */
    public function getAuthorizedData();


    /**
     * set authorized data from payment provider
     * @param array $authorizedData
     */
    public function setAuthorizedData(array $authorizedData);


    /**
     * execute payment
     * @param OnlineShop_Framework_IPrice $price
     * @param string                      $reference
     *
     * @return OnlineShop_Framework_Payment_IStatus
     */
    public function executeDebit(OnlineShop_Framework_IPrice $price = null, $reference = null);


    /**
     * execute credit
     * @param OnlineShop_Framework_IPrice $price
     * @param string                      $reference
     * @param                             $transactionId
     *
     * @return OnlineShop_Framework_Payment_IStatus
     */
    public function executeCredit(OnlineShop_Framework_IPrice $price, $reference, $transactionId);
}
