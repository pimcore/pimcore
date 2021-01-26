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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment;

/**
 * Interface for payment provider brick
 */
interface PaymentProviderInterface
{
    /**
     * Get auth_amount - Amount
     * @return string|null
     */
    public function getAuth_amount();

    /**
     * Get auth_currency - currency
     * @return string|null
     */
    public function getAuth_currency();
}
