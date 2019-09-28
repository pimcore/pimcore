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

class QPayRequest extends AbstractRequest
{
    protected $language;
    protected $successURL;
    protected $cancelURL;
    protected $failureURL;
    protected $serviceURL;
    protected $confirmMail;
    protected $orderDescription;
    protected $imageURL;
    protected $orderIntent;

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language): void
    {
        $this->language = $language;
    }

    /**
     * @return mixed
     */
    public function getSuccessURL()
    {
        return $this->successURL;
    }

    /**
     * @param mixed $successURL
     */
    public function setSuccessURL($successURL): void
    {
        $this->successURL = $successURL;
    }

    /**
     * @return mixed
     */
    public function getCancelURL()
    {
        return $this->cancelURL;
    }

    /**
     * @param mixed $cancelURL
     */
    public function setCancelURL($cancelURL): void
    {
        $this->cancelURL = $cancelURL;
    }

    /**
     * @return mixed
     */
    public function getFailureURL()
    {
        return $this->failureURL;
    }

    /**
     * @param mixed $failureURL
     */
    public function setFailureURL($failureURL): void
    {
        $this->failureURL = $failureURL;
    }

    /**
     * @return mixed
     */
    public function getServiceURL()
    {
        return $this->serviceURL;
    }

    /**
     * @param mixed $serviceURL
     */
    public function setServiceURL($serviceURL): void
    {
        $this->serviceURL = $serviceURL;
    }

    /**
     * @return mixed
     */
    public function getConfirmMail()
    {
        return $this->confirmMail;
    }

    /**
     * @param mixed $confirmMail
     */
    public function setConfirmMail($confirmMail): void
    {
        $this->confirmMail = $confirmMail;
    }

    /**
     * @return mixed
     */
    public function getOrderDescription()
    {
        return $this->orderDescription;
    }

    /**
     * @param mixed $orderDescription
     */
    public function setOrderDescription($orderDescription): void
    {
        $this->orderDescription = $orderDescription;
    }

    /**
     * @return mixed
     */
    public function getImageURL()
    {
        return $this->imageURL;
    }

    /**
     * @param mixed $imageURL
     */
    public function setImageURL($imageURL): void
    {
        $this->imageURL = $imageURL;
    }

    /**
     * @return mixed
     */
    public function getOrderIntent()
    {
        return $this->orderIntent;
    }

    /**
     * @param mixed $orderIntent
     */
    public function setOrderIntent($orderIntent): void
    {
        $this->orderIntent = $orderIntent;
    }
}
