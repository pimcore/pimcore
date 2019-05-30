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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager;

interface PricingManagerLocatorInterface
{
    /**
     * @param string|null $tenant
     *
     * @return PricingManagerInterface
     */
    public function getPricingManager(string $tenant = null): PricingManagerInterface;

    /**
     * @param string $tenant
     *
     * @return bool
     */
    public function hasPricingManager(string $tenant): bool;
}

class_alias(PricingManagerLocatorInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPricingManagerLocator');
