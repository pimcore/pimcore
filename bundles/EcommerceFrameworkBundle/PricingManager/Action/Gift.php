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
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ActionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;

class Gift implements GiftInterface
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
     * @param EnvironmentInterface $environment
     *
     * @return GiftInterface
     */
    public function executeOnProduct(EnvironmentInterface $environment)
    {
        // TODO: Implement executeOnProduct() method.
        return $this;
    }

    /**
     * @param EnvironmentInterface $environment
     *
     * @return GiftInterface
     */
    public function executeOnCart(EnvironmentInterface $environment)
    {
        $comment = $environment->getRule()->getDescription();
        $environment->getCart()->addGiftItem($this->getProduct(), 1, null, true, [], [], $comment);

        return $this;
    }

    /**
     * set gift product
     *
     * @param AbstractProduct $product
     *
     * @return GiftInterface
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
     * @return string
     */
    public function toJSON()
    {
        return json_encode([
            'type' => 'Gift',
            'product' => $this->getProduct() ? $this->getProduct()->getFullPath() : null,
        ]);
    }

    /**
     * @param string $string
     *
     * @return ActionInterface
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);
        $product = AbstractProduct::getByPath($json->product);

        if ($product) {
            $this->setProduct($product);
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

        return ['productPath'];
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
