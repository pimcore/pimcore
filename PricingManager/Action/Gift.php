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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Action;

class Gift implements IGift
{
    /**
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct
     */
    protected $product;

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IEnvironment $environment
     *
     * @return IGift
     */
    public function executeOnProduct(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IEnvironment $environment)
    {
        // TODO: Implement executeOnProduct() method.
    }

    /**
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IEnvironment $environment
     *
     * @return IGift
     */
    public function executeOnCart(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IEnvironment $environment)
    {
        $comment = $environment->getRule()->getDescription();
        $environment->getCart()->addGiftItem($this->getProduct(), 1, null, true, array(), array(), $comment);
    }

    /**
     * set gift product
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct $product
     *
     * @return IGift
     */
    public function setProduct(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct
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
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);
        $product = \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct::getByPath( $json->product );

        if($product) {
            $this->setProduct( $product );
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
            $this->product = $this->product->getFullPath();
        }

        return array('product');
    }

    /**
     * restore product
     */
    public function __wakeup()
    {
        if($this->product != '') {
            $this->product = \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct::getByPath( $this->product );
        }

    }
}