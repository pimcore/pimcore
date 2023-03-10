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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;

/**
 * Price system which caches created price info objects per product and request
 */
abstract class CachingPriceSystem extends AbstractPriceSystem implements CachingPriceSystemInterface
{
    /**
     * @var PriceInfoInterface[][] $priceInfos
     */
    protected array $priceInfos = [];

    /**
     * {@inheritdoc}
     */
    public function getPriceInfo(CheckoutableInterface $product, int|string $quantityScale = null, array $products = null): PriceInfoInterface
    {
        $pId = $product->getId();
        if (!is_array($this->priceInfos[$pId] ?? null)) {
            $this->priceInfos[$pId] = [];
        }

        $quantityScaleKey = (string) $quantityScale;

        if (empty($this->priceInfos[$pId][$quantityScaleKey])) {
            $priceInfo = $this->initPriceInfoInstance($quantityScale, $product, $products ?? []);
            $this->priceInfos[$pId][$quantityScaleKey] = $priceInfo;
        }

        return $this->priceInfos[$pId][$quantityScaleKey];
    }

    /**
     * {@inheritdoc}
     */
    public function loadPriceInfos(array $productEntries, array $options): mixed
    {
        throw new UnsupportedException(__METHOD__  . ' is not supported for ' . get_class($this));
    }

    /**
     * {@inheritdoc}
     */
    public function clearPriceInfos(array $productEntries, array $options): mixed
    {
        throw new UnsupportedException(__METHOD__  . ' is not supported for ' . get_class($this));
    }

    /**
     * {@inheritdoc}
     */
    public function filterProductIds(array $productIds, ?float $fromPrice, ?float $toPrice, string $order, int $offset, int $limit): array
    {
        throw new UnsupportedException(__METHOD__  . ' is not supported for ' . get_class($this));
    }
}
