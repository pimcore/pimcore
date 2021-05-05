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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Exception\ProviderNotFoundException;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\PaymentInterface;

interface PaymentManagerInterface
{
    /**
     * Get a payment provider by name
     *
     * @param string $name
     *
     * @return PaymentInterface
     *
     * @throws ProviderNotFoundException
     */
    public function getProvider(string $name): PaymentInterface;

    /**
     * Get configured payment providers
     *
     * @return array
     */
    public function getProviderTypes(): array;
}
