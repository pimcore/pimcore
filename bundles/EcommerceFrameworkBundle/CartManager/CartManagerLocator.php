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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\ServiceLocator\CheckoutTenantAwareServiceLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;

class CartManagerLocator extends CheckoutTenantAwareServiceLocator implements CartManagerLocatorInterface
{
    public function getCartManager(string $tenant = null): CartManagerInterface
    {
        return $this->locate($tenant);
    }

    public function hasCartManager(string $tenant): bool
    {
        return $this->locator->has($tenant);
    }

    protected function buildNotFoundException(string $tenant): UnsupportedException
    {
        return new UnsupportedException(sprintf(
            'Cart manager for tenant "%s" is not defined. Please check the configuration.',
            $tenant
        ));
    }
}
