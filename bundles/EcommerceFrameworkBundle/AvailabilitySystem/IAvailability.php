<?php

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem;

@trigger_error(
    'Interface Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\IAvailability is deprecated since version 6.0.0 and will be removed in Pimcore 10. ' .
    'Use ' . AvailabilityInterface::class . '  class instead.',
    E_USER_DEPRECATED
);

class_exists(AvailabilityInterface::class);

if (false) {
    /**
     * @deprecated use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilityInterface instead.
     */
    interface IAvailability
    {
    }
}
