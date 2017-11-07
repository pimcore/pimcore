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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment;

class Gift implements IGift
{
    /**
     * @var AbstractProduct
     */
    protected $product;

    /**
     * @var string
     */
    protected $productPath;

    /**
     * If true, the gift action will check if an item is available
     * before adding it to the cart.
     *
     * @var bool
     */
    protected $checkAvailability = false;

    /**
     * @param IEnvironment $environment
     *
     * @return IGift
     */
    public function executeOnProduct(IEnvironment $environment)
    {
        // TODO: Implement executeOnProduct() method.
    }

    /**
     * @param IEnvironment $environment
     *
     * @return IGift
     */
    public function executeOnCart(IEnvironment $environment)
    {
        $comment = $environment->getRule()->getDescription();

        $add = true;
        if ($this->checkAvailability) {
            $add = $this->getProduct()->getOSAvailabilityInfo()->getAvailable();
        }

        if ($add) {
           $environment->getCart()->addGiftItem($this->getProduct(), 1, null, true, array(), array(), $comment);
        }
    }

    /**
     * set gift product
     *
     * @param AbstractProduct $product
     *
     * @return IGift
     */
    public function setProduct(AbstractProduct $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return AbstractProduct
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return bool
     */
    public function getCheckAvailability(): bool
    {
        return $this->checkAvailability;
    }

    /**
     * @param bool $checkAvailability
     */
    public function setCheckAvailability($checkAvailability)
    {
        $this->checkAvailability = (bool)$checkAvailability;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode([
            'type'               => 'Gift',
            'product'            => $this->getProduct() ? $this->getProduct()->getFullPath() : null,
            'check_availability' => $this->checkAvailability
        ]);
    }

    /**
     * @param string $string
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);
        $product = AbstractProduct::getByPath($json->product);

        if ($product) {
            $this->setProduct($product);
        }

        if (isset($json->check_availability)) {
            $this->setCheckAvailability($json->check_availability);
        }

        return $this;
    }

    /**
     * dont cache the entire product object
     *
     * @return array
     */
    public function __sleep()
    {
        if (is_object($this->product)) {
            $this->productPath = $this->product->getFullPath();
        }

        return ['productPath', 'checkAvailability'];
    }

    /**
     * restore product
     */
    public function __wakeup()
    {
        if ($this->productPath != '') {
            $this->product = AbstractProduct::getByPath($this->productPath);
        } elseif (is_string($this->product)) {
            $this->product = AbstractProduct::getByPath($this->product);
        }
    }
}
