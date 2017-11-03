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

namespace Pimcore\Targeting\Condition;

use GeoIp2\Model\City;
use Pimcore\Targeting\DataProvider\GeoIp;
use Pimcore\Targeting\Model\VisitorInfo;

class Country implements ProviderDependentConditionInterface
{
    /**
     * @var string
     */
    private $country;

    /**
     * @param string $country
     */
    public function __construct(string $country = null)
    {
        $this->country = $country;
    }

    /**
     * @inheritDoc
     */
    public function getDataProviderKeys(): array
    {
        return [GeoIp::PROVIDER_KEY];
    }

    /**
     * @inheritDoc
     */
    public function canMatch(): bool
    {
        return !empty($this->country);
    }

    /**
     * @inheritDoc
     */
    public function match(VisitorInfo $visitorInfo): bool
    {
        /** @var City $city */
        $city = $visitorInfo->get(GeoIp::PROVIDER_KEY);

        if (!$city) {
            return false;
        }

        return $city->country->isoCode === $this->country;
    }
}
