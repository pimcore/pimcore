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

/**
 * add a gift product to the given cart
 */
interface IGift extends \OnlineShop\Framework\PricingManager\IAction
{
    /**
     * set gift product
     * @param \OnlineShop\Framework\Model\AbstractProduct $product
     *
     * @return IGift
     */
    public function setProduct(\OnlineShop\Framework\Model\AbstractProduct $product);

    /**
     * @return \OnlineShop\Framework\Model\AbstractProduct
     */
    public function getProduct();
}