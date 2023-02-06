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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator;

use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

/**
 * special interface for shipping price modifications - needed for pricing rule that remove shipping costs
 */
interface ShippingInterface extends CartPriceModificatorInterface
{
    public function setCharge(Decimal $charge): CartPriceModificatorInterface;

    public function getCharge(): Decimal;
}
