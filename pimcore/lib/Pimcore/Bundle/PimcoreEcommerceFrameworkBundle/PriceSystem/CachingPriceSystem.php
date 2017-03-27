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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem;

/**
 * Class CachingPriceSystem
 *
 * price system which caches created price info objects per product and request
 *
 */
abstract class CachingPriceSystem extends AbstractPriceSystem implements ICachingPriceSystem
{

    /** @var IPriceInfo[] $priceInfos  */
    protected $priceInfos = [];

    public function loadPriceInfos($productEntries, $options)
    {
        throw new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\UnsupportedException(__METHOD__  . " is not supported for " . get_class($this));
    }

    public function clearPriceInfos($productEntries, $options)
    {
        throw new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\UnsupportedException(__METHOD__  . " is not supported for " . get_class($this));
    }

    public function getPriceInfo(\Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\ICheckoutable $abstractProduct, $quantityScale = 1, $products = null)
    {
        $pId = $abstractProduct->getId();
        if (!array_key_exists($pId, $this->priceInfos) || !is_array($this->priceInfos[$pId])) {
            $this->priceInfos[$pId] = [];
        }
        if (!array_key_exists($quantityScale, $this->priceInfos[$pId]) || !$this->priceInfos[$pId][$quantityScale]) {
            $priceInfo = $this->initPriceInfoInstance($quantityScale, $abstractProduct, $products);
            $this->priceInfos[$pId][$quantityScale]=$priceInfo;
        }

        return $this->priceInfos[$pId][$quantityScale];
    }

    public function filterProductIds($productIds, $fromPrice, $toPrice, $order, $offset, $limit)
    {
        throw new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Exception\UnsupportedException(__METHOD__  . " is not supported for " . get_class($this));
    }
}
