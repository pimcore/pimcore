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

class DatatransRequest extends AbstractRequest
{
    protected $reqtype;
    protected $refno;
    protected $language;
    protected $successUrl;
    protected $errorUrl;
    protected $cancelUrl;
    protected $uppStartTarget;
    protected $useAlias;

    /**
     * @return mixed
     */
    public function getReqtype()
    {
        return $this->reqtype;
    }

    /**
     * @param mixed $reqtype
     */
    public function setReqtype($reqtype): void
    {
        $this->reqtype = $reqtype;
    }

    /**
     * @return mixed
     */
    public function getRefno()
    {
        return $this->refno;
    }

    /**
     * @param mixed $refno
     */
    public function setRefno($refno): void
    {
        $this->refno = $refno;
    }

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
    public function getSuccessUrl()
    {
        return $this->successUrl;
    }

    /**
     * @param mixed $successUrl
     */
    public function setSuccessUrl($successUrl): void
    {
        $this->successUrl = $successUrl;
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

    /**
     * @return mixed
     */
    public function getCancelUrl()
    {
        return $this->cancelUrl;
    }

    /**
     * @param mixed $cancelUrl
     */
    public function setCancelUrl($cancelUrl): void
    {
        $this->cancelUrl = $cancelUrl;
    }

    /**
     * @return mixed
     */
    public function getUppStartTarget()
    {
        return $this->uppStartTarget;
    }

    /**
     * @param mixed $uppStartTarget
     */
    public function setUppStartTarget($uppStartTarget): void
    {
        $this->uppStartTarget = $uppStartTarget;
    }

    /**
     * @return mixed
     */
    public function getUseAlias()
    {
        return $this->useAlias;
    }

    /**
     * @param mixed $useAlias
     */
    public function setUseAlias($useAlias): void
    {
        $this->useAlias = $useAlias;
    }
}
