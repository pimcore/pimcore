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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\Payment;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PaymentManager\IStatus;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPrice;
use Pimcore\Config\Config;

/**
 * Interface for checkout payment provider
 */
interface IPayment
{
    /**
     * @param Config $config
     */
    public function __construct(Config $config);


    /**
     * @return string
     */
    public function getName();


    /**
     * start payment
     * @param IPrice $price
     * @param array                       $config
     *
     * @return mixed - either an url for a link the user has to follow to (e.g. paypal) or
     *                 an symfony form builder which needs to submitted (e.g. datatrans and wirecard)
     */
    public function initPayment(IPrice $price, array $config);


    /**
     * Handles response of payment provider and creates payment status object
     *
     * @param IStatus $response
     *
     * @return IStatus
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
     * @param IPrice $price
     * @param string                      $reference
     *
     * @return IStatus
     */
    public function executeDebit(IPrice $price = null, $reference = null);


    /**
     * execute credit
     * @param IPrice $price
     * @param string                      $reference
     * @param                             $transactionId
     *
     * @return IStatus
     */
    public function executeCredit(IPrice $price, $reference, $transactionId);
}
