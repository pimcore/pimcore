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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ConditionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;

class Tenant implements ConditionInterface
{
    /**
     * @var string[]
     */
    protected array $tenant;

    public function check(EnvironmentInterface $environment): bool
    {
        $currentTenant = Factory::getInstance()->getEnvironment()->getCurrentCheckoutTenant();

        return in_array($currentTenant, $this->getTenant());
    }

    public function toJSON(): string
    {
        // basic
        $json = [
            'type' => 'Tenant', 'tenant' => implode(',', $this->getTenant()),
        ];

        return json_encode($json);
    }

    public function fromJSON(string $string): ConditionInterface
    {
        $json = json_decode($string);

        $this->setTenant(explode(',', $json->tenant));

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTenant(): array
    {
        return $this->tenant;
    }

    /**
     * @param string[] $tenant
     *
     * @return $this
     */
    public function setTenant(array $tenant): static
    {
        $this->tenant = $tenant;

        return $this;
    }
}
