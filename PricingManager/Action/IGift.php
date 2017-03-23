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

/**
 * add a gift product to the given cart
 */
interface IGift extends \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\IAction
{
    /**
     * set gift product
     * @param \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct $product
     *
     * @return IGift
     */
    public function setProduct(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct $product);

    /**
     * @return \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\AbstractProduct
     */
    public function getProduct();
}