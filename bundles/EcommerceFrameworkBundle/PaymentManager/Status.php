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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager;

class Status implements StatusInterface
{
    /**
     * internal pimcore order status - see also constants \Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder::ORDER_STATE_*
     *
     * @var string
     */
    protected $status;

    /**
     * pimcore internal payment id, necessary to identify payment information in order object
     *
     * @var string
     */
    protected $internalPaymentId;

    /**
     * payment reference from payment provider
     *
     * @var string
     */
    protected $paymentReference;

    /**
     * payment message provided from payment provider - e.g. error message on error
     *
     * @var string
     */
    protected $message;

    /**
     * additional payment data
     *
     * @var array
     */
    protected $data = [];

    /**
     * @param string $internalPaymentId
     * @param string $paymentReference
     * @param string $message
     * @param string $status
     * @param array  $data  extended data
     */
    public function __construct($internalPaymentId, $paymentReference, $message, $status, array $data = [])
    {
        $this->internalPaymentId = $internalPaymentId;
        $this->paymentReference = $paymentReference;
        $this->message = $message;
        $this->status = $status;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getInternalPaymentId()
    {
        return $this->internalPaymentId;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getPaymentReference()
    {
        return $this->paymentReference;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
