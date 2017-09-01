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

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;

class Availability implements IAvailability
{
    /**
     * @var ICheckoutable
     */
    private $product;

    /**
     * @var bool
     */
    private $available;

    /**
     * @param bool $available
     */
    public function __construct(ICheckoutable $product, bool $available)
    {
        $this->product   = $product;
        $this->available = $available;
    }

    /**
     * @return ICheckoutable
     */
    public function getProduct(): ICheckoutable
    {
        return $this->product;
    }

    /**
     * @inheritDoc
     */
    public function getAvailable(): bool
    {
        return $this->available;
    }
}
