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

use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;

interface CartFactoryInterface
{
    public function getCartClassName(EnvironmentInterface $environment): string;

    public function create(EnvironmentInterface $environment, string $name, string $id = null, array $options = []): CartInterface;
}

class_alias(CartFactoryInterface::class, 'Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartFactory');