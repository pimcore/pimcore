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
        $environment->getCart()->addGiftItem($this->getProduct(), 1, null, true, [], [], $comment);
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
     * @return string
     */
    public function toJSON()
    {
        return json_encode([
            'type'    => 'Gift',
            'product' => $this->getProduct() ? $this->getProduct()->getFullPath() : null,
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

        return $this;
    }

    /**
     * dont cache the entire product object
     * @return array
     */
    public function __sleep()
    {
        if($this->product) {
            $this->productPath = $this->product->getFullPath();
        }

        return array('productPath');
    }

    /**
     * restore product
     */
    public function __wakeup()
    {
        if($this->productPath != '') {
            $this->product = \OnlineShop\Framework\Model\AbstractProduct::getByPath( $this->productPath );
        }

    }
}
