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


namespace OnlineShop\Framework\PriceSystem\TaxManagement;


use OnlineShop\Framework\Model\ICheckoutable;
use Pimcore\Model\Object\OnlineShopTaxClass;

class TaxClassCalculator {

    /**
     * @param ICheckoutable $product
     * @param $environment
     * @return OnlineShopTaxClass
     */
    public static function getTaxClass(ICheckoutable $product, $environment) {
        return OnlineShopTaxClass::getById(12103);
    }

    /**
     * @param OnlineShopTaxClass $taxClass
     * @return TaxEntry[]
     */
    public static function convertTaxEntries(OnlineShopTaxClass $taxClass) {

        $convertedTaxEntries = [];
        foreach($taxClass->getTaxEntries() as $index => $entry) {
            $convertedTaxEntries[] = new TaxEntry($entry->getPercent(), 0, $taxClass->getId() . "-" . $index, $entry);
        }

        return $convertedTaxEntries;

    }

}