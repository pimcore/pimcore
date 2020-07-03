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

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\RuleInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;

class ModificatedPrice extends Price implements ModificatedPriceInterface
{
    /**
     * @var null|string
     */
    protected $description;

    /**
     * @var null|RuleInterface
     */
    protected $rule;

    public function __construct(Decimal $amount, Currency $currency, bool $minPrice = false, string $description = null)
    {
        parent::__construct($amount, $currency, $minPrice);

        $this->description = $description;
    }

    /**
     * @return RuleInterface|null
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @param RuleInterface|null $rule
     *
     * @return $this
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description = null)
    {
        $this->description = $description;
    }
}
