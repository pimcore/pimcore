<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\TaxManagement\TaxEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

/**
 * Interface for price implementations of online shop framework
 */
interface PriceInterface
{
    const PRICE_MODE_NET = 'net';
    const PRICE_MODE_GROSS = 'gross';

    /**
     * Returns $grossAmount
     *
     * @return Decimal
     */
    public function getAmount(): Decimal;

    /**
     * @return Currency
     */
    public function getCurrency(): Currency;

    /**
     * @return bool
     */
    public function isMinPrice(): bool;

    /**
     * Sets amount of price, depending on $priceMode and $recalc it sets net price or gross price and recalculates the
     * corresponding net or gross price.
     *
     * @param Decimal $amount
     * @param string $priceMode - default to PRICE_MODE_GROSS
     * @param bool $recalc - default to false
     */
    public function setAmount(Decimal $amount, string $priceMode = self::PRICE_MODE_GROSS, bool $recalc = false);

    /**
     * Returns gross amount of price
     *
     * @return Decimal
     */
    public function getGrossAmount(): Decimal;

    /**
     * Returns net amount of price
     *
     * @return Decimal
     */
    public function getNetAmount(): Decimal;

    /**
     * Returns tax entries of price as an array
     *
     * @return TaxEntry[]
     */
    public function getTaxEntries(): array;

    /**
     * Returns tax entry combination mode needed for tax calculation
     *
     * @return string
     */
    public function getTaxEntryCombinationMode(): string;

    /**
     * Sets gross amount of price. If $recalc is set to true, corresponding net price
     * is calculated based on tax entries and tax entry combination mode.
     *
     * @param Decimal $grossAmount
     * @param bool $recalc
     *
     * @return void
     */
    public function setGrossAmount(Decimal $grossAmount, bool $recalc = false);

    /**
     * Sets net amount of price. If $recalc is set to true, corresponding gross price
     * is calculated based on tax entries and tax entry combination mode.
     *
     * @param Decimal $netAmount
     * @param bool $recalc
     *
     * @return void
     */
    public function setNetAmount(Decimal $netAmount, bool $recalc = false);

    /**
     * Sets tax entries for price.
     *
     * @param array $taxEntries
     *
     * @return void
     */
    public function setTaxEntries(array $taxEntries);

    /**
     * Sets $taxEntryCombinationMode for price.
     *
     * @param string $taxEntryCombinationMode
     *
     * @return void
     */
    public function setTaxEntryCombinationMode(string $taxEntryCombinationMode);
}

class_alias(PriceInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice');
