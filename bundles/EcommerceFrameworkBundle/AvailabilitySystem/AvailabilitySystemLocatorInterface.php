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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem;

interface AvailabilitySystemLocatorInterface
{
    public function getAvailabilitySystem(string $name = null): AvailabilitySystemInterface;

    public function hasAvailabilitySystem(string $name): bool;
}

class_alias(AvailabilitySystemLocatorInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\IAvailabilitySystemLocator');
