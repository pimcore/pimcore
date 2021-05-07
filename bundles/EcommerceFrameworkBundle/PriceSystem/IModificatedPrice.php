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

@trigger_error(
    'Interface Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IModificatedPrice is deprecated since version 6.0.0 and will be removed in Pimcore 10. ' .
    ' Use ' . ModificatedPriceInterface::class . ' class instead.',
    E_USER_DEPRECATED
);

class_exists(ModificatedPriceInterface::class);

if (false) {
    /**
     * @deprecated use ModificatedPriceInterface instead.
     */
    interface IModificatedPrice
    {
    }
}
