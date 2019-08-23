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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentRequest;

class HeidelpayRequest extends AbstractRequest
{
    protected $paymentReference;
    protected $internalPaymentId;
    protected $returnUrl;
    protected $errorUrl;

    /**
     * @return mixed
     */
    public function getPaymentReference()
    {
        return $this->paymentReference;
    }

    /**
     * @param mixed $paymentReference
     */
    public function setPaymentReference($paymentReference): void
    {
        $this->paymentReference = $paymentReference;
    }

    /**
     * @return mixed
     */
    public function getInternalPaymentId()
    {
        return $this->internalPaymentId;
    }

    /**
     * @param mixed $internalPaymentId
     */
    public function setInternalPaymentId($internalPaymentId): void
    {
        $this->internalPaymentId = $internalPaymentId;
    }

    /**
     * @return mixed
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * @param mixed $returnUrl
     */
    public function setReturnUrl($returnUrl): void
    {
        $this->returnUrl = $returnUrl;
    }

    /**
     * @return mixed
     */
    public function getErrorUrl()
    {
        return $this->errorUrl;
    }

    /**
     * @param mixed $errorUrl
     */
    public function setErrorUrl($errorUrl): void
    {
        $this->errorUrl = $errorUrl;
    }
}
