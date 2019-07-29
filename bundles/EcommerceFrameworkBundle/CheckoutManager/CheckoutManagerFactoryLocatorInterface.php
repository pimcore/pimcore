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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager;

interface CheckoutManagerFactoryLocatorInterface
{
    public function getCheckoutManagerFactory(string $tenant = null): CheckoutManagerFactoryInterface;

    public function hasCheckoutManagerFactory(string $tenant): bool;
}

class_alias(CheckoutManagerFactoryLocatorInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\ICheckoutManagerFactoryLocator');
