<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractProduct;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ActionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;

class Gift implements GiftInterface
{
    protected ?AbstractProduct $product = null;

    protected string $productPath = '';

    public function executeOnCart(EnvironmentInterface $environment): GiftInterface
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
    public function setProduct(AbstractProduct $product): GiftInterface
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct(): ?AbstractProduct
    {
        return $this->product;
    }

    public function toJSON(): string
    {
        return json_encode([
            'type' => 'Gift',
            'product' => $this->getProduct() ? $this->getProduct()->getFullPath() : null,
        ]);
    }

    public function fromJSON(string $string): ActionInterface
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
     *
     * @internal
     */
    public function __sleep(): array
    {
        if (is_object($this->product)) {
            $this->productPath = $this->product->getFullPath();
        }

        return ['productPath'];
    }

    /**
     * restore product
     *
     * @internal
     */
    public function __wakeup(): void
    {
        if ($this->productPath !== '') {
            $this->product = AbstractProduct::getByPath($this->productPath);
        }
    }
}
