<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop\Framework\PricingManager\Action;

class Gift implements IGift
{
    /**
     * @var \OnlineShop\Framework\Model\AbstractProduct
     */
    protected $product;

    /**
     * @param \OnlineShop\Framework\PricingManager\IEnvironment $environment
     *
     * @return IGift
     */
    public function executeOnProduct(\OnlineShop\Framework\PricingManager\IEnvironment $environment)
    {
        // TODO: Implement executeOnProduct() method.
    }

    /**
     * @param \OnlineShop\Framework\PricingManager\IEnvironment $environment
     *
     * @return IGift
     */
    public function executeOnCart(\OnlineShop\Framework\PricingManager\IEnvironment $environment)
    {
        $comment = $environment->getRule()->getDescription();
        $environment->getCart()->addGiftItem($this->getProduct(), 1, null, true, array(), array(), $comment);
    }

    /**
     * set gift product
     * @param \OnlineShop\Framework\Model\AbstractProduct $product
     *
     * @return IGift
     */
    public function setProduct(\OnlineShop\Framework\Model\AbstractProduct $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return \OnlineShop\Framework\Model\AbstractProduct
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
        return json_encode(array(
                                'type' => 'Gift',
                                'product' => $this->getProduct() ? $this->getProduct()->getFullPath() : null,
                           ));
    }

    /**
     * @param string $string
     *
     * @return \OnlineShop\Framework\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);
        $product = \OnlineShop\Framework\Model\AbstractProduct::getByPath( $json->product );

        if($product)
            $this->setProduct( $product );

        return $this;
    }

    /**
     * dont cache the entire product object
     * @return array
     */
    public function __sleep()
    {
        if($this->product)
            $this->product = $this->product->getFullPath();

        return array('product');
    }

    /**
     * restore product
     */
    public function __wakeup()
    {
        if($this->product != '')
            $this->product = \OnlineShop\Framework\Model\AbstractProduct::getByPath( $this->product );

    }
}