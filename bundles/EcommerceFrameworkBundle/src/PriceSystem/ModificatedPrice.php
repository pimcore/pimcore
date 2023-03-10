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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem;

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\RuleInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

class ModificatedPrice extends Price implements ModificatedPriceInterface
{
    protected ?string $description = null;

    protected ?RuleInterface $rule = null;

    public function __construct(Decimal $amount, Currency $currency, bool $minPrice = false, string $description = null)
    {
        parent::__construct($amount, $currency, $minPrice);

        $this->description = $description;
    }

    public function getRule(): ?RuleInterface
    {
        return $this->rule;
    }

    /**
     * @return $this
     */
    public function setRule(?RuleInterface $rule): static
    {
        $this->rule = $rule;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * @return $this
     */
    public function setDescription(?string $description = null): static
    {
        $this->description = $description;

        return $this;
    }
}
