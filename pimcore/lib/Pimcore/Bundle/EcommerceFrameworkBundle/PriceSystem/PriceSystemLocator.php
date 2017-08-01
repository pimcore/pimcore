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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem;

use Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\ServiceLocator\NameServiceLocator;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;

class PriceSystemLocator extends NameServiceLocator implements IPriceSystemLocator
{
    public function getPriceSystem(string $name = null): IPriceSystem
    {
        return $this->locate($name);
    }

    public function hasPriceSystem(string $name): bool
    {
        return $this->locator->has($name);
    }

    protected function buildNotFoundException(string $name): UnsupportedException
    {
        return new UnsupportedException(sprintf(
            'Price system "%s" is not supported. Please check the configuration.',
            $name
        ));
    }
}
