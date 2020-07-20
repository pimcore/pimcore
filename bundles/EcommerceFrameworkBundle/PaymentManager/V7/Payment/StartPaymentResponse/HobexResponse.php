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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\StartPaymentResponse;

class HobexResponse implements StartPaymentResponseInterface
{
    protected $checkoutId = "";

    protected $renderedFormWidget = "";

    protected $originalResponse = [];

    public function __construct(string $checkoutId = "")
    {
        $this->checkoutId = $checkoutId;
    }


    /**
     * @return string
     */
    public function getCheckoutId(): string
    {
        return $this->checkoutId;
    }

    /**
     * @param string $checkoutId
     * @return HobexResponse
     */
    public function setCheckoutId(string $checkoutId): HobexResponse
    {
        $this->checkoutId = $checkoutId;
        return $this;
    }

    /**
     * @return string
     */
    public function getRenderedFormWidget(): string
    {
        return $this->renderedFormWidget;
    }

    /**
     * @param string $renderedFormWidget
     * @return HobexResponse
     */
    public function setRenderedFormWidget(string $renderedFormWidget): HobexResponse
    {
        $this->renderedFormWidget = $renderedFormWidget;
        return $this;
    }

    /**
     * @return array
     */
    public function getOriginalResponse(): array
    {
        return $this->originalResponse;
    }

    /**
     * @param array $originalResponse
     * @return HobexResponse
     */
    public function setOriginalResponse(array $originalResponse): HobexResponse
    {
        $this->originalResponse = $originalResponse;
        return $this;
    }

}
