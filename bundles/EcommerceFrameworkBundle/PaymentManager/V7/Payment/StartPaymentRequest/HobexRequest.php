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

class HobexRequest extends AbstractRequest
{
    const STYLE_CARD = 'card';
    const STYLE_PLAIN = 'plain';

    protected $shopperResultUrl = '';

    protected $locale = '';

    protected $invoiceId = '';

    protected $memo = '';

    protected $brands = []; //'VISA', 'MASTER'

    protected $style = 'card';

    /**
     * @return string
     */
    public function getShopperResultUrl(): string
    {
        return $this->shopperResultUrl;
    }

    /**
     * @param string $shopperResultUrl
     *
     * @return HobexRequest
     */
    public function setShopperResultUrl(string $shopperResultUrl): self
    {
        $this->shopperResultUrl = $shopperResultUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return HobexRequest
     */
    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getInvoiceId(): string
    {
        return $this->invoiceId;
    }

    /**
     * @param string $invoiceId
     *
     * @return HobexRequest
     */
    public function setInvoiceId(string $invoiceId): self
    {
        $this->invoiceId = $invoiceId;

        return $this;
    }

    /**
     * @return string
     */
    public function getMemo(): string
    {
        return $this->memo;
    }

    /**
     * @param string $memo
     *
     * @return HobexRequest
     */
    public function setMemo(string $memo): HobexRequest
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getBrands(): array
    {
        return $this->brands;
    }

    /**
     * see https://hobex.docs.oppwa.com/reference/brands-reference
     *
     * @param string[] $brands
     *
     * @return HobexRequest
     */
    public function setBrands(array $brands): self
    {
        $this->brands = $brands;

        return $this;
    }

    /**
     * see https://hobex.docs.oppwa.com/reference/brands-reference
     *
     * @param string $brand
     *
     * @return HobexRequest
     */
    public function addBrand(string $brand): self
    {
        $this->brands[] = $brand;

        return $this;
    }

    /**
     * @return string
     */
    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * @param string $style
     *
     * @return HobexRequest
     */
    public function setStyle(string $style): self
    {
        $this->style = $style;

        return $this;
    }
}
