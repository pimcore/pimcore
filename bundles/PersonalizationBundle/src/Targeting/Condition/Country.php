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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Condition;

use Pimcore\Bundle\PersonalizationBundle\Targeting\DataProvider\GeoIp;
use Pimcore\Bundle\PersonalizationBundle\Targeting\DataProviderDependentInterface;
use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;

class Country extends AbstractVariableCondition implements DataProviderDependentInterface
{
    private ?string $country = null;

    /**
     * @param string|null $country
     */
    public function __construct(string $country = null)
    {
        $this->country = $country;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromConfig(array $config): static
    {
        return new static($config['country'] ?? null);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataProviderKeys(): array
    {
        return [GeoIp::PROVIDER_KEY];
    }

    /**
     * {@inheritdoc}
     */
    public function canMatch(): bool
    {
        return !empty($this->country);
    }

    /**
     * {@inheritdoc}
     */
    public function match(VisitorInfo $visitorInfo): bool
    {
        $city = $visitorInfo->get(GeoIp::PROVIDER_KEY);

        if (!$city || ! isset($city['country'])) {
            return false;
        }

        if ($city['country']['iso_code'] === $this->country) {
            $this->setMatchedVariable('iso_code', $city['country']['iso_code']);

            return true;
        }

        return false;
    }
}
