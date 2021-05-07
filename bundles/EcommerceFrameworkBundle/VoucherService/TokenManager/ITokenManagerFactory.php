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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager;

@trigger_error(
    'Interface Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager\ITokenManagerFactory is deprecated since version 6.0.0 and will be removed in Pimcore 10. ' .
    ' Use ' . TokenManagerFactoryInterface::class . ' class instead.',
    E_USER_DEPRECATED
);

class_exists(TokenManagerFactoryInterface::class);

if (false) {
    /**
     * @deprecated use TokenManagerFactoryInterface instead.
     */
    interface ITokenManagerFactory
    {
    }
}
